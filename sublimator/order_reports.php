<?php
// Start the session before any output
session_start();

// Include database connection
include '../db.php';

// If user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "../index.php";</script>';
    exit();
}

// If user is not a manager or sublimator, redirect to index.php
if ($_SESSION['role'] !== 'Manager' && $_SESSION['role'] !== 'Sublimator') {
    echo '<script>window.location.href = "../index.php";</script>';
    exit();
}

// Set default filter values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$staff_filter = isset($_GET['staff_id']) ? $_GET['staff_id'] : 'all';

// Build the WHERE clause based on filters
$where_conditions = [];
$params = [];
$types = "";

if ($start_date && $end_date) {
    $where_conditions[] = "DATE(o.created_at) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

if ($status_filter !== 'all') {
    $where_conditions[] = "o.order_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($type_filter !== 'all') {
    $where_conditions[] = "o.order_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if ($staff_filter !== 'all') {
    $where_conditions[] = "o.staff_id = ?";
    $params[] = $staff_filter;
    $types .= "i";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query to get filtered orders
$query = "SELECT o.*, c.first_name AS customer_fname, c.last_name AS customer_lname,
          s.first_name AS staff_fname, s.last_name AS staff_lname,
          m.first_name AS manager_fname, m.last_name AS manager_lname,
          CASE 
            WHEN o.order_type = 'sublimation' THEN sub.completion_date
            WHEN o.order_type = 'tailoring' THEN t.completion_date
            ELSE NULL
          END AS completion_date
          FROM orders o
          LEFT JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN users s ON o.staff_id = s.user_id
          LEFT JOIN users m ON o.manager_id = m.user_id
          LEFT JOIN sublimation_orders sub ON o.order_id = sub.order_id AND o.order_type = 'sublimation'
          LEFT JOIN tailoring_orders t ON o.order_id = t.order_id AND o.order_type = 'tailoring'
          $where_clause
          ORDER BY o.created_at DESC";

$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get staff list for filter
$staff_query = "SELECT user_id, first_name, last_name FROM users WHERE role = 'Staff'";
$staff_result = mysqli_query($conn, $staff_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="JXT Manager - Order Reports">
    <meta name="author" content="">
    <link rel="icon" type="image/png" href="../image/logo.png">
    <title>JX Tailoring - Order Reports</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
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
        }
        
        .filter-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .summary-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
            transition: transform 0.3s;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
        }
        
        .summary-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray);
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary);
        }
        
        .table thead th {
            background-color: var(--secondary);
            color: var(--light);
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
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending-approval { 
            background-color: var(--warning);
            color: var(--secondary);
        }
        
        .status-approved { 
            background-color: var(--primary);
            color: var(--white);
        }
        
        .status-in-process { 
            background-color: var(--secondary);
            color: var(--light);
        }
        
        .status-ready-for-pickup { 
            background-color: var(--light);
            color: var(--secondary);
        }
        
        .status-completed { 
            background-color: var(--success);
            color: var(--white);
        }
        
        .status-declined { 
            background-color: var(--danger);
            color: var(--white);
        }
        
        .payment-pending {
            background-color: var(--warning);
            color: var(--secondary);
        }
        
        .payment-downpayment-paid {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .payment-fully-paid {
            background-color: var(--success);
            color: var(--white);
        }
        
        .export-btn {
            background-color: var(--success);
            color: white;
        }
        
        .export-btn:hover {
            background-color: #218838;
            color: white;
        }
        
        .table {
            font-size: 0.85rem;
            background-color: var(--white);
            border-radius: 8px;
            margin-bottom: 0;
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
        
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        
        .card-header {
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
            background-color: #fff;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .text-primary {
            color: var(--primary) !important;
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
                        <h1 class="h3 mb-0 text-gray-800" style="font-weight: bold;">Order Reports</h1>
                        <button onclick="exportToExcel()" class="d-none d-sm-inline-block btn btn-sm export-btn shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Export to Excel
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="card filter-card mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Orders</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row align-items-end">
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                        <option value="pending_approval" <?= $status_filter === 'pending_approval' ? 'selected' : '' ?>>Pending Approval</option>
                                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                                        <option value="in_process" <?= $status_filter === 'in_process' ? 'selected' : '' ?>>In Process</option>
                                        <option value="forward_to_sublimator" <?= $status_filter === 'forward_to_sublimator' ? 'selected' : '' ?>>Forward to Sublimator</option>
                                        <option value="printing_done" <?= $status_filter === 'printing_done' ? 'selected' : '' ?>>Printing Done</option>
                                        <option value="ready_for_pickup" <?= $status_filter === 'ready_for_pickup' ? 'selected' : '' ?>>Ready for Pickup</option>
                                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="declined" <?= $status_filter === 'declined' ? 'selected' : '' ?>>Declined</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="type">
                                        <option value="all" <?= $type_filter === 'all' ? 'selected' : '' ?>>All Types</option>
                                        <option value="sublimation" <?= $type_filter === 'sublimation' ? 'selected' : '' ?>>Sublimation</option>
                                        <option value="tailoring" <?= $type_filter === 'tailoring' ? 'selected' : '' ?>>Tailoring</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Staff</label>
                                    <select class="form-select" name="staff_id">
                                        <option value="all" <?= $staff_filter === 'all' ? 'selected' : '' ?>>All Staff</option>
                                        <?php while ($staff = mysqli_fetch_assoc($staff_result)): ?>
                                            <option value="<?= $staff['user_id'] ?>" <?= $staff_filter == $staff['user_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Cards Row -->
                    <div class="row mb-4">
                        <?php
                        // Calculate summary statistics
                        $total_orders = mysqli_num_rows($result);
                        $total_revenue = 0;
                        $payment_status_counts = [
                            'pending' => 0,
                            'downpayment_paid' => 0,
                            'fully_paid' => 0
                        ];
                        $order_status_counts = [
                            'pending_approval' => 0,
                            'approved' => 0,
                            'in_process' => 0,
                            'ready_for_pickup' => 0,
                            'completed' => 0,
                            'declined' => 0
                        ];
                        $type_counts = [
                            'sublimation' => 0,
                            'tailoring' => 0
                        ];
                        
                        if ($total_orders > 0) {
                            mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $total_revenue += $row['total_amount'];
                                $payment_status_counts[$row['payment_status']] = isset($payment_status_counts[$row['payment_status']]) ? 
                                    $payment_status_counts[$row['payment_status']] + 1 : 1;
                                $order_status_counts[$row['order_status']] = isset($order_status_counts[$row['order_status']]) ? 
                                    $order_status_counts[$row['order_status']] + 1 : 1;
                                $type_counts[$row['order_type']]++;
                            }
                            mysqli_data_seek($result, 0);
                        }
                        ?>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="summary-card">
                                <div class="summary-title">Total Orders</div>
                                <div class="summary-value"><?= number_format($total_orders) ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="summary-card">
                                <div class="summary-title">Total Revenue</div>
                                <div class="summary-value">₱<?= number_format($total_revenue, 2) ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="summary-card">
                                <div class="summary-title">Sublimation Orders</div>
                                <div class="summary-value"><?= number_format($type_counts['sublimation']) ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="summary-card">
                                <div class="summary-title">Tailoring Orders</div>
                                <div class="summary-value"><?= number_format($type_counts['tailoring']) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Status Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="summary-card">
                                <div class="summary-title">Fully Paid Orders</div>
                                <div class="summary-value"><?= number_format($payment_status_counts['fully_paid']) ?></div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="summary-card">
                                <div class="summary-title">Downpayment Paid</div>
                                <div class="summary-value"><?= number_format($payment_status_counts['downpayment_paid']) ?></div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="summary-card">
                                <div class="summary-title">Pending Payment</div>
                                <div class="summary-value"><?= number_format($payment_status_counts['pending']) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Order Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="ordersTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Type</th>
                                            <th>Order Status</th>
                                            <th>Payment Status</th>
                                            <th>Amount</th>
                                            <th>Created Date</th>
                                            <th>Expected Completion</th>
                                            <th>Staff Assigned</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['order_id']) ?></td>
                                                <td><?= htmlspecialchars($row['customer_fname'] . ' ' . $row['customer_lname']) ?></td>
                                                <td><?= ucfirst(htmlspecialchars($row['order_type'])) ?></td>
                                                <td>
                                                    <span class="status-badge status-<?= str_replace('_', '-', $row['order_status']) ?>">
                                                        <?= ucwords(str_replace('_', ' ', $row['order_status'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge payment-<?= str_replace('_', '-', $row['payment_status']) ?>">
                                                        <?= ucwords(str_replace('_', ' ', $row['payment_status'])) ?>
                                                    </span>
                                                </td>
                                                <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                                <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                                <td><?= $row['completion_date'] ? date('M j, Y', strtotime($row['completion_date'])) : 'N/A' ?></td>
                                                <td><?= $row['staff_fname'] ? htmlspecialchars($row['staff_fname'] . ' ' . $row['staff_lname']) : 'Not Assigned' ?></td>
                                                <td>
                                                    <a href="#" class="btn btn-sm btn-info" onclick="viewOrderDetails('<?= $row['order_id'] ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
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
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.html5.min.js"></script>

    <!-- Custom scripts -->
    <script>
        $(document).ready(function() {
            $('#ordersTable').DataTable({
                "order": [[6, "desc"]], // Sort by created date by default
                "pageLength": 25,
                "language": {
                    "lengthMenu": "Show _MENU_ entries per page",
                    "zeroRecords": "No orders found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ orders",
                    "infoEmpty": "Showing 0 to 0 of 0 orders",
                    "infoFiltered": "(filtered from _MAX_ total orders)"
                }
            });
        });

        function viewOrderDetails(orderId) {
            // Here you'd typically fetch order details via AJAX
            $('#orderDetailsContent').html('Loading details for Order #' + orderId + '...');
            $('#orderDetailsModal').modal('show');
            
            // In a real implementation, you'd fetch the details from the server:
            $.ajax({
                url: 'get_order_details.php',
                type: 'GET',
                data: { id: orderId },
                success: function(response) {
                    $('#orderDetailsContent').html(response);
                },
                error: function() {
                    $('#orderDetailsContent').html('Error loading order details. Please try again.');
                }
            });
        }

        function exportToExcel() {
            const table = $('#ordersTable').DataTable();
            const filteredData = table.rows({ search: 'applied' }).data();
            
            let csvContent = "Order ID,Customer,Type,Order Status,Payment Status,Amount,Created Date,Expected Completion,Staff Assigned\n";
            
            for (let i = 0; i < filteredData.length; i++) {
                const row = filteredData[i];
                // Remove HTML tags and format the data
                const rowData = [
                    row[0], // Order ID
                    row[1], // Customer
                    row[2], // Type
                    row[3].replace(/<[^>]+>/g, '').trim(), // Order Status (remove HTML)
                    row[4].replace(/<[^>]+>/g, '').trim(), // Payment Status (remove HTML)
                    row[5].replace('₱', '').trim(), // Amount (remove peso sign)
                    row[6], // Created Date
                    row[7],  // Expected Completion
                    row[8]   // Staff Assigned
                ].map(cell => `"${cell}"`).join(',');
                csvContent += rowData + "\n";
            }
            
            // Create download link
            const encodedUri = encodeURI("data:text/csv;charset=utf-8," + csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `orders_report_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
