<?php
session_start();
include '../db.php';

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

// Get today's orders
$today_orders_query = "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = '$today'";
$today_orders_result = mysqli_query($conn, $today_orders_query);
$today_orders = mysqli_fetch_assoc($today_orders_result)['count'];

// Get total revenue
$total_revenue_query = "SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'fully_paid'";
$total_revenue_result = mysqli_query($conn, $total_revenue_query);
$total_revenue = mysqli_fetch_assoc($total_revenue_result)['total'] ?? 0;

// Get monthly revenue
$monthly_revenue_query = "SELECT SUM(total_amount) as monthly FROM orders 
                          WHERE payment_status = 'fully_paid' 
                          AND created_at BETWEEN '$current_month_start 00:00:00' AND '$current_month_end 23:59:59'";
$monthly_revenue_result = mysqli_query($conn, $monthly_revenue_query);
$monthly_revenue = mysqli_fetch_assoc($monthly_revenue_result)['monthly'] ?? 0;

// Get pending orders count
$pending_orders_query = "SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending_approval'";
$pending_orders_result = mysqli_query($conn, $pending_orders_query);
$pending_orders = mysqli_fetch_assoc($pending_orders_result)['count'];

// Get orders by status
$order_status_query = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status ORDER BY count DESC";
$order_status_result = mysqli_query($conn, $order_status_query);
$order_status_data = [];
while ($row = mysqli_fetch_assoc($order_status_result)) {
    $order_status_data[$row['order_status']] = $row['count'];
}

// Get payment status summary
$payment_status_query = "SELECT payment_status, COUNT(*) as count FROM orders GROUP BY payment_status";
$payment_status_result = mysqli_query($conn, $payment_status_query);
$payment_status_data = [];
while ($row = mysqli_fetch_assoc($payment_status_result)) {
    $payment_status_data[$row['payment_status']] = $row['count'];
}

// Get recent orders
$recent_orders_query = "SELECT o.order_id, o.total_amount, o.created_at, o.order_status, o.payment_status, o.order_type,
                        c.first_name, c.last_name
                        FROM orders o
                        JOIN customers c ON o.customer_id = c.customer_id
                        ORDER BY o.created_at DESC LIMIT 5";
$recent_orders_result = mysqli_query($conn, $recent_orders_query);

