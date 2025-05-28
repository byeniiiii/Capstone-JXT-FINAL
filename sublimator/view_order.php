<?php
session_start();
include '../db.php';

// If the user is not logged in or not a sublimator, redirect to login page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Sublimator') {
    header('Location: ../index.php?error=unauthorized');
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

// Get sublimation order details
$sublimation_query = "SELECT so.*, t.name as template_name, t.image_path as template_image 
                     FROM sublimation_orders so 
                     LEFT JOIN templates t ON so.template_id = t.template_id
                     WHERE so.order_id = ?";
$stmt = mysqli_prepare($conn, $sublimation_query);
mysqli_stmt_bind_param($stmt, "s", $order_id);
mysqli_stmt_execute($stmt);
$sublimation_result = mysqli_stmt_get_result($stmt);
$sublimation = mysqli_fetch_assoc($sublimation_result);

// Get player details if applicable
$players = [];
if ($sublimation) {
    $player_query = "SELECT * FROM sublimation_players WHERE sublimation_id = ?";
    $stmt = mysqli_prepare($conn, $player_query);
    mysqli_stmt_bind_param($stmt, "i", $sublimation['sublimation_id']);
    mysqli_stmt_execute($stmt);
    $player_result = mysqli_stmt_get_result($stmt);
    
    while ($player = mysqli_fetch_assoc($player_result)) {
        $players[] = $player;
    }
}

// Get order history
$history_query = "SELECT h.*, 
                  CONCAT(u.first_name, ' ', u.last_name) as updated_by_name,
                  u.role as updated_by_role
                  FROM order_status_history h
                  LEFT JOIN users u ON h.updated_by = u.user_id
                  WHERE h.order_id = ?
                  ORDER BY h.created_at DESC";
$stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($stmt, "s", $order_id);
mysqli_stmt_execute($stmt);
$history_result = mysqli_stmt_get_result($stmt);

// Format status for display
function formatStatus($status) {
    return ucwords(str_replace('_', ' ', $status));
}

