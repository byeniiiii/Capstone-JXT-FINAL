<?php
/**
 * Authentication check for staff pages
 * This file verifies that the user is logged in and has appropriate staff permissions
 */

// Debug session
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Make sure session is started (although it should be started in the including file)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session variables
var_dump($_SESSION);

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Debug redirect
    echo "Redirecting to index.php because: ";
    if (!isset($_SESSION['user_id'])) echo "No user_id in session. ";
    if (!isset($_SESSION['role'])) echo "No role in session.";
    header("Location: ../index.php");
    exit();
}

// Check if user has staff permissions (all roles except Customer)
$allowed_roles = ['Admin', 'Manager', 'Tailor', 'Staff', 'Sublimator'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    // Debug redirect
    echo "Redirecting to index.php because role " . $_SESSION['role'] . " is not allowed.";
    header("Location: ../index.php?error=insufficient_permissions");
    exit();
}

// Include database connection
include '../db.php';

// Check if user is logged in and has Staff role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: ../index.php");
    exit();
}

// Set default filter values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build the WHERE clause based on filters
$where_conditions = [];
$params = [];
$types = '';

// Add date range filter
$where_conditions[] = "o.created_at BETWEEN ? AND ?";
$params[] = $start_date . ' 00:00:00';
$params[] = $end_date . ' 23:59:59';
$types .= "ss";

// Add status filter if not 'all'
if ($status_filter != 'all') {
    $where_conditions[] = "o.order_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add type filter if not 'all'
if ($type_filter != 'all') {
    $where_conditions[] = "o.order_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

// Combine WHERE conditions
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Prepare the SQL query
$query = "SELECT o.*, c.first_name, c.last_name, c.phone_number,
          u.first_name AS staff_fname, u.last_name AS staff_lname,
          m.first_name AS manager_fname, m.last_name AS manager_lname,
          p.method AS payment_method
          FROM orders o
          LEFT JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN users u ON o.staff_id = u.user_id
          LEFT JOIN users m ON o.manager_id = m.user_id
          LEFT JOIN payments p ON o.payment_id = p.payment_id
          $where_clause
          ORDER BY o.created_at DESC";

// Use prepared statement
$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $orders = [];
    // Handle error
}

// Get distinct order statuses for filter
$status_query = "SELECT DISTINCT order_status FROM orders ORDER BY order_status";
$status_result = $conn->query($status_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Reports - JX Tailoring</title>
    <?php include 'includes/header_scripts.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
    <style>
        .filter-section {
            background-color: #f8f9fc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .btn-export {
            margin-right: 8px;
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
                    <h1 class="h3 mb-4 text-gray-800">Order Reports</h1>
                    
                    <!-- Filters -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Options</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Statuses</option>
                                        <?php while ($status = $status_result->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($status['order_status']) ?>" 
                                                <?= $status_filter == $status['order_status'] ? 'selected' : '' ?>>
                                                <?= ucfirst(str_replace('_', ' ', $status['order_status'])) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="type" class="form-label">Type</label>
                                    <select class="form-control" id="type" name="type">
                                        <option value="all" <?= $type_filter == 'all' ? 'selected' : '' ?>>All Types</option>
                                        <option value="alteration" <?= $type_filter == 'alteration' ? 'selected' : '' ?>>Alteration</option>
                                        <option value="custom" <?= $type_filter == 'custom' ? 'selected' : '' ?>>Custom</option>
                                        <option value="repair" <?= $type_filter == 'repair' ? 'selected' : '' ?>>Repair</option>
                                        <option value="sublimation" <?= $type_filter == 'sublimation' ? 'selected' : '' ?>>Sublimation</option>
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
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="ordersTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Payment Status</th>
                                            <th>Amount</th>
                                            <th>Staff</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($order['order_id']) ?></td>
                                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                                <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                                                <td><?= ucfirst(htmlspecialchars($order['order_type'])) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= getStatusBadgeClass($order['order_status']) ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $order['order_status'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= getPaymentBadgeClass($order['payment_status']) ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $order['payment_status'])) ?>
                                                    </span>
                                                </td>
                                                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                                <td><?= $order['staff_fname'] ? htmlspecialchars($order['staff_fname'] . ' ' . $order['staff_lname']) : 'N/A' ?></td>
                                                <td>
                                                    <a href="view_order.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
    
    <?php include 'includes/scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    
    <script>
        // Helper functions for status badges
        <?php
        function getStatusBadgeClass($status) {
            switch ($status) {
                case 'pending_approval':
                    return 'warning';
                case 'approved':
                    return 'info';
                case 'completed':
                    return 'success';
                case 'ready_for_pickup':
                    return 'primary';
                case 'in_process':
                    return 'secondary';
                case 'declined':
                    return 'danger';
                default:
                    return 'secondary';
            }
        }
        
        function getPaymentBadgeClass($status) {
            switch ($status) {
                case 'paid':
                case 'fully_paid':
                    return 'success';
                case 'partial':
                case 'downpayment_paid':
                    return 'warning';
                case 'unpaid':
                    return 'danger';
                default:
                    return 'secondary';
            }
        }
        ?>
        
        $(document).ready(function() {
            // Date picker initialization
            flatpickr("#start_date", {
                dateFormat: "Y-m-d"
            });
            
            flatpickr("#end_date", {
                dateFormat: "Y-m-d"
            });
            
            // Initialize DataTable with export buttons
            $('#ordersTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-sm btn-success btn-export',
                        title: 'JX Tailoring Orders Report <?= date("Y-m-d") ?>'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-sm btn-danger btn-export',
                        title: 'JX Tailoring Orders Report <?= date("Y-m-d") ?>'
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Print',
                        className: 'btn btn-sm btn-primary btn-export',
                        title: 'JX Tailoring Orders Report'
                    }
                ],
                responsive: true,
                pageLength: 25
            });
        });
    </script>
</body>
</html>