// Get order type distribution
$order_type_query = "SELECT order_type, COUNT(*) as count FROM orders GROUP BY order_type";
$order_type_result = mysqli_query($conn, $order_type_query);
$order_type_data = [];
while ($row = mysqli_fetch_assoc($order_type_result)) {
    $order_type_data[$row['order_type']] = $row['count'];
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
    <title>Manage Payments - JX Tailoring</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .payment-form-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .payment-form-title {
            color: #4e73df;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .form-label {
            font-weight: 600;
            color: #5a5c69;
        }
        
        .receipt {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .receipt-title {
            font-size: 24px;
            font-weight: 700;
            color: #4e73df;
            margin-bottom: 5px;
        }
        
        .receipt-subtitle {
            color: #5a5c69;
            font-size: 14px;
        }
        
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .receipt-label {
            font-weight: 600;
            color: #5a5c69;
        }
        
        .receipt-value {
            text-align: right;
        }
        
        .receipt-total {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e3e6f0;
            font-weight: 700;
            font-size: 18px;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            color: #5a5c69;
            font-size: 14px;
        }
        
        .receipt-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .payment-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .payment-pending {
            background-color: #f6c23e;
            color: #fff;
        }
        
        .payment-partial {
            background-color: #36b9cc;
            color: #fff;
        }
        
        .payment-paid {
            background-color: #1cc88a;
            color: #fff;
        }
        
        .table th {
            font-weight: 600;
            color: #4e73df;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
    </style>
    <style>
    /* Container Styles */
    .payment-container {
        background-color: #EFDCAB;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
    }

    /* Table Styles */
    .table {
        background-color: white;
        border-radius: 8px;
        margin-bottom: 0;
    }

    .table thead th {
        background-color: #443627;
        color: #EFDCAB;
        font-size: 0.85rem;
        font-weight: 600;
        border: none;
        padding: 12px;
    }

    .table td {
        padding: 12px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    /* Status Badge Styles */
    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .status-pending { background-color: #D98324; color: white; }
    .status-partial { background-color: #443627; color: #EFDCAB; }
    .status-completed { background-color: #28a745; color: white; }

    /* Form Styles */
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #443627;
        padding: 8px 12px;
    }

    .form-label {
        color: #443627;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    /* Button Styles */
    .btn-action {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: 4px;
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
                        <h1 class="h3 mb-0 text-gray-800" style="font-weight: bold; text-shadow: 1px 1px 2px #fff;">Dashboard</h1>
                        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
                        </a>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Monthly Revenue Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Monthly Revenue</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($monthly_revenue, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-white-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <!-- Total Revenue Card -->
                         <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Revenue</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($total_revenue, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-white-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Orders Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Today's Orders
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_orders; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-white-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Orders Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Pending Orders</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_orders; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-white-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row">
                        <!-- Order Status Distribution -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Order Status Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="position: relative; height:400px;">
                                        <canvas id="orderStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Status Distribution -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Payment Status Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="position: relative; height:400px;">
                                        <canvas id="paymentStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders Row -->
                    <div class="row">
                        <div class="col-lg-12">
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
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Payment</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if ($recent_orders_result && mysqli_num_rows($recent_orders_result) > 0) {
                                                    while ($order = mysqli_fetch_assoc($recent_orders_result)) {
                                                        $status_class = match($order['order_status']) {
                                                            'pending_approval' => 'bg-warning',
                                                            'approved' => 'bg-info',
                                                            'in_process' => 'bg-primary',
                                                            'ready_for_pickup' => 'bg-info',
                                                            'completed' => 'bg-success',
                                                            'declined' => 'bg-danger',
                                                            default => 'bg-secondary'
                                                        };
                                                        $payment_class = match($order['payment_status']) {
                                                            'pending' => 'bg-warning',
                                                            'downpayment_paid' => 'bg-info',
                                                            'fully_paid' => 'bg-success',
                                                            default => 'bg-secondary'
                                                        };
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $order['order_id']; ?></td>
                                                            <td><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></td>
                                                            <td><?php echo ucfirst($order['order_type']); ?></td>
                                                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                            <td><span class="badge <?php echo $status_class; ?>"><?php echo str_replace('_', ' ', ucfirst($order['order_status'])); ?></span></td>
                                                            <td><span class="badge <?php echo $payment_class; ?>"><?php echo str_replace('_', ' ', ucfirst($order['payment_status'])); ?></span></td>
                                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                        </tr>
                                                    <?php }
                                                } else { ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">No recent orders found</td>
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
    <!-- End of Page Wrapper -->
    
    <!-- JavaScript for the charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Order Status Distribution Chart
            const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
            const orderStatusData = <?php echo json_encode(array_values($order_status_data)); ?>;
            const orderStatusLabels = <?php echo json_encode(array_map(function($key) { 
                                            return ucfirst(str_replace('_', ' ', $key)); 
                                        }, array_keys($order_status_data))); ?>;
            
            new Chart(orderStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: orderStatusLabels,
                    datasets: [{
                        data: orderStatusData,
                        backgroundColor: [
                            '#D98324', '#443627', '#EFDCAB', '#886F4F', '#A67F5D', '#664E38'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Payment Status Distribution Chart
            const paymentStatusCtx = document.getElementById('paymentStatusChart').getContext('2d');
            const paymentStatusData = <?php echo json_encode(array_values($payment_status_data)); ?>;
            const paymentStatusLabels = <?php echo json_encode(array_map(function($key) { 
                                            return ucfirst(str_replace('_', ' ', $key)); 
                                        }, array_keys($payment_status_data))); ?>;
            
            new Chart(paymentStatusCtx, {
                type: 'pie',
                data: {
                    labels: paymentStatusLabels,
                    datasets: [{
                        data: paymentStatusData,
                        backgroundColor: [
                            '#D98324', '#443627', '#EFDCAB'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