// Determine status badge class
function getStatusBadgeClass($status) {
    switch(strtolower($status)) {
        case 'pending_approval':
            return 'bg-secondary';
        case 'declined':
            return 'bg-danger';
        case 'approved':
            return 'bg-info';
        case 'forward_to_sublimator':
            return 'bg-primary';
        case 'in_process':
            return 'bg-warning text-dark';
        case 'printing_done':
            return 'bg-info';
        case 'ready_for_pickup':
            return 'bg-success';
        case 'completed':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
}

// Function to get team positions for jersey display
function getPositionName($position) {
    $positions = [
        'gk' => 'Goalkeeper',
        'df' => 'Defender',
        'mf' => 'Midfielder',
        'fw' => 'Forward',
        'coach' => 'Coach',
        'player' => 'Player'
    ];
    
    return $positions[strtolower($position)] ?? 'Player';
}

// Function to get color class for position badges
function getPositionColorClass($position) {
    switch(strtolower($position)) {
        case 'gk': return 'bg-warning text-dark';
        case 'df': return 'bg-danger text-white';
        case 'mf': return 'bg-success text-white';
        case 'fw': return 'bg-primary text-white';
        case 'coach': return 'bg-dark text-white';
        default: return 'bg-secondary text-white';
    }
}

// Get sublimation status from the order status
$sublimation_status = $order['order_status'];

// Map order status to sublimation status if needed
if ($sublimation_status == 'forward_to_sublimator') {
    $sublimation_status = 'pending';
} elseif ($sublimation_status == 'in_process') {
    $sublimation_status = 'printing';
} elseif ($sublimation_status == 'printing_done') {
    $sublimation_status = 'printing_done';
} elseif ($sublimation_status == 'ready_for_pickup' || $sublimation_status == 'completed') {
    $sublimation_status = 'completed';
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= htmlspecialchars($order_id) ?> - JXT Sublimator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        
        .order-header {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .order-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .order-card .card-header {
            background-color: #4e73df;
            color: white;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        
        .order-card .card-body {
            padding: 1.5rem;
        }
        
        .timeline {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 2rem;
        }
        
        .timeline:before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            height: 100%;
            width: 2px;
            background-color: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background-color: #4e73df;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #4e73df;
        }
        
        .timeline-date {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .timeline-content {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
        }
        
        .timeline-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .timeline-text {
            margin-bottom: 0;
        }
        
        .template-image {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .player-card {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .player-card:last-child {
            margin-bottom: 0;
        }
        
        .player-card h5 {
            margin-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.5rem;
        }
        
        .player-info {
            margin-bottom: 0.5rem;
        }
        
        .player-info strong {
            display: inline-block;
            width: 100px;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.5rem 0.75rem;
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
            top: 40px;
            left: 60%;
            width: 80%;
            height: 3px;
            background-color: #e9ecef;
            z-index: 1;
        }
        
        .progress-step.active:not(:last-child)::after,
        .progress-step.completed:not(:last-child)::after {
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
        
        .progress-step.active .step-icon,
        .progress-step.completed .step-icon {
            background-color: #4e73df;
        }
        
        .progress-step.active .step-icon i,
        .progress-step.completed .step-icon i {
            color: white;
        }
        
        .step-label {
            font-size: 14px;
            font-weight: 600;
            color: #6c757d;
        }
        
        .progress-step.active .step-label,
        .progress-step.completed .step-label {
            color: #4e73df;
        }
        
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
        
        .btn-success {
            background-color: #1cc88a;
            border-color: #1cc88a;
        }
        
        .btn-success:hover {
            background-color: #17a673;
            border-color: #17a673;
        }
        
        .btn-info {
            background-color: #36b9cc;
            border-color: #36b9cc;
        }
        
        .btn-info:hover {
            background-color: #2c9faf;
            border-color: #2c9faf;
        }
        
        .info-group {
            margin-bottom: 1rem;
        }
        
        .info-group:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .info-value {
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'topbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                Order #<?= htmlspecialchars($order_id) ?>
                <span class="badge <?= $status_class ?>"><?= $formatted_status ?></span>
            </h1>
            <div>
                <a href="my_assignments.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Assignments
                </a>
            </div>
        </div>
        
        <!-- Order Progress Tracker -->
        <div class="order-card mb-4">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-tasks me-2"></i> Sublimation Progress</h5>
            </div>
            <div class="card-body">
                <div class="progress-tracker">
                    <div class="row">
                        <?php
                        // Define all possible sublimation statuses in order
                        $all_statuses = [
                            'pending' => ['icon' => 'clock', 'label' => 'Pending'],
                            'printing' => ['icon' => 'print', 'label' => 'Printing'],
                            'printing_done' => ['icon' => 'check-circle', 'label' => 'Printing Done'],
                            'completed' => ['icon' => 'flag-checkered', 'label' => 'Completed']
                        ];
                        
                        $col_class = 12 / count($all_statuses);
                        
                        foreach ($all_statuses as $status_key => $status_info) {
                            $is_active = ($sublimation_status === $status_key);
                            $is_completed = false;
                            
                            // Determine if this step is completed (any step before current)
                            $status_order = array_keys($all_statuses);
                            $current_index = array_search($sublimation_status, $status_order);
                            $status_index = array_search($status_key, $status_order);
                            $is_completed = ($status_index < $current_index);
                            
                            $step_class = $is_active ? 'active' : ($is_completed ? 'completed' : '');
                        ?>
                            <div class="col-md-<?= $col_class ?> progress-step <?= $step_class ?> mb-3">
                                <div class="step-icon">
                                    <i class="fas fa-<?= $status_info['icon'] ?>"></i>
                                </div>
                                <div class="step-label"><?= $status_info['label'] ?></div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                
                <?php if ($sublimation_status === 'pending'): ?>
                <div class="d-flex justify-content-center mt-4">
                    <a href="update_sublimation_status.php?order_id=<?= $order_id ?>&status=printing" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i> Start Printing
                    </a>
                </div>
                <?php elseif ($sublimation_status === 'printing'): ?>
                <div class="d-flex justify-content-center mt-4">
                    <a href="update_sublimation_status.php?order_id=<?= $order_id ?>&status=printing_done" class="btn btn-success">
                        <i class="fas fa-check-circle me-2"></i> Mark Printing as Done
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <!-- Left Column: Order and Customer Details -->
            <div class="col-lg-8">
                <!-- Order Details -->
                <div class="order-card mb-4">
                    <div class="card-header">
                        <h5 class="m-0"><i class="fas fa-info-circle me-2"></i> Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group">
                                    <div class="info-label">Order ID</div>
                                    <div class="info-value">#<?= htmlspecialchars($order_id) ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Order Date</div>
                                    <div class="info-value"><?= date('F j, Y', strtotime($order['created_at'])) ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Order Status</div>
                                    <div class="info-value">
                                        <span class="badge <?= $status_class ?>"><?= $formatted_status ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <div class="info-label">Customer</div>
                                    <div class="info-value"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Contact</div>
                                    <div class="info-value"><?= htmlspecialchars($order['phone_number']) ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?= htmlspecialchars($order['email']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sublimation Details -->
                <div class="order-card mb-4">
                    <div class="card-header">
                        <h5 class="m-0"><i class="fas fa-tshirt me-2"></i> Sublimation Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group">
                                    <div class="info-label">Printing Type</div>
                                    <div class="info-value"><?= htmlspecialchars($sublimation['printing_type'] ?? 'N/A') ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Quantity</div>
                                    <div class="info-value"><?= htmlspecialchars($sublimation['quantity'] ?? count($players)) ?></div>
                                </div>
                                <?php if (!empty($sublimation['instructions'])): ?>
                                <div class="info-group">
                                    <div class="info-label">Special Instructions</div>
                                    <div class="info-value"><?= nl2br(htmlspecialchars($sublimation['instructions'])) ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <div class="info-label">Design Type</div>
                                    <div class="info-value">
                                        <?php if (!empty($sublimation['custom_design'])): ?>
                                            <span class="badge bg-info text-white">Custom Design</span>
                                        <?php elseif (!empty($sublimation['template_id'])): ?>
                                            <span class="badge bg-primary text-white">Template #<?= htmlspecialchars($sublimation['template_id']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary text-white">Not Specified</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Sublimation Status</div>
                                    <div class="info-value">
                                        <span class="badge <?= $sublimation_badge_class ?>">
                                            <?= ucwords(str_replace('_', ' ', $sublimation_status)) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($sublimation['design_path'])): ?>
                        <div class="mt-4">
                            <div class="info-label">Custom Design</div>
                            <div class="text-center mt-2">
                                <img src="../<?= htmlspecialchars($sublimation['design_path']) ?>" alt="Custom Design" class="img-fluid" style="max-height: 300px;">
                            </div>
                        </div>
                        <?php elseif (!empty($sublimation['template_image'])): ?>
                        <div class="mt-4">
                            <div class="info-label">Template Design</div>
                            <div class="text-center mt-2">
                                <img src="../<?= htmlspecialchars($sublimation['template_image']) ?>" alt="Template Design" class="img-fluid" style="max-height: 300px;">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Player Details -->
                <?php if (!empty($players)): ?>
                <div class="order-card mb-4">
                    <div class="card-header">
                        <h5 class="m-0"><i class="fas fa-users me-2"></i> Player Details</h5>
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
            </div>
            
            <!-- Right Column: Timeline and Actions -->
            <div class="col-lg-4">
                <!-- Order Timeline -->
                <div class="order-card mb-4">
                    <div class="card-header">
                        <h5 class="m-0"><i class="fas fa-history me-2"></i> Order Timeline</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($history_result) > 0): ?>
                        <div class="timeline">
                            <?php while ($history = mysqli_fetch_assoc($history_result)): ?>
                            <div class="timeline-item">
                                <div class="timeline-date">
                                    <?= date('M j, Y g:i A', strtotime($history['created_at'])) ?>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        Status changed to <span class="badge <?= getStatusBadgeClass($history['status']) ?>"><?= formatStatus($history['status']) ?></span>
                                    </div>
                                    <?php if (!empty($history['notes'])): ?>
                                    <p class="timeline-text"><?= htmlspecialchars($history['notes']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($history['updated_by_name'])): ?>
                                    <small class="text-muted">By: <?= htmlspecialchars($history['updated_by_name']) ?> (<?= htmlspecialchars($history['updated_by_role']) ?>)</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No history records found for this order.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
