<?php
	
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Order Approval</h1>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']); 
        ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error_message'];
        unset($_SESSION['error_message']); 
        ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Orders</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="approve_orders.php" class="row">
                <div class="col-md-5 mb-3">
                    <label for="status">Order Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending_approval" <?= $status_filter === 'pending_approval' ? 'selected' : '' ?>>Pending Approval</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-5 mb-3">
                    <label for="type">Order Type</label>
                    <select class="form-control" id="type" name="type">
                        <option value="all" <?= $order_type === 'all' ? 'selected' : '' ?>>All Types</option>
                        <option value="sublimation" <?= $order_type === 'sublimation' ? 'selected' : '' ?>>Sublimation</option>
                        <option value="tailoring" <?= $order_type === 'tailoring' ? 'selected' : '' ?>>Tailoring</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <?= $result->num_rows ?> Orders 
                <?= $status_filter !== 'all' ? '(' . ucfirst(str_replace('_', ' ', $status_filter)) . ')' : '' ?>
                <?= $order_type !== 'all' ? '- ' . ucfirst($order_type) : '' ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="ordersTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="#" data-toggle="modal" data-target="#orderModal<?= $row['order_id'] ?>">
                                            <?= $row['order_id'] ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['customer_name']) ?><br>
                                        <small class="text-muted"><?= $row['email'] ?></small>
                                    </td>
                                    <td>
                                        <?php if ($row['order_type'] === 'sublimation'): ?>
                                            <span class="badge badge-info">Sublimation</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Tailoring</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <?php
                                            switch ($row['order_status']) {
                                                case 'pending_approval':
                                                    echo '<span class="badge badge-warning">Pending Approval</span>';
                                                    break;
                                                case 'approved':
                                                    echo '<span class="badge badge-success">Approved</span>';
                                                    break;
                                                case 'rejected':
                                                    echo '<span class="badge badge-danger">Rejected</span>';
                                                    break;
                                                case 'in_progress':
                                                    echo '<span class="badge badge-primary">In Progress</span>';
                                                    break;
                                                case 'completed':
                                                    echo '<span class="badge badge-info">Completed</span>';
                                                    break;
                                                case 'delivered':
                                                    echo '<span class="badge badge-success">Delivered</span>';
                                                    break;
                                                case 'cancelled':
                                                    echo '<span class="badge badge-dark">Cancelled</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge badge-secondary">Unknown</span>';
                                            }
                                        ?>
                                    </td>
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#orderModal<?= $row['order_id'] ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if ($row['order_status'] === 'pending_approval'): ?>
                                            <button class="btn btn-sm btn-success mt-1" data-toggle="modal" data-target="#approveModal<?= $row['order_id'] ?>">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger mt-1" data-toggle="modal" data-target="#rejectModal<?= $row['order_id'] ?>">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <h5>No orders found with the selected filters</h5>
                    <p class="text-muted">Try changing your filter criteria or check back later</p>
                    <a href="approve_orders.php" class="btn btn-outline-primary">Reset Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Reset result pointer
