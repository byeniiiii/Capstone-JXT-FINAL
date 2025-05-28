<?php
session_start();
include '../db.php'; // Adjust path to your database connection

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header('Location: ../index.php?error=unauthorized');
    exit();
}

if (!isset($_GET['order_id'])) {
    die("No order ID provided.");
}

$order_id = $_GET['order_id'];

// Fetch order details from `orders` table with customer information
$order_query = $conn->prepare("
    SELECT o.*, c.first_name, c.last_name, c.email, c.phone_number, c.address
    FROM orders o
    JOIN customers c ON o.customer_id = c.customer_id
    WHERE o.order_id = ?
");
$order_query->bind_param("i", $order_id);
$order_query->execute();
$order_result = $order_query->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

// Fetch sublimation details if it's a sublimation order
$sublimation_query = $conn->prepare("
    SELECT s.*, so.sublimation_status
    FROM sublimation_orders s
    LEFT JOIN sublimation_order_status so ON s.sublimation_id = so.sublimation_id
    WHERE s.order_id = ?
");
$sublimation_query->bind_param("i", $order_id);
$sublimation_query->execute();
$sublimation_result = $sublimation_query->get_result();
$sublimation = $sublimation_result->fetch_assoc();

// Fetch tailoring details if it's a tailoring order
$tailoring_query = $conn->prepare("
    SELECT t.*
    FROM tailoring_orders t
    WHERE t.order_id = ?
");
$tailoring_query->bind_param("i", $order_id);
$tailoring_query->execute();
$tailoring_result = $tailoring_query->get_result();
$tailoring = $tailoring_result->fetch_assoc();

// Determine design option for sublimation orders
$design_option = "N/A";
if ($sublimation) {
    if ($sublimation['custom_design']) {
        $design_option = "Custom Design (" . htmlspecialchars($sublimation['design_path']) . ")";
    } elseif ($sublimation['template_id']) {
        $design_option = "Template (" . htmlspecialchars($sublimation['template_id']) . ")";
    }
}

// Fetch player details for sublimation orders
$players = [];
if ($sublimation) {
    $players_query = $conn->prepare("
        SELECT player_name, jersey_number, size, include_lower 
        FROM sublimation_players 
        WHERE sublimation_id = ?
    ");
    $players_query->bind_param("i", $sublimation['sublimation_id']);
    $players_query->execute();
    $players_result = $players_query->get_result();
    
    while ($player = $players_result->fetch_assoc()) {
        $players[] = $player;
    }
}

// Format order status for display
$formatted_status = str_replace('_', ' ', ucwords($order['order_status']));

// Determine status badge class
$status_class = '';
switch(strtolower($order['order_status'])) {
    case 'pending_approval':
        $status_class = 'bg-warning text-dark';
        break;
    case 'declined':
        $status_class = 'bg-danger text-white';
        break;
    case 'approved':
        $status_class = 'bg-success text-white';
        break;
    case 'in_process':
        $status_class = 'bg-info text-white';
        break;
    case 'ready_for_pickup':
        $status_class = 'bg-primary text-white';
        break;
    case 'completed':
        $status_class = 'bg-success text-white';
        break;
    default:
        $status_class = 'bg-secondary text-white';
}

// Define all possible statuses in order
$all_statuses = [
    'pending_approval' => ['icon' => 'clock', 'label' => 'Pending Approval'],
    'approved' => ['icon' => 'check-circle', 'label' => 'Approved'],
    'in_process' => ['icon' => 'sync', 'label' => 'In Process'],
    'ready_for_pickup' => ['icon' => 'box', 'label' => 'Ready for Pickup'],
    'completed' => ['icon' => 'flag-checkered', 'label' => 'Completed'],
    'declined' => ['icon' => 'times-circle', 'label' => 'Declined']
];

// Get current status position
$current_status = strtolower($order['order_status']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order - JXT Tailoring</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
        }
        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        .card-header {
            font-weight: bold;
            background-color: #4e73df;
            color: white;
            border-bottom: none;
        }
        .progress-tracker {
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 10px;
        }
        .status-step {
            text-align: center;
            position: relative;
        }
        .status-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 40px;
            left: 60%;
            width: 80%;
            height: 3px;
            background-color: #e9ecef;
            z-index: 1;
        }
        .status-step.active:not(:last-child)::after,
        .status-step.completed:not(:last-child)::after {
            background-color: #4e73df;
        }
        .step-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            z-index: 2;
            position: relative;
        }
        .step-icon i {
            font-size: 30px;
            color: #6c757d;
        }
        .status-step.active .step-icon,
        .status-step.completed .step-icon {
            background-color: #4e73df;
        }
        .status-step.active .step-icon i,
        .status-step.completed .step-icon i {
            color: white;
        }
        .status-step.declined .step-icon {
            background-color: #e74a3b;
        }
        .status-step.declined .step-icon i {
            color: white;
        }
        .step-label {
            font-size: 14px;
            font-weight: 600;
            color: #6c757d;
        }
        .status-step.active .step-label,
        .status-step.completed .step-label {
            color: #4e73df;
        }
        .status-step.declined .step-label {
            color: #e74a3b;
        }
        .table th {
            font-weight: 600;
            background-color: #f8f9fc;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Order #<?= htmlspecialchars($order_id) ?>
            <span class="badge <?= $status_class ?>"><?= $formatted_status ?></span>
        </h1>
        <a href="orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Orders</a>
    </div>

    <!-- Order Progress Tracker -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Order Progress</h6>
        </div>
        <div class="card-body">
            <div class="progress-tracker">
                <div class="row">
                    <?php
                    // Skip declined status for normal flow
                    $display_statuses = array_filter($all_statuses, function($key) {
                        return $key !== 'declined';
                    }, ARRAY_FILTER_USE_KEY);
                    
                    // If order is declined, show only that status
                    if ($current_status === 'declined') {
                        $display_statuses = ['declined' => $all_statuses['declined']];
                    }
                    
                    $i = 0;
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
                        
                        $col_class = count($display_statuses) <= 4 ? 12 / count($display_statuses) : 3;
                    ?>
                        <div class="col-md-<?= $col_class ?> status-step <?= $step_class ?> mb-3">
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
        <!-- Customer Information -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Customer Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="35%">Name</th>
                            <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?= htmlspecialchars($order['email']) ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?= htmlspecialchars($order['phone_number']) ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?= htmlspecialchars($order['address']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Details -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Order Details</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="35%">Order ID</th>
                            <td><?= htmlspecialchars($order['order_id']) ?></td>
                        </tr>
                        <tr>
                            <th>Order Date</th>
                            <td><?= date('F j, Y', strtotime($order['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <th>Order Type</th>
                            <td><?= ucfirst(htmlspecialchars($order['order_type'])) ?></td>
                        </tr>
                        <tr>
                            <th>Total Amount</th>
                            <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                        </tr>
                        <tr>
                            <th>Downpayment</th>
                            <td>₱<?= number_format($order['downpayment_amount'], 2) ?></td>
                        </tr>
                        <tr>
                            <th>Payment Method</th>
                            <td><?= htmlspecialchars($order['payment_method']) ?></td>
                        </tr>
                        <tr>
                            <th>Payment Status</th>
                            <td>
                                <?php if ($order['payment_status'] == 'fully_paid'): ?>
                                    <span class="badge bg-success">Fully Paid</span>
                                <?php elseif ($order['payment_status'] == 'partially_paid'): ?>
                                    <span class="badge bg-warning text-dark">Partially Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Unpaid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($order['order_type'] == 'sublimation' && $sublimation): ?>
    <!-- Sublimation Details -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Sublimation Details</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="35%">Printing Type</th>
                            <td><?= htmlspecialchars($sublimation['printing_type']) ?></td>
                        </tr>
                        <tr>
                            <th>Design Option</th>
                            <td><?= $design_option ?></td>
                        </tr>
                        <tr>
                            <th>Quantity</th>
                            <td><?= htmlspecialchars($sublimation['quantity'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <th>Sublimation Status</th>
                            <td>
                                <?php 
                                $sublimation_status = $sublimation['sublimation_status'] ?? 'pending';
                                $sublimation_badge_class = '';
                                
                                switch($sublimation_status) {
                                    case 'pending':
                                        $sublimation_badge_class = 'bg-warning text-dark';
                                        break;
                                    case 'printing':
                                        $sublimation_badge_class = 'bg-info text-white';
                                        break;
                                    case 'printing_done':
                                        $sublimation_badge_class = 'bg-primary text-white';
                                        break;
                                    case 'completed':
                                        $sublimation_badge_class = 'bg-success text-white';
                                        break;
                                    default:
                                        $sublimation_badge_class = 'bg-secondary text-white';
                                }
                                ?>
                                <span class="badge <?= $sublimation_badge_class ?>">
                                    <?= ucwords(str_replace('_', ' ', $sublimation_status)) ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-lg-6">
                    <?php if ($sublimation['design_path']): ?>
                    <div class="card">
                        <div class="card-header bg-light">Design Preview</div>
                        <div class="card-body text-center">
                            <img src="<?= htmlspecialchars('../' . $sublimation['design_path']) ?>" class="img-fluid" style="max-height: 200px;" alt="Design Preview">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($players) > 0): ?>
    <!-- Player Details -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Player Details</h6>
        </div>
        <div class="card-body">
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
                        <?php foreach ($players as $player): ?>
                            <tr>
                                <td><?= htmlspecialchars($player['player_name']) ?></td>
                                <td><?= htmlspecialchars($player['jersey_number']) ?></td>
                                <td><?= htmlspecialchars($player['size']) ?></td>
                                <td><?= $player['include_lower'] ? 'Yes' : 'No' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($order['order_type'] == 'tailoring' && $tailoring): ?>
    <!-- Tailoring Details -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Tailoring Details</h6>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th width="25%">Garment Type</th>
                    <td><?= htmlspecialchars($tailoring['garment_type'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Fabric</th>
                    <td><?= htmlspecialchars($tailoring['fabric'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Color</th>
                    <td><?= htmlspecialchars($tailoring['color'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Measurements</th>
                    <td>
                        <?php
                        $measurements = [];
                        if (!empty($tailoring['chest'])) $measurements[] = "Chest: " . htmlspecialchars($tailoring['chest']) . " inches";
                        if (!empty($tailoring['waist'])) $measurements[] = "Waist: " . htmlspecialchars($tailoring['waist']) . " inches";
                        if (!empty($tailoring['hips'])) $measurements[] = "Hips: " . htmlspecialchars($tailoring['hips']) . " inches";
                        if (!empty($tailoring['shoulder'])) $measurements[] = "Shoulder: " . htmlspecialchars($tailoring['shoulder']) . " inches";
                        if (!empty($tailoring['sleeve'])) $measurements[] = "Sleeve: " . htmlspecialchars($tailoring['sleeve']) . " inches";
                        if (!empty($tailoring['inseam'])) $measurements[] = "Inseam: " . htmlspecialchars($tailoring['inseam']) . " inches";
                        
                        echo !empty($measurements) ? implode("<br>", $measurements) : "N/A";
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Special Instructions</th>
                    <td><?= nl2br(htmlspecialchars($tailoring['special_instructions'] ?? 'None')) ?></td>
                </tr>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center mt-4 mb-5">
        <a href="orders.php" class="btn btn-secondary btn-lg">Back to Orders</a>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
