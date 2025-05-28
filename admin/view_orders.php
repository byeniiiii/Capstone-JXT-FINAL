<?php
include '../db.php';
session_start();

// If the user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get the order ID from the URL
$order_id = isset($_GET['id']) ? $_GET['id'] : '';

// Validate that order ID is provided
if (empty($order_id)) {
    echo "No order specified.";
    exit;
}

// Query to get order details
$order_query = "SELECT o.*,
                c.first_name, c.last_name, c.email, c.phone_number,
                c.address
                FROM orders o
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE o.order_id = ?";

$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "s", $order_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($order_result) == 0) {
    echo "Order not found.";
    exit;
}

$order = mysqli_fetch_assoc($order_result);

// Get specific order details based on order type
$specific_details = [];
if ($order['order_type'] == 'tailoring') {
    $tailoring_query = "SELECT * FROM tailoring_orders WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $tailoring_query);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $specific_details = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
} else if ($order['order_type'] == 'sublimation') {
    $sublimation_query = "SELECT * FROM sublimation_orders WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $sublimation_query);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $specific_details = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// Get order items if applicable (depends on your database structure)
$items_query = "";
$items_result = false;

// For tailoring orders
if ($order['order_type'] == 'tailoring') {
    // Instead of querying a separate items table, get data from the main order table
    // or from the tailoring_orders table that we already have
    if ($specific_details) {
        // Create a single-row result using the data we already have
        $item = [
            'description' => $specific_details['garment_type'] ?? 'Tailoring Service',
            'quantity' => $specific_details['quantity'] ?? 1,
            'price' => $order['total_amount'],
            'item_total' => $order['total_amount']
        ];
        
        // Store the item data for later use in the table
        $items_data = [$item];
        $items_result = true;
    }
}
// For sublimation orders 
else if ($order['order_type'] == 'sublimation') {
    // Instead of querying a separate items table, get data from the main order table
    // or from the sublimation_orders table that we already have
    if ($specific_details) {
        // Create a single-row result using the data we already have
        $item = [
            'description' => $specific_details['printing_type'] ?? 'Sublimation Printing',
            'quantity' => $specific_details['quantity'] ?? 1,
            'price' => $order['total_amount'] / ($specific_details['quantity'] ?? 1),
            'item_total' => $order['total_amount']
        ];
        
        // Store the item data for later use in the table
        $items_data = [$item];
        $items_result = true;
    }
}

// Format status for display
$formatted_status = str_replace('_', ' ', ucwords($order['order_status']));

// Determine status badge class
$status_class = '';
switch(strtolower($order['order_status'])) {
    case 'pending_approval':
        $status_class = 'status-pending';
        break;
    case 'declined':
        $status_class = 'status-declined';
        break;
    case 'approved':
        $status_class = 'status-approved';
        break;
    case 'in_process':
        $status_class = 'status-in-progress';
        break;
    case 'ready_for_pickup':
        $status_class = 'status-ready';
        break;
    case 'completed':
        $status_class = 'status-completed';
        break;
    default:
        $status_class = 'status-pending';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="../image/logo.png">
    <title>Order Details - JXT Admin</title>

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
        
        /* Order details card styling */
        .detail-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            background-color: #D98324;
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .info-group {
            margin-bottom: 20px;
        }
        
        .info-group:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 1rem;
            color: #343a40;
            font-weight: 500;
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
        
        .status-pending-payment {
            background-color: #cfe2ff;
            color: #0a58ca;
        }
        
        .status-in-progress {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-completed {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-declined {
            background-color: #f8d7da;
            color: #842029;
        }
        
        /* Button styling */
        .btn-primary {
            background-color: #D98324;
            border-color: #D98324;
        }
        
        .btn-primary:hover {
            background-color: #c27420;
            border-color: #c27420;
        }
        
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
        }
        
        .table-sm th {
            font-weight: 600;
            color: #343a40;
        }
        
        .amount-row {
            font-weight: 600;
        }
        
        .total-row {
            font-weight: 700;
            font-size: 1.1rem;
            color: #D98324;
        }
        
        .footer {
            width: 100%;
            background-color: #443627 !important;
            color: #EFDCAB !important;
            text-align: center;
            padding: 10px 0;
        }
        
        /* Timeline styling */
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            height: 100%;
            width: 2px;
            background-color: #dee2e6;
            left: 6px;
            top: 0;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 25px;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: #D98324;
            left: -30px;
            top: 5px;
        }
        
        .timeline-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .timeline-content {
            padding: 5px 0;
        }
        
        .timeline-title {
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .timeline-text {
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .order-summary {
                order: -1;
            }
        }

        /* Add to your existing styles */
        .progress-tracker {
            margin: 15px 0 30px;
        }

        .status-steps {
            position: relative;
            z-index: 1;
        }

        .step {
            position: relative;
        }

        .step-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #adb5bd;
        }

        .step.active .step-icon {
            color: #28a745;
        }

        .step-label {
            font-size: 0.75rem;
            color: #6c757d;
            font-weight: 600;
        }

        .step.active .step-label {
            color: #212529;
        }

        .status-ready {
            background-color: #e0f7fa;
            color: #0288d1;
        }

        .status-approved {
            background-color: #e8f5e9;
            color: #2e7d32;
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
                        <?php include 'notification.php'; ?>

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
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Order Details</h1>
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Orders
                        </a>
                    </div>

                    <!-- Order Progress Tracker -->
                    <div class="detail-card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-tasks mr-2"></i> Order Progress</h5>
                        </div>
                        <div class="card-body">
                            <div class="progress-tracker">
                                <div class="row status-steps">
                                    <?php
                                    // Define all possible statuses in order
                                    $all_statuses = [
                                        'pending_approval' => ['icon' => 'clock', 'label' => 'Pending Approval'],
                                        'approved' => ['icon' => 'check-circle', 'label' => 'Approved'],
                                        'in_process' => ['icon' => 'cog fa-spin', 'label' => 'In Process'],
                                        'ready_for_pickup' => ['icon' => 'box', 'label' => 'Ready for Pickup'],
                                        'completed' => ['icon' => 'flag-checkered', 'label' => 'Completed'],
                                        'declined' => ['icon' => 'times-circle', 'label' => 'Declined']
                                    ];
                                    
                                    // Skip declined status for normal flow
                                    $display_statuses = array_filter($all_statuses, function($key) {
                                        return $key !== 'declined';
                                    }, ARRAY_FILTER_USE_KEY);
                                    
                                    // If order is declined, show only that status
                                    $current_status = strtolower($order['order_status']);
                                    if ($current_status === 'declined') {
                                        $display_statuses = ['declined' => $all_statuses['declined']];
                                    }
                                    
                                    $col_class = count($display_statuses) <= 4 ? 12 / count($display_statuses) : 3;
                                    
                                    foreach ($display_statuses as $status_key => $status_info) {
                                        $is_active = ($current_status === $status_key);
                                        $is_completed = false;
                                        
                                        // Determine if this step is completed (any step before current)
                                        if ($current_status !== 'declined') {
                                            $status_order = array_keys($all_statuses);
                                            $current_index = array_search($current_status, $status_order);
                                            $status_index = array_search($status_key, $status_order);
                                            $is_completed = ($status_index < $current_index);
                                        }
                                        
                                        $step_class = $is_active ? 'active' : ($is_completed ? 'completed' : '');
                                    ?>
                                    <div class="col-md-<?= $col_class ?> mb-4">
                                        <div class="step text-center <?= $step_class ?>">
                                            <div class="step-icon">
                                                <i class="fas fa-<?= $status_info['icon'] ?>"></i>
                                            </div>
                                            <div class="step-label"><?= $status_info['label'] ?></div>
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Order Summary Column -->
                        <div class="col-lg-4 order-summary">
                            <div class="detail-card">
                                <div class="card-header">
                                    <h5><i class="fas fa-info-circle mr-2"></i> Order Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="info-group">
                                        <div class="info-label">Order ID</div>
                                        <div class="info-value">#<?= htmlspecialchars($order['order_id']) ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Order Date</div>
                                        <div class="info-value"><?= date('F j, Y', strtotime($order['created_at'])) ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Service Type</div>
                                        <div class="info-value"><?= htmlspecialchars(ucfirst($order['order_type'])) ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Status</div>
                                        <div class="info-value">
                                            <span class="status-badge <?= $status_class ?>">
                                                <?= htmlspecialchars($formatted_status) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Total Amount</div>
                                        <div class="info-value">₱<?= number_format($order['total_amount'], 2) ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Downpayment</div>
                                        <div class="info-value">₱<?= number_format($order['downpayment_amount'], 2) ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Payment Status</div>
                                        <div class="info-value">
                                            <?php if ($order['payment_status'] == 'paid'): ?>
                                                <span class="badge bg-success text-white">Paid</span>
                                            <?php elseif ($order['payment_status'] == 'partial'): ?>
                                                <span class="badge bg-warning text-dark">Partial</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger text-white">Unpaid</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-card">
                                <div class="card-header">
                                    <h5><i class="fas fa-user mr-2"></i> Customer Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="info-group">
                                        <div class="info-label">Name</div>
                                        <div class="info-value">
                                            <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                                        </div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Email</div>
                                        <div class="info-value"><?= htmlspecialchars($order['email']) ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Phone</div>
                                        <div class="info-value"><?= htmlspecialchars($order['phone_number'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Address</div>
                                        <div class="info-value">
                                            <?= htmlspecialchars($order['address'] ?? 'N/A') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Details Column -->
                        <div class="col-lg-8">
                            <div class="detail-card">
                                <div class="card-header">
                                    <h5><i class="fas fa-clipboard-list mr-2"></i> Order Details</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($order['order_type'] == 'tailoring'): ?>
                                    <!-- Tailoring specific details -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <div class="info-label">Service Type</div>
                                                <div class="info-value">
                                                    <?= isset($specific_details['service_type']) ? htmlspecialchars($specific_details['service_type']) : 'N/A' ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <div class="info-label">Fabric Type</div>
                                                <div class="info-value">
                                                    <?= isset($specific_details['fabric_type']) ? htmlspecialchars($specific_details['fabric_type']) : 'N/A' ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Measurements could be displayed here -->

                                    <?php elseif ($order['order_type'] == 'sublimation'): ?>
                                    <!-- Sublimation specific details -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <div class="info-label">Design Option</div>
                                                <div class="info-value">
                                                    <?= isset($specific_details['custom_design']) && $specific_details['custom_design'] == 1 ? 'Custom Design' : 'Template' ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Order Items Table -->
                                    <h6 class="font-weight-bold mb-3">Order Items</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Item Description</th>
                                                    <th>Quantity</th>
                                                    <th class="text-end">Price</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $item_count = 1;
                                                $subtotal = 0;
                                                
                                                if ($items_result):
                                                    foreach ($items_data as $item): 
                                                        $item_total = $item['quantity'] * $item['price'];
                                                        $subtotal += $item_total;
                                                ?>
                                                <tr>
                                                    <td><?= $item_count++ ?></td>
                                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                                    <td><?= $item['quantity'] ?></td>
                                                    <td class="text-end">₱<?= number_format($item['price'], 2) ?></td>
                                                    <td class="text-end">₱<?= number_format($item_total, 2) ?></td>
                                                </tr>
                                                <?php 
                                                    endforeach; 
                                                else:
                                                ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No items found for this order</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="amount-row">
                                                    <td colspan="4" class="text-end">Subtotal:</td>
                                                    <td class="text-end">₱<?= number_format($subtotal > 0 ? $subtotal : $order['total_amount'], 2) ?></td>
                                                </tr>
                                                <?php if(isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                                <tr class="amount-row">
                                                    <td colspan="4" class="text-end">Discount:</td>
                                                    <td class="text-end">-₱<?= number_format($order['discount_amount'], 2) ?></td>
                                                </tr>
                                                <?php endif; ?>
                                                <tr class="total-row">
                                                    <td colspan="4" class="text-end">Total:</td>
                                                    <td class="text-end">₱<?= number_format($order['total_amount'], 2) ?></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    
                                    <!-- Additional Notes -->
                                    <?php if (isset($order['notes']) && !empty($order['notes'])): ?>
                                    <div class="mt-4">
                                        <h6 class="font-weight-bold">Notes</h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <!-- If order was declined, show reason -->
                                    <?php if ($order['order_status'] == 'declined' && isset($order['decline_reason'])): ?>
                                    <div class="mt-4">
                                        <h6 class="font-weight-bold text-danger">Decline Reason</h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($order['decline_reason'])) ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Order Timeline -->
                            <div class="detail-card">
                                <div class="card-header">
                                    <h5><i class="fas fa-history mr-2"></i> Order Timeline</h5>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <div class="timeline-item">
                                            <div class="timeline-date"><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></div>
                                            <div class="timeline-content">
                                                <div class="timeline-title">Order Placed</div>
                                                <div class="timeline-text">Customer submitted the order</div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($order['order_status'] != 'pending_approval'): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-date">
                                                <?= isset($order['updated_at']) ? date('M d, Y h:i A', strtotime($order['updated_at'])) : date('M d, Y h:i A', strtotime('+1 day', strtotime($order['created_at']))) ?>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="timeline-title">
                                                    <?php if ($order['order_status'] == 'declined'): ?>
                                                        Order Declined
                                                    <?php else: ?>
                                                        Order Approved
                                                    <?php endif; ?>
                                                </div>
                                                <div class="timeline-text">
                                                    <?php if ($order['order_status'] == 'declined'): ?>
                                                        Staff declined the order
                                                    <?php else: ?>
                                                        Staff approved the order
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($order['order_status'], ['in_progress', 'completed'])): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-date">
                                                <?= isset($order['start_date']) ? date('M d, Y h:i A', strtotime($order['start_date'])) : date('M d, Y h:i A', strtotime('+3 days', strtotime($order['created_at']))) ?>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="timeline-title">Production Started</div>
                                                <div class="timeline-text">Order is now in production</div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['order_status'] == 'completed'): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-date">
                                                <?= isset($order['completion_date']) ? date('M d, Y h:i A', strtotime($order['completion_date'])) : date('M d, Y h:i A', strtotime('+7 days', strtotime($order['created_at']))) ?>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="timeline-title">Order Completed</div>
                                                <div class="timeline-text">Order has been completed and is ready for pickup/delivery</div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <?php include 'footer.php'; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>