if ($result && $result->num_rows > 0) {
    $result->data_seek(0);

    // Create modals for each order
    while ($row = $result->fetch_assoc()): 
        // Get order status history
        $history_query = "SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
                        FROM order_status_history h
                        LEFT JOIN users u ON h.changed_by = u.user_id
                        WHERE h.order_id = ?
                        ORDER BY h.changed_at DESC";
        $stmt = $conn->prepare($history_query);
        $stmt->bind_param("s", $row['order_id']);
        $stmt->execute();
        $history_result = $stmt->get_result();
?>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal<?= $row['order_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel<?= $row['order_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel<?= $row['order_id'] ?>">
                    Order #<?= $row['order_id'] ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="font-weight-bold mb-0">Order Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th>Order ID:</th>
                                        <td><?= $row['order_id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Type:</th>
                                        <td>
                                            <?php if ($row['order_type'] === 'sublimation'): ?>
                                                <span class="badge badge-info">Sublimation</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Tailoring</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Date:</th>
                                        <td><?= date('F d, Y h:i A', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <?php
                                                switch ($row['order_status']) {
                                                    case 'pending_approval':
                                                        echo '<span class="badge badge-warning">Pending Approval</span>';
                                                        break;
                                                    case 'approved':
                                                        echo '<span class="badge badge-success">Approved</span>';
                                                        break;
                                                    case 'rejected':
                                                        echo '<span class="badge badge-danger">Rejected</span>';
                                                        break;
                                                    case 'in_progress':
                                                        echo '<span class="badge badge-primary">In Progress</span>';
                                                        break;
                                                    case 'completed':
                                                        echo '<span class="badge badge-info">Completed</span>';
                                                        break;
                                                    case 'delivered':
                                                        echo '<span class="badge badge-success">Delivered</span>';
                                                        break;
                                                    case 'cancelled':
                                                        echo '<span class="badge badge-dark">Cancelled</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge badge-secondary">Unknown</span>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Payment:</th>
                                        <td>
                                            <?php if ($row['payment_status'] === 'paid'): ?>
                                                <span class="badge badge-success">Paid</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Amount:</th>
                                        <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="font-weight-bold mb-0">Customer Information</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> <?= htmlspecialchars($row['customer_name']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($row['phone_number']) ?></p>
                            </div>                            <?php
                            session_start();
                            include_once '../db.php';
                            
                            // If the user is not logged in, redirect to index.php
                            if (!isset($_SESSION['user_id'])) {
                                header("Location: ../index.php");
                                exit();
                            }
                            
                            // Filtering options
                            $status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending_approval';
                            $order_type = isset($_GET['type']) ? $_GET['type'] : 'all';
                            
                            // Process status changes if form is submitted
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
                                $order_id = $_POST['order_id'];
                                $new_status = $_POST['status'];
                                $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
                                
                                // Start transaction
                                $conn->begin_transaction();
                                
                                try {
                                    // Update order status
                                    $update_query = "UPDATE orders SET order_status = ? WHERE order_id = ?";
                                    $stmt = $conn->prepare($update_query);
                                    $stmt->bind_param("ss", $new_status, $order_id);
                                    $stmt->execute();
                                    
                                    // Add entry to order status history
                                    $history_query = "INSERT INTO order_status_history (order_id, status, notes, changed_at, changed_by) 
                                                     VALUES (?, ?, ?, NOW(), ?)";
                                    $stmt = $conn->prepare($history_query);
                                    $user_id = $_SESSION['user_id'];
                                    $stmt->bind_param("sssi", $order_id, $new_status, $notes, $user_id);
                                    $stmt->execute();
                                    
                                    // Create notification for the customer
                                    $get_customer_query = "SELECT customer_id FROM orders WHERE order_id = ?";
                                    $stmt = $conn->prepare($get_customer_query);
                                    $stmt->bind_param("s", $order_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    if ($customer = $result->fetch_assoc()) {
                                        $customer_id = $customer['customer_id'];
                                        $title = "Order Status Updated";
                                        $message = "Your order #$order_id has been updated to: " . ucfirst(str_replace('_', ' ', $new_status));
                                        
                                        $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) 
                                                              VALUES (?, ?, ?, ?, NOW())";
                                        $stmt = $conn->prepare($notification_query);
                                        $stmt->bind_param("isss", $customer_id, $order_id, $title, $message);
                                        $stmt->execute();
                                    }
                                    
                                    // Commit transaction
                                    $conn->commit();
                                    
                                    // Set success message
                                    $_SESSION['success_message'] = "Order #$order_id status has been updated to " . ucfirst(str_replace('_', ' ', $new_status));
                                } catch (Exception $e) {
                                    // Rollback on error
                                    $conn->rollback();
                                    $_SESSION['error_message'] = "Error updating order status: " . $e->getMessage();
                                }
                                
                                // Redirect to prevent form resubmission
                                header("Location: approve_orders.php");
                                exit();
                            }
                            
                            // Build query based on filters
                            $query = "SELECT o.*, 
                                      CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                                      c.phone_number, c.email
                                      FROM orders o
                                      JOIN customers c ON o.customer_id = c.customer_id
                                      WHERE 1=1";
                            
                            if ($status_filter !== 'all') {
                                $query .= " AND o.order_status = '$status_filter'";
                            }
                            
                            if ($order_type !== 'all') {
                                $query .= " AND o.order_type = '$order_type'";
                            }
                            
                            $query .= " ORDER BY o.created_at DESC";
                            
                            // Execute query
                            $result = $conn->query($query);
                            
                            // Get current date info
                            $today = date('Y-m-d');
                            $current_week_start = date('Y-m-d', strtotime('monday this week'));
                            $current_week_end = date('Y-m-d', strtotime('sunday this week'));
                            $current_month_start = date('Y-m-01');
                            $current_month_end = date('Y-m-t');
                            
                            // Get total orders
                            $total_orders_query = "SELECT COUNT(*) as total FROM orders";
                            $total_orders_result = $conn->query($total_orders_query);
                            $total_orders = $total_orders_result->fetch_assoc()['total'];
                            
                            // Get monthly orders
                            $monthly_orders_query = "SELECT COUNT(*) as monthly FROM orders 
                                                    WHERE created_at BETWEEN '$current_month_start 00:00:00' AND '$current_month_end 23:59:59'";
                            $monthly_orders_result = $conn->query($monthly_orders_query);
                            $monthly_orders = $monthly_orders_result->fetch_assoc()['monthly'];
                            ?>
                            
                            <!DOCTYPE html>
                            <html lang="en">
                            <head>
                                <meta charset="utf-8">
                                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                                <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
                                <meta name="description" content="">
                                <meta name="author" content="">
                                <link rel="icon" type="image/png" href="../image/logo.png">
                                <title>JXT Admin - Approve Orders</title>
                            
                                <!-- Custom fonts -->
                                <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
                                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
                                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
                            
                                <!-- Custom styles -->
                                <link href="css/sb-admin-2.min.css" rel="stylesheet">
                            
                                <style>
                                    body {
                                        font-family: 'Poppins', sans-serif;
                                        background-color: #f8f9fc;
                                        color: #5a5c69;
                                        overflow-x: hidden;
                                    }
                            
                                    /* Remove all sidebar styling since it's handled in sidebar.php */
                                    /* Keep only the main content styling */
                                    
                                    .card {
                                        border: none !important;
                                        border-radius: 0.35rem;
                                        background-color: #ffffff !important;
                                        color: #5a5c69 !important;
                                        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1) !important;
                                        transition: transform 0.3s ease, box-shadow 0.3s ease;
                                    }
                            
                                    .card:hover {
                                        transform: translateY(-3px);
                                        box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15) !important;
                                    }
                            
                                    .card-header {
                                        background-color: #f8f9fc !important;
                                        border-bottom: 1px solid #e3e6f0;
                                    }
                            
                                    .btn-primary {
                                        background-color: #4e73df !important;
                                        border-color: #4e73df !important;
                                        box-shadow: 0 0.125rem 0.25rem 0 rgba(58, 59, 69, 0.2) !important;
                                        transition: all 0.2s ease;
                                    }
                            
                                    .btn-primary:hover {
                                        background-color: #2e59d9 !important;
                                        border-color: #2653d4 !important;
                                        transform: translateY(-1px);
                                    }
                            
                                    .text-primary {
                                        color: #4e73df !important;
                                    }
                            
                                    .text-gray-800 {
                                        color: #5a5c69 !important;
                                    }
                            
                                    .footer {
                                        background-color: #f8f9fc !important;
                                        color: #858796 !important;
                                        border-top: 1px solid #e3e6f0;
                                        font-size: 0.85rem;
                                    }
                            
                                    /* Card highlight colors */
                                    .border-left-primary {
                                        border-left: 0.25rem solid #4e73df !important;
                                    }
                            
                                    .border-left-success {
                                        border-left: 0.25rem solid #1cc88a !important;
                                    }
                            
                                    .border-left-info {
                                        border-left: 0.25rem solid #36b9cc !important;
                                    }
                            
                                    .border-left-warning {
                                        border-left: 0.25rem solid #f6c23e !important;
                                    }
                            
                                    /* Modern table styling */
                                    .table {
                                        color: #5a5c69;
                                    }
                            
                                    .table-bordered {
                                        border: 1px solid #e3e6f0;
                                    }
                            
                                    .table th {
                                        background-color: #f8f9fc;
                                        border-bottom: 2px solid #e3e6f0;
                                        font-weight: 600;
                                    }
                            
                                    /* Status badges */
                                    .badge {
                                        font-weight: 600;
                                        padding: 0.35em 0.65em;
                                        border-radius: 0.35rem;
                                    }
                            
                                    .badge.bg-light {
                                        background-color: #f8f9fc !important;
                                        color: #5a5c69;
                                        border: 1px solid #e3e6f0;
                                    }
                            
                                    .badge.bg-warning {
                                        background-color: #f6c23e !important;
                                        color: #fff;
                                    }
                            
                                    .badge.bg-danger {
                                        background-color: #e74a3b !important;
                                    }
                            
                                    .badge.bg-info {
                                        background-color: #36b9cc !important;
                                    }
                            
                                    .badge.bg-primary {
                                        background-color: #4e73df !important;
                                    }
                            
                                    .badge.bg-success {
                                        background-color: #1cc88a !important;
                                    }
                            
                                    .badge.bg-secondary {
                                        background-color: #858796 !important;
                                    }
                                </style>
                            </head>
                            
                            <body id="page-top">
                                <!-- Page Wrapper -->
                                <div id="wrapper">
                                    <?php include 'sidebar.php'; ?>
                            
                                    <!-- Content Wrapper -->
                                    <div id="content-wrapper" class="d-flex flex-column">
                            
                                        <!-- Main Content -->
                                        <div id="content">
                            
                                            <!-- Topbar -->
                                            <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">
                            
                                                <!-- Topbar Navbar -->
                                                <ul class="navbar-nav ml-auto">
                                                    <?php include 'notifications.php'; ?>
                            
                                                    <!-- User Info -->
                                                    <li class="nav-item dropdown no-arrow">
                                                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                                                <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                                                                <i class='fas fa-user-circle' style="font-size:20px; margin-left: 10px;"></i>
                                                            </span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </nav>
                                            <!-- End of Topbar -->
                            
                                            <!-- Begin Page Content -->
                                            <div class="container-fluid">
                                                <!-- Page Heading -->
                                                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                                                    <h1 class="h3 mb-0 text-gray-800">Order Approval</h1>
                                                </div>
                            
                                                <?php if (isset($_SESSION['success_message'])): ?>
                                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                    <?php 
                                                    echo $_SESSION['success_message'];
                                                    unset($_SESSION['success_message']); 
                                                    ?>
                                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                            
                                                <?php if (isset($_SESSION['error_message'])): ?>
                                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                    <?php 
                                                    echo $_SESSION['error_message'];
                                                    unset($_SESSION['error_message']); 
                                                    ?>
                                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                            
                                                <!-- Filters -->
                                                <div class="card shadow mb-4">
                                                    <div class="card-header py-3">
                                                        <h6 class="m-0 font-weight-bold text-primary">Filter Orders</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <form method="GET" action="approve_orders.php" class="row">
                                                            <div class="col-md-5 mb-3">
                                                                <label for="status">Order Status</label>
                                                                <select class="form-control" id="status" name="status">
                                                                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                                                    <option value="pending_approval" <?= $status_filter === 'pending_approval' ? 'selected' : '' ?>>Pending Approval</option>
                                                                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                                                                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                                                    <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                                                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                                    <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-5 mb-3">
                                                                <label for="type">Order Type</label>
                                                                <select class="form-control" id="type" name="type">
                                                                    <option value="all" <?= $order_type === 'all' ? 'selected' : '' ?>>All Types</option>
                                                                    <option value="sublimation" <?= $order_type === 'sublimation' ? 'selected' : '' ?>>Sublimation</option>
                                                                    <option value="tailoring" <?= $order_type === 'tailoring' ? 'selected' : '' ?>>Tailoring</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-2 mb-3 d-flex align-items-end">
                                                                <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                            
                                                <!-- Orders List -->
                                                <div class="card shadow mb-4">
                                                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <?= $result->num_rows ?> Orders 
                                                            <?= $status_filter !== 'all' ? '(' . ucfirst(str_replace('_', ' ', $status_filter)) . ')' : '' ?>
                                                            <?= $order_type !== 'all' ? '- ' . ucfirst($order_type) : '' ?>
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php if ($result && $result->num_rows > 0): ?>
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered" id="ordersTable" width="100%" cellspacing="0">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Order ID</th>
                                                                            <th>Customer</th>
                                                                            <th>Type</th>
                                                                            <th>Date</th>
                                                                            <th>Status</th>
                                                                            <th>Amount</th>
                                                                            <th>Action</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php while ($row = $result->fetch_assoc()): ?>
                                                                            <tr>
                                                                                <td>
                                                                                    <a href="#" data-toggle="modal" data-target="#orderModal<?= $row['order_id'] ?>">
                                                                                        <?= $row['order_id'] ?>
                                                                                    </a>
                                                                                </td>
                                                                                <td>
                                                                                    <?= htmlspecialchars($row['customer_name']) ?><br>
                                                                                    <small class="text-muted"><?= $row['email'] ?></small>
                                                                                </td>
                                                                                <td>
                                                                                    <?php if ($row['order_type'] === 'sublimation'): ?>
                                                                                        <span class="badge badge-info">Sublimation</span>
                                                                                    <?php else: ?>
                                                                                        <span class="badge badge-secondary">Tailoring</span>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                                                                <td>
                                                                                    <?php
                                                                                        switch ($row['order_status']) {
                                                                                            case 'pending_approval':
                                                                                                echo '<span class="badge badge-warning">Pending Approval</span>';
                                                                                                break;
                                                                                            case 'approved':
                                                                                                echo '<span class="badge badge-success">Approved</span>';
                                                                                                break;
                                                                                            case 'rejected':
                                                                                                echo '<span class="badge badge-danger">Rejected</span>';
                                                                                                break;
                                                                                            case 'in_progress':
                                                                                                echo '<span class="badge badge-primary">In Progress</span>';
                                                                                                break;
                                                                                            case 'completed':
                                                                                                echo '<span class="badge badge-info">Completed</span>';
                                                                                                break;
                                                                                            case 'delivered':
                                                                                                echo '<span class="badge badge-success">Delivered</span>';
                                                                                                break;
                                                                                            case 'cancelled':
                                                                                                echo '<span class="badge badge-dark">Cancelled</span>';
                                                                                                break;
                                                                                            default:
                                                                                                echo '<span class="badge badge-secondary">Unknown</span>';
                                                                                        }
                                                                                    ?>
                                                                                </td>
                                                                                <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                                                                <td>
                                                                                    <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#orderModal<?= $row['order_id'] ?>">
                                                                                        <i class="fas fa-eye"></i> View
                                                                                    </button>
                                                                                    <?php if ($row['order_status'] === 'pending_approval'): ?>
                                                                                        <button class="btn btn-sm btn-success mt-1" data-toggle="modal" data-target="#approveModal<?= $row['order_id'] ?>">
                                                                                            <i class="fas fa-check"></i> Approve
                                                                                        </button>
                                                                                        <button class="btn btn-sm btn-danger mt-1" data-toggle="modal" data-target="#rejectModal<?= $row['order_id'] ?>">
                                                                                            <i class="fas fa-times"></i> Reject
                                                                                        </button>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endwhile; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="text-center py-4">
                                                                <h5>No orders found with the selected filters</h5>
                                                                <p class="text-muted">Try changing your filter criteria or check back later</p>
                                                                <a href="approve_orders.php" class="btn btn-outline-primary">Reset Filters</a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End of Main Content -->
                            
                                            <!-- Footer -->
                                            <footer class="footer text-center py-3">
                                                <span>Copyright &copy; JXT Tailoring and Printing Services</span>
                                            </footer>
                                            <!-- End of Footer -->
                                        </div>
                                        <!-- End of Content Wrapper -->
                                    </div>
                                </div>
                            
                            <?php
                            // Reset result pointer
                            if ($result && $result->num_rows > 0) {
                                $result->data_seek(0);
                            
                                // Create modals for each order
                                while ($row = $result->fetch_assoc()): 
                                    // Get order status history
                                    $history_query = "SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
                                                    FROM order_status_history h
                                                    LEFT JOIN users u ON h.changed_by = u.user_id
                                                    WHERE h.order_id = ?
                                                    ORDER BY h.changed_at DESC";
                                    $stmt = $conn->prepare($history_query);
                                    $stmt->bind_param("s", $row['order_id']);
                                    $stmt->execute();
                                    $history_result = $stmt->get_result();
                            ?>
                            
                            <!-- Order Details Modal -->
                            <div class="modal fade" id="orderModal<?= $row['order_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel<?= $row['order_id'] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="orderModalLabel<?= $row['order_id'] ?>">
                                                Order #<?= $row['order_id'] ?>
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="card mb-4">
                                                        <div class="card-header">
                                                            <h6 class="font-weight-bold mb-0">Order Information</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <table class="table table-sm">
                                                                <tr>
                                                                    <th>Order ID:</th>
                                                                    <td><?= $row['order_id'] ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Type:</th>
                                                                    <td>
                                                                        <?php if ($row['order_type'] === 'sublimation'): ?>
                                                                            <span class="badge badge-info">Sublimation</span>
                                                                        <?php else: ?>
                                                                            <span class="badge badge-secondary">Tailoring</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Date:</th>
                                                                    <td><?= date('F d, Y h:i A', strtotime($row['created_at'])) ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Status:</th>
                                                                    <td>
                                                                        <?php
                                                                            switch ($row['order_status']) {
                                                                                case 'pending_approval':
                                                                                    echo '<span class="badge badge-warning">Pending Approval</span>';
                                                                                    break;
                                                                                case 'approved':
                                                                                    echo '<span class="badge badge-success">Approved</span>';
                                                                                    break;
                                                                                case 'rejected':
                                                                                    echo '<span class="badge badge-danger">Rejected</span>';
                                                                                    break;
                                                                                case 'in_progress':
                                                                                    echo '<span class="badge badge-primary">In Progress</span>';
                                                                                    break;
                                                                                case 'completed':
                                                                                    echo '<span class="badge badge-info">Completed</span>';
                                                                                    break;
                                                                                case 'delivered':
                                                                                    echo '<span class="badge badge-success">Delivered</span>';
                                                                                    break;
                                                                                case 'cancelled':
                                                                                    echo '<span class="badge badge-dark">Cancelled</span>';
                                                                                    break;
                                                                                default:
                                                                                    echo '<span class="badge badge-secondary">Unknown</span>';
                                                                            }
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Payment:</th>
                                                                    <td>
                                                                        <?php if ($row['payment_status'] === 'paid'): ?>
                                                                            <span class="badge badge-success">Paid</span>
                                                                        <?php else: ?>
                                                                            <span class="badge badge-warning">Unpaid</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Amount:</th>
                                                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="card mb-4">
                                                        <div class="card-header">
                                                            <h6 class="font-weight-bold mb-0">Customer Information</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p><strong>Name:</strong> <?= htmlspecialchars($row['customer_name']) ?></p>
                                                            <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                                                            <p><strong>Phone:</strong> <?= htmlspecialchars($row['phone_number']) ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <!-- Status History -->
                                                    <div class="card mb-4">
                                                        <div class="card-header">
                                                            <h6 class="font-weight-bold mb-0">Order History</h6>
                                                        </div>
                                                        <div class="card-body p-0">
                                                            <div class="list-group list-group-flush">
                                                                <?php if ($history_result->num_rows > 0): ?>
                                                                    <?php while ($history = $history_result->fetch_assoc()): ?>
                                                                        <div class="list-group-item py-3">
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <strong>
                                                                                    <?php
                                                                                        switch ($history['status']) {
                                                                                            case 'pending_approval':
                                                                                                echo '<span class="badge badge-warning">Pending Approval</span>';
                                                                                                break;
                                                                                            case 'approved':
                                                                                                echo '<span class="badge badge-success">Approved</span>';
                                                                                                break;
                                                                                            case 'rejected':
                                                                                                echo '<span class="badge badge-danger">Rejected</span>';
                                                                                                break;
                                                                                            case 'in_progress':
                                                                                                echo '<span class="badge badge-primary">In Progress</span>';
                                                                                                break;
                                                                                            case 'completed':
                                                                                                echo '<span class="badge badge-info">Completed</span>';
                                                                                                break;
                                                                                            case 'delivered':
                                                                                                echo '<span class="badge badge-success">Delivered</span>';
                                                                                                break;
                                                                                            case 'cancelled':
                                                                                                echo '<span class="badge badge-dark">Cancelled</span>';
                                                                                                break;
                                                                                            default:
                                                                                                echo '<span class="badge badge-secondary">Unknown</span>';
                                                                                        }
                                                                                    ?>
                                                                                </strong>
                                                                                <small class="text-muted">
                                                                                    <?= date('M d, Y h:i A', strtotime($history['changed_at'])) ?>
                                                                                </small>
                                                                            </div>
                                                                            <?php if (!empty($history['notes'])): ?>
                                                                                <p class="mb-1 mt-2"><?= nl2br(htmlspecialchars($history['notes'])) ?></p>
                                                                            <?php endif; ?>
                                                                            <?php if (!empty($history['changed_by_name'])): ?>
                                                                                <small class="text-muted">By: <?= htmlspecialchars($history['changed_by_name']) ?></small>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    <?php endwhile; ?>
                                                                <?php else: ?>
                                                                    <div class="list-group-item py-3 text-center text-muted">
                                                                        No history available
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            <?php if ($row['order_status'] === 'pending_approval'): ?>
                                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#approveModal<?= $row['order_id'] ?>" data-dismiss="modal">
                                                    <i class="fas fa-check"></i> Approve Order
                                                </button>
                                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal<?= $row['order_id'] ?>" data-dismiss="modal">
                                                    <i class="fas fa-times"></i> Reject Order
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Approve Order Modal -->
                            <?php if ($row['order_status'] === 'pending_approval'): ?>
                            <div class="modal fade" id="approveModal<?= $row['order_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel<?= $row['order_id'] ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title" id="approveModalLabel<?= $row['order_id'] ?>">
                                                Approve Order #<?= $row['order_id'] ?>
                                            </h5>
                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form method="POST" action="approve_orders.php">
                                            <div class="modal-body">
                                                <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                                <input type="hidden" name="status" value="approved">
                                                
                                                <div class="text-center mb-4">
                                                    <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                                                    <h5>Are you sure you want to approve this order?</h5>
                                                    <p class="text-muted">This will notify the customer that their order has been approved and will be processed.</p>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="notes<?= $row['order_id'] ?>">Notes (Optional)</label>
                                                    <textarea class="form-control" id="notes<?= $row['order_id'] ?>" name="notes" rows="3" placeholder="Add any additional notes or instructions..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-check"></i> Approve Order
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Reject Order Modal -->
                            <div class="modal fade" id="rejectModal<?= $row['order_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel<?= $row['order_id'] ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title" id="rejectModalLabel<?= $row['order_id'] ?>">
                                                Reject Order #<?= $row['order_id'] ?>
                                            </h5>
                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form method="POST" action="approve_orders.php">
                                            <div class="modal-body">
                                                <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                
                                                <div class="text-center mb-4">
                                                    <i class="fas fa-times-circle text-danger fa-4x mb-3"></i>
                                                    <h5>Are you sure you want to reject this order?</h5>
                                                    <p class="text-muted">The customer will be notified that their order has been rejected.</p>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="reject_notes<?= $row['order_id'] ?>">Reason for Rejection</label>
                                                    <textarea class="form-control" id="reject_notes<?= $row['order_id'] ?>" name="notes" rows="3" placeholder="Please provide a reason for rejecting this order..." required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-times"></i> Reject Order
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php endwhile; ?>
                            <?php } ?>
                            
                            <!-- Bootstrap core JavaScript-->
                            <script src="vendor/jquery/jquery.min.js"></script>
                            <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
                            
                            <!-- Core plugin JavaScript-->
                            <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
                            
                            <!-- Custom scripts for all pages-->
                            <script src="js/sb-admin-2.min.js"></script>
                            
                            <!-- Page level plugins -->
                            <script src="vendor/datatables/jquery.dataTables.min.js"></script>
                            <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
                            
                            <script>
                                $(document).ready(function() {
                                    // Initialize DataTable
                                    $('#ordersTable').DataTable({
                                        order: [[3, 'desc']], // Sort by date column descending
                                        pageLength: 10,
                                        language: {
                                            search: "Search orders:"
                                        }
                                    });
                                });
                            </script>
                            
                            </body>
                            </html>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <!-- Status History -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="font-weight-bold mb-0">Order History</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if ($history_result->num_rows > 0): ?>
                                        <?php while ($history = $history_result->fetch_assoc()): ?>
                                            <div class="list-group-item py-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <strong>
                                                        <?php
                                                            switch ($history['status']) {
                                                                case 'pending_approval':
                                                                    echo '<span class="badge badge-warning">Pending Approval</span>';
                                                                    break;
                                                                case 'approved':
                                                                    echo '<span class="badge badge-success">Approved</span>';
                                                                    break;
                                                                case 'rejected':
                                                                    echo '<span class="badge badge-danger">Rejected</span>';
                                                                    break;
                                                                case 'in_progress':
                                                                    echo '<span class="badge badge-primary">In Progress</span>';
                                                                    break;
                                                                case 'completed':
                                                                    echo '<span class="badge badge-info">Completed</span>';
                                                                    break;
                                                                case 'delivered':
                                                                    echo '<span class="badge badge-success">Delivered</span>';
                                                                    break;
                                                                case 'cancelled':
                                                                    echo '<span class="badge badge-dark">Cancelled</span>';
                                                                    break;
                                                                default:
                                                                    echo '<span class="badge badge-secondary">Unknown</span>';
                                                            }
                                                        ?>
                                                    </strong>
                                                    <small class="text-muted">
                                                        <?= date('M d, Y h:i A', strtotime($history['changed_at'])) ?>
                                                    </small>
                                                </div>
                                                <?php if (!empty($history['notes'])): ?>
                                                    <p class="mb-1 mt-2"><?= nl2br(htmlspecialchars($history['notes'])) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($history['changed_by_name'])): ?>
                                                    <small class="text-muted">By: <?= htmlspecialchars($history['changed_by_name']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="list-group-item py-3 text-center text-muted">
                                            No history available
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <?php if ($row['order_status'] === 'pending_approval'): ?>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#approveModal<?= $row['order_id'] ?>" data-dismiss="modal">
                        <i class="fas fa-check"></i> Approve Order
                    </button>
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal<?= $row['order_id'] ?>" data-dismiss="modal">
                        <i class="fas fa-times"></i> Reject Order
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Approve Order Modal -->
<?php if ($row['order_status'] === 'pending_approval'): ?>
<div class="modal fade" id="approveModal<?= $row['order_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel<?= $row['order_id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approveModalLabel<?= $row['order_id'] ?>">
                    Approve Order #<?= $row['order_id'] ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="approve_orders.php">
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                    <input type="hidden" name="status" value="approved">
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                        <h5>Are you sure you want to approve this order?</h5>
                        <p class="text-muted">This will notify the customer that their order has been approved and will be processed.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes<?= $row['order_id'] ?>">Notes (Optional)</label>
                        <textarea class="form-control" id="notes<?= $row['order_id'] ?>" name="notes" rows="3" placeholder="Add any additional notes or instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Order Modal -->
<div class="modal fade" id="rejectModal<?= $row['order_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel<?= $row['order_id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectModalLabel<?= $row['order_id'] ?>">
                    Reject Order #<?= $row['order_id'] ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="approve_orders.php">
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                    <input type="hidden" name="status" value="rejected">
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-times-circle text-danger fa-4x mb-3"></i>
                        <h5>Are you sure you want to reject this order?</h5>
                        <p class="text-muted">The customer will be notified that their order has been rejected.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="reject_notes<?= $row['order_id'] ?>">Reason for Rejection</label>
                        <textarea class="form-control" id="reject_notes<?= $row['order_id'] ?>" name="notes" rows="3" placeholder="Please provide a reason for rejecting this order..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endwhile; ?>
<?php } ?>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#ordersTable').DataTable({
            order: [[3, 'desc']], // Sort by date column descending
            pageLength: 10,
            language: {
                search: "Search orders:"
            }
        });
    });
</script>

<?php
// Include footer
include 'footer.php';
?>