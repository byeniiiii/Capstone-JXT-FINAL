<?php
include '../db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['approve'])) {
    include '../db.php';

    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);

    // Update order status to "Approved"
    $updateQuery = "UPDATE orders SET order_status = 'approved' WHERE order_id = '$order_id'";

    if (mysqli_query($conn, $updateQuery)) {
        echo "Order approved! Waiting for customer downpayment.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit;
}

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending_approval'";
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch orders that are "Pending Approval" with pagination
$query = "SELECT o.order_id, c.first_name, c.last_name, o.order_type, o.total_amount, o.downpayment_amount, o.order_status, o.created_at
          FROM orders o
          JOIN customers c ON o.customer_id = c.customer_id
          WHERE o.order_status = 'pending_approval'
          ORDER BY o.created_at DESC
          LIMIT $offset, $records_per_page";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="../image/logo.png">
    <title>JXT Admin</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        /* Simplified color scheme */
        :root {
            --primary: #443627;
            --secondary: #D98324;
            --light-bg: #f8f9fc;
            --white: #ffffff;
            --text-dark: #333333;
            --text-light: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
        }
        
        /* Simplified header */
        .page-header {
            background-color: var(--white);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }
        
        /* Simplified filter section */
        .filters-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        /* Smaller compact table */
        .table-container {
            background-color: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }

        .table {
            margin-bottom: 0;
            width: 100%;
            font-size: 0.85rem; /* Smaller font */
        }
        
        .table th {
            background-color: var(--secondary);
            color: white;
            font-weight: 600;
            padding: 0.5rem; /* Reduced padding */
        }
        
        .table td {
            padding: 0.5rem; /* Reduced padding */
            vertical-align: middle;
        }
        
        /* Simplified button styles */
        .btn-action {
            width: 30px; /* Smaller */
            height: 30px; /* Smaller */
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            color: white;
            margin: 0 2px;
        }
        
        .btn-view { background-color: var(--info); }
        .btn-approve { background-color: var(--success); }
        .btn-decline { background-color: var(--danger); }
        
        /* Simple status badges */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        /* Basic pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">
                        <?php include 'notification.php'; ?>  <!-- Changed from 'notifications.php' to 'notification.php' -->

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                                    <i class='fas fa-user-circle' style="font-size:20px; margin-left: 10px;"></i>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <!-- Enhanced Header -->
                    <div class="page-header">
                        <h1>Order Approvals</h1>
                        <div>
                            <span class="badge bg-warning text-dark me-2"><?= $total_records ?> Pending Orders</span>
                            <a href="?refresh=true" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </a>
                        </div>
                    </div>
                    
                    <!-- Filters Section -->
                    <div class="filters-container">
                        <form class="filter-form" method="GET">
                            <div class="filter-group">
                                <label class="filter-label">Order Type</label>
                                <select name="type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="sublimation" <?= isset($_GET['type']) && $_GET['type'] == 'sublimation' ? 'selected' : '' ?>>Sublimation</option>
                                    <option value="tailoring" <?= isset($_GET['type']) && $_GET['type'] == 'tailoring' ? 'selected' : '' ?>>Tailoring</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Date Range</label>
                                <input type="date" name="date_from" class="form-control" value="<?= isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : '' ?>">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">To</label>
                                <input type="date" name="date_to" class="form-control" value="<?= isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : '' ?>">
                            </div>
                            <div class="filter-buttons">
                                <button type="submit" class="btn btn-confirm">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="orders.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Orders Table -->
                    <div class="table-container">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Service Type</th>
                                        <th>Total Amount</th>
                                        <th>Downpayment</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                        <tr>
                                            <td><span class="order-id"><?= htmlspecialchars($row['order_id']) ?></span></td>
                                            <td>
                                                <span class="customer-name"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($row['order_type'] == 'sublimation'): ?>
                                                    <span class="badge bg-info text-white">Sublimation</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary text-white">Tailoring</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="amount">₱<?= number_format($row['total_amount'], 2) ?></span></td>
                                            <td><span class="amount">₱<?= number_format($row['downpayment_amount'], 2) ?></span></td>
                                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <div class="status-badge status-pending">
                                                    <?= htmlspecialchars(str_replace('_', ' ', ucfirst($row['order_status']))) ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="actions-container">
                                                    <!-- View button with tooltip -->
                                                    <a href="view_order.php?id=<?= $row['order_id'] ?>" class="btn-action btn-view" data-tooltip="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <!-- Approve button with tooltip -->
                                                    <button class="btn-action btn-approve" onclick="approveOrder('<?= $row['order_id'] ?>')" data-tooltip="Approve Order">
                                                        <i class="fas fa-thumbs-up"></i>
                                                    </button>
                                                    
                                                    <!-- Decline button with tooltip -->
                                                    <button class="btn-action btn-decline" data-bs-toggle="modal" data-bs-target="#declineModal" 
                                                            onclick="setDeclineOrderId('<?= $row['order_id'] ?>')" data-tooltip="Decline Order">
                                                        <i class="fas fa-thumbs-down"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <!-- Empty state with icon -->
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <h4>No Pending Orders</h4>
                                <p class="empty-state-text">There are currently no orders that require your approval.</p>
                                <a href="orders.php" class="btn btn-confirm">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Enhanced Pagination -->
                    <?php if ($total_pages > 0) : ?>
                    <div class="pagination-container">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php if ($page > 1) : ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page - 1) ?><?= isset($_GET['type']) ? '&type=' . $_GET['type'] : '' ?>" aria-label="Previous">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                // Show limited page numbers with ellipsis
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                // Show first page if not in range
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : '') . '">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                // Show page numbers
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                                    echo '<a class="page-link" href="?page=' . $i . (isset($_GET['type']) ? '&type=' . $_GET['type'] : '') . '">' . $i . '</a>';
                                    echo '</li>';
                                }
                                
                                // Show last page if not in range
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . (isset($_GET['type']) ? '&type=' . $_GET['type'] : '') . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                
                                <?php if ($page < $total_pages) : ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page + 1) ?><?= isset($_GET['type']) ? '&type=' . $_GET['type'] : '' ?>" aria-label="Next">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Footer -->
                <?php include 'footer.php'; ?>
            </div>
        </div>
    </div
    
    <!-- Decline Order Modal -->
    <div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="declineModalLabel">Decline Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <span class="fa-stack fa-2x">
                            <i class="fas fa-circle fa-stack-2x text-danger opacity-25"></i>
                            <i class="fas fa-times fa-stack-1x text-danger"></i>
                        </span>
                        <h4 class="mt-3">Decline Order Confirmation</h4>
                        <p class="text-muted">Please provide a reason for declining this order</p>
                    </div
                    
                    <input type="hidden" id="declineOrderId" value="">
                    <div class="mb-3">
                        <label for="declineReason" class="form-label">Reason for Declining</label>
                        <textarea class="form-control" id="declineReason" rows="3" required placeholder="Example: Insufficient details provided"></textarea>
                        <div class="form-text">This reason will be visible to the customer in their order history.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-confirm" onclick="declineOrder()">
                        <i class="fas fa-check me-1"></i> Confirm Decline
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add a modern confirmation modal for approvals -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(to right, #28a745, #5cb85c);">
                    <h5 class="modal-title" id="approveModalLabel">Approve Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <span class="fa-stack fa-2x">
                            <i class="fas fa-circle fa-stack-2x text-success opacity-25"></i>
                            <i class="fas fa-check fa-stack-1x text-success"></i>
                        </span>
                        <h4 class="mt-3">Approve Order Confirmation</h4>
                        <p class="text-muted">Are you sure you want to approve this order?</p>
                        <p class="text-muted small">The customer will be notified and asked to complete the downpayment.</p>
                    </div>
                    
                    <input type="hidden" id="approveOrderId" value="">
                    <div class="mb-3">
                        <label for="approveNotes" class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" id="approveNotes" rows="2" placeholder="Example: Please contact customer to confirm details"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" style="background-color: #28a745; color: white;" onclick="confirmApproval()">
                        <i class="fas fa-check me-1"></i> Confirm Approval
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS and jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Function to approve an order
        function approveOrder(orderId) {
            if (confirm('Are you sure you want to approve this order?')) {
                $.ajax({
                    url: "process_approval.php",
                    type: "POST",
                    data: { 
                        order_id: orderId, 
                        action: 'approve'
                    },
                    success: function(response) {
                        alert('Order has been approved successfully!');
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Error: ' + error);
                    }
                });
            }
        }
        
        // Function to decline an order
        function setDeclineOrderId(orderId) {
            document.getElementById('declineOrderId').value = orderId;
        }
        
        function declineOrder() {
            const orderId = document.getElementById('declineOrderId').value;
            const reason = document.getElementById('declineReason').value;
            
            if (!reason.trim()) {
                alert('Please provide a reason for declining the order.');
                return;
            }
            
            $.ajax({
                url: "process_approval.php",
                type: "POST",
                data: { 
                    order_id: orderId,
                    action: 'decline',
                    reason: reason 
                },
                success: function(response) {
                    alert('Order has been declined successfully!');
                    $('#declineModal').modal('hide');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    alert('Error: ' + error);
                }
            });
        }
    </script>
</body>
</html>
