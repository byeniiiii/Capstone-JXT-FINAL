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

// If user is not a manager, redirect to index.php
if ($_SESSION['role'] !== 'Manager') {
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
    <title>JXT Tailoring - Order Reports</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome & Custom Fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">

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
        .footer {
            background-color: #443627 !important;
            color: #EFDCAB !important;
        }
        .table {
            background-color: white;
        }
        .table th {
            background-color: #fff !important;
            color: #000 !important;
            font-weight: bold;
        }
        .table-responsive {
            margin-top: 1rem;
        }
        .filter-section {
            padding: 1rem;
            background-color: white;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .status-badge {
            padding: 0.35rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .card-header {
            padding: 0.75rem 1.25rem;
            background-color: rgba(217, 131, 36, 0.1) !important;
            border-bottom: 1px solid rgba(217, 131, 36, 0.2);
        }
        .form-control, .form-select {
            border-color: #e3e6f0;
        }
        .form-control:focus, .form-select:focus {
            border-color: #D98324;
            box-shadow: 0 0 0 0.25rem rgba(217, 131, 36, 0.25);
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
                <?php include 'topbar.php'; ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800" style="font-weight: bold; text-shadow: 1px 1px 2px #fff;">Order Reports</h1>
                    </div>

                    <!-- Filters Section -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Options</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="order_reports.php" class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Order Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                        <option value="pending_approval" <?php echo $status_filter === 'pending_approval' ? 'selected' : ''; ?>>Pending Approval</option>
                                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="in_process" <?php echo $status_filter === 'in_process' ? 'selected' : ''; ?>>In Process</option>
                                        <option value="forwarded_to_sublimator" <?php echo $status_filter === 'forwarded_to_sublimator' ? 'selected' : ''; ?>>Forwarded to Sublimator</option>
                                        <option value="printing_done" <?php echo $status_filter === 'printing_done' ? 'selected' : ''; ?>>Printing Done</option>
                                        <option value="ready_for_pickup" <?php echo $status_filter === 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="type" class="form-label">Order Type</label>
                                    <select class="form-select" id="type" name="type">
                                        <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                                        <option value="tailoring" <?php echo $type_filter === 'tailoring' ? 'selected' : ''; ?>>Tailoring</option>
                                        <option value="sublimation" <?php echo $type_filter === 'sublimation' ? 'selected' : ''; ?>>Sublimation</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="staff_id" class="form-label">Staff</label>
                                    <select class="form-select" id="staff_id" name="staff_id">
                                        <option value="all" <?php echo $staff_filter === 'all' ? 'selected' : ''; ?>>All Staff</option>
                                        <?php while ($staff = mysqli_fetch_assoc($staff_result)) { ?>
                                            <option value="<?php echo $staff['user_id']; ?>" 
                                                <?php echo $staff_filter == $staff['user_id'] ? 'selected' : ''; ?>>
                                                <?php echo $staff['first_name'] . ' ' . $staff['last_name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Orders Report</h6>
                            <div>
                                <button id="printReport" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-print fa-sm"></i> Print
                                </button>
                                <button id="exportExcel" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-file-excel fa-sm"></i> Export to Excel
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="ordersTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Type</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Order Date</th>
                                            <th>Expected Completion</th>
                                            <th>Staff</th>
                                            <th>Manager</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($result && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                // Format the order status for display
                                                $status_class = '';
                                                switch($row['order_status']) {
                                                    case 'pending_approval':
                                                        $status_class = 'bg-warning text-dark';
                                                        break;
                                                    case 'approved':
                                                    case 'in_process':
                                                    case 'forwarded_to_sublimator':
                                                    case 'printing_done':
                                                        $status_class = 'bg-info text-dark';
                                                        break;
                                                    case 'ready_for_pickup':
                                                        $status_class = 'bg-primary text-white';
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'bg-success text-white';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'bg-danger text-white';
                                                        break;
                                                    default:
                                                        $status_class = 'bg-secondary text-white';
                                                        break;
                                                }
                                                
                                                $status_display = str_replace('_', ' ', ucwords($row['order_status']));
                                                
                                                // Format the dates
                                                $order_date = date('M j, Y', strtotime($row['created_at']));
                                                $completion_date = !empty($row['completion_date']) ? date('M j, Y', strtotime($row['completion_date'])) : 'Not Set';
                                        ?>
                                        <tr>
                                            <td><a href="view_order.php?id=<?php echo $row['order_id']; ?>" class="fw-bold"><?php echo $row['order_id']; ?></a></td>
                                            <td><?php echo htmlspecialchars($row['customer_fname'] . ' ' . $row['customer_lname']); ?></td>
                                            <td><?php echo ucfirst($row['order_type']); ?></td>
                                            <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_display; ?></span></td>
                                            <td><?php echo $order_date; ?></td>
                                            <td><?php echo $completion_date; ?></td>
                                            <td><?php echo !empty($row['staff_fname']) ? htmlspecialchars($row['staff_fname'] . ' ' . $row['staff_lname']) : 'Not Assigned'; ?></td>
                                            <td><?php echo !empty($row['manager_fname']) ? htmlspecialchars($row['manager_fname'] . ' ' . $row['manager_lname']) : 'Not Assigned'; ?></td>
                                        </tr>
                                        <?php
                                            }
                                        } else {
                                            echo "<tr><td colspan='9' class='text-center'>No orders found matching the criteria</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->

                <!-- Footer -->
                <footer class="footer text-center py-3">
                    <span>Copyright &copy; JXT Tailoring and Printing Services</span>
                </footer>
                <!-- End of Footer -->
            </div>
            <!-- End of Content -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- JavaScript dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Excel Export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            var table = $('#ordersTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[5, 'desc']], // Order by order date column descending
                columnDefs: [
                    { responsivePriority: 1, targets: 0 }, // Order ID
                    { responsivePriority: 2, targets: 1 }, // Customer
                    { responsivePriority: 3, targets: 4 }  // Status
                ]
            });
            
            // Handle Print button click
            $('#printReport').click(function() {
                window.print();
            });
            
            // Handle Excel Export button click
            $('#exportExcel').click(function() {
                // Function to convert HTML table to workbook
                function html_table_to_excel(type) {
                    var data = document.getElementById('ordersTable');
                    var wb = XLSX.utils.table_to_book(data, {sheet: "Orders Report"});
                    XLSX.writeFile(wb, 'orders_report_<?php echo date('Y-m-d'); ?>.xlsx');
                }
                
                html_table_to_excel('xlsx');
            });
            
            // Date filter validation
            $('form').submit(function(e) {
                var startDate = new Date($('#start_date').val());
                var endDate = new Date($('#end_date').val());
                
                if (startDate > endDate) {
                    alert('Start date cannot be after end date');
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>