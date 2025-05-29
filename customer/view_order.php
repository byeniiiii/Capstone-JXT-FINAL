<?php
include '../db.php';
session_start();

// If the customer is not logged in, redirect to login page
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order ID is specified
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Option 1: Redirect to orders list
    header("Location: track_order.php");
    exit();
    
    // OR Option 2: Show error message with link to go back
    /*
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Not Found - JXT</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="icon" type="image/png" href="../image/logo.png">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-body text-center p-5">
                            <img src="../image/error.png" alt="Error" class="img-fluid mb-4" style="max-width: 150px;">
                            <h2 class="text-danger mb-4">No Order Specified</h2>
                            <p class="lead mb-4">Please select an order to view its details.</p>
                            <a href="my_orders.php" class="btn btn-primary">View My Orders</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    */
    exit;
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
    <title>Order Details - JXT Tailoring</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 70px;
        }

        .navbar {
            background-color: #443627 !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 10px 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: #EFDCAB !important;
        }

        .navbar-dark .navbar-nav .nav-link {
            color: #EFDCAB !important;
            font-weight: 500;
            padding: 8px 15px;
            transition: all 0.3s;
        }

        .navbar-dark .navbar-nav .nav-link:hover,
        .navbar-dark .navbar-nav .nav-link.active {
            color: #D98324 !important;
        }

        .navbar-dark .navbar-toggler {
            border-color: #EFDCAB;
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
        
        .status-ready {
            background-color: #e0f7fa;
            color: #0288d1;
        }
        
        .status-approved {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        /* Progress tracker styling */
        .progress-tracker {
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 10px;
        }
        
        .progress-step {
            text-align: center;
            position: relative;
        }
        
        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 60%;
            width: 80%;
            height: 2px;
            background-color: #e9ecef;
            z-index: 1;
        }
        
        .progress-step.active:not(:last-child)::after,
        .progress-step.completed:not(:last-child)::after {
            background-color: #D98324;
        }
        
        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 5px;
            z-index: 2;
            position: relative;
        }
        
        .step-icon i {
            font-size: 12px;
            color: #6c757d;
        }
        
        .progress-step.active .step-icon,
        .progress-step.completed .step-icon {
            background-color: #D98324;
        }
        
        .progress-step.active .step-icon i,
        .progress-step.completed .step-icon i {
            color: white;
        }
        
        .progress-step.declined .step-icon {
            background-color: #dc3545;
        }
        
        .progress-step.declined .step-icon i {
            color: white;
        }
        
        .step-label {
            font-size: 11px;
            font-weight: 600;
            color: #6c757d;
        }
        
        .progress-step.active .step-label,
        .progress-step.completed .step-label {
            color: #D98324;
        }
        
        .progress-step.declined .step-label {
            color: #dc3545;
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
                        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="track_order.php"><i class="fas fa-list"></i> My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-5">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                Order #<?= htmlspecialchars($order_id) ?>
                <span class="status-badge <?= $status_class ?>"><?= $formatted_status ?></span>
            </h1>
            <a href="track_order.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back to Orders
            </a>
        </div>

        <!-- Order Progress Tracker -->
        <div class="detail-card">
            <div class="card-header">
                <h5><i class="fas fa-tasks mr-2"></i> Order Progress</h5>
            </div>
            <div class="card-body">
                <div class="progress-tracker">
                    <div class="row">
                        <?php
                        // Define all possible statuses in order
                        $all_statuses = [
                            'pending_approval' => ['icon' => 'clock', 'label' => 'Pending Approval'],
                            'approved' => ['icon' => 'check-circle', 'label' => 'Approved'],
                            'in_process' => ['icon' => 'sync', 'label' => 'In Process'],
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
                        
                        $i = 0;
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
                            if ($status_key === 'declined' && $current_status === 'declined') {
                                $step_class = 'declined';
                            }
                        ?>
                            <div class="col-md-<?= $col_class ?> progress-step <?= $step_class ?> mb-3">
                                <div class="step-icon">
                                    <i class="fas fa-<?= $status_info['icon'] ?>"></i>
                                </div>
                                <div class="step-label"><?= $status_info['label'] ?></div>
                            </div>
                        <?php
                            $i++;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Order Summary Column -->
            <div class="col-lg-4 order-lg-2">
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
                                <?php if ($order['payment_status'] == 'fully_paid'): ?>
                                    <span class="badge bg-success">Fully Paid</span>
                                <?php elseif ($order['payment_status'] == 'partially_paid'): ?>
                                    <span class="badge bg-warning text-dark">Partially Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Unpaid</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Payment Method</div>
                            <div class="info-value"><?= htmlspecialchars($order['payment_method']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Details Column -->
            <div class="col-lg-8 order-lg-1">
                <!-- Order Details -->
                <div class="detail-card">
                    <div class="card-header">
                        <h5>
                            <?php if ($order['order_type'] == 'sublimation'): ?>
                                <i class="fas fa-tshirt mr-2"></i> Sublimation Details
                            <?php else: ?>
                                <i class="fas fa-cut mr-2"></i> Tailoring Details
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($order['order_type'] == 'sublimation' && isset($specific_details)): ?>
                            <!-- Sublimation Details -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-group">
                                        <div class="info-label">Printing Type</div>
                                        <div class="info-value"><?= htmlspecialchars($specific_details['printing_type'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Quantity</div>
                                        <div class="info-value"><?= htmlspecialchars($specific_details['quantity'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Design Option</div>
                                        <div class="info-value">
                                            <?php 
                                            if (isset($specific_details['custom_design']) && $specific_details['custom_design']) {
                                                echo 'Custom Design';
                                            } elseif (isset($specific_details['template_id']) && $specific_details['template_id']) {
                                                echo 'Template #' . htmlspecialchars($specific_details['template_id']);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <?php if (isset($specific_details['design_path']) && $specific_details['design_path']): ?>
                                        <div class="text-center">
                                            <p class="info-label mb-2">Design Preview</p>
                                            <img src="<?= htmlspecialchars('../' . $specific_details['design_path']) ?>" 
                                                 class="img-fluid border rounded" style="max-height: 150px;" alt="Design Preview">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php
                            // Get player details for sublimation orders
                            if (isset($specific_details['sublimation_id'])) {
                                $players_query = "SELECT * FROM sublimation_players WHERE sublimation_id = ?";
                                $stmt = mysqli_prepare($conn, $players_query);
                                mysqli_stmt_bind_param($stmt, "i", $specific_details['sublimation_id']);
                                mysqli_stmt_execute($stmt);
                                $players_result = mysqli_stmt_get_result($stmt);
                                
                                if (mysqli_num_rows($players_result) > 0):
                            ?>
                                <hr class="my-4">
                                <h6 class="font-weight-bold mb-3">Player Details</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Player Name</th>
                                                <th>Jersey Number</th>
                                                <th>Size</th>
                                                <th>Include Lower</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($player = mysqli_fetch_assoc($players_result)): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($player['player_name']) ?></td>
                                                    <td><?= htmlspecialchars($player['jersey_number']) ?></td>
                                                    <td><?= htmlspecialchars($player['size']) ?></td>
                                                    <td><?= $player['include_lower'] ? 'Yes' : 'No' ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php 
                                endif;
                            }
                            ?>
                            
                        <?php elseif ($order['order_type'] == 'tailoring' && isset($specific_details)): ?>
                            <!-- Tailoring Details -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-group">
                                        <div class="info-label">Garment Type</div>
                                        <div class="info-value"><?= htmlspecialchars($specific_details['garment_type'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Fabric</div>
                                        <div class="info-value"><?= htmlspecialchars($specific_details['fabric'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Color</div>
                                        <div class="info-value"><?= htmlspecialchars($specific_details['color'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-group">
                                        <div class="info-label">Measurements</div>
                                        <div class="info-value">
                                            <?php
                                            $measurements = [];
                                            if (!empty($specific_details['chest'])) $measurements[] = "Chest: " . htmlspecialchars($specific_details['chest']) . " inches";
                                            if (!empty($specific_details['waist'])) $measurements[] = "Waist: " . htmlspecialchars($specific_details['waist']) . " inches";
                                            if (!empty($specific_details['hips'])) $measurements[] = "Hips: " . htmlspecialchars($specific_details['hips']) . " inches";
                                            if (!empty($specific_details['shoulder'])) $measurements[] = "Shoulder: " . htmlspecialchars($specific_details['shoulder']) . " inches";
                                            if (!empty($specific_details['sleeve'])) $measurements[] = "Sleeve: " . htmlspecialchars($specific_details['sleeve']) . " inches";
                                            if (!empty($specific_details['inseam'])) $measurements[] = "Inseam: " . htmlspecialchars($specific_details['inseam']) . " inches";
                                            
                                            echo !empty($measurements) ? implode("<br>", $measurements) : "N/A";
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($specific_details['special_instructions'])): ?>
                            <hr class="my-4">
                            <div class="info-group">
                                <div class="info-label">Special Instructions</div>
                                <div class="info-value">
                                    <?= nl2br(htmlspecialchars($specific_details['special_instructions'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 mb-5">
            <a href="track_order.php" class="btn btn-primary btn-lg">
                <i class="fas fa-arrow-left mr-2"></i> Back to My Orders
            </a>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
</body>

</html>