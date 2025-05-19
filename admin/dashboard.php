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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
        font-family: 'Poppins', sans-serif;
        background-color: #443627;
        color: #EFDCAB;
        overflow-x: hidden;
    }

    .sidebar {
        transition: all 0.3s ease-in-out;
    }

    .sidebar.toggled {
        width: 80px !important;
    }

    .sidebar .nav-item .nav-link {
        color: #EFDCAB !important;
    }

    .navbar {
        background-color: #ffffff !important;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .navbar a {
        color: #443627 !important;
    }

    .card {
        border-left: 4px solid #D98324 !important;
        background-color: #EFDCAB !important;
        color: #443627 !important;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }

    .btn-primary {
        background-color: #D98324 !important;
        border-color: #D98324 !important;
        transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #443627 !important;
        border-color: #443627 !important;
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
        font-size: 14px;
    }

    /* Burger Menu Fix */
    #sidebarToggleTop {
        color: #443627;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .sidebar {
            width: 100% !important;
        }

        .sidebar .nav-item {
            text-align: center;
        }

        .card {
            margin-bottom: 1rem;
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
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Orders</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_orders); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shopping-bag fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <!-- Monthly Orders Card -->
                         <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.1s;">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Orders This Month</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($monthly_orders); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Revenue Card -->
                        <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.2s;">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Revenue</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($total_income, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-peso-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Revenue Card -->
                        <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.3s;">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Revenue This Month</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($monthly_income, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
        // Daily orders chart
        const ordersChartCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersChartCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($daily_dates); ?>,
                datasets: [{
                    label: 'Daily Orders',
                    data: <?php echo json_encode($daily_counts); ?>,
                    backgroundColor: 'rgba(217, 131, 36, 0.2)',
                    borderColor: 'rgba(217, 131, 36, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Order status chart
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
                        '#ffc107', // warning - pending
                        '#17a2b8', // info - approved
                        '#6c757d', // secondary - in process
                        '#007bff', // primary - forward to sublimator
                        '#6610f2', // indigo - ready for pickup
                        '#28a745', // success - completed
                        '#dc3545'  // danger - declined
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Order type chart
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
                        'rgba(217, 131, 36, 0.8)',
                        'rgba(68, 54, 39, 0.8)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
