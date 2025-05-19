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
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sidebar {
            background-color: #443627 !important;
        }

        .sidebar .nav-item .nav-link {
            color: #EFDCAB !important;
        }
        
        /* Enhanced table styling */
        .table-container {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: #D98324;
            color: white;
            font-weight: 600;
            border: none;
            padding: 12px 15px;
            white-space: nowrap;
        }
        
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .table tbody tr {
            border-bottom: 1px solid #f2f2f2;
            transition: background-color 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(217, 131, 36, 0.05);
        }
        
        .table tbody tr:last-child {
            border-bottom: none;
        }

        /* Action buttons styling */
        .actions-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            border: none;
            transition: all 0.2s;
            color: white;
            flex-shrink: 0;
        }
        
        .btn-view {
            background-color: #17a2b8;
        }
        
        .btn-view:hover {
            background-color: #138496;
            transform: translateY(-2px);
        }
        
        .btn-approve {
            background-color: #28a745;
        }
        
        .btn-approve:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        
        .btn-decline {
            background-color: #dc3545;
        }
        
        .btn-decline:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
        /* Status badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        /* Pagination styling */
        .pagination {
            margin-top: 1rem;
        }
        
        .page-link {
            color: #D98324;
            border-color: #dee2e6;
        }
        
        .page-item.active .page-link {
            background-color: #D98324;
            border-color: #D98324;
        }
        
        .page-link:hover {
            color: #443627;
            background-color: #e9ecef;
        }
        
        /* Modal styling */
        .modal-header {
            background-color: #D98324;
            color: white;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .btn-confirm {
            background-color: #D98324;
            border-color: #D98324;
            color: white;
        }
        
        .btn-confirm:hover {
            background-color: #c27420;
            border-color: #c27420;
        }
        
        .footer {
            width: 100%;
            background-color: #443627 !important;
            color: #EFDCAB !important;
            text-align: center;
            padding: 10px 0;
            margin-top: 2rem;
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                            Approve Orders
                        </h1>
                    </div>

                    <!-- Orders Table -->
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Service Type</th>
                                    <th>Total Amount</th>
                                    <th>Downpayment</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($row['order_id']) ?></td>
                                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($row['order_type'])) ?></td>
                                        <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                        <td>₱<?= number_format($row['downpayment_amount'], 2) ?></td>
                                        <td>
                                            <div class="status-badge status-pending">
                                                <?= htmlspecialchars(str_replace('_', ' ', ucfirst($row['order_status']))) ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="actions-container">
                                                <!-- View button with eye icon -->
                                                <a href="view_order.php?id=<?= $row['order_id'] ?>" class="btn-action btn-view">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <!-- Approve button with thumbs-up icon -->
                                                <button class="btn-action btn-approve" onclick="approveOrder('<?= $row['order_id'] ?>')">
                                                    <i class="fas fa-thumbs-up"></i>
                                                </button>
                                                
                                                <!-- Decline button with thumbs-down icon -->
                                                <button class="btn-action btn-decline" data-bs-toggle="modal" data-bs-target="#declineModal" 
                                                        onclick="setDeclineOrderId('<?= $row['order_id'] ?>')">
                                                    <i class="fas fa-thumbs-down"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                
                                <?php if (mysqli_num_rows($result) == 0) : ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No pending orders found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 0) : ?>
                    <div class="d-flex justify-content-center">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php if ($page > 1) : ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page - 1) ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo; Previous</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                // Show limited page numbers with ellipsis
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                // Show first page if not in range
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                // Show page numbers
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                                    echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                                    echo '</li>';
                                }
                                
                                // Show last page if not in range
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                
                                <?php if ($page < $total_pages) : ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page + 1) ?>" aria-label="Next">
                                            <span aria-hidden="true">Next &raquo;</span>
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
    </div>
    
    <!-- Decline Order Modal -->
    <div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="declineModalLabel">Decline Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="declineOrderId" value="">
                    <div class="mb-3">
                        <label for="declineReason" class="form-label">Reason for Declining</label>
                        <textarea class="form-control" id="declineReason" rows="3" required></textarea>
                        <div class="form-text">Please provide a detailed reason why this order is being declined.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-confirm" onclick="declineOrder()">Confirm Decline</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS and jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function approveOrder(orderId) {
            if (confirm("Are you sure you want to approve this order?")) {
                $.ajax({
                    url: "orders.php",
                    type: "POST",
                    data: { order_id: orderId, approve: true },
                    success: function(response) {
                        alert(response);
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        alert("Error approving order. Check the console for details.");
                    }
                });
            }
        }
        
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
                url: "decline_order.php",
                type: "POST",
                data: { 
                    order_id: orderId, 
                    reason: reason 
                },
                success: function(response) {
                    alert(response);
                    $('#declineModal').modal('hide');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("Error declining order. Check the console for details.");
                }
            });
        }
    </script>
</body>
</html>
