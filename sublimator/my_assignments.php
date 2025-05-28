<?php
session_start();
include_once '../db.php';

// If the user is not logged in or not a sublimator, redirect to index.php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Sublimator') {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

$sublimator_id = $_SESSION['user_id'];

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'sublimator_errors.log');

// Get assigned orders
$assigned_orders_query = "SELECT o.order_id, o.order_status, o.total_amount, o.created_at, 
                         o.payment_status, c.first_name, c.last_name, c.phone_number,
                         so.template_id, so.completion_date, 
                         t.name AS template_name, t.image_path AS template_image
                         FROM orders o
                         JOIN customers c ON o.customer_id = c.customer_id
                         JOIN sublimation_orders so ON o.order_id = so.order_id
                         LEFT JOIN templates t ON so.template_id = t.template_id
                         WHERE so.sublimator_id = ? 
                         AND o.order_status IN ('forward_to_sublimator', 'in_process', 'printing_done', 'ready_for_pickup')
                         ORDER BY 
                            CASE 
                                WHEN o.order_status = 'forward_to_sublimator' THEN 1
                                WHEN o.order_status = 'in_process' THEN 2
                                WHEN o.order_status = 'printing_done' THEN 3
                                WHEN o.order_status = 'ready_for_pickup' THEN 4
                                ELSE 5
                            END,
                            so.completion_date ASC";

$stmt = mysqli_prepare($conn, $assigned_orders_query);
mysqli_stmt_bind_param($stmt, "i", $sublimator_id);
mysqli_stmt_execute($stmt);
$assigned_orders_result = mysqli_stmt_get_result($stmt);

// Get counts for each status
$status_counts = [
    'forward_to_sublimator' => 0,
    'in_process' => 0,
    'printing_done' => 0,
    'ready_for_pickup' => 0,
    'completed' => 0
];

$count_query = "SELECT order_status, COUNT(*) as count 
               FROM orders o
               JOIN sublimation_orders so ON o.order_id = so.order_id
               WHERE so.sublimator_id = ?
               GROUP BY order_status";

$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $sublimator_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);

while ($row = mysqli_fetch_assoc($count_result)) {
    if (array_key_exists($row['order_status'], $status_counts)) {
        $status_counts[$row['order_status']] = $row['count'];
    }
}

// Get any unread notifications
$notifications_query = "SELECT COUNT(*) as unread 
                      FROM staff_notifications 
                      WHERE staff_id = ? AND is_read = 0";
$notifications_stmt = mysqli_prepare($conn, $notifications_query);
mysqli_stmt_bind_param($notifications_stmt, "i", $sublimator_id);
mysqli_stmt_execute($notifications_stmt);
$notifications_result = mysqli_stmt_get_result($notifications_stmt);
$unread_notifications = mysqli_fetch_assoc($notifications_result)['unread'] ?? 0;

