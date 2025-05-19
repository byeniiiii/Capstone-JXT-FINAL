<?php
// Include database connection
include '../db.php';

// Default date range (last 30 days)
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');
$date_filter = "WHERE o.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";

// Apply date filters if submitted
if (isset($_GET['filter'])) {
    $filter_type = $_GET['filter_type'] ?? 'custom';
    
    switch ($filter_type) {
        case 'today':
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
            break;
        case 'yesterday':
            $start_date = date('Y-m-d', strtotime('-1 day'));
            $end_date = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'this_week':
            $start_date = date('Y-m-d', strtotime('monday this week'));
            $end_date = date('Y-m-d');
            break;
        case 'last_week':
            $start_date = date('Y-m-d', strtotime('monday last week'));
            $end_date = date('Y-m-d', strtotime('sunday last week'));
            break;
        case 'this_month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d');
            break;
        case 'last_month':
            $start_date = date('Y-m-01', strtotime('last month'));
            $end_date = date('Y-m-t', strtotime('last month'));
            break;
        case 'this_year':
            $start_date = date('Y-01-01');
            $end_date = date('Y-m-d');
            break;
        case 'last_year':
            $start_date = date('Y-01-01', strtotime('last year'));
            $end_date = date('Y-12-31', strtotime('last year'));
            break;
        case 'custom':
            $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $end_date = $_GET['end_date'] ?? date('Y-m-d');
            break;
    }
    
    $date_filter = "WHERE o.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
}

// Get sales summary data
$summary_query = "SELECT 
                    COUNT(*) as order_count,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value,
                    SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    COUNT(DISTINCT customer_id) as unique_customers,
                    SUM(CASE WHEN payment_status = 'fully_paid' THEN 1 ELSE 0 END) as fully_paid_orders,
                    SUM(CASE WHEN payment_status = 'downpayment_paid' THEN 1 ELSE 0 END) as partially_paid_orders,
                    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_payment_orders
                  FROM orders o
                  $date_filter";

$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

// Get sales by order type
$order_type_query = "SELECT 
                        order_type,
                        COUNT(*) as order_count,
                        SUM(total_amount) as total_revenue
                     FROM orders o
                     $date_filter
                     GROUP BY order_type
                     ORDER BY total_revenue DESC";
                     
$order_type_result = mysqli_query($conn, $order_type_query);

// Get sales by payment method
$payment_method_query = "SELECT 
                            payment_method,
                            COUNT(*) as order_count,
                            SUM(total_amount) as total_revenue
                         FROM orders o 
                         $date_filter
                         GROUP BY payment_method
                         ORDER BY total_revenue DESC";
                         
$payment_method_result = mysqli_query($conn, $payment_method_query);

// Get daily sales for chart
$daily_sales_query = "SELECT 
                        DATE(created_at) as sale_date,
                        COUNT(*) as order_count,
                        SUM(total_amount) as daily_revenue
                      FROM orders o
                      $date_filter
                      GROUP BY DATE(created_at)
                      ORDER BY sale_date";
                      
$daily_sales_result = mysqli_query($conn, $daily_sales_query);
$daily_labels = [];
$daily_data = [];

while ($row = mysqli_fetch_assoc($daily_sales_result)) {
    $daily_labels[] = date('M d', strtotime($row['sale_date']));
    $daily_data[] = $row['daily_revenue'];
}

// Get monthly trend data for the year
$monthly_trend_query = "SELECT 
                          MONTH(created_at) as month_num,
                          MONTHNAME(created_at) as month_name,
                          COUNT(*) as order_count,
                          SUM(total_amount) as monthly_revenue,
                          SUM(CASE WHEN order_type = 'tailoring' THEN total_amount ELSE 0 END) as tailoring_revenue,
                          SUM(CASE WHEN order_type = 'sublimation' THEN total_amount ELSE 0 END) as sublimation_revenue
                        FROM orders
                        WHERE YEAR(created_at) = YEAR(CURRENT_DATE)
                        GROUP BY MONTH(created_at), MONTHNAME(created_at)
                        ORDER BY MONTH(created_at)";

$monthly_trend_result = mysqli_query($conn, $monthly_trend_query);
$monthly_labels = [];
$monthly_data = [];
$tailoring_data = [];
$sublimation_data = [];

while ($row = mysqli_fetch_assoc($monthly_trend_result)) {
    $monthly_labels[] = $row['month_name'];
    $monthly_data[] = $row['monthly_revenue'];
    $tailoring_data[] = $row['tailoring_revenue'];
    $sublimation_data[] = $row['sublimation_revenue'];
}

