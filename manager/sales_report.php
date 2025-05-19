<?php
session_start();
include '../db.php';

// If the user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Default filter values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : 'all';
$order_status = isset($_GET['order_status']) ? $_GET['order_status'] : 'all';
$order_type = isset($_GET['order_type']) ? $_GET['order_type'] : 'all';

// Build WHERE clause based on filters
$where_conditions = [];
$params = [];
$types = "";

// Date range filter
$where_conditions[] = "o.created_at BETWEEN ? AND CONCAT(?, ' 23:59:59')";
$params[] = $start_date;
$params[] = $end_date;
$types .= "ss";

// Payment status filter
if ($payment_status !== 'all') {
    $where_conditions[] = "o.payment_status = ?";
    $params[] = $payment_status;
    $types .= "s";
}

// Order status filter
if ($order_status !== 'all') {
    $where_conditions[] = "o.order_status = ?";
    $params[] = $order_status;
    $types .= "s";
}

// Order type filter
if ($order_type !== 'all') {
    $where_conditions[] = "o.order_type = ?";
    $params[] = $order_type;
    $types .= "s";
}

// Combine conditions
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// The main query with filters
$query = "SELECT o.*, 
          c.first_name AS customer_fname, c.last_name AS customer_lname,
          s.first_name AS staff_fname, s.last_name AS staff_lname,
          m.first_name AS manager_fname, m.last_name AS manager_lname
          FROM orders o
          LEFT JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN users s ON o.staff_id = s.user_id
          LEFT JOIN users m ON o.manager_id = m.user_id
          $where_clause
          ORDER BY o.created_at DESC";

// Prepare and execute the statement
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get summary statistics
$summary_query = "SELECT 
                COUNT(*) AS total_orders,
                SUM(total_amount) AS total_sales,
                SUM(downpayment_amount) AS total_downpayments,
                SUM(IF(payment_status = 'fully_paid', total_amount, 0)) AS total_paid,
                SUM(IF(payment_status != 'fully_paid', total_amount - downpayment_amount, 0)) AS total_receivables,
                COUNT(IF(order_status = 'completed', 1, NULL)) AS completed_orders,
                COUNT(IF(order_type = 'sublimation', 1, NULL)) AS sublimation_count,
                COUNT(IF(order_type = 'tailoring', 1, NULL)) AS tailoring_count
                FROM orders o
                $where_clause";

