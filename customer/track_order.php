<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Get customer_id from session
$customer_id = $_SESSION['customer_id'];

// Include database connection
include '../db.php';

// Get customer details
$customer_query = "SELECT first_name, last_name FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$customer_name = $customer ? $customer['first_name'] . ' ' . $customer['last_name'] : 'Guest';

// Handle search input
$search_query = "";
$where_clause = "o.customer_id = ?";
if (!empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clause .= " AND o.order_id LIKE '%$search_query%'";
}

// Pagination setup
$records_per_page = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Update the main query to properly handle both order types
$query = "SELECT o.order_id, o.order_type, o.total_amount, o.downpayment_amount, 
                 o.payment_method, o.payment_status, o.order_status, o.created_at, o.updated_at,
                 CASE 
                    WHEN o.order_type = 'sublimation' THEN s.completion_date
                    WHEN o.order_type = 'tailoring' THEN t.completion_date
                    ELSE DATE_ADD(o.created_at, INTERVAL 7 DAY)
                 END AS completion_date,
                 o.notes,
                 s.template_id, s.custom_design, s.printing_type, s.size, s.color, s.instructions,
                 CASE
                    WHEN o.order_type = 'sublimation' THEN COUNT(sp.player_id)
                    ELSE 1
                 END AS quantity
          FROM orders o
          LEFT JOIN sublimation_orders s ON o.order_id = s.order_id AND o.order_type = 'sublimation'
          LEFT JOIN tailoring_orders t ON o.order_id = t.order_id AND o.order_type = 'tailoring'
          LEFT JOIN sublimation_players sp ON s.sublimation_id = sp.sublimation_id
          WHERE $where_clause
          GROUP BY o.order_id
          ORDER BY o.created_at DESC LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $customer_id, $records_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total FROM orders o WHERE $where_clause";
$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $customer_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// First, add this function at the top of your PHP code after database connection
function getPaginatedOrders($orders, $page = 1, $per_page = 8) {
    $total_records = count($orders);
    $total_pages = ceil($total_records / $per_page);
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $per_page;
    
    return [
        'data' => array_slice($orders, $offset, $per_page),
        'total_pages' => $total_pages,
        'current_page' => $page
    ];
}

