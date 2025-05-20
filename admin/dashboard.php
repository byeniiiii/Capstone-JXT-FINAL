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
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

// Get total revenue
$total_revenue_query = "SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'";
$total_revenue_result = $conn->query($total_revenue_query);
$total_revenue = $total_revenue_result->fetch_assoc()['total'] ?? 0;

// Get monthly revenue
$monthly_revenue_query = "SELECT SUM(total_amount) as monthly FROM orders 
                          WHERE payment_status = 'paid' 
                          AND created_at BETWEEN '$current_month_start 00:00:00' AND '$current_month_end 23:59:59'";
$monthly_revenue_result = $conn->query($monthly_revenue_query);
$monthly_revenue = $monthly_revenue_result->fetch_assoc()['monthly'] ?? 0;

// Get total orders
$total_orders_query = "SELECT COUNT(*) as total FROM orders";
$total_orders_result = $conn->query($total_orders_query);
$total_orders = $total_orders_result->fetch_assoc()['total'];

// Get monthly orders
$monthly_orders_query = "SELECT COUNT(*) as monthly FROM orders 
                        WHERE created_at BETWEEN '$current_month_start 00:00:00' AND '$current_month_end 23:59:59'";
$monthly_orders_result = $conn->query($monthly_orders_query);
$monthly_orders = $monthly_orders_result->fetch_assoc()['monthly'];

// Get order type breakdown
$order_types_query = "SELECT order_type, COUNT(*) as count FROM orders GROUP BY order_type";
$order_types_result = $conn->query($order_types_query);
$order_types = [];
while ($row = $order_types_result->fetch_assoc()) {
    $order_types[$row['order_type']] = $row['count'];
}

// Get total income
$total_income_query = "SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'fully_paid'";
$total_income_result = $conn->query($total_income_query);
$total_income = $total_income_result->fetch_assoc()['total'] ?? 0;

// Get monthly income
$monthly_income_query = "SELECT SUM(total_amount) as monthly FROM orders 
                        WHERE payment_status = 'fully_paid' 
                        AND created_at BETWEEN '$current_month_start 00:00:00' AND '$current_month_end 23:59:59'";
$monthly_income_result = $conn->query($monthly_income_query);
$monthly_income = $monthly_income_result->fetch_assoc()['monthly'] ?? 0;

// Get order status distribution
$status_query = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
$status_result = $conn->query($status_query);
$statuses = [];
while ($row = $status_result->fetch_assoc()) {
    $statuses[$row['order_status']] = $row['count'];
}

// Get daily order trends for chart (last 30 days)
$thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
$daily_orders_query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                       FROM orders 
                       WHERE created_at >= '$thirty_days_ago 00:00:00' 
                       GROUP BY DATE(created_at) 
                       ORDER BY date ASC";
$daily_orders_result = $conn->query($daily_orders_query);
$daily_dates = [];
$daily_counts = [];
while ($row = $daily_orders_result->fetch_assoc()) {
    $daily_dates[] = date('M d', strtotime($row['date']));
    $daily_counts[] = $row['count'];
}