// Get service type breakdown for tailoring orders
$tailoring_services_query = "SELECT 
                              t.service_type,
                              COUNT(*) as order_count,
                              SUM(o.total_amount) as total_revenue
                            FROM orders o
                            JOIN tailoring_orders t ON o.order_id = t.order_id
                            $date_filter
                            GROUP BY t.service_type
                            ORDER BY total_revenue DESC";
                            
$tailoring_services_result = mysqli_query($conn, $tailoring_services_query);

// Get detailed orders data
$orders_query = "SELECT o.*, 
          c.first_name AS customer_fname, c.last_name AS customer_lname,
          s.first_name AS staff_fname, s.last_name AS staff_lname,
          m.first_name AS manager_fname, m.last_name AS manager_lname
          FROM orders o
          LEFT JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN users s ON o.staff_id = s.user_id
          LEFT JOIN users m ON o.manager_id = m.user_id
                $date_filter
          ORDER BY o.created_at DESC";

$orders_result = mysqli_query($conn, $orders_query);

// Check if query execution was successful
if (!$orders_result) {
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
    <link rel="icon" type="image" href="../image/logo.png">
    <title>JXT Admin - Sales Report</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome & Custom Fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F2F6D0;
        }
        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .sidebar {
            background-color: #443627 !important;
        }
        .sidebar .nav-item .nav-link {
            color: #EFDCAB !important;
        }
        .card {
            border-left: 5px solid #D98324 !important;
            background-color: #EFDCAB !important;
            color: #443627 !important;
        }
        .btn-primary {
            background-color: #D98324 !important;
            border-color: #D98324 !important;
        }
        .btn-primary:hover {
            background-color: #443627 !important;
            border-color: #443627 !important;
        }
        .text-primary {
            color: #D98324 !important;
        }
        .footer {
            background-color: #443627 !important;
            color: #EFDCAB !important;
        }
        .card-body {
            padding: 1rem;
        }
        .table {
            font-size: 0.85rem;
        }
        .table td, .table th {
            padding: 0.5rem;
        }
        @media (max-width: 768px) {
            .table {
                font-size: 0.75rem;
            }
            .table td, .table th {
                padding: 0.35rem;
            }
        }
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            font-size: 0.85rem;
            margin: 0.5rem;
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
                </nav>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800" style="font-weight: bold; text-shadow: 1px 1px 2px #fff;">
                            Sales Report
                        </h1>
                    </div>

                    <!-- Sales Report Table -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="salesTable">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer Name</th>
                                            <th>Order Type</th>
                                            <th>Total Amount</th>
                                            <th>Downpayment</th>
                                            <th>Payment Method</th>
                                            <th>Order Status</th>
                                            <th>Payment Status</th>
                                            <th>Staff</th>
                                            <th>Manager</th>
                                            <th>Date Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>                                        <?php
                                        if (isset($orders_result) && mysqli_num_rows($orders_result) > 0) {
                                            while ($row = mysqli_fetch_assoc($orders_result)) { ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['customer_fname'] . ' ' . $row['customer_lname']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['order_type']); ?></td>
                                                    <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                                    <td>₱<?php echo number_format($row['downpayment_amount'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                                    <td>
                                                        <span class="badge <?php 
                                                            echo match($row['order_status']) {
                                                                'Pending' => 'bg-warning',
                                                                'In Progress' => 'bg-info',
                                                                'Completed' => 'bg-success',
                                                                'Cancelled' => 'bg-danger',
                                                                default => 'bg-secondary'
                                                            };
                                                        ?>">
                                                            <?php echo htmlspecialchars($row['order_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $row['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo htmlspecialchars($row['payment_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['staff_fname'] . ' ' . $row['staff_lname']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['manager_fname'] . ' ' . $row['manager_lname']); ?></td>
                                                    <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
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

                <!-- Footer -->
                <footer class="footer text-center py-3">
                    <span>Copyright &copy; JXT Tailoring and Printing Services</span>
                </footer>
            </div>
        </div>
    </div>

    <!-- Add DataTables for better table functionality -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#salesTable').DataTable({
                order: [[10, 'desc']], // Sort by date created by default
                pageLength: 5,         // Changed from 10 to 5 rows
                responsive: true,
                lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]], // Add this to customize the "rows per page" dropdown
            });
        });
    </script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