// Mark notifications as read
if ($unread_notifications > 0) {
    $update_query = "UPDATE staff_notifications 
                   SET is_read = 1 
                   WHERE staff_id = ? AND is_read = 0";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "i", $sublimator_id);
    mysqli_stmt_execute($update_stmt);
}
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
    <title>JXT Sublimator - My Assignments</title>

    <!-- Custom fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .order-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
            margin-bottom: 1rem;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        
        .order-forwarded {
            border-left-color: #f6c23e;
        }
        
        .order-in-process {
            border-left-color: #4e73df;
        }
        
        .order-printing-done {
            border-left-color: #36b9cc;
        }
        
        .order-ready {
            border-left-color: #1cc88a;
        }
        
        .status-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-forwarded {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-in-process {
            background-color: #e1f5fe;
            color: #0277bd;
        }
        
        .status-printing-done {
            background-color: #e0f7fa;
            color: #006064;
        }
        
        .status-ready {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .template-image {
            max-width: 100px;
            height: auto;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .nav-pills .nav-link.active {
            background-color: #D98324;
        }
        
        .btn-primary {
            background-color: #D98324;
            border-color: #D98324;
        }
        
        .btn-primary:hover {
            background-color: #c57420;
            border-color: #c57420;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7rem;
            background-color: #e74a3b;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 0;
        }
        
        .empty-state img {
            max-width: 200px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'topbar.php'; ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">My Assignments</h1>
                    </div>

                    <?php if ($unread_notifications > 0): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <strong>You have <?php echo $unread_notifications; ?> new assignment(s)!</strong> Check your assigned orders below.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Nav tabs -->
                    <ul class="nav nav-pills mb-4">
                        <li class="nav-item">
                            <a class="nav-link active" href="#all" data-toggle="tab">
                                All <span class="badge bg-secondary text-white"><?php echo mysqli_num_rows($assigned_orders_result); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#forwarded" data-toggle="tab">
                                Forwarded <span class="badge bg-secondary text-white"><?php echo $status_counts['forward_to_sublimator']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#in-process" data-toggle="tab">
                                In Process <span class="badge bg-secondary text-white"><?php echo $status_counts['in_process']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#printing-done" data-toggle="tab">
                                Printing Done <span class="badge bg-secondary text-white"><?php echo $status_counts['printing_done']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#ready" data-toggle="tab">
                                Ready <span class="badge bg-secondary text-white"><?php echo $status_counts['ready_for_pickup']; ?></span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="all">
                            <?php 
                            if (mysqli_num_rows($assigned_orders_result) > 0) {
                                // Reset result pointer
                                mysqli_data_seek($assigned_orders_result, 0);
                                displayOrders($assigned_orders_result, 'all');
                            } else {
                                displayEmptyState("You don't have any assigned orders yet.");
                            }
                            ?>
                        </div>
                        <div class="tab-pane fade" id="forwarded">
                            <?php 
                            if ($status_counts['forward_to_sublimator'] > 0) {
                                // Reset result pointer
                                mysqli_data_seek($assigned_orders_result, 0);
                                displayOrders($assigned_orders_result, 'forward_to_sublimator');
                            } else {
                                displayEmptyState("No orders forwarded to you at the moment.");
                            }
                            ?>
                        </div>
                        <div class="tab-pane fade" id="in-process">
                            <?php 
                            if ($status_counts['in_process'] > 0) {
                                // Reset result pointer
                                mysqli_data_seek($assigned_orders_result, 0);
                                displayOrders($assigned_orders_result, 'in_process');
                            } else {
                                displayEmptyState("No orders in process at the moment.");
                            }
                            ?>
                        </div>
                        <div class="tab-pane fade" id="printing-done">
                            <?php 
                            if ($status_counts['printing_done'] > 0) {
                                // Reset result pointer
                                mysqli_data_seek($assigned_orders_result, 0);
                                displayOrders($assigned_orders_result, 'printing_done');
                            } else {
                                displayEmptyState("No orders with printing done at the moment.");
                            }
                            ?>
                        </div>
                        <div class="tab-pane fade" id="ready">
                            <?php 
                            if ($status_counts['ready_for_pickup'] > 0) {
                                // Reset result pointer
                                mysqli_data_seek($assigned_orders_result, 0);
                                displayOrders($assigned_orders_result, 'ready_for_pickup');
                            } else {
                                displayEmptyState("No orders ready for pickup at the moment.");
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Function to display orders
    function displayOrders($result, $status_filter) {
        echo '<div class="row">';
        while ($order = mysqli_fetch_assoc($result)) {
            // Skip if we're filtering by status and this order doesn't match
            if ($status_filter !== 'all' && $order['order_status'] !== $status_filter) {
                continue;
            }
            
            // Determine card and status classes
            $card_class = '';
            $status_class = '';
            $status_text = '';
            
            switch ($order['order_status']) {
                case 'forward_to_sublimator':
                    $card_class = 'order-forwarded';
                    $status_class = 'status-forwarded';
                    $status_text = 'Forwarded';
                    break;
                case 'in_process':
                    $card_class = 'order-in-process';
                    $status_class = 'status-in-process';
                    $status_text = 'In Process';
                    break;
                case 'printing_done':
                    $card_class = 'order-printing-done';
                    $status_class = 'status-printing-done';
                    $status_text = 'Printing Done';
                    break;
                case 'ready_for_pickup':
                    $card_class = 'order-ready';
                    $status_class = 'status-ready';
                    $status_text = 'Ready';
                    break;
            }
            
            // Format dates
            $created_date = date('M d, Y', strtotime($order['created_at']));
            $completion_date = $order['completion_date'] ? date('M d, Y', strtotime($order['completion_date'])) : 'Not set';
            
            // Display the order card
            echo '<div class="col-xl-6 col-lg-12 mb-4">';
            echo '<div class="card shadow ' . $card_class . '">';
            echo '<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">';
            echo '<h6 class="m-0 font-weight-bold text-primary">Order #' . htmlspecialchars($order['order_id']) . '</h6>';
            echo '<span class="status-badge ' . $status_class . '">' . $status_text . '</span>';
            echo '</div>';
            echo '<div class="card-body">';
            echo '<div class="row">';
            
            // Left column with template image
            echo '<div class="col-md-4 text-center mb-3 mb-md-0">';
            if ($order['template_image']) {
                echo '<img src="../' . htmlspecialchars($order['template_image']) . '" class="template-image" alt="Template">';
                echo '<p class="mt-2 mb-0 small">' . htmlspecialchars($order['template_name'] ?: 'Custom Design') . '</p>';
            } else {
                echo '<div class="p-3 bg-light rounded"><i class="fas fa-tshirt fa-3x text-gray-300"></i></div>';
                echo '<p class="mt-2 mb-0 small">Custom Design</p>';
            }
            echo '</div>';
            
            // Right column with order details
            echo '<div class="col-md-8">';
            echo '<div class="row mb-2">';
            echo '<div class="col-sm-4 text-sm font-weight-bold">Customer:</div>';
            echo '<div class="col-sm-8 text-sm">' . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . '</div>';
            echo '</div>';
            
            echo '<div class="row mb-2">';
            echo '<div class="col-sm-4 text-sm font-weight-bold">Phone:</div>';
            echo '<div class="col-sm-8 text-sm">' . htmlspecialchars($order['phone_number']) . '</div>';
            echo '</div>';
            
            echo '<div class="row mb-2">';
            echo '<div class="col-sm-4 text-sm font-weight-bold">Total:</div>';
            echo '<div class="col-sm-8 text-sm">₱' . number_format($order['total_amount'], 2) . '</div>';
            echo '</div>';
            
            echo '<div class="row mb-2">';
            echo '<div class="col-sm-4 text-sm font-weight-bold">Created:</div>';
            echo '<div class="col-sm-8 text-sm">' . $created_date . '</div>';
            echo '</div>';
            
            echo '<div class="row mb-3">';
            echo '<div class="col-sm-4 text-sm font-weight-bold">Due Date:</div>';
            echo '<div class="col-sm-8 text-sm">' . $completion_date . '</div>';
            echo '</div>';
            
            // Action buttons based on status
            echo '<div class="d-flex justify-content-end">';
            echo '<a href="view_order.php?id=' . $order['order_id'] . '" class="btn btn-sm btn-info mr-2"><i class="fas fa-eye"></i> View Details</a>';
            
            if ($order['order_status'] == 'forward_to_sublimator') {
                echo '<button class="btn btn-sm btn-primary" onclick="updateStatus(\'' . $order['order_id'] . '\', \'in_process\')"><i class="fas fa-play"></i> Start Process</button>';
            } else if ($order['order_status'] == 'in_process') {
                echo '<button class="btn btn-sm btn-success" onclick="updateStatus(\'' . $order['order_id'] . '\', \'printing_done\')"><i class="fas fa-check"></i> Mark Printing Done</button>';
            } else if ($order['order_status'] == 'printing_done') {
                echo '<button class="btn btn-sm btn-success" onclick="updateStatus(\'' . $order['order_id'] . '\', \'ready_for_pickup\')"><i class="fas fa-box"></i> Mark Ready for Pickup</button>';
            }
            
            echo '</div>'; // End of action buttons
            echo '</div>'; // End of right column
            echo '</div>'; // End of row
            echo '</div>'; // End of card body
            echo '</div>'; // End of card
            echo '</div>'; // End of column
        }
        echo '</div>'; // End of row
    }
    
    // Function to display empty state
    function displayEmptyState($message) {
        echo '<div class="empty-state">';
        echo '<img src="../image/no-data.svg" alt="No Orders">';
        echo '<h5>No Orders Found</h5>';
        echo '<p class="text-muted">' . $message . '</p>';
        echo '</div>';
    }
    ?>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function updateStatus(orderId, newStatus) {
            let statusText = '';
            switch(newStatus) {
                case 'in_process':
                    statusText = 'In Process';
                    break;
                case 'printing_done':
                    statusText = 'Printing Done';
                    break;
                case 'ready_for_pickup':
                    statusText = 'Ready for Pickup';
                    break;
            }
            
            Swal.fire({
                title: 'Update Status',
                text: `Are you sure you want to mark this order as "${statusText}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#D98324',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Updating...',
                        text: 'Please wait while we update the order status.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Send AJAX request
                    $.ajax({
                        url: 'update_sublimation_status.php',
                        type: 'POST',
                        data: {
                            order_id: orderId,
                            new_status: newStatus
                        },
                        success: function(response) {
                            try {
                                const data = typeof response === 'string' ? JSON.parse(response) : response;
                                
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: data.message || 'Order status updated successfully.',
                                        icon: 'success',
                                        confirmButtonColor: '#D98324'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: data.message || 'Failed to update order status.',
                                        icon: 'error',
                                        confirmButtonColor: '#D98324'
                                    });
                                }
                            } catch (e) {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An unexpected error occurred. Please try again.',
                                    icon: 'error',
                                    confirmButtonColor: '#D98324'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to connect to the server. Please try again.',
                                icon: 'error',
                                confirmButtonColor: '#D98324'
                            });
                        }
                    });
                }
            });
        }
        
        $(document).ready(function() {
            // Handle tab changes via URL hash
            let hash = window.location.hash;
            if (hash) {
                $('.nav-pills a[href="' + hash + '"]').tab('show');
            }
            
            // Update hash when tab changes
            $('.nav-pills a').on('click', function(e) {
                window.location.hash = $(this).attr('href');
            });
        });
    </script>
</body>
</html> 