$stmt = $conn->prepare($summary_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

// Get payment method breakdown
$payment_method_query = "SELECT 
                        payment_method,
                        COUNT(*) AS order_count,
                        SUM(total_amount) AS total_amount
                        FROM orders o
                        $where_clause
                        GROUP BY payment_method";

$stmt = $conn->prepare($payment_method_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$payment_methods = $stmt->get_result();

// Get order status breakdown
$status_query = "SELECT 
                order_status,
                COUNT(*) AS order_count
                FROM orders o
                $where_clause
                GROUP BY order_status";

$stmt = $conn->prepare($status_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$order_statuses = $stmt->get_result();

// Get all possible order statuses for filter dropdown
$all_statuses_query = "SELECT DISTINCT order_status FROM orders";
$all_statuses_result = mysqli_query($conn, $all_statuses_query);

if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
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
    <title>JXT Sublimator</title>

    <!-- Custom fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    
    <!-- Date Range Picker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #443627;
        }

        .sidebar .nav-item .nav-link {
            color: #EFDCAB !important;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-item .nav-link:hover {
            color: #D98324 !important;
            background-color: rgba(239, 220, 171, 0.1);
        }

        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar a {
            color: #443627 !important;
        }

        .card {
            border-left: 5px solid #D98324 !important;
            background-color: #EFDCAB !important;
            color: #443627 !important;
        }        .card {
            border: none !important;
            border-radius: 15px !important;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05) !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12) !important;
        }

        .card.primary-card {
            border-left: 5px solid #D98324 !important;
            background-color: #EFDCAB !important;
            color: #443627 !important;
        }

        .btn-primary {
            background-color: #D98324 !important;
            border-color: #D98324 !important;
            box-shadow: 0 4px 10px rgba(217, 131, 36, 0.2) !important;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #c37420 !important;
            border-color: #c37420 !important;
            box-shadow: 0 6px 14px rgba(217, 131, 36, 0.3) !important;
        }
        
        .btn-outline-primary {
            color: #D98324 !important;
            border-color: #D98324 !important;
        }

        .btn-outline-primary:hover {
            background-color: #D98324 !important;
            color: white !important;
        }

        .text-primary {
            color: #D98324 !important;
        }

        .text-gray-800 {
            color: #443627 !important;
        }

        .footer {
            background-color: #443627 !important;
            color: #EFDCAB !important;
        }        /* Enhanced table styling */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .table {
            margin: 0;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            color: #443627;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(217, 131, 36, 0.05) !important;
        }
        
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            font-size: 0.85rem;
            color: #555;
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }
        
        /* Status badges */
        .badge {
            font-size: 0.7rem;
            font-weight: 500;
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
        }
        
        /* Filter section styling */
        .filters-section {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .filters-section .form-control {
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,0.1);
            padding: 8px 15px;
            font-size: 0.85rem;
        }
        
        .filters-section .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 5px;
            color: #443627;
        }
        
        /* Summary cards */
        .summary-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .summary-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #D98324;
        }
        
        .summary-card .label {
            color: #6c757d;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .summary-card .icon {
            font-size: 2rem;
            opacity: 0.2;
            position: absolute;
            top: 20px;
            right: 20px;
            color: #443627;
        }
        
        /* Chart containers */
        .chart-container {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .chart-title {
            font-size: 1rem;
            font-weight: 600;
            color: #443627;
            margin-bottom: 15px;
        }

        /* Minimal Table Styles */
        .table-responsive {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }

        .table {
            margin: 0;
            border: none;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: #443627;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px;
            border: none;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border: none;
            font-size: 0.85rem;
            color: #555;
        }

        .table tbody tr {
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Minimal Badge Design */
        .badge {
            font-size: 0.7rem;
            font-weight: 500;
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
        }

        /* Minimal DataTables Design */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #eee;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.85rem;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin: 15px 0;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: none !important;
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: 4px;
            background: transparent !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f8f9fa !important;
            border: none !important;
            color: #443627 !important;
        }        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #f8f9fa !important;
            border: none !important;
            color: #443627 !important;
            font-weight: 600;
        }
        
        /* DataTables customization */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 5px 10px;
            margin-left: 5px;
        }
        
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 5px 10px;
        }
        
        .dataTables_wrapper .dataTables_info {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        /* Export buttons */
        .dt-buttons {
            margin-bottom: 15px;
        }
        
        .dt-button {
            background-color: #f8f9fa !important;
            border-color: #ddd !important;
            color: #444 !important;
            font-size: 0.8rem !important;
            padding: 5px 15px !important;
            border-radius: 6px !important;
            box-shadow: none !important;
            transition: all 0.2s ease !important;
        }
        
        .dt-button:hover {
            background-color: #e9ecef !important;
            border-color: #c8c8c8 !important;
            color: #000 !important;
        }
        
        /* Pagination styling */
        .page-link {
            color: #D98324;
            border-color: #dee2e6;
        }
        
        .page-item.active .page-link {
            background-color: #D98324;
            border-color: #D98324;
        }
        
        .page-link:hover {
            color: #c37420;
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .filters-section {
                padding: 15px;
            }
            
            .table-container {
                padding: 10px;
                overflow-x: auto;
            }
            
            .summary-card {
                margin-bottom: 15px;
            }
            
            .summary-card .value {
                font-size: 1.5rem;
            }
            
            .filters-section .col-auto {
                margin-bottom: 15px;
            }
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
                <div class="container-fluid">                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800" style="font-weight: bold; text-shadow: 1px 1px 2px rgba(255,255,255,0.5);">
                            Sales Report
                        </h1>
                        <!-- Export buttons will be added by DataTables -->
                    </div>

                    <!-- Filters Section -->
                    <div class="filters-section mb-4">
                        <h5 class="mb-3">Filter Sales Data</h5>
                        <form method="get" action="" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Date Range</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="daterange" name="date_range" value="<?php echo $start_date . ' - ' . $end_date; ?>">
                                    <input type="hidden" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                    <input type="hidden" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Payment Status</label>
                                <select class="form-select" name="payment_status">
                                    <option value="all" <?php echo $payment_status == 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="pending" <?php echo $payment_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="downpayment_paid" <?php echo $payment_status == 'downpayment_paid' ? 'selected' : ''; ?>>Downpayment Paid</option>
                                    <option value="fully_paid" <?php echo $payment_status == 'fully_paid' ? 'selected' : ''; ?>>Fully Paid</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Order Status</label>
                                <select class="form-select" name="order_status">
                                    <option value="all">All</option>
                                    <?php
                                    if ($all_statuses_result && mysqli_num_rows($all_statuses_result) > 0) {
                                        while ($status = mysqli_fetch_assoc($all_statuses_result)) {
                                            $selected = $order_status == $status['order_status'] ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($status['order_status']) . "' $selected>" . 
                                                 ucfirst(str_replace('_', ' ', $status['order_status'])) . 
                                                 "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Order Type</label>
                                <select class="form-select" name="order_type">
                                    <option value="all" <?php echo $order_type == 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="sublimation" <?php echo $order_type == 'sublimation' ? 'selected' : ''; ?>>Sublimation</option>
                                    <option value="tailoring" <?php echo $order_type == 'tailoring' ? 'selected' : ''; ?>>Tailoring</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i> Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Summary Statistics Cards -->
                    <div class="row mb-4">
                        <!-- Total Sales -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="summary-card">
                                <div class="icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="label">Total Sales</div>
                                <div class="value">₱<?php echo number_format($summary['total_sales'] ?? 0, 2); ?></div>
                                <div class="text-muted small"><?php echo number_format($summary['total_orders'] ?? 0); ?> orders</div>
                            </div>
                        </div>
                        
                        <!-- Collected Payments -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="summary-card">
                                <div class="icon">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                                <div class="label">Collected Payments</div>
                                <div class="value">₱<?php echo number_format(($summary['total_downpayments'] ?? 0) + ($summary['total_paid'] ?? 0), 2); ?></div>
                                <div class="text-muted small"><?php echo number_format($summary['completed_orders'] ?? 0); ?> completed orders</div>
                            </div>
                        </div>
                        
                        <!-- Receivables -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="summary-card">
                                <div class="icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="label">Receivables</div>
                                <div class="value">₱<?php echo number_format($summary['total_receivables'] ?? 0, 2); ?></div>
                                <div class="text-muted small">From <?php echo number_format($summary['total_orders'] - ($summary['completed_orders'] ?? 0)); ?> pending orders</div>
                            </div>
                        </div>
                        
                        <!-- Order Type Breakdown -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="summary-card">
                                <div class="icon">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <div class="label">Order Types</div>
                                <div class="d-flex align-items-center mt-2">
                                    <div>
                                        <div class="h5 mb-0 me-3">Sublimation: <?php echo number_format($summary['sublimation_count'] ?? 0); ?></div>
                                        <div class="h5 mb-0">Tailoring: <?php echo number_format($summary['tailoring_count'] ?? 0); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <!-- Payment Methods Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="chart-container">
                                <h5 class="chart-title">Payment Methods</h5>
                                <canvas id="paymentMethodsChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Order Status Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="chart-container">
                                <h5 class="chart-title">Order Status Distribution</h5>
                                <canvas id="orderStatusChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Report Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Detailed Sales Report</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="salesTable">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Order Type</th>
                                            <th>Total (₱)</th>
                                            <th>Downpayment (₱)</th>
                                            <th>Payment Method</th>
                                            <th>Order Status</th>
                                            <th>Payment Status</th>
                                            <th>Staff</th>                                            <th>Date Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (isset($result) && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) { ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['customer_fname'] . ' ' . $row['customer_lname']); ?></td><td><?php echo ucfirst(htmlspecialchars($row['order_type'])); ?></td>
                                                    <td><?php echo number_format($row['total_amount'], 2); ?></td>
                                                    <td><?php echo number_format($row['downpayment_amount'], 2); ?></td>
                                                    <td><?php echo ucfirst(htmlspecialchars($row['payment_method'])); ?></td>
                                                    <td>
                                                        <?php 
                                                        $status_class = match($row['order_status']) {
                                                            'pending_approval' => 'bg-warning',
                                                            'approved' => 'bg-info',
                                                            'in_process' => 'bg-primary',
                                                            'ready_for_pickup' => 'bg-info',
                                                            'completed' => 'bg-success',
                                                            'declined' => 'bg-danger',
                                                            default => 'bg-secondary'
                                                        };
                                                        $status_text = str_replace('_', ' ', ucfirst($row['order_status']));
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $payment_class = match($row['payment_status']) {
                                                            'pending' => 'bg-warning',
                                                            'downpayment_paid' => 'bg-info',
                                                            'fully_paid' => 'bg-success',
                                                            default => 'bg-secondary'
                                                        };
                                                        $payment_text = str_replace('_', ' ', ucfirst($row['payment_status']));
                                                        ?>
                                                        <span class="badge <?php echo $payment_class; ?>"><?php echo $payment_text; ?></span>
                                                    </td>
                                                    <td><?php echo $row['staff_fname'] ? htmlspecialchars($row['staff_fname'] . ' ' . $row['staff_lname']) : 'N/A'; ?></td>                                                    <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                                    <td>
                                                        <a href="../staff/view_order.php?id=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-primary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                        <?php }
                                        } else {
                                            echo "<tr><td colspan='11' class='text-center'>No orders found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
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
    <!-- End of Page Wrapper -->    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- Date Range Picker -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#salesTable').DataTable({
                order: [[9, 'desc']], // Sort by date created by default
                pageLength: 5,
                responsive: true,
                lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
            
            // Initialize Date Range Picker
            $('#daterange').daterangepicker({
                startDate: moment('<?php echo $start_date; ?>'),
                endDate: moment('<?php echo $end_date; ?>'),
                locale: {
                    format: 'YYYY-MM-DD'
                }
            }, function(start, end) {
                $('#start_date').val(start.format('YYYY-MM-DD'));
                $('#end_date').val(end.format('YYYY-MM-DD'));
            });
            
            // Initialize Payment Method Chart
            const paymentMethodsChartCanvas = document.getElementById('paymentMethodsChart');
            if (paymentMethodsChartCanvas) {
                const paymentMethodsData = {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#D98324', '#443627', '#EFDCAB', '#886F4F', '#A67F5D']
                    }]
                };
                
                <?php
                if ($payment_methods && $payment_methods->num_rows > 0) {
                    $payment_methods->data_seek(0);
                    while ($method = $payment_methods->fetch_assoc()) {
                        echo "paymentMethodsData.labels.push('" . ucfirst($method['payment_method']) . "');\n";
                        echo "paymentMethodsData.datasets[0].data.push(" . $method['order_count'] . ");\n";
                    }
                }
                ?>
                
                new Chart(paymentMethodsChartCanvas, {
                    type: 'pie',
                    data: paymentMethodsData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Initialize Order Status Chart
            const orderStatusChartCanvas = document.getElementById('orderStatusChart');
            if (orderStatusChartCanvas) {
                const orderStatusData = {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#D98324', '#443627', '#EFDCAB', '#886F4F', '#A67F5D', '#664E38']
                    }]
                };
                
                <?php
                if ($order_statuses && $order_statuses->num_rows > 0) {
                    $order_statuses->data_seek(0);
                    while ($status = $order_statuses->fetch_assoc()) {
                        $status_label = ucfirst(str_replace('_', ' ', $status['order_status']));
                        echo "orderStatusData.labels.push('" . $status_label . "');\n";
                        echo "orderStatusData.datasets[0].data.push(" . $status['order_count'] . ");\n";
                    }
                }
                ?>
                
                new Chart(orderStatusChartCanvas, {
                    type: 'doughnut',
                    data: orderStatusData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
