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
          ORDER BY o.created_at DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Orders | JX Tailoring</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
            font-family: 'Poppins', sans-serif;
            padding-top: 56px; /* Space for fixed navbar */
        }
        
        /* Navbar styling */
        .navbar {
            background-color: #343a40;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        
        .navbar-brand {
            font-weight: 600;
            color: white;
        }
        
        .navbar-toggler {
            border: none;
            color: white;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 0.75rem 1rem;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        
        .navbar-nav .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 8px;
        }
        
        /* Main content area */
        .orders-container {
            max-width: 1000px;
            margin: 30px auto;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            border-radius: 0.5rem;
        }
        
        .card-header {
            background-color: #343a40;
            color: white;
            font-weight: 600;
            border-top-left-radius: 0.5rem !important;
            border-top-right-radius: 0.5rem !important;
        }
        
        /* Search bar */
        .search-form {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-form .form-control {
            padding-left: 2.5rem;
            padding-right: 6.5rem;
            height: 3rem;
            font-size: 1rem;
            border-radius: 25px;
            border: 1px solid #ced4da;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .search-form .form-control:focus {
            border-color: #ff7d00;
            box-shadow: 0 0 0 0.25rem rgb(255 125 0 / 25%);
        }
        
        .search-form .icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-form .btn {
            position: absolute;
            right: 5px;
            top: 5px;
            border-radius: 20px;
            background-color: #ff7d00;
            border-color: #ff7d00;
            color: white;
            height: calc(100% - 10px);
            transition: all 0.2s;
        }
        
        .search-form .btn:hover {
            background-color: #e06c00;
            border-color: #e06c00;
        }
        
        /* Tables */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
            color: #343a40;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .table .status {
            font-weight: 600;
            color: #ff7d00;
        }
        
        .table .view-btn {
            background-color: #343a40;
            color: white;
            border: none;
            border-radius: 0.25rem;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
        }
        
        .table .view-btn:hover {
            background-color: #ff7d00;
            color: white;
        }
        
        /* Modal */
        .modal-content {
            border-radius: 0.5rem;
        }
        
        .modal-header {
            background-color: #343a40;
            color: white;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }
        
        .modal-body p {
            margin-bottom: 0.5rem;
        }
        
        .modal-body p strong {
            color: #343a40;
        }
        
        .modal-footer .btn {
            background-color: #ff7d00;
            border-color: #ff7d00;
            color: white;
            transition: all 0.2s;
        }
        
        .modal-footer .btn:hover {
            background-color: #e06c00;
            border-color: #e06c00;
        }

        /* Updated styles for tabs navigation */
        .status-tabs {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            border-bottom: 2px solid #343a40;
            margin-bottom: 20px;
            padding-bottom: 1px;
            background: #fff;
            width: 100%;
            -ms-overflow-style: none; /* Hide scrollbar IE and Edge */
            scrollbar-width: none; /* Hide scrollbar Firefox */
            position: relative;
        }

        /* Hide scrollbar for Chrome/Safari */
        .status-tabs::-webkit-scrollbar {
            display: none;
        }

        .status-tabs .nav-item {
            flex: 0 0 auto;
            margin-bottom: -2px;
            text-align: center;
            white-space: nowrap;
        }

        .status-tabs .nav-link {
            color: #343a40;
            background-color: transparent;
            font-weight: 500;
            padding: 12px 20px;
            border: none;
            border-radius: 0;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .status-tabs .nav-link:hover {
            color: #ff7d00;
            background-color: rgba(255, 125, 0, 0.03);
        }

        .status-tabs .nav-link.active {
            color: #ff7d00;
            font-weight: 700;
            border-bottom: 2px solid #ff7d00;
        }

        .status-badge {
            background-color: #ff7d00;
            color: white;
            font-size: 0.7rem;
            border-radius: 10px;
            padding: 1px 6px;
            margin-left: 4px;
            display: inline-block;
            position: relative;
            top: -2px;
        }

        /* Improved mobile view */
        @media (max-width: 576px) {
            .status-tabs {
                justify-content: space-between;
                padding: 0 5px;
            }
            
            .status-tabs .nav-link {
                padding: 12px 15px;
                border-radius: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                border: none;
            }
            
            .status-tabs .nav-link.active {
                border-bottom: 2px solid #ff7d00;
            }
            
            .status-tabs .nav-link i {
                font-size: 18px;
                margin-bottom: 4px;
                margin-right: 0;
            }
            
            .status-tabs .nav-link .tab-text {
                font-size: 11px;
                display: block;
            }
            
            .status-badge {
                position: absolute;
                top: 6px;
                right: 5px;
                margin: 0;
            }
            
            /* Update the active and inactive colors */
            .status-tabs .nav-link.active i,
            .status-tabs .nav-link.active .tab-text {
                color: #ff7d00;
                font-weight: 700;
            }
        }

        /* Make tabs more bottom-nav like on very small devices */
        @media (max-width: 400px) {
            .status-tabs {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                border-top: 1px solid #e9ecef;
                border-bottom: none;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                z-index: 1000;
                padding: 8px 0 5px;
            }
            
            .status-tabs .nav-item {
                flex: 1;
                max-width: 20%; /* Show at most 5 tabs, rest with horizontal scroll */
            }
            
            .status-tabs .nav-link {
                padding: 8px 5px;
            }
            
            .card-body {
                padding-bottom: 80px;
            }
        }

        /* Add this to your CSS for improved tab scrolling */
        .status-tabs {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            border-bottom: 2px solid #343a40;
            margin-bottom: 20px;
            padding-bottom: 1px;
            background: #fff;
            width: 100%;
            position: relative;
            scrollbar-width: thin;  /* Firefox */
            scroll-behavior: smooth; /* Smooth scrolling */
            -webkit-overflow-scrolling: touch; /* Better scroll on iOS */
        }

        /* Show a subtle scrollbar instead of hiding it completely */
        .status-tabs::-webkit-scrollbar {
            height: 3px;
            display: block;
        }

        .status-tabs::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .status-tabs::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 3px;
        }

        .status-tabs::-webkit-scrollbar-thumb:hover {
            background: #ccc;
        }

        /* Add scroll arrows to indicate there's more content */
        .tab-scroll-container {
            position: relative;
            width: 100%;
        }

        .tab-scroll-arrow {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 32px;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(90deg, rgba(255,255,255,0.9), rgba(255,255,255,0.3));
            border: none;
            color: #343a40;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .tab-scroll-arrow.left {
            left: 0;
            background: linear-gradient(90deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0) 100%);
        }

        .tab-scroll-arrow.right {
            right: 0;
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.9) 100%);
        }

        .tab-scroll-container:hover .tab-scroll-arrow {
            opacity: 1;
        }

        /* Make sure tabs have enough space */
        .status-tabs .nav-item {
            flex: 0 0 auto;
            margin-bottom: -2px;
            text-align: center;
            white-space: nowrap;
            padding: 0 2px; /* Small padding between tabs */
        }

        /* Modal styling */
        .modal-xl {
            max-width: 95%;
        }
        
        .modal-content {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .modal-header {
            background-color: #343a40;
            color: white;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }
        
        /* Detail card styling */
        .detail-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            background-color: #343a40;
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
            color: white;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .info-group {
            margin-bottom: 15px;
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
        .modal-status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending-approval {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-declined {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-in-process {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-ready-for-pickup {
            background-color: #e0f7fa;
            color: #0288d1;
        }
        
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        
        /* Progress tracker */
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
            font-size:
        }

        /* Payment status pills */
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-downpayment-paid {
            background-color: #e1bee7;
            color: #6a1b9a;
        }

        .status-fully-paid {
            background-color: #c8e6c9;
            color: #2e7d32;
        }

        /* Add this to handle both variations */
        .status-fully-paid, .status-fully-paid {
            background-color: #c8e6c9;
            color: #2e7d32;
        }

        /* Generic status pill style if not already defined */
        .status-pill {
            padding: 0.3rem 0.6rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }

        /* Add these styles to your CSS */
        :root {
            --shopee-orange: #ee4d2d;
            --shopee-light-orange: #fef6f5;
            --shopee-green: #26aa99;
            --border-color: #efefef;
        }

        /* Summary section styles */
        .summary-section {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 15px;
        }

        .summary-section:last-child {
            border-bottom: none;
        }

        .summary-header {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .summary-label {
            color: #757575;
            font-size: 14px;
        }

        .summary-value {
            font-weight: 500;
            text-align: right;
        }

        .total-row .summary-label,
        .total-row .summary-value {
            font-weight: 600;
            font-size: 16px;
        }

        /* Shopee style badges */
        .shopee-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .pending-badge {
            background-color: #fff3cd;
            color: #856404;
        }

        .downpayment-paid-badge, .partially-paid-badge {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .fully-paid-badge {
            background-color: #d4edda;
            color: #155724;
        }

        /* Shopee progress tracker */
        .shopee-tracking {
            margin: 30px 0;
            position: relative;
        }

        .progress-bar-wrapper {
            position: relative;
            height: 100px;
            margin-bottom: 20px;
        }

        .progress-bar {
            position: absolute;
            top: 36px;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: #e0e0e0;
            z-index: 1;
        }

        .progress-inner {
            height: 100%;
            background-color: var(--shopee-orange);
            transition: width 0.5s ease;
        }

        .progress-steps {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .progress-step {
            position: absolute;
            transform: translateX(-50%);
            text-align: center;
            width: 80px;
        }

        .step-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #fff;
            border: 2px solid #e0e0e0;
            margin: 0 auto 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #bdbdbd;
            position: relative;
            z-index: 2;
        }

        .step-label {
            font-size: 12px;
            color: #9e9e9e;
            white-space: nowrap;
        }

        .progress-step.active .step-icon {
            border-color: var(--shopee-orange);
            background-color: var(--shopee-light-orange);
            color: var(--shopee-orange);
        }

        .progress-step.active .step-label {
            color: var(--shopee-orange);
            font-weight: 500;
        }

        .progress-step.current .step-icon {
            border-color: var(--shopee-orange);
            background-color: var(--shopee-orange);
            color: #fff;
            transform: scale(1.1);
            box-shadow: 0 0 0 4px rgba(238, 77, 45, 0.2);
        }

        /* Current status card */
        .current-status-card {
            display: flex;
            align-items: center;
            background-color: var(--shopee-light-orange);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .status-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: var(--shopee-orange);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 18px;
        }

        .status-title {
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }

        .status-timestamp {
            color: #757575;
            font-size: 14px;
            margin-top: 4px;
        }

        /* Product details */
        .details-header {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 14px;
        }

        .product-card {
            display: flex;
            justify-content: space-between;
            border: 1px solid #efefef;
            padding: 15px;
            border-radius: 8px;
            background-color: #fff;
        }

        .product-title {
            font-weight: 500;
            margin-bottom: 8px;
        }

        .product-specs {
            font-size: 13px;
            color: #757575;
        }

        .product-price {
            font-weight: 600;
            color: var(--shopee-orange);
            font-size: 18px;
        }

        /* Timeline styles */
        .shopee-timeline {
            margin-top: 30px;
        }

        .timeline-list {
            list-style-type: none;
            padding-left: 8px;
            margin: 0;
            position: relative;
        }

        .timeline-list::before {
            content: '';
            position: absolute;
            top: 0;
            left: 8px;
            height: 100%;
            width: 2px;
            background-color: #e0e0e0;
        }

        .timeline-item {
            position: relative;
            padding: 0 0 20px 30px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-point {
            position: absolute;
            left: 0;
            top: 8px;
            width: 18px;
            height: 18px;
            background-color: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 50%;
            transform: translateX(-8px);
            z-index: 1;
        }

        .timeline-point.active {
            background-color: var(--shopee-orange);
            border-color: var(--shopee-orange);
        }

        .timeline-content {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 12px 15px;
        }

        .event-time {
            font-size: 12px;
            color: #757575;
        }

        .event-title {
            font-weight: 600;
            margin: 5px 0;
        }

        .event-message {
            font-size: 13px;
            color: #555;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#">JX Tailoring</a>
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
            <form method="GET" class="search-form">
                <i class="fas fa-search icon"></i>
                <input type="text" class="form-control" name="search" placeholder="Enter Order ID..." value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
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
                    mysqli_data_seek($result, 0); // Reset result pointer
                    $pending_orders = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        if ($row['order_status'] == 'pending_approval') {
                            $pending_orders[] = $row;
                        }
                    }
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
                                    <?php foreach ($pending_orders as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['order_id']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($row['order_type'])) ?></td>
                                            <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a class="btn view-btn" href="view_order.php?id=<?= htmlspecialchars($row['order_id']) ?>">
                                                        View Details
                                                    </a>
                                                    <button class="btn btn-danger cancel-order-btn" data-order-id="<?= htmlspecialchars($row['order_id']) ?>">
                                                        <i class="fas fa-times-circle me-1"></i> Cancel Order
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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
                        if ($row['order_status'] == 'approved') {
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
                                                <div class="d-flex flex-wrap gap-1">
                                                    <a class="btn view-btn" href="view_order.php?id=<?= htmlspecialchars($row['order_id']) ?>">
                                                        View Details
                                                    </a>
                                                    <?php if ($row['payment_status'] == 'pending'): ?>
                                                    <a href="payment_downpayment.php?order_id=<?= $row['order_id'] ?>" class="btn btn-success">
                                                        <i class="fas fa-credit-card me-1"></i> Pay Downpayment
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
                                                    <a href="payment_full.php?order_id=<?= $row['order_id'] ?>" class="btn btn-success ms-1">Pay Balance</a>
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
    
    // Parse order status from server
    fetch(`get_order_tracking.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
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
                </div>
                
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
                        <div class="step-label
