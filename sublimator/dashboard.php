<?php
session_start();
include_once '../db.php';

// If the user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get current date info
$today = date('Y-m-d');
$current_week_start = date('Y-m-d', strtotime('monday this week'));
$current_week_end = date('Y-m-d', strtotime('sunday this week'));

// Get total orders for sublimation
$total_orders_query = "SELECT COUNT(*) as total FROM orders WHERE order_type = 'sublimation'";
$total_orders_result = $conn->query($total_orders_query);
$total_orders = $total_orders_result->fetch_assoc()['total'] ?? 0;

// Get weekly orders for sublimation
$weekly_orders_query = "SELECT COUNT(*) as weekly FROM orders 
                       WHERE order_type = 'sublimation' 
                       AND created_at BETWEEN '$current_week_start 00:00:00' AND '$current_week_end 23:59:59'";
$weekly_orders_result = $conn->query($weekly_orders_query);
$weekly_orders = $weekly_orders_result->fetch_assoc()['weekly'] ?? 0;

// Get templates created count
$templates_query = "SELECT COUNT(template_id) as total FROM templates";
$templates_result = $conn->query($templates_query);
$templates_created = $templates_result->fetch_assoc()['total'] ?? 0;

// Get total income from sublimation
$total_income_query = "SELECT SUM(total_amount) as total FROM orders 
                      WHERE order_type = 'sublimation' AND payment_status = 'paid'";
$total_income_result = $conn->query($total_income_query);
$total_income = $total_income_result->fetch_assoc()['total'] ?? 0;

// Get weekly income from sublimation
$weekly_income_query = "SELECT SUM(total_amount) as weekly FROM orders 
                       WHERE order_type = 'sublimation' AND payment_status = 'paid' 
                       AND created_at BETWEEN '$current_week_start 00:00:00' AND '$current_week_end 23:59:59'";
