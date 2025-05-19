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

// Get total orders
$total_orders_query = "SELECT COUNT(*) as total FROM orders";
$total_orders_result = $conn->query($total_orders_query);
$total_orders = $total_orders_result->fetch_assoc()['total'];

// Get weekly orders
$weekly_orders_query = "SELECT COUNT(*) as weekly FROM orders 
                        WHERE created_at BETWEEN '$current_week_start 00:00:00' AND '$current_week_end 23:59:59'";
$weekly_orders_result = $conn->query($weekly_orders_query);
$weekly_orders = $weekly_orders_result->fetch_assoc()['weekly'];

// Get pending orders
$pending_orders_query = "SELECT COUNT(*) as pending FROM orders 
                         WHERE order_status = 'pending_approval' OR order_status = 'pending_payment'";
$pending_orders_result = $conn->query($pending_orders_query);
$pending_orders = $pending_orders_result->fetch_assoc()['pending'];

// Get total income
$total_income_query = "SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'";
$total_income_result = $conn->query($total_income_query);
$total_income = $total_income_result->fetch_assoc()['total'] ?? 0;

// Get weekly income
$weekly_income_query = "SELECT SUM(total_amount) as weekly FROM orders 
                        WHERE payment_status = 'paid' 
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
    <meta name="description" content="JX Tailoring Staff Dashboard">
    <meta name="author" content="">
    <link rel="icon" type="image/png" href="../image/logo.png">
    <title>JX Tailoring - Staff Dashboard</title>

    <!-- Custom fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #D98324;
            --primary-dark: #b36c1d;
            --secondary: #443627;
            --light: #EFDCAB;
            --light-hover: #e6d092;
            --white: #ffffff;
            --gray: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            color: var(--secondary);
        }
        
        /* Topbar styling */
        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: 70px;
        }
        
        .navbar a {
            color: var(--secondary) !important;
        }
        
        .topbar .dropdown-list {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Dashboard heading */
        .page-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .page-header h1 {
            font-weight: 600;
            color: var(--secondary);
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
        
        /* Charts section */
        .chart-container {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .chart-heading {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--secondary);
        }

        .chart-container canvas {
            width: 100% !important;
            height: 100% !important;
        }
        
        /* Recent orders table */
        .table-container {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .table-heading {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--secondary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .view-all-link {
            font-size: 0.9rem;
            color: var(--primary);
            text-decoration: none;
        }
        
        .order-table th {
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--gray);
            border-top: none;
        }
        
        .order-table td {
            vertical-align: middle;
            color: var(--secondary);
        }
        
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }
        
        .status-completed {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }
        
        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }
        
        /* Footer styling */
        .footer {
            background-color: white !important;
            color: var(--secondary) !important;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            font-size: 0.9rem;
        }
        
        /* Button styling */
        .btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            border-radius: 6px;
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
            transform: translateY(-2px);
        }
        
        .btn-sm {
            font-size: 0.85rem;
            padding: 0.4rem 1rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stat-card .card-body {
                padding: 1.25rem;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .stat-content h6 {
                font-size: 0.75rem;
            }
            
            .stat-content h2 {
                font-size: 1.5rem;
            }
        }
        
        /* More compact card styling */
        .stat-card .card-body {
            padding: 1rem;
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-right: 0.75rem;
            color: white;
        }
        
        .stat-content h6 {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
            color: var(--gray);
            font-weight: 500;
        }
        
        .stat-content h2 {
            font-size: 1.3rem;
            margin-bottom: 0;
            font-weight: 600;
            color: var(--secondary);
        }
        
        /* Hide trend indicators to save space */
        .trend-indicator {
            display: none;
        }
        
        @media (max-width: 1200px) {
            .stat-content h2 {
                font-size: 1.1rem;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 767px) {
            .stat-card .card-body {
                padding: 0.75rem;
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
                        <?php include 'notification.php'; ?>  <!-- Changed from 'notifications.php' to 'notification.php' -->

                        <!-- User Info -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                                </span>
                                <i class='fas fa-user-circle' style="font-size:20px;"></i>
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between page-header">
                        <h1 class="h3 mb-0">Dashboard</h1>
                        <div>
                            <form id="reportForm" class="d-flex align-items-center" action="reports.php" method="GET">
                                <div class="input-group mr-2">
                                    <input type="date" id="reportDate" name="date" class="form-control form-control-sm" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <button type="submit" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                                    <i class="fas fa-download fa-sm"></i> Generate Report
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
                                    <div class="stat-value"><?php echo number_format($pending_orders); ?></div>
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

                    <!-- Content Row - Charts -->
                    <div class="row">
                        <!-- Weekly Sales Chart -->
                        <div class="col-lg-8 mb-4">
                            <div class="chart-container" style="position: relative; height:400px;">
                                <h5 class="chart-heading">Weekly Sales Overview</h5>
                                <div style="height: 350px;">
                                    <canvas id="weeklySalesChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Order Status Chart -->
                        <div class="col-lg-4 mb-4">
                            <div class="chart-container" style="position: relative; height:400px;">
                                <h5 class="chart-heading">Order Status Distribution</h5>
                                <div style="height: 350px;">
                                    <canvas id="orderStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="table-container">
                                <div class="table-heading">
                                    <span>Recent Orders</span>
                                    <a href="orders.php" class="view-all-link">View All <i class="fas fa-arrow-right ml-1"></i></a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table order-table">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Get recent orders
                                            $recent_orders_query = "SELECT o.*, c.first_name, c.last_name 
                                                                FROM orders o
                                                                JOIN customers c ON o.customer_id = c.customer_id
                                                                ORDER BY o.created_at DESC LIMIT 5";
                                            $recent_orders_result = $conn->query($recent_orders_query);
                                            
                                            if ($recent_orders_result && $recent_orders_result->num_rows > 0) {
                                                while ($order = $recent_orders_result->fetch_assoc()) {
                                                    // Determine status badge class
                                                    $status_class = '';
                                                    switch($order['order_status']) {
                                                        case 'pending_approval':
                                                        case 'pending_payment':
                                                            $status_class = 'status-pending';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'status-completed';
                                                            break;
                                                        case 'cancelled':
                                                        case 'declined':
                                                            $status_class = 'status-cancelled';
                                                            break;
                                                    }
                                                    
                                                    echo '<tr>';
                                                    echo '<td>#' . htmlspecialchars($order['order_id']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . '</td>';
                                                    echo '<td>' . date('M j, Y', strtotime($order['created_at'])) . '</td>';
                                                    echo '<td>₱' . number_format($order['total_amount'], 2) . '</td>';
                                                    echo '<td><span class="status-badge ' . $status_class . '">' . 
                                                         ucwords(str_replace('_', ' ', $order['order_status'])) . '</span></td>';
                                                    echo '<td>
                                                            <a href="view_order.php?id=' . $order['order_id'] . '" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                         </td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="6" class="text-center">No recent orders found</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of Main Content -->

                <!-- Footer -->
                <footer class="footer text-center py-3">
                    <span>Copyright &copy; JX Tailoring and Printing Services <?php echo date('Y'); ?></span>
                </footer>
                <!-- End of Footer -->
            </div>
            <!-- End of Content Wrapper -->
        </div>
    </div>
    <!-- End of Page Wrapper -->

    <!-- JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    
    <!-- Chart Initialization -->
    <script>
        // Weekly Sales Chart
        var weeklySalesCtx = document.getElementById('weeklySalesChart').getContext('2d');
        var weeklySalesChart = new Chart(weeklySalesCtx, {
            type: 'line',
            data: {
                labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                datasets: [{
                    label: 'Orders',
                    data: [5, 8, 12, 7, 10, 15, 9],
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: '#4e73df',
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4e73df',
                    borderWidth: 2,
                    tension: 0.3
                }, {
                    label: 'Revenue',
                    data: [3500, 5200, 7800, 4500, 6300, 8900, 6000],
                    backgroundColor: 'rgba(217, 131, 36, 0.05)',
                    borderColor: '#D98324',
                    pointBackgroundColor: '#D98324',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#D98324',
                    borderWidth: 2,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 10,
                        top: 10,
                        bottom: 10
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Order Status Chart
        var orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        var orderStatusChart = new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Pending', 'Cancelled'],
                datasets: [{
                    data: [65, 15, 12, 8],
                    backgroundColor: ['#28a745', '#4e73df', '#ffc107', '#dc3545'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                },
                cutout: '70%'
            }
        });

        // Form submission handler for report generation
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const dateValue = document.getElementById('reportDate').value;
            if (!dateValue) {
                e.preventDefault();
                alert('Please select a date for the report');
            }
        });
    </script>
</body>
</html>
