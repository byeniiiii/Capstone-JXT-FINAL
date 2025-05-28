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
    $sublimation_query = "SELECT so.*, t.name as template_name, t.price as template_price, t.image_path as template_image 
                         FROM sublimation_orders so 
                         LEFT JOIN templates t ON so.template_id = t.template_id
                         WHERE so.order_id = ?";
    $stmt = mysqli_prepare($conn, $sublimation_query);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $specific_details = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    // Get player details for sublimation orders
    $player_query = "SELECT * FROM sublimation_players WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $player_query);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $player_result = mysqli_stmt_get_result($stmt);
    $players = [];
    while ($player = mysqli_fetch_assoc($player_result)) {
        $players[] = $player;
    }
}

// Get order items for proper display
$items_data = [];
$items_result = false;

// For tailoring orders
if ($order['order_type'] == 'tailoring') {
    if ($specific_details) {
        $service_type = ucfirst($specific_details['service_type'] ?? 'Tailoring Service');
        $qty = $specific_details['quantity'] ?? 1;
        $unit_price = $order['total_amount'] / $qty;
        
        // Create item data for display
        $item = [
            'description' => $service_type . ' Service',
            'quantity' => $qty,
            'price' => $unit_price,
            'item_total' => $order['total_amount']
        ];
        
        $items_data = [$item];
        $items_result = true;
    }
}
// For sublimation orders 
else if ($order['order_type'] == 'sublimation') {
    if ($specific_details) {
        // Count players with and without lower
        $player_count = count($players);
        $lower_count = 0;
        
        foreach ($players as $player) {
            if (isset($player['include_lower']) && $player['include_lower'] == 'Yes') {
                $lower_count++;
            }
        }

        // Get jersey price from template or default price
        $jersey_price = isset($specific_details['template_price']) && $specific_details['template_price'] > 0 ? 
            $specific_details['template_price'] : 
            ($specific_details['jersey_price'] ?? 350); // Default jersey price if not set
        
        // Create jersey item with proper description
        $jersey_description = '';
        if (isset($specific_details['printing_type'])) {
            $jersey_description .= ucfirst($specific_details['printing_type']) . ' ';
        } else {
            $jersey_description .= 'Sublimation ';
        }
        
        $jersey_description .= 'Jersey';
        
        // Add template name if available
        if (isset($specific_details['template_name']) && !empty($specific_details['template_name'])) {
            $jersey_description .= ' - ' . $specific_details['template_name'];
        } elseif (isset($specific_details['template_id']) && $specific_details['template_id'] > 0) {
            $jersey_description .= ' - Template #' . $specific_details['template_id'];
        }
        
        $jersey_item = [
            'description' => $jersey_description,
            'quantity' => $player_count,
            'price' => $jersey_price,
            'item_total' => $player_count * $jersey_price
        ];
        
        $items_data = [$jersey_item];
        
        // Add lower item if any
        if ($lower_count > 0) {
            // Get lower price from specific details or use default
            $lower_price = isset($specific_details['lower_price']) && $specific_details['lower_price'] > 0 ? 
                $specific_details['lower_price'] : 150; // Default price per lower item
                
            $items_data[] = [
                'description' => 'Shorts/Lower',
                'quantity' => $lower_count,
                'price' => $lower_price,
                'item_total' => $lower_count * $lower_price
            ];
        }
        
        // Add any additional fees or charges if they exist in the database
        if (isset($specific_details['additional_fee']) && $specific_details['additional_fee'] > 0) {
            $items_data[] = [
                'description' => 'Additional Fee' . (isset($specific_details['fee_description']) ? ' - ' . $specific_details['fee_description'] : ''),
                'quantity' => 1,
                'price' => $specific_details['additional_fee'],
                'item_total' => $specific_details['additional_fee']
            ];
        }
        
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
            color: #343a40;
        }

        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }

        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%) !important;
            box-shadow: 2px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-item .nav-link {
            color: #ecf0f1 !important;
            font-weight: 500;
            padding: 1rem 1.5rem;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-item .nav-link:hover {
            border-left: 3px solid #3498db;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        /* Order details card styling */
        .detail-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        
        .detail-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        
        .card-header {
            background: linear-gradient(135deg, #3a7bd5 0%, #2c3e50 100%);
            color: white;
            padding: 18px 24px;
            font-weight: 600;
            border: none;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
        }
        
        .card-header h5 i {
            margin-right: 12px;
            opacity: 0.8;
        }
        
        .card-body {
            padding: 24px;
        }
        
        .info-group {
            margin-bottom: 22px;
            position: relative;
            padding-left: 5px;
        }
        
        .info-group:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            font-size: 0.78rem;
            color: #6c757d;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
            font-weight: 600;
            display: inline-block;
            position: relative;
            text-transform: uppercase;
        }
        
        .info-label::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 20px;
            height: 2px;
            background-color: #3498db;
            transition: width 0.3s ease;
        }
        
        .info-group:hover .info-label::after {
            width: 40px;
        }
        
        .info-value {
            font-size: 1.05rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        /* Status badges */
        .status-badge {
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin: 4px 0;
            transition: all 0.3s ease;
        }
        
        .status-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .status-pending {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }
        
        .status-pending-payment {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .status-in-progress {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }
        
        .status-declined {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        
        .status-ready {
            background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
            color: white;
        }
        
        /* Button styling */
        .btn-primary {
            background: linear-gradient(135deg, #3a7bd5 0%, #2c3e50 100%);
            border: none;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #3a7bd5 0%, #1a252f 100%);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
            transform: translateY(-2px);
        }
        
        .btn-outline-secondary {
            color: #34495e;
            border-color: #bdc3c7;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            background-color: #34495e;
            border-color: #34495e;
            color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .table-sm th {
            font-weight: 600;
            color: #34495e;
            border-top: none;
            border-bottom: 2px solid #ecf0f1;
            padding: 12px 8px;
        }
        
        .table-sm td {
            padding: 12px 8px;
            border-color: #ecf0f1;
            vertical-align: middle;
        }
        
        .amount-row {
            font-weight: 600;
            color: #34495e;
        }
        
        .total-row {
            font-weight: 700;
            font-size: 1.1rem;
            color: #2980b9;
        }
        
        .footer {
            width: 100%;
            background-color: #34495e !important;
            color: #ecf0f1 !important;
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
            background: linear-gradient(to bottom, #3a7bd5, #2c3e50);
            left: 6px;
            top: 0;
            opacity: 0.3;
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
            background-color: #3a7bd5;
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
        
        /* General layout improvements */
        .container-fluid {
            padding: 30px 30px;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: #2c3e50;
        }
        
        .h3 {
            font-weight: 700 !important;
            letter-spacing: -0.5px;
        }
        
        .font-weight-bold {
            font-weight: 600 !important;
        }
        
        .fw-bold {
            font-weight: 600 !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .order-summary {
                order: -1;
            }
            
            .container-fluid {
                padding: 20px 15px;
            }
        }

        /* Progress Tracking Enhancements */
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
        
        .progress-step.declined .step-icon {
            background-color: #e74a3b;
        }
        
        .progress-step.declined .step-icon i {
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
        
        .progress-step.declined .step-label {
            color: #e74a3b;
        }

        /* Player card styling - ENHANCED */
        .player-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease-in-out;
            position: relative;
            overflow: hidden;
        }
        
        .player-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #3a7bd5, #2c3e50);
            opacity: 0.7;
        }
        
        .player-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        .player-card h6 {
            border-bottom: 2px solid rgba(236, 240, 241, 0.8);
            padding-bottom: 12px;
            margin-bottom: 15px;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .jersey-number {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3a7bd5 0%, #2c3e50 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
            padding: 5px;
            line-height: 1;
            position: relative;
        }
        
        .jersey-number::after {
            content: '';
            position: absolute;
            width: 120%;
            height: 120%;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            top: -10%;
            left: -10%;
            z-index: -1;
        }

        /* Image preview */
        .design-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .design-image {
            max-width: 100%;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        
        .design-image:hover {
            transform: scale(1.02);
        }
        
        .design-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%);
            padding: 20px 15px 15px;
            color: white;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        
        .design-overlay h5 {
            color: white;
            margin: 0;
            font-size: 1rem;
        }

        .measurements-details {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.04);
            border: none;
            position: relative;
        }
        
        /* Customer detail badges */
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            letter-spacing: 0.3px;
            border-radius: 8px;
        }
        
        .badge.bg-primary {
            background: linear-gradient(135deg, #3a7bd5 0%, #2c3e50 100%) !important;
        }
        
        .badge.bg-success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%) !important;
        }
        
        .badge.bg-warning {
            background: linear-gradient(135deg, #f1c40f 0%, #f39c12 100%) !important;
        }
        
        .badge.bg-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%) !important;
        }
        
        .badge.bg-secondary {
            background: linear-gradient(135deg, #7f8c8d 0%, #95a5a6 100%) !important;
        }
        
        /* Modern timeline styling */
        .timeline {
            position: relative;
            padding-left: 40px;
            margin-left: 10px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            height: 100%;
            width: 3px;
            background: linear-gradient(to bottom, #3a7bd5, #2c3e50);
            left: 6px;
            top: 0;
            opacity: 0.5;
            border-radius: 3px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 35px;
            transition: transform 0.3s ease;
        }
        
        .timeline-item:hover {
            transform: translateX(5px);
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3a7bd5 0%, #2c3e50 100%);
            left: -40px;
            top: 5px;
            box-shadow: 0 0 0 5px rgba(52, 152, 219, 0.3);
            transition: all 0.3s ease;
        }
        
        .timeline-item:hover::before {
            transform: scale(1.2);
            box-shadow: 0 0 0 6px rgba(52, 152, 219, 0.4);
        }
        
        .timeline-date {
            font-size: 0.8rem;
            font-weight: 500;
            color: #7f8c8d;
            margin-bottom: 4px;
            display: inline-block;
            padding: 3px 10px;
            background-color: #ecf0f1;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        
        .timeline-content {
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #3a7bd5;
            margin-top: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
        }
        
        .timeline-item:hover .timeline-content {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            background-color: #ffffff;
        }
        
        .timeline-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
            font-size: 1.05rem;
        }
        
        .timeline-text {
            color: #7f8c8d;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        /* Improved step indicator for progress tracking */
        .progress-tracker {
            margin: 30px 0;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #ecf0f1;
            margin-bottom: 30px;
            overflow: visible;
        }
        
        .progress-bar {
            background: linear-gradient(to right, #3a7bd5, #2ecc71);
            border-radius: 4px;
            position: relative;
            transition: width 1.5s ease;
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            right: -5px;
            top: -4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: #2ecc71;
            box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.3);
        }
        
        .step-date {
            font-size: 0.7rem;
            margin-top: 8px;
            color: #7f8c8d;
        }
        
        .step-icon {
            margin-bottom: 8px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .step.active .step-icon {
            transform: scale(1.3);
            color: #3a7bd5 !important;
        }
        
        .step.active .step-label {
            color: #3a7bd5 !important;
            font-weight: 600;
        }
        
        /* New additions for enhanced player cards */
        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .player-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .player-info-item {
            display: flex;
            flex-direction: column;
        }
        
        .player-info-label {
            font-size: 0.7rem;
            color: #6c757d;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .player-info-value {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .position-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 5px;
        }
        
        .jersey-thumbnail {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            font-weight: 800;
            font-size: 1.2rem;
            color: #2c3e50;
        }
        
        /* Template showcase */
        .template-showcase {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .template-showcase:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .template-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        
        .template-details {
            padding: 20px;
        }
        
        .template-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .template-price {
            color: #3a7bd5;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .template-description {
            margin-top: 10px;
            color: #7f8c8d;
        }
        
        /* Custom Design Display */
        .custom-design-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .custom-design-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .custom-design-image {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
        }
        
        .custom-design-details {
            padding: 20px;
        }
        
        /* Size Display */
        .size-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            background-color: #ecf0f1;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
            border: 1px solid #dfe6e9;
        }
        
        /* Team Section */
        .team-section {
            margin: 20px 0;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border-left: 5px solid #3a7bd5;
        }
        
        .team-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        /* Details section headings */
        .section-heading {
            display: inline-block;
            position: relative;
            font-size: 1.15rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        
        .section-heading::after {
            content: '';
            position: absolute;
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, #3a7bd5, #2c3e50);
            bottom: 0;
            left: 0;
        }
        
        /* Jersey color visual */
        .jersey-color {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border: 2px solid white;
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
                                </span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            Order #<?= htmlspecialchars($order_id) ?>
                            <span class="badge <?= $status_class ?>"><?= $formatted_status ?></span>
                        </h1>
                        <div>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Orders
                            </a>
                        </div>
                    </div>

                    <!-- Order Progress Tracker -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Order Progress</h6>
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
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Left Column: Order Details -->
                        <div class="col-lg-8">
                            <!-- Order Details Card -->
                            <div class="card shadow mb-4">
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
                                        <div class="info-value">₱<?= number_format($order['downpayment_amount'] ?? 0, 2) ?></div>
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
                                    
                                    <?php if (!empty($order['completion_date'])): ?>
                                    <div class="info-group">
                                        <div class="info-label">Target Completion</div>
                                        <div class="info-value"><?= date('F j, Y', strtotime($order['completion_date'])) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Customer Information -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Name:</strong> <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></p>
                                            <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone_number']) ?></p>
                                            <?php if (!empty($order['address'])): ?>
                                            <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Items -->
                            <div class="detail-card">
                                <div class="card-header">
                                    <h5><i class="fas fa-shopping-cart mr-2"></i> Order Items</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $subtotal = 0;
                                                
                                                if ($items_result):
                                                    foreach ($items_data as $item): 
                                                        $item_total = $item['quantity'] * $item['price'];
                                                        $subtotal += $item_total;
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                                    <td class="text-center"><?= $item['quantity'] ?></td>
                                                    <td class="text-end">₱<?= number_format($item_total, 2) ?></td>
                                                </tr>
                                                <?php 
                                                    endforeach; 
                                                else:
                                                ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No items found</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="amount-row">
                                                    <td colspan="2" class="text-end">Subtotal:</td>
                                                    <td class="text-end">₱<?= number_format($subtotal > 0 ? $subtotal : $order['total_amount'], 2) ?></td>
                                                </tr>
                                                <?php if(isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                                <tr class="amount-row">
                                                    <td colspan="2" class="text-end">Discount:</td>
                                                    <td class="text-end">-₱<?= number_format($order['discount_amount'], 2) ?></td>
                                                </tr>
                                                <?php endif; ?>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order-specific details (Sublimation or Tailoring) -->
                            <?php if ($order['order_type'] == 'sublimation' && isset($specific_details)): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Sublimation Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Printing Type:</strong> <?= htmlspecialchars($specific_details['printing_type'] ?? 'Sublimation') ?></p>
                                            <p><strong>Quantity:</strong> <?= htmlspecialchars($specific_details['quantity'] ?? count($players)) ?></p>
                                            
                                            <?php if (!empty($specific_details['instructions'])): ?>
                                            <p><strong>Special Instructions:</strong> <?= htmlspecialchars($specific_details['instructions']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if (!empty($specific_details['completion_date'])): ?>
                                            <p><strong>Est. Completion Date:</strong> <?= date('F j, Y', strtotime($specific_details['completion_date'])) ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($specific_details['sublimator_id'])): ?>
                                            <?php 
                                                // Get sublimator name
                                                $sublimator_query = "SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE user_id = ?";
                                                $sublimator_stmt = mysqli_prepare($conn, $sublimator_query);
                                                mysqli_stmt_bind_param($sublimator_stmt, "i", $specific_details['sublimator_id']);
                                                mysqli_stmt_execute($sublimator_stmt);
                                                $sublimator_result = mysqli_stmt_get_result($sublimator_stmt);
                                                $sublimator = mysqli_fetch_assoc($sublimator_result);
                                            ?>
                                            <p><strong>Assigned Sublimator:</strong> <?= htmlspecialchars($sublimator['name'] ?? 'Unknown') ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($specific_details['template_id']) && !empty($specific_details['template_image'])): ?>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <p><strong>Template:</strong> <?= htmlspecialchars($specific_details['template_name'] ?? 'Template #'.$specific_details['template_id']) ?></p>
                                            <img src="../<?= htmlspecialchars($specific_details['template_image']) ?>" alt="Template" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($specific_details['custom_design']) && !empty($specific_details['design_path'])): ?>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <p><strong>Custom Design:</strong></p>
                                            <img src="../<?= htmlspecialchars($specific_details['design_path']) ?>" alt="Custom Design" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($players)): ?>
                            <!-- Player Details -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Player Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Jersey #</th>
                                                    <th>Name</th>
                                                    <th>Position</th>
                                                    <th>Size</th>
                                                    <th>Includes Lower</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($players as $player): ?>
                                                <tr>
                                                    <td class="text-center font-weight-bold"><?= htmlspecialchars($player['jersey_number']) ?></td>
                                                    <td><?= htmlspecialchars($player['player_name']) ?></td>
                                                    <td>
                                                        <?php if (!empty($player['position'])): ?>
                                                        <span class="badge <?= getPositionColorClass($player['position']) ?>">
                                                            <?= getPositionName($player['position']) ?>
                                                        </span>
                                                        <?php else: ?>
                                                        <span class="text-muted">Not specified</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($player['size']) ?></td>
                                                    <td>
                                                        <?php if (isset($player['include_lower']) && $player['include_lower']): ?>
                                                        <span class="text-success"><i class="fas fa-check"></i> Yes</span>
                                                        <?php else: ?>
                                                        <span class="text-danger"><i class="fas fa-times"></i> No</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php elseif ($order['order_type'] == 'tailoring' && isset($specific_details)): ?>
                            <!-- Tailoring Details -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Tailoring Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Service Type:</strong> <?= htmlspecialchars(ucfirst($specific_details['service_type'] ?? 'Tailoring')) ?></p>
                                            <p><strong>Garment Type:</strong> <?= htmlspecialchars(ucfirst($specific_details['garment_type'] ?? 'Not specified')) ?></p>
                                            <p><strong>Quantity:</strong> <?= htmlspecialchars($specific_details['quantity'] ?? '1') ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if (!empty($specific_details['completion_date'])): ?>
                                            <p><strong>Est. Completion Date:</strong> <?= date('F j, Y', strtotime($specific_details['completion_date'])) ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($specific_details['instructions'])): ?>
                                            <p><strong>Special Instructions:</strong> <?= htmlspecialchars($specific_details['instructions']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($specific_details['measurements'])): ?>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h6 class="font-weight-bold">Measurements</h6>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm">
                                                    <tbody>
                                                        <?php 
                                                        $measurements = json_decode($specific_details['measurements'], true);
                                                        if (is_array($measurements)): 
                                                            foreach ($measurements as $key => $value):
                                                        ?>
                                                        <tr>
                                                            <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></th>
                                                            <td><?= htmlspecialchars($value) ?></td>
                                                        </tr>
                                                        <?php 
                                                            endforeach;
                                                        endif;
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Right Column: Order History and Actions -->
                        <div class="col-lg-4">
                            <!-- Order History -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Order History</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Fetch order history
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
                                    
                                    if (mysqli_num_rows($history_result) > 0):
                                    ?>
                                    <div class="timeline">
                                        <?php while ($history = mysqli_fetch_assoc($history_result)): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-date">
                                                <?= date('M j, Y g:i A', strtotime($history['created_at'])) ?>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="timeline-title">
                                                    Status changed to <span class="badge <?= $status_class ?>"><?= formatStatus($history['status']) ?></span>
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
                            
                            <!-- Payment Information -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Payment Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="payment-details">
                                        <div class="row mb-2">
                                            <div class="col-6 text-muted">Total Amount:</div>
                                            <div class="col-6 text-right font-weight-bold">₱<?= number_format($order['total_amount'], 2) ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6 text-muted">Downpayment:</div>
                                            <div class="col-6 text-right">₱<?= number_format($order['downpayment_amount'], 2) ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6 text-muted">Balance:</div>
                                            <div class="col-6 text-right">₱<?= number_format($order['total_amount'] - $order['downpayment_amount'], 2) ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6 text-muted">Payment Method:</div>
                                            <div class="col-6 text-right"><?= ucfirst(htmlspecialchars($order['payment_method'])) ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 text-muted">Payment Status:</div>
                                            <div class="col-6 text-right">
                                                <span class="badge <?= $order['payment_status'] == 'fully_paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                    <?= formatStatus($order['payment_status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Actions</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Status Update Actions -->
                                    <div class="mb-3">
                                        <label for="statusUpdate" class="form-label">Update Status</label>
                                        <select class="form-control" id="statusUpdate">
                                            <option value="">Select new status...</option>
                                            <?php
                                            $available_statuses = [];
                                            
                                            // Determine available status options based on current status
                                            switch ($order['order_status']) {
                                                case 'pending_approval':
                                                    $available_statuses = ['approved', 'declined'];
                                                    break;
                                                case 'approved':
                                                    if ($order['order_type'] == 'sublimation') {
                                                        $available_statuses = ['forward_to_sublimator'];
                                                    } else {
                                                        $available_statuses = ['in_process'];
                                                    }
                                                    break;
                                                case 'in_process':
                                                    $available_statuses = ['ready_for_pickup'];
                                                    break;
                                                case 'ready_for_pickup':
                                                    $available_statuses = ['completed'];
                                                    break;
                                            }
                                            
                                            foreach ($available_statuses as $status) {
                                                echo '<option value="' . $status . '">' . formatStatus($status) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <button id="updateStatusBtn" class="btn btn-primary btn-block mb-3">Update Status</button>
                                    
                                    <?php if ($order['payment_status'] != 'fully_paid'): ?>
                                    <button id="recordPaymentBtn" class="btn btn-success btn-block mb-3">Record Payment</button>
                                    <?php endif; ?>
                                    
                                    <a href="orders.php" class="btn btn-secondary btn-block">Back to Orders</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add custom CSS for progress tracker -->
    <style>
    .progress-tracker {
        margin: 20px 0;
    }

    .progress-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        position: relative;
    }

    .progress-steps:before {
        content: '';
        position: absolute;
        top: 15px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #e9ecef;
        z-index: 1;
    }

    .progress-step {
        text-align: center;
        position: relative;
        z-index: 2;
        flex: 1;
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
    }

    .progress-step.active .step-icon {
        background-color: #4e73df;
        color: white;
    }

    .progress-step.completed .step-icon {
        background-color: #1cc88a;
        color: white;
    }

    .step-label {
        font-size: 0.75rem;
        color: #6c757d;
        max-width: 80px;
        margin: 0 auto;
    }

    .progress-step.active .step-label,
    .progress-step.completed .step-label {
        color: #4e73df;
    }

    .progress-step.declined .step-label {
        color: #e74a3b;
    }

    /* Timeline styling */
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline:before {
        content: '';
        position: absolute;
        left: 9px;
        top: 0;
        height: 100%;
        width: 2px;
        background-color: #e9ecef;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-item:before {
        content: '';
        position: absolute;
        left: -30px;
        top: 5px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #4e73df;
    }

    .timeline-date {
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .timeline-content {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
    }

    .timeline-title {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .payment-details .row {
        padding: 8px 0;
        border-bottom: 1px solid #f1f1f1;
    }

    .payment-details .row:last-child {
        border-bottom: none;
    }
    </style>

    <!-- Add JavaScript for status updates -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const updateStatusBtn = document.getElementById('updateStatusBtn');
        const statusSelect = document.getElementById('statusUpdate');
        
        updateStatusBtn.addEventListener('click', function() {
            const newStatus = statusSelect.value;
            if (!newStatus) {
                alert('Please select a status');
                return;
            }
            
            if (confirm('Are you sure you want to update the status to ' + statusSelect.options[statusSelect.selectedIndex].text + '?')) {
                // Submit form or AJAX request to update status
                window.location.href = 'update_order_status.php?id=<?= $order_id ?>&status=' + newStatus;
            }
        });
        
        <?php if ($order['payment_status'] != 'fully_paid'): ?>
        const recordPaymentBtn = document.getElementById('recordPaymentBtn');
        recordPaymentBtn.addEventListener('click', function() {
            window.location.href = 'record_payment.php?id=<?= $order_id ?>';
        });
        <?php endif; ?>
    });
    </script>
</body>
</html>