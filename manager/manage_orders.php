<?php
// filepath: c:\xampp\htdocs\capstone_jxt\staff\manage_payments.php
session_start();
include '../db.php';

// Function to log user activity
function logActivity($conn, $user_id, $action_type, $description) {
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $user_type = mysqli_real_escape_string($conn, $_SESSION['role'] ?? 'Unknown');
    $action_type = mysqli_real_escape_string($conn, $action_type);
    $description = mysqli_real_escape_string($conn, $description);
    
    $query = "INSERT INTO activity_logs (user_id, user_type, action_type, description, created_at) 
              VALUES ('$user_id', '$user_type', '$action_type', '$description', NOW())";
    
    mysqli_query($conn, $query);
}

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Manager' && $_SESSION['role'] != 'Admin')) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = ($_SESSION['first_name'] ?? 'User') . ' ' . ($_SESSION['last_name'] ?? '');

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Prepare WHERE clause based on filters
$where_clauses = ["p.payment_status = ?"];
$params = [$filter_status];
$types = "s";

if ($filter_type != 'all') {
    $where_clauses[] = "p.payment_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if (!empty($search_query)) {
    $where_clauses[] = "(o.order_id LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR p.transaction_reference LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

if (!empty($date_from)) {
    $where_clauses[] = "DATE(p.payment_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(p.payment_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_str = implode(' AND ', $where_clauses);

// Get total count of filtered payments for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM payments p
                JOIN orders o ON p.order_id = o.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE $where_str";

$stmt = $conn->prepare($count_query);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total_payments = $count_result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $total_payments = 0;
}

// Pagination
$payments_per_page = 10;
$total_pages = ceil($total_payments / $payments_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $payments_per_page;

// Get payments with pagination
$query = "SELECT p.*, o.total_amount, o.order_status, o.payment_status as order_payment_status,
          o.order_type, c.first_name, c.last_name, c.phone_number, c.email,
          CONCAT(u.first_name, ' ', u.last_name) as received_by_name,
          DATE_FORMAT(p.payment_date, '%M %d, %Y %h:%i %p') as formatted_date
          FROM payments p
          JOIN orders o ON p.order_id = o.order_id
          JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN users u ON p.received_by = u.user_id
          WHERE $where_str
          ORDER BY p.payment_date DESC
          LIMIT ?, ?";

$params[] = $offset;
$params[] = $payments_per_page;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);

// Count pending payments for notification badge
$pending_count_query = "SELECT COUNT(*) as count FROM payments WHERE payment_status = 'pending'";
$pending_result = $conn->query($pending_count_query);
$pending_count = $pending_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Payments - JXT Tailoring</title>
    <link rel="icon" type="image/png" href="../image/logo.png">
    
    <!-- Custom fonts and styles -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.20/dist/sweetalert2.min.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .card-header-custom {
            background-color: #443627;
            color: white;
            padding: 15px 20px;
        }
        
        .payment-card {
            transition: all 0.3s;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .payment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .payment-details {
            padding: 15px;
        }
        
        .payment-meta {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .payment-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .payment-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .type-downpayment {
            background-color: #e1f5fe;
            color: #0277bd;
        }
        
        .type-full_payment {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .type-balance {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }
        
        .action-btn {
            margin-right: 5px;
        }
        
        .filter-form {
            background-color: #f8f9fc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .tab-content {
            border: 1px solid #dee2e6;
            border-top: none;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        
        .thumbnail-img {
            max-width: 100px;
            max-height: 100px;
            cursor: pointer;
        }
        
        .modal-img-full {
            max-width: 100%;
            max-height: 80vh;
        }
        
        .order-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
        
        .payment-date {
            font-size: 12px;
            color: #6c757d;
        }
        
        .badge-count {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 10px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-tab-custom {
            border-bottom: 2px solid #dee2e6;
        }
        
        .nav-link.active {
            border-bottom: 2px solid #443627 !important;
            color: #443627 !important;
            font-weight: 600;
        }
        
        .placeholder-img {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: 5px;
            color: #6c757d;
        }
        
        .payment-amount {
            font-size: 1.2rem;
            font-weight: 600;
            color: #D98324;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 0;
        }
        
        .empty-state i {
            font-size: 50px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .page-item.active .page-link {
            background-color: #443627;
            border-color: #443627;
        }
        
        .page-link {
            color: #443627;
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <div class="py-3"></div>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage Payments</h1>
                        <?php if ($pending_count > 0): ?>
                        <div class="position-relative">
                            <span class="badge-count"><?= $pending_count ?></span>
                            <span class="badge bg-warning text-dark">
                                <?= $pending_count ?> Pending Verification
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Filter Form -->
                    <div class="card mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Payments</h6>
                            <a class="btn btn-sm btn-outline-primary" href="manage_payments.php">
                                <i class="fas fa-sync-alt fa-sm"></i> Reset Filters
                            </a>
                        </div>
                        <div class="card-body filter-form">
                            <form action="manage_payments.php" method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status">
                                        <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="confirmed" <?= $filter_status == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="rejected" <?= $filter_status == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>All</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="type" class="form-label">Payment Type</label>
                                    <select class="form-select" name="type" id="type">
                                        <option value="all" <?= $filter_type == 'all' ? 'selected' : '' ?>>All Types</option>
                                        <option value="downpayment" <?= $filter_type == 'downpayment' ? 'selected' : '' ?>>Downpayment</option>
                                        <option value="full_payment" <?= $filter_type == 'full_payment' ? 'selected' : '' ?>>Full Payment</option>
                                        <option value="balance" <?= $filter_type == 'balance' ? 'selected' : '' ?>>Balance</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           placeholder="Order ID, customer name..." value="<?= htmlspecialchars($search_query) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $date_from ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $date_to ?>">
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter fa-sm"></i> Apply Filters
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Status Tabs -->
                    <ul class="nav nav-tabs nav-tab-custom mb-0" id="paymentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $filter_status == 'pending' ? 'active' : '' ?>" 
                               href="manage_payments.php?status=pending">
                                <i class="fas fa-clock fa-sm me-1"></i> Pending 
                                <?php if ($pending_count > 0): ?>
                                <span class="badge bg-danger text-white"><?= $pending_count ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $filter_status == 'confirmed' ? 'active' : '' ?>" 
                               href="manage_payments.php?status=confirmed">
                                <i class="fas fa-check fa-sm me-1"></i> Confirmed
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $filter_status == 'rejected' ? 'active' : '' ?>" 
                               href="manage_payments.php?status=rejected">
                                <i class="fas fa-times fa-sm me-1"></i> Rejected
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $filter_status == 'all' ? 'active' : '' ?>" 
                               href="manage_payments.php?status=all">
                                <i class="fas fa-list fa-sm me-1"></i> All Payments
                            </a>
                        </li>
                    </ul>

                    <!-- Payments List -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <?php if (count($payments) > 0): ?>
                                <div class="row row-cols-1 row-cols-md-2 g-4">
                                    <?php foreach ($payments as $payment): ?>
                                        <?php 
                                            $paymentStatusClass = 'status-pending';
                                            if ($payment['payment_status'] == 'confirmed') $paymentStatusClass = 'status-confirmed';
                                            if ($payment['payment_status'] == 'rejected') $paymentStatusClass = 'status-rejected';
                                            
                                            $paymentTypeClass = 'type-downpayment';
                                            if ($payment['payment_type'] == 'full_payment') $paymentTypeClass = 'type-full_payment';
                                            if ($payment['payment_type'] == 'balance') $paymentTypeClass = 'type-balance';
                                        ?>
                                        <div class="col">
                                            <div class="card payment-card h-100">
                                                <div class="card-header-custom d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <span class="fw-bold">Order #<?= htmlspecialchars($payment['order_id']) ?></span>
                                                        <span class="payment-type <?= $paymentTypeClass ?>">
                                                            <?= ucfirst(str_replace('_', ' ', $payment['payment_type'])) ?>
                                                        </span>
                                                    </div>
                                                    <span class="payment-status <?= $paymentStatusClass ?>">
                                                        <?= ucfirst($payment['payment_status']) ?>
                                                    </span>
                                                </div>
                                                <div class="payment-details">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h5 class="card-title mb-1"><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></h5>
                                                            <p class="card-text text-muted mb-2"><?= htmlspecialchars($payment['phone_number']) ?></p>
                                                            <div class="payment-amount">₱<?= number_format($payment['amount'], 2) ?></div>
                                                            <small class="payment-date"><?= $payment['formatted_date'] ?></small>
                                                        </div>
                                                        <div>
                                                            <?php if (!empty($payment['screenshot_path'])): ?>
                                                                <img src="../uploads/payment_screenshots/<?= basename($payment['screenshot_path']) ?>" 
                                                                     class="thumbnail-img" 
                                                                     alt="Payment screenshot"
                                                                     data-bs-toggle="modal" 
                                                                     data-bs-target="#imageModal"
                                                                     data-src="../uploads/payment_screenshots/<?= basename($payment['screenshot_path']) ?>">
                                                            <?php else: ?>
                                                                <div class="placeholder-img">
                                                                    <i class="fas fa-image"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="order-info mt-3">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <small class="text-muted">Method:</small>
                                                                <div><?= ucfirst($payment['payment_method']) ?></div>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Order Total:</small>
                                                                <div>₱<?= number_format($payment['total_amount'], 2) ?></div>
                                                            </div>
                                                        </div>
                                                        <?php if (!empty($payment['transaction_reference'])): ?>
                                                        <div class="mt-2">
                                                            <small class="text-muted">Reference #:</small>
                                                            <div><?= htmlspecialchars($payment['transaction_reference']) ?></div>
                                                        </div>
                                                        <?php endif; ?>
                                                        <div class="mt-2">
                                                            <small class="text-muted">Received by:</small>
                                                            <div><?= htmlspecialchars($payment['received_by_name'] ?? 'N/A') ?></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="payment-meta">
                                                        <?php if ($payment['payment_status'] == 'pending'): ?>
                                                            <div class="d-flex justify-content-between">
                                                                <button class="btn btn-sm btn-success approve-payment" 
                                                                        data-id="<?= $payment['payment_id'] ?>"
                                                                        data-order-id="<?= $payment['order_id'] ?>"
                                                                        data-amount="<?= $payment['amount'] ?>"
                                                                        data-type="<?= $payment['payment_type'] ?>"
                                                                        data-order-total="<?= $payment['total_amount'] ?>">
                                                                    <i class="fas fa-check"></i> Approve
                                                                </button>
                                                                <button class="btn btn-sm btn-danger reject-payment"
                                                                        data-id="<?= $payment['payment_id'] ?>"
                                                                        data-order-id="<?= $payment['order_id'] ?>">
                                                                    <i class="fas fa-times"></i> Reject
                                                                </button>
                                                                <a href="view_order.php?id=<?= $payment['order_id'] ?>" 
                                                                   class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-eye"></i> View Order
                                                                </a>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="text-end">
                                                                <a href="view_order.php?id=<?= $payment['order_id'] ?>" 
                                                                   class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-eye"></i> View Order
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <div class="d-flex justify-content-center mt-4">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination">
                                            <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $current_page-1 ?>&status=<?= $filter_status ?>&type=<?= $filter_type ?>&search=<?= urlencode($search_query) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            
                                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= $filter_status ?>&type=<?= $filter_type ?>&search=<?= urlencode($search_query) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                                                        <?= $i ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $current_page+1 ?>&status=<?= $filter_status ?>&type=<?= $filter_type ?>&search=<?= urlencode($search_query) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-search"></i>
                                    <h4>No payments found</h4>
                                    <p>No payments match your search criteria.</p>
                                    <a href="manage_payments.php" class="btn btn-outline-primary">
                                        <i class="fas fa-sync-alt"></i> Clear Filters
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include 'footer.php'; ?>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment Screenshot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" class="modal-img-full" id="fullImage" alt="Payment screenshot full size">
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Modal -->
    <div class="modal fade" id="approvalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this payment?</p>
                    <div class="mb-3">
                        <label for="approvalNote" class="form-label">Add a note (optional)</label>
                        <textarea class="form-control" id="approvalNote" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmApproval">Approve Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div class="modal fade" id="rejectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject this payment?</p>
                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label">Reason for rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectionReason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmRejection">Reject Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.20/dist/sweetalert2.all.min.js"></script>

    <script>
        $(document).ready(function() {
            // Show full image in modal
            $('.thumbnail-img').click(function() {
                var imgSrc = $(this).data('src');
                $('#fullImage').attr('src', imgSrc);
            });
            
            // Variables to store current payment being processed
            let currentPaymentId = null;
            let currentOrderId = null;
            let currentPaymentType = null;
            let currentAmount = 0;
            let currentOrderTotal = 0;
            
            // Handle approve button click
            $('.approve-payment').click(function() {
                currentPaymentId = $(this).data('id');
                currentOrderId = $(this).data('order-id');
                currentPaymentType = $(this).data('type');
                currentAmount = $(this).data('amount');
                currentOrderTotal = $(this).data('order-total');
                
                $('#approvalModal').modal('show');
            });
            
            // Handle reject button click
            $('.reject-payment').click(function() {
                currentPaymentId = $(this).data('id');
                currentOrderId = $(this).data('order-id');
                
                $('#rejectionModal').modal('show');
            });
            
            // Confirm approval
            $('#confirmApproval').click(function() {
                const note = $('#approvalNote').val();
                
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    html: 'Approving payment, please wait.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Call AJAX to approve payment
                $.ajax({
                    url: 'handle_order_ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'approve',
                        payment_id: currentPaymentId,
                        order_id: currentOrderId,
                        payment_type: currentPaymentType,
                        amount: currentAmount,
                        order_total: currentOrderTotal,
                        note: note,
                        log_activity: true,
                        user_id: <?= $_SESSION['user_id'] ?>,
                        action_type: 'payment_approved',
                        description: 'Approved payment #' + currentPaymentId + ' for order #' + currentOrderId
                    },
                    success: function(response) {
                        $('#approvalModal').modal('hide');
                        
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Payment Approved!',
                                text: response.message,
                                confirmButtonColor: '#443627'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'An error occurred during approval.',
                                confirmButtonColor: '#443627'
                            });
                        }
                    },
                    error: function() {
                        $('#approvalModal').modal('hide');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Server error. Please try again later.',
                            confirmButtonColor: '#443627'
                        });
                    }
                });
            });
            
            // Confirm rejection
            $('#confirmRejection').click(function() {
                const reason = $('#rejectionReason').val();
                
                // Validate reason is provided
                if (!reason) {
                    $('#rejectionReason').addClass('is-invalid');
                    return;
                }
                
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    html: 'Rejecting payment, please wait.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Call AJAX to reject payment
                $.ajax({
                    url: 'handle_order_ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'reject',
                        payment_id: currentPaymentId,
                        order_id: currentOrderId,
                        reason: reason,
                        log_activity: true,
                        user_id: <?= $_SESSION['user_id'] ?>,
                        action_type: 'payment_rejected',
                        description: 'Rejected payment #' + currentPaymentId + ' for order #' + currentOrderId + '. Reason: ' + reason
                    },
                    success: function(response) {
                        $('#rejectionModal').modal('hide');
                        
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Payment Rejected',
                                text: response.message,
                                confirmButtonColor: '#443627'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'An error occurred during rejection.',
                                confirmButtonColor: '#443627'
                            });
                        }
                    },
                    error: function() {
                        $('#rejectionModal').modal('hide');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Server error. Please try again later.',
                            confirmButtonColor: '#443627'
                        });
                    }
                });
            });
            
            // Clear invalid state on input
            $('#rejectionReason').on('input', function() {
                $(this).removeClass('is-invalid');
            });
            
            // Reset modals when hidden
            $('#approvalModal, #rejectionModal').on('hidden.bs.modal', function() {
                $('#approvalNote').val('');
                $('#rejectionReason').val('').removeClass('is-invalid');
                currentPaymentId = null;
                currentOrderId = null;
            });
        });
    </script>
</body>
</html>