// Then add this function to generate pagination HTML
function generatePagination($current_page, $total_pages, $tab_id) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav aria-label="Page navigation" class="custom-pagination mt-4">';
    $html .= '<ul class="pagination justify-content-center">';
    
    // First page
    $html .= '<li class="page-item ' . ($current_page == 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="#" data-tab="' . $tab_id . '" data-page="1">First</a></li>';
    
    // Previous page
    $html .= '<li class="page-item ' . ($current_page == 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="#" data-tab="' . $tab_id . '" data-page="' . ($current_page - 1) . '">Previous</a></li>';
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $html .= '<li class="page-item ' . ($i == $current_page ? 'active' : '') . '">';
        $html .= '<a class="page-link" href="#" data-tab="' . $tab_id . '" data-page="' . $i . '">' . $i . '</a></li>';
    }
    
    // Next page
    $html .= '<li class="page-item ' . ($current_page == $total_pages ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="#" data-tab="' . $tab_id . '" data-page="' . ($current_page + 1) . '">Next</a></li>';
    
    // Last page
    $html .= '<li class="page-item ' . ($current_page == $total_pages ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="#" data-tab="' . $tab_id . '" data-page="' . $total_pages . '">Last</a></li>';
    
    $html .= '</ul></nav>';
    return $html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Orders | JXT Tailoring</title>
    <link rel="icon" type="image/png" href="../image/logo.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-orange: #D98324;
            --secondary-orange: #FF9F45;
            --dark: #2D2D2D;
            --light: #FFFFFF;
            --gray: #F5F5F5;
            --border-color: #E5E5E5;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            font-family: 'Poppins', sans-serif;
            padding-top: 70px;
        }
        
        /* Navbar Styling */
        .navbar {
            background: var(--light);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            border-bottom: 2px solid var(--primary-orange);
        }

        .navbar-brand {
            color: var(--primary-orange) !important;
            font-weight: 700;
            font-size: 1.4rem;
        }

        .nav-link {
            color: var(--dark) !important;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-orange) !important;
        }
        
        /* Enhanced Card Design */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.08);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-orange) 100%);
            color: white;
            padding: 1.5rem;
            border: none;
        }
        
        /* Enhanced Table Design */
        .table {
            border-spacing: 0 12px;
            margin-top: -12px;
        }

        .table tr {
            background: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .table tr:hover {
            transform: scale(1.01);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .table td:first-child {
            border-top-left-radius: 15px;
            border-bottom-left-radius: 15px;
        }

        .table td:last-child {
            border-top-right-radius: 15px;
            border-bottom-right-radius: 15px;
        }

        .table th {
            background: var(--gray);
            color: var(--dark);
        }

        .table td {
            background: white;
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }
        
        /* Enhanced Status Pills */
        .status-pill {
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        }

        .status-pending {
            background: var(--gray);
            color: var(--dark);
        }

        .status-approved {
            background: var(--primary-orange);
            color: var(--light);
        }

        .status-processing {
            background: var(--secondary-orange);
            color: var(--light);
        }
        
        /* Enhanced Buttons */
        .btn {
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .view-btn {
            background: var(--dark);
            color: white;
        }

        .view-btn:hover {
            background: var(--primary-orange);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(217, 131, 36, 0.3);
        }

        .payment-btn {
            background: var(--primary-orange);
            color: var(--light);
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .payment-btn:hover {
            background: var(--secondary-orange);
            color: var(--light);
        }
        
        /* Enhanced Search Box Styling */
.search-form {
    position: relative;
    max-width: 600px;
    margin: 0 auto 2rem;
}

.search-wrapper {
    position: relative;
    width: 100%;
}

.search-form .form-control {
    height: 50px;
    border-radius: 25px;
    padding-left: 45px;
    padding-right: 100px;
    border: 2px solid #e9ecef;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    width: 100%;
}

.search-form .form-control:focus {
    border-color: var(--primary-orange);
    box-shadow: 0 0 0 0.2rem rgba(217, 131, 36, 0.15);
}

.search-form .search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary-orange);
    font-size: 1.2rem;
    z-index: 2;
    pointer-events: none;
}

.search-form .btn-search {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    background: var(--primary-orange);
    color: white;
    border: none;
    height: 40px;
    padding: 0 25px;
    border-radius: 20px;
    font-weight: 500;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.search-form .btn-search:hover {
    background: var(--secondary-orange);
    transform: translateY(-50%) scale(1.02);
    box-shadow: 0 4px 15px rgba(217, 131, 36, 0.2);
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .search-form .form-control {
        height: 45px;
        font-size: 0.9rem;
        padding-right: 90px;
    }
    
    .search-form .btn-search {
        height: 35px;
        padding: 0 15px;
        font-size: 0.9rem;
    }
}
        
        /* Empty State Improvements */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state img {
            max-width: 200px;
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }

        .empty-state h3 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--dark);
            margin-bottom: 1.5rem;
        }
        
        /* Modal Improvements */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .modal-header {
            background: var(--light);
            border-bottom: 2px solid var(--primary-orange);
        }

        .modal-title {
            color: var(--dark);
        }

        .modal-body {
            padding: 1.5rem;
        }

        /* Progress Tracker */
        .progress-inner {
            background: var(--primary-orange);
        }

        .progress-step.active .step-icon {
            background: var(--primary-orange);
            color: var(--light);
        }

        .progress-step .step-icon {
            background: var(--gray);
            color: var(--dark);
        }

        /* Timeline */
        .timeline-point.active {
            background: var(--primary-orange);
        }

        /* Custom Pagination */
        .custom-pagination {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }

        .page-link {
            border: none;
            padding: 0.8rem 1.2rem;
            border-radius: 12px;
            color: var(--dark);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: var(--primary-orange);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(217, 131, 36, 0.2);
        }

        .page-item.active .page-link {
            background: var(--primary-orange);
            color: white;
            box-shadow: 0 5px 15px rgba(217, 131, 36, 0.3);
        }

        /* Responsive Design Improvements */
        @media (max-width: 768px) {
            .card-header {
                padding: 1.2rem;
            }
            
            .table td {
                padding: 1rem 0.5rem;
            }
            
            .status-pill {
                padding: 6px 12px;
                font-size: 0.75rem;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 0.875rem;
            }
            
            .nav-tabs .nav-link {
                padding: 0.8rem;
                font-size: 0.875rem;
            }
            
            .tab-text {
                display: none;
            }
            
            .nav-tabs .nav-link i {
                margin: 0;
                font-size: 1.2rem;
            }
        }

        /* Animation Effects */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .table tr {
            animation: fadeIn 0.3s ease-out forwards;
        }

        .table tr:nth-child(1) { animation-delay: 0.1s; }
        .table tr:nth-child(2) { animation-delay: 0.2s; }
        .table tr:nth-child(3) { animation-delay: 0.3s; }
        .table tr:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#">JXT Tailoring</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="index.php"><i class="fas fa-home"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="fas fa-user"></i> <?= htmlspecialchars($customer_name) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="orders-container">
    <div class="card">
        <div class="card-header">
            <h2 class="mb-0">Track Your Order</h2>
        </div>
        <!-- Replace the current card body with this tabbed interface -->
        <div class="card-body">
            <!-- Replace the existing search form with this -->
<form method="GET" class="search-form">
    <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input type="text" 
               class="form-control" 
               name="search" 
               placeholder="Enter Order ID to search..." 
               value="<?= htmlspecialchars($search_query) ?>">
        <button type="submit" class="btn btn-search">
            Search
        </button>
    </div>
</form>

            <!-- Update the status tabs navigation with icons -->
            <ul class="nav nav-tabs status-tabs mb-3" id="orderTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                        <i class="fas fa-list me-1"></i> <span class="tab-text">All Orders</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="false">
                        <i class="fas fa-clock me-1"></i> <span class="tab-text">Pending</span>
                        <?php
                        $pending_count = 0;
                        mysqli_data_seek($result, 0); // Reset result pointer
                        while ($row = mysqli_fetch_assoc($result)) {
                            if ($row['order_status'] == 'pending_approval') $pending_count++;
                        }
                        if ($pending_count > 0): ?>
                        <span class="status-badge"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab" aria-controls="approved" aria-selected="false">
                        <i class="fas fa-check-circle me-1"></i> <span class="tab-text">Approved</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="processing-tab" data-bs-toggle="tab" data-bs-target="#processing" type="button" role="tab" aria-controls="processing" aria-selected="false">
                        <i class="fas fa-cog me-1 fa-spin-hover"></i> <span class="tab-text">Processing</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ready-tab" data-bs-toggle="tab" data-bs-target="#ready" type="button" role="tab" aria-controls="ready" aria-selected="false">
                        <i class="fas fa-box me-1"></i> <span class="tab-text">Ready</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab" aria-controls="completed" aria-selected="false">
                        <i class="fas fa-check-double me-1"></i> <span class="tab-text">Completed</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="declined-tab" data-bs-toggle="tab" data-bs-target="#declined" type="button" role="tab" aria-controls="declined" aria-selected="false">
                        <i class="fas fa-times-circle me-1"></i> <span class="tab-text">Declined</span>
                    </button>
                </li>
            </ul>

            <!-- Tab content -->
            <div class="tab-content" id="orderTabsContent">
                <!-- All Orders Tab -->
                <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                    <?php
                    mysqli_data_seek($result, 0); // Reset result pointer
                    $has_orders = mysqli_num_rows($result) > 0;
                    if ($has_orders): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Service Type</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['order_id']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($row['order_type'])) ?></td>
                                            <td>
                                                <span class="status-pill status-<?= str_replace('_', '-', $row['order_status']) ?>">
                                                    <?= htmlspecialchars(str_replace('_', ' ', ucwords($row['order_status']))) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="view_order.php?id=<?= htmlspecialchars($row['order_id']) ?>" class="btn view-btn">
                                                        <i class="fas fa-eye me-1"></i> View Details
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <img src="../image/no-orders.svg" alt="No orders" class="empty-state-img">
                            <h3>No orders found</h3>
                            <p>You haven't placed any orders yet or no orders match your search criteria.</p>
                            <a href="place_order.php" class="btn btn-primary">Place Your First Order</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pending Tab -->
                <div class="tab-pane fade" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                    <?php
                    mysqli_data_seek($result, 0);
                    $pending_orders = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        if ($row['order_status'] == 'pending_approval') {
                            $pending_orders[] = $row;
                        }
                    }
                    
                    $current_page = isset($_GET['pending_page']) ? (int)$_GET['pending_page'] : 1;
                    $paginated_orders = getPaginatedOrders($pending_orders, $current_page);
                    
                    if (!empty($pending_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Service Type</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paginated_orders['data'] as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['order_id']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($row['order_type'])) ?></td>
                                            <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a class="btn view-btn" href="view_order.php?id=<?= htmlspecialchars($row['order_id']) ?>">
                                                        View Details
                                                    </a>
                                                    <button class="btn btn-danger cancel-order-btn" 
                                                            onclick="cancelOrder('<?= htmlspecialchars($row['order_id']) ?>')"
                                                            title="Cancel Order">
                                                        <i class="fas fa-times-circle me-1"></i> Cancel Order
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?= generatePagination($paginated_orders['current_page'], $paginated_orders['total_pages'], 'pending') ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <img src="../image/pending.svg" alt="No pending orders" class="empty-state-img">
                            <h3>No pending orders</h3>
                            <p>You don't have any orders awaiting approval.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Approved Tab -->
                <div class="tab-pane fade" id="approved" role="tabpanel" aria-labelledby="approved-tab">
                    <?php
                    mysqli_data_seek($result, 0); // Reset result pointer
                    $approved_orders = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Only include orders that are approved AND haven't been paid yet
                        if ($row['order_status'] == 'approved' && 
                            $row['payment_status'] != 'downpayment_paid' && 
                            $row['payment_status'] != 'fully_paid') {
                            $approved_orders[] = $row;
                        }
                    }
                    if (!empty($approved_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Service Type</th>
                                        <th>Total Amount</th>
                                        <th>Payment Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_orders as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['order_id']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($row['order_type'])) ?></td>
                                            <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                            <td>
                                                <span class="status-pill status-<?= str_replace('_', '-', $row['payment_status']) ?>">
                                                    <?= htmlspecialchars(str_replace('_', ' ', ucwords($row['payment_status']))) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <div class="d-flex flex-row gap-2">
                                                    <a class="btn view-btn" href="view_order.php?id=<?= htmlspecialchars($row['order_id']) ?>">
                                                        View Details
                                                    </a>
                                                    <a href="payment_downpayment.php?order_id=<?= $row['order_id'] ?>" class="btn btn-success payment-btn">
                                                        <i class="fas fa-credit-card me-1"></i> Pay Downpayment
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <img src="../image/approved.svg" alt="No approved orders" class="empty-state-img">
                            <h3>No approved orders</h3>
                            <p>You don't have any approved orders waiting for payment.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Processing Tab -->
                <div class="tab-pane fade" id="processing" role="tabpanel" aria-labelledby="processing-tab">
                    <?php
                    mysqli_data_seek($result, 0);
                    
                    // Remove debug output in production
                    /*
                    echo "<div class='alert alert-info mb-3'>";
                    echo "<h5>Debug Info - Order Status Values:</h5><ul>";
                    mysqli_data_seek($result, 0);
                    while ($debug_row = mysqli_fetch_assoc($result)) {
                        echo "<li>Order ID: {$debug_row['order_id']} - Status: '{$debug_row['order_status']}' - Payment: '{$debug_row['payment_status']}'</li>";
                    }
                    echo "</ul></div>";
                    mysqli_data_seek($result, 0);
                    */
                    
                    $processing_orders = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Improved check for in-process orders with better status detection
                        $status = strtolower(trim($row['order_status']));
                        
                        // Fix empty status values
                        if (empty($status) && $row['payment_status'] == 'downpayment_paid') {
                            // If status is empty but downpayment is paid, it should be in process
                            $processing_orders[] = $row;
                        } 
                        // Continue with existing checks - FIXED complete condition here
                        else if ($status == 'in_process' || 
                            $status == 'inprocess' || 
                            $status == 'in process' || 
                            $status == 'in-process' || 
                            $status == 'processing' || 
                            (
                                ($row['payment_status'] == 'downpayment_paid' || $row['payment_status'] == 'partial') && 
                                $row['order_status'] != 'ready_for_pickup' && 
                                $row['order_status'] != 'completed'
                            )
                        ) {
                            $processing_orders[] = $row;
                        }
                    }
                    
                    // Add this debug to see what's in processing_orders
                    /*
                    echo "<div class='alert alert-success mb-3'>";
                    echo "<h5>Processing Orders Found: " . count($processing_orders) . "</h5>";
                    echo "</div>";
                    */
                    
                    if (!empty($processing_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Service Type</th>
                                        <th>Expected Completion</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($processing_orders as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['order_id']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($row['order_type'])) ?></td>
                                            <td><?= date('M j, Y', strtotime($row['completion_date'])) ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="view_order.php?id=<?= htmlspecialchars($row['order_id']) ?>" class="btn view-btn">
                                                        <i class="fas fa-eye me-1"></i> View Details
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <img src="../image/processing.svg" alt="No processing orders" class="empty-state-img">
                            <h3>No orders in process</h3>
                            <p>You don't have any orders currently being processed.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Ready for Pickup Tab -->
                <div class="tab-pane fade" id="ready" role="tabpanel" aria-labelledby="ready-tab">
                    <?php
                    mysqli_data_seek($result, 0); // Reset result pointer
                    $ready_orders = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        if ($row['order_status'] == 'ready_for_pickup') {
                            $ready_orders[] = $row;
                        }
                    }
                    if (!empty($ready_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Service Type</th>
                                        <th>Payment Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ready_orders as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['order_id']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($row['order_type'])) ?></td>
                                            <td>
                                                <span class="status-pill status-<?= str_replace('_', '-', $row['payment_status']) ?>">
                                                    <?= htmlspecialchars(str_replace('_', ' ', ucwords($row['payment_status']))) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_order.php?id=<?= htmlspecialchars($row['order_id']) ?>" class="btn view-btn">
                                                        <i class="fas fa-eye me-1"></i> View Details
                                                    </a>
                                                    <?php if ($row['payment_status'] != 'fully_paid'): ?>
                                                    <a href="payment_full.php?order_id=<?= $row['order_id'] ?>" class="btn btn-success payment-btn ms-1">
                                                        <i class="fas fa-credit-card me-1"></i> Pay Balance
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <img src="../image/ready.svg" alt="No ready orders" class="empty-state-img">
                            <h3>No orders ready for pickup</h3>
                            <p>You don't have any orders ready for pickup at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Completed Tab -->
                <div class="tab-pane fade" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                    <?php
                    mysqli_data_seek($result, 0); // Reset result pointer
                    $completed_orders = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        if ($row['order_status'] == 'completed') {
                            $completed_orders[] = $row;
                        }
                    }
                    if (!empty($completed_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Service Type</th>
                                        <th>Completed Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_orders as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['order_id']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($row['order_type'])) ?></td>
                                            <td><?= date('M j, Y', strtotime($row['updated_at'])) ?></td>
                                            <td>
                                                <a href="view_order.php?id=<?= htmlspecialchars($row['order_id']) ?>" class="btn view-btn">
                                                    <i class="fas fa-eye me-1"></i> View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <img src="../image/completed.svg" alt="No completed orders" class="empty-state-img">
                            <h3>No completed orders</h3>
                            <p>You don't have any completed orders yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Declined Tab -->
                <div class="tab-pane fade" id="declined" role="tabpanel" aria-labelledby="declined-tab">
                    <?php
                    mysqli_data_seek($result, 0); // Reset result pointer
                    $declined_orders = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        if ($row['order_status'] == 'declined') {
                            $declined_orders[] = $row;
                        }
                    }
                    if (!empty($declined_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Service Type</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($declined_orders as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['order_id']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($row['order_type'])) ?></td>
                                            <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <a href="view_order.php?id=<?= htmlspecialchars($row['order_id']) ?>" class="btn view-btn">
                                                    <i class="fas fa-eye me-1"></i> View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <img src="../image/declined.svg" alt="No declined orders" class="empty-state-img">
                            <h3>No declined orders</h3>
                            <p>You don't have any declined orders.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination controls -->
            <div class="mt-4">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=1&search=<?= urlencode($search_query) ?>" tabindex="-1">First</a>
                        </li>
                        <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search_query) ?>" tabindex="-1">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search_query) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page == $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search_query) ?>">Next</a>
                        </li>
                        <li class="page-item <?= $page == $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search_query) ?>">Last</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Order Modal -->
<div id="orderModal" class="modal fade" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modal-body">
                <div class="row">
                    <!-- Order Summary Column -->
                    <div class="col-lg-4 order-summary">
                        <div class="detail-card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle me-2"></i> Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div id="order-summary-content">
                                    <!-- Order summary will be filled by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column for Progress and Timeline -->
                    <div class="col-lg-8">
                        <!-- Order Progress -->
                        <div class="detail-card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-tasks me-2"></i> Order Progress</h5>
                            </div>
                            <div class="card-body">
                                <div id="order-progress-content">
                                    <!-- Order progress will be filled by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Order Timeline -->
                        <div class="detail-card">
                            <div class="card-header">
                                <h5><i class="fas fa-history me-2"></i> Order Timeline</h5>
                            </div>
                            <div class="card-body">
                                <div id="order-timeline-content">
                                    <!-- Order timeline will be filled by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a id="view-full-details" href="#" class="btn btn-primary">View Full Details</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Initialize the orderModal as a Bootstrap modal object
let orderModalElement = document.getElementById('orderModal');
let orderModal = new bootstrap.Modal(orderModalElement);

function openModal(orderId, totalAmount, downpayment, paymentMethod, paymentStatus, createdAt, completionDate, designOption, printingType, quantity, size, color, instructions) {
    // Update modal title and view details link
    document.getElementById("orderModalLabel").innerHTML = `Order #${orderId}`;
    document.getElementById("view-full-details").href = `view_order.php?id=${orderId}`;
    
    // Show loading state
    document.getElementById("order-summary-content").innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading order details...</p></div>';
    document.getElementById("order-progress-content").innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';
    document.getElementById("order-timeline-content").innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';
    
    // Reset footer buttons
    const modalFooter = document.querySelector('#orderModal .modal-footer');
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a id="view-full-details" href="view_order.php?id=${orderId}" class="btn btn-primary">View Full Details</a>
    `;
    
    // Parse order status from server
    fetch(`get_order_tracking.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            const orderStatus = data.status || 'pending_approval';
            
            // Add payment buttons based on order status
            if (orderStatus === 'approved' && paymentStatus === 'pending') {
                // Add Pay Downpayment button
                const payButton = document.createElement('a');
                payButton.href = `payment_downpayment.php?order_id=${orderId}`;
                payButton.className = 'btn btn-success ms-2 payment-btn';
                payButton.innerHTML = '<i class="fas fa-credit-card me-2"></i> Pay Downpayment';
                modalFooter.prepend(payButton);
            } else if (orderStatus === 'ready_for_pickup' && 
                     (paymentStatus !== 'fully_paid' && paymentStatus !== 'paid')) {
                // Add Pay Balance button
                const payButton = document.createElement('a');
                payButton.href = `payment_full.php?order_id=${orderId}`;
                payButton.className = 'btn btn-success ms-2 payment-btn';
                payButton.innerHTML = '<i class="fas fa-credit-card me-2"></i> Pay Balance';
                modalFooter.prepend(payButton);
            }
            
            // Order summary section
            let orderSummaryContent = `
                <div class="summary-section">
                    <div class="summary-row">
                        <div class="summary-label">Order Type</div>
                        <div class="summary-value">${printingType ? 'Sublimation' : 'Tailoring'}</div>
                    </div>
                    <div class="summary-row">
                        <div class="summary-label">Order Date</div>
                        <div class="summary-value">${createdAt}</div>
                    </div>
                    <div class="summary-row">
                        <div class="summary-label">Expected Delivery</div>
                        <div class="summary-value">${completionDate}</div>
                    </div>
                </div>
                
                <div class="summary-section">
                    <div class="summary-header">Payment Details</div>
                    <div class="summary-row">
                        <div class="summary-label">Payment Method</div>
                        <div class="summary-value">${paymentMethod.toUpperCase()}</div>
                    </div>
                    <div class="summary-row">
                        <div class="summary-label">Status</div>
                        <div class="summary-value">
                            <span class="shopee-badge ${paymentStatus.replace('_', '-')}-badge">
                                ${paymentStatus.replace(/_/g, ' ').toUpperCase()}
                            </span>
                        </div>
                    </div>
                </div>`;
                
            // Add payment action button to summary section if needed
            if ((orderStatus === 'approved' && paymentStatus === 'pending') || 
                (orderStatus === 'ready_for_pickup' && paymentStatus !== 'fully_paid' && paymentStatus !== 'paid')) {
                
                let btnText = '';
                let redirectUrl = '';
                
                if (orderStatus === 'approved') {
                    btnText = 'Pay Downpayment';
                    redirectUrl = `payment_downpayment.php?order_id=${orderId}`;
                } else {
                    btnText = 'Pay Remaining Balance';
                    redirectUrl = `payment_full.php?order_id=${orderId}`;
                }
                
                // Add call-to-action payment button below the payment details
                orderSummaryContent += `
                <div class="summary-section payment-cta text-center">
                    <a href="${redirectUrl}" class="btn btn-success btn-lg payment-btn payment-btn-lg w-100">
                        <i class="fas fa-credit-card me-2"></i> ${btnText}
                    </a>
                    <p class="text-muted mt-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        ${orderStatus === 'approved' ? 
                          'Payment is required to start production of your order.' : 
                          'Complete your payment to pick up your order.'}
                    </p>
                </div>`;
            }
            
            // Add price section
            orderSummaryContent += `
                <div class="summary-section price-section">
                    <div class="summary-row">
                        <div class="summary-label">Total Amount</div>
                        <div class="summary-value">₱${totalAmount}</div>
                    </div>
                    <div class="summary-row">
                        <div class="summary-label">Downpayment</div>
                        <div class="summary-value">₱${downpayment}</div>
                    </div>
                    <div class="summary-row total-row">
                        <div class="summary-label">Remaining Balance</div>
                        <div class="summary-value">₱${(parseFloat(totalAmount.replace(/,/g, '')) - parseFloat(downpayment.replace(/,/g, ''))).toFixed(2)}</div>
                    </div>
                </div>`;
            
            document.getElementById("order-summary-content").innerHTML = orderSummaryContent;
            
            // Build the Shopee-style tracking timeline
            const orderStatus = data.status || 'pending_approval';
            
            // Define the steps in the order process
            const steps = [
                { id: 'pending_approval', label: 'Order Placed', icon: 'shopping-cart' },
                { id: 'approved', label: 'Order Approved', icon: 'check-circle' },
                { id: 'in_process', label: 'Processing', icon: 'cog' },
                { id: 'ready_for_pickup', label: 'Ready for Pickup', icon: 'box' },
                { id: 'completed', label: 'Completed', icon: 'check-double' }
            ];
            
            // Find current step index
            let currentStepIndex = steps.findIndex(step => 
                step.id === orderStatus ||
                (orderStatus === 'processing' && step.id === 'in_process') ||
                (orderStatus === 'in-process' && step.id === 'in_process')
            );
            
            if (currentStepIndex === -1) currentStepIndex = 0;
            
            // Create shopee-style progress tracker
            let progressHtml = `
                <div class="shopee-tracking">
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar">
                            <div class="progress-inner" style="width: ${(currentStepIndex / (steps.length - 1)) * 100}%"></div>
                        </div>
                        <div class="progress-steps">`;
                        
            steps.forEach((step, index) => {
                const isActive = index <= currentStepIndex;
                progressHtml += `
                    <div class="progress-step ${isActive ? 'active' : ''} ${index === currentStepIndex ? 'current' : ''}" 
                         style="left: ${(index / (steps.length - 1)) * 100}%">
                        <div class="step-icon">
                            <i class="fas fa-${step.icon}"></i>
                        </div>
                        <div class="step-label">${step.label}</div>
                    </div>`;
            });
            
            progressHtml += `</div></div></div>`;
            
            document.getElementById("order-progress-content").innerHTML = progressHtml;
            
            // Check if timeline data exists before rendering
            if (data.timeline && Array.isArray(data.timeline) && data.timeline.length > 0) {
                let timelineHtml = `
                    <div class="shopee-timeline">
                        <div class="timeline-list">`;
                        
                data.timeline.forEach((event, index) => {
                    timelineHtml += `
                        <div class="timeline-item">
                            <div class="timeline-point ${event.status === 'completed' ? 'active' : ''}"></div>
                            <div class="timeline-content">
                                <div class="event-time">${event.time}</div>
                                <div class="event-title">${event.title}</div>
                                <div class="event-message">${event.message}</div>
                            </div>
                        </div>`;
                });
                
                timelineHtml += `</div></div>`;
                document.getElementById("order-timeline-content").innerHTML = timelineHtml;
            } else {
                document.getElementById("order-timeline-content").innerHTML = '<p class="text-center text-muted">No timeline information available</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching order details:', error);
            document.getElementById("order-summary-content").innerHTML = '<div class="alert alert-danger">Error loading order details. Please try again.</div>';
            document.getElementById("order-progress-content").innerHTML = '<div class="alert alert-danger">Error loading order progress.</div>';
            document.getElementById("order-timeline-content").innerHTML = '<div class="alert alert-danger">Error loading order timeline.</div>';
        });
}

// Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Handle pagination clicks
    document.querySelectorAll('.pagination .page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tab = this.dataset.tab;
            const page = this.dataset.page;
            
            // Store the current page in session storage
            sessionStorage.setItem(`${tab}_page`, page);
            
            // Reload the tab content
            loadTabContent(tab, page);
        });
    });
});

function loadTabContent(tab, page) {
    const tabContent = document.querySelector(`#${tab}`);
    
    // Show loading spinner
    tabContent.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>`;
    
    // Fetch new content
    fetch(`get_tab_content.php?tab=${tab}&page=${page}`)
        .then(response => response.text())
        .then(html => {
            tabContent.innerHTML = html;
            
            // Reinitialize pagination event listeners
            initPagination();
        })
        .catch(error => {
            console.error('Error loading tab content:', error);
            tabContent.innerHTML = `
                <div class="alert alert-danger">
                    Error loading content. Please try again.
                </div>`;
        });
}

function initPagination() {
    document.querySelectorAll('.pagination .page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tab = this.dataset.tab;
            const page = this.dataset.page;
            loadTabContent(tab, page);
        });
    });
}

// Cancel order function
function cancelOrder(orderId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently cancel your order. This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, cancel order',
        cancelButtonText: 'No, keep order'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Cancelling order...',
                text: 'Please wait',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send delete request
            fetch('cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Cancelled!',
                        text: 'Your order has been cancelled.',
                        icon: 'success',
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to cancel order');
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Failed to cancel order',
                    icon: 'error'
                });
            });
        }
    });
}
</script>

</body>
</html>