$weekly_income_result = $conn->query($weekly_income_query);
$weekly_income = $weekly_income_result->fetch_assoc()['weekly'] ?? 0;
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

    <!-- Bootstrap icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
        }
        
        /* Main content background */
        #content {
            background-color: #f8f9fc;
        }
        
        /* Dashboard heading */
        .page-header {
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .page-header h1 {
            font-weight: 700;
            color: #443627;
        }
        
        /* Card styling */
        .stat-card {
            border: none !important;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .card-accent-primary {
            border-left: 5px solid #D98324 !important;
            background: linear-gradient(to right, #EFDCAB, #FFFFFF) !important;
        }
        
        .card-accent-secondary {
            border-left: 5px solid #443627 !important;
            background: linear-gradient(to right, #EFDCAB, #FFFFFF) !important;
        }
        
        .card-accent-success {
            border-left: 5px solid #28a745 !important;
            background: linear-gradient(to right, #EFDCAB, #FFFFFF) !important;
        }
        
        .card-accent-info {
            border-left: 5px solid #17a2b8 !important;
            background: linear-gradient(to right, #EFDCAB, #FFFFFF) !important;
        }
        
        .card-accent-warning {
            border-left: 5px solid #ffc107 !important;
            background: linear-gradient(to right, #EFDCAB, #FFFFFF) !important;
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
            background-color: #D98324;
            box-shadow: 0 4px 10px rgba(217, 131, 36, 0.3);
        }
        
        .bg-primary-gradient {
            background: linear-gradient(45deg, #D98324, #E9A251);
        }
        
        .bg-secondary-gradient {
            background: linear-gradient(45deg, #443627, #5E4C36);
        }
        
        .bg-success-gradient {
            background: linear-gradient(45deg, #28a745, #48c768);
        }
        
        .bg-info-gradient {
            background: linear-gradient(45deg, #17a2b8, #36c2d8);
        }
        
        .bg-warning-gradient {
            background: linear-gradient(45deg, #ffc107, #ffda6a);
        }
        
        .stat-label {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            color: #443627;
            font-weight: 600;
            opacity: 0.8;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #443627;
        }
        
        .stat-description {
            font-size: 0.82rem;
            color: #443627;
            opacity: 0.7;
        }
        
        /* Charts section */
        .chart-container {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            border-top: 5px solid #D98324;
        }
        
        .chart-heading {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #443627;
        }
        
        /* Navbar styling */
        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: 70px;
        }
        
        .navbar a {
            color: #443627 !important;
        }
        
        /* Button styling */
        .btn-primary {
            background-color: #D98324 !important;
            border-color: #D98324 !important;
            border-radius: 6px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(217, 131, 36, 0.3);
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #443627 !important;
            border-color: #443627 !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(68, 54, 39, 0.35);
        }
        
        /* Date selector */
        .date-selector {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 0.375rem 0.75rem;
            background-color: white;
            color: #443627;
        }
        
        /* Footer styling */
        .footer {
            background-color: white !important; 
            color: #443627 !important;
            border-top: 1px solid #e9ecef;
            font-weight: 500;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stat-value {
                font-size: 1.5rem;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            
            .navbar {
                height: auto;
                padding: 0.5rem 1rem;
            }
        }
        
        /* Modern Stat Cards */
        .stat-cards-row {
            margin-bottom: 2rem;
        }
        
        .stat-card-container {
            padding: 0 8px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            height: 100%;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .stat-icon i {
            font-size: 20px;
            color: white;
        }
        
        .stat-content {
            flex-grow: 1;
        }
        
        .stat-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: #6c757d;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #343a40;
        }
        
        /* Card-specific styles */
        #total-orders {
            border-left: 4px solid #f39c12;
        }
        
        #total-orders .stat-icon {
            background-color: #f39c12;
        }
        
        #weekly-orders {
            border-left: 4px solid #3498db;
        }
        
        #weekly-orders .stat-icon {
            background-color: #3498db;
        }
        
        #pending-orders {
            border-left: 4px solid #9b59b6;
        }
        
        #pending-orders .stat-icon {
            background-color: #9b59b6;
        }
        
        #total-income {
            border-left: 4px solid #2ecc71;
        }
        
        #total-income .stat-icon {
            background-color: #2ecc71;
        }
        
        #weekly-income {
            border-left: 4px solid #1abc9c;
        }
        
        #weekly-income .stat-icon {
            background-color: #1abc9c;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .stat-cards-row {
                flex-wrap: wrap;
            }
            
            .stat-card-container {
                flex: 0 0 33.333%;
                max-width: 33.333%;
                margin-bottom: 15px;
            }
        }
        
        @media (max-width: 992px) {
            .stat-card-container {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        
        @media (max-width: 576px) {
            .stat-card-container {
                flex: 0 0 100%;
                max-width: 100%;
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
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between page-header">
                        <h1 class="h3 mb-0">Dashboard</h1>
                        <div class="d-flex align-items-center">
                            <form id="reportForm" class="d-flex align-items-center mr-2" action="reports.php" method="GET">
                                <input type="date" id="reportDate" name="date" class="date-selector mr-2" 
                                       value="<?php echo date('Y-m-d'); ?>">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-download fa-sm mr-1"></i> Generate Report
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Modern Stat Cards -->
                    <div class="row stat-cards-row">
                        <!-- Total Orders Card -->
                        <div class="col stat-card-container">
                            <div class="stat-card" id="total-orders">
                                <div class="stat-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label">TOTAL ORDERS</div>
                                    <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Weekly Orders Card -->
                        <div class="col stat-card-container">
                            <div class="stat-card" id="weekly-orders">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-week"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label">WEEKLY ORDERS</div>
                                    <div class="stat-value"><?php echo number_format($weekly_orders); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Orders Card -->
                        <div class="col stat-card-container">
                            <div class="stat-card" id="pending-orders">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label">PENDING ORDERS</div>
                                    <div class="stat-value"><?php echo number_format($templates_created); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Income Card -->
                        <div class="col stat-card-container">
                            <div class="stat-card" id="total-income">
                                <div class="stat-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label">TOTAL INCOME</div>
                                    <div class="stat-value">₱<?php echo number_format($total_income, 2); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Weekly Income Card -->
                        <div class="col stat-card-container">
                            <div class="stat-card" id="weekly-income">
                                <div class="stat-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label">WEEKLY INCOME</div>
                                    <div class="stat-value">₱<?php echo number_format($weekly_income, 2); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rest of your dashboard content -->
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
    <!-- End of Page Wrapper -->
</body>
</html>