// Get recent orders
$recent_orders_query = "SELECT o.order_id, CONCAT(c.first_name, ' ', c.last_name) as customer_name, 
                        o.order_type, o.order_status 
                        FROM orders o
                        LEFT JOIN customers c ON o.customer_id = c.customer_id
                        ORDER BY o.created_at DESC LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_query);
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
    <title>JXT Admin</title>

    <!-- Custom fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            color: #5a5c69;
            overflow-x: hidden;
        }

        /* Remove all sidebar styling since it's handled in sidebar.php */
        /* Keep only the main content styling */
        
        
        .card {
            border: none !important;
            border-radius: 0.35rem;
            background-color: #ffffff !important;
            color: #5a5c69 !important;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1) !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15) !important;
        }

        .card-header {
            background-color: #f8f9fc !important;
            border-bottom: 1px solid #e3e6f0;
        }

        .btn-primary {
            background-color: #4e73df !important;
            border-color: #4e73df !important;
            box-shadow: 0 0.125rem 0.25rem 0 rgba(58, 59, 69, 0.2) !important;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #2e59d9 !important;
            border-color: #2653d4 !important;
            transform: translateY(-1px);
        }

        .text-primary {
            color: #4e73df !important;
        }

        .text-gray-800 {
            color: #5a5c69 !important;
        }

        .footer {
            background-color: #f8f9fc !important;
            color: #858796 !important;
            border-top: 1px solid #e3e6f0;
            font-size: 0.85rem;
        }

        /* Card highlight colors */
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }

        /* Modern table styling */
        .table {
            color: #5a5c69;
        }

        .table-bordered {
            border: 1px solid #e3e6f0;
        }

        .table th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            font-weight: 600;
        }

        /* Status badges */
        .badge {
            font-weight: 600;
            padding: 0.35em 0.65em;
            border-radius: 0.35rem;
        }

        .badge.bg-light {
            background-color: #f8f9fc !important;
            color: #5a5c69;
            border: 1px solid #e3e6f0;
        }

        .badge.bg-warning {
            background-color: #f6c23e !important;
            color: #fff;
        }

        .badge.bg-danger {
            background-color: #e74a3b !important;
        }

        .badge.bg-info {
            background-color: #36b9cc !important;
        }

        .badge.bg-primary {
            background-color: #4e73df !important;
        }

        .badge.bg-success {
            background-color: #1cc88a !important;
        }

        .badge.bg-secondary {
            background-color: #858796 !important;
        }

        /* Animated counters */
        .counter-animation {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Chart container */
        .chart-container {
            position: relative;
            margin: auto;
        }

        /* Card icons */
        .card-icon {
            color: #dddfeb;
            font-size: 2rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card {
                margin-bottom: 1rem;
            }
        }

        /* Important: Add this line to ensure sidebar styles remain intact */
        #sidebar-wrapper, .sidebar { background-color: #4e73df !important; }
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800" style="font-weight: bold; text-shadow: 1px 1px 2px #fff;">Admin Dashboard</h1>
                        <a href="sales_report.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-chart-line fa-sm text-white-50"></i> View Sales Report
                        </a>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Total Orders Card -->
                        <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeIn">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Orders</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter-animation"><?php echo number_format($total_orders); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shopping-bag fa-2x card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <!-- Monthly Orders Card -->
                         <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.1s;">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Orders This Month</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter-animation"><?php echo number_format($monthly_orders); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Revenue Card -->
                        <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.2s;">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Revenue</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter-animation">₱<?php echo number_format($total_income, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-coins fa-2x card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Revenue Card -->
                        <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.3s;">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Revenue This Month</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter-animation">₱<?php echo number_format($monthly_income, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Orders Line Chart -->
                        <div class="col-xl-8 col-lg-7 animate__animated animate__fadeIn" style="animation-delay: 0.4s;">
                            <div class="card shadow mb-4">
                                <!-- Card Header - Dropdown -->
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Daily Orders (Last 30 Days)</h6>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-container" style="height: 400px; position: relative;">
                                        <canvas id="ordersChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Status Distribution -->
                        <div class="col-xl-4 col-lg-5 animate__animated animate__fadeIn" style="animation-delay: 0.5s;">
                            <div class="card shadow mb-4">
                                <!-- Card Header - Dropdown -->
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Order Status Distribution</h6>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-container" style="height: 400px; position: relative;">
                                        <canvas id="orderStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Order Type Distribution -->
                        <div class="col-xl-4 col-lg-5 animate__animated animate__fadeIn" style="animation-delay: 0.6s;">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Order Type Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height: 300px; position: relative;">
                                        <canvas id="orderTypeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Orders -->
                        <div class="col-xl-8 col-lg-7 animate__animated animate__fadeIn" style="animation-delay: 0.7s;">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Customer</th>
                                                    <th>Type</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($order = $recent_orders_result->fetch_assoc()) { ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                        <td><?php echo ucfirst(htmlspecialchars($order['order_type'])); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo match($order['order_status']) {
                                                                    'pending_approval' => 'warning',
                                                                    'declined' => 'danger',
                                                                    'approved' => 'info',
                                                                    'forward_to_sublimator' => 'info',
                                                                    'in_process' => 'info',
                                                                    'printing_done' => 'info',
                                                                    'ready_for_pickup' => 'primary',
                                                                    'completed' => 'success',
                                                                    default => 'secondary'
                                                                };
                                                            ?>">
                                                                <?php echo str_replace('_', ' ', ucfirst($order['order_status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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
    <!-- End of Page Wrapper -->                    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <script>
        // Utility function for random colors with high lightness
        function generatePastelColor() {
            const hue = Math.floor(Math.random() * 360);
            return `hsla(${hue}, 70%, 80%, 0.7)`;
        }

        // Updated chart theme
        Chart.defaults.font.family = "'Poppins', sans-serif";
        Chart.defaults.color = "#5a5c69";
        
        // Daily orders chart with modern styling
        const ordersChartCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersChartCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($daily_dates); ?>,
                datasets: [{
                    label: 'Daily Orders',
                    data: <?php echo json_encode($daily_counts); ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "#fff",
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointBorderWidth: 2,
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            padding: 10,
                            precision: 0
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineColor: "rgb(234, 236, 244)"
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            padding: 10
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        titleMarginBottom: 10,
                        titleColor: "#6e707e",
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: false,
                        caretPadding: 10,
                        callbacks: {
                            label: function(context) {
                                return 'Orders: ' + context.parsed.y;
                            }
                        }
                    }
                }
            }
        });

        // Order status chart with modern styling
        const statusChartCtx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(statusChartCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    'Pending Approval', 
                    'Approved', 
                    'In Process', 
                    'Forward to Sublimator',
                    'Ready for Pickup',
                    'Completed',
                    'Declined'
                ],
                datasets: [{
                    data: [
                        <?php echo $statuses['pending_approval'] ?? 0; ?>,
                        <?php echo $statuses['approved'] ?? 0; ?>,
                        <?php echo $statuses['in_process'] ?? 0; ?>,
                        <?php echo $statuses['forward_to_sublimator'] ?? 0; ?>,
                        <?php echo $statuses['ready_for_pickup'] ?? 0; ?>,
                        <?php echo $statuses['completed'] ?? 0; ?>,
                        <?php echo $statuses['declined'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        '#f6c23e', // warning - pending
                        '#36b9cc', // info - approved
                        '#858796', // secondary - in process
                        '#4e73df', // primary - forward to sublimator
                        '#8540f5', // indigo - ready for pickup
                        '#1cc88a', // success - completed
                        '#e74a3b'  // danger - declined
                    ],
                    hoverBackgroundColor: [
                        '#f4b619',
                        '#2ca8bf',
                        '#717384',
                        '#2e59d9',
                        '#7032d6',
                        '#17a673',
                        '#d52a1a'
                    ],
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        titleColor: "#6e707e",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: true
                    }
                }
            }
        });

        // Order type chart with modern styling
        const typeChartCtx = document.getElementById('orderTypeChart').getContext('2d');
        new Chart(typeChartCtx, {
            type: 'pie',
            data: {
                labels: ['Tailoring', 'Sublimation'],
                datasets: [{
                    data: [
                        <?php echo $order_types['tailoring'] ?? 0; ?>,
                        <?php echo $order_types['sublimation'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a'
                    ],
                    hoverBackgroundColor: [
                        '#2e59d9',
                        '#17a673'
                    ],
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        titleColor: "#6e707e",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: true
                    }
                }
            }
        });

        // Animation for counters
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.querySelectorAll('.counter-animation').forEach(function(element, index) {
                    element.style.animationDelay = (index * 0.1) + 's';
                    element.style.animationPlayState = 'running';
                });
            }, 300);
        });
    </script>
</body>
</html>
