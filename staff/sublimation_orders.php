<?php
// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// Don't require HTTPS on localhost
ini_set('session.cookie_secure', 0);
session_start();

// If the user is not logged in or not a staff member, redirect to index.php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php?error=session_expired");
    exit();
}

$userRole = strtolower($_SESSION['role']);
if (!in_array($userRole, ['admin', 'staff'])) {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

// Generate or validate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include database connection
include '../db.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'sublimation_errors.log');

// Status filter (default to 'all')
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // Ensure page is at least 1
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build the query with filters
$where_clauses = ["o.order_type = 'sublimation'"]; // Add this filter for tailoring orders
$params = [];
$types = "";

if ($status_filter != 'all') {
    $where_clauses[] = "o.order_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_term)) {
    $where_clauses[] = "(o.order_id LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR c.phone_number LIKE ?)";
    $search_pattern = "%$search_term%";
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $types .= "ssss";
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM orders o 
               LEFT JOIN customers c ON o.customer_id = c.customer_id 
               $where_clause";

$stmt = mysqli_prepare($conn, $count_query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch orders with pagination
$query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone_number, 
         CASE 
            WHEN o.order_type = 'sublimation' THEN t.completion_date
            ELSE NULL
         END AS completion_date
         FROM orders o
         LEFT JOIN customers c ON o.customer_id = c.customer_id
         LEFT JOIN sublimation_orders t ON o.order_id = t.order_id AND o.order_type = 'sublimation'
         $where_clause
         ORDER BY 
            CASE 
                WHEN o.order_status = 'pending_approval' THEN 1
                WHEN o.order_status = 'approved' THEN 2
                WHEN o.order_status = 'in_process' THEN 3
                WHEN o.order_status = 'printing_done' THEN 4
                WHEN o.order_status = 'ready_for_pickup' THEN 5
                WHEN o.order_status = 'completed' THEN 6
                ELSE 7
            END,
            o.created_at DESC
         LIMIT ?, ?";

$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    $types .= "ii";
    $params[] = $offset;
    $params[] = $records_per_page;
    mysqli_stmt_bind_param($stmt, $types, ...$params);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $offset, $records_per_page);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get order status counts for the tabs
$status_counts = [
    'pending_approval' => 0,
    'approved' => 0,
    'forward_to_sublimator' => 0,
    'in_process' => 0,
    'printing_done' => 0,
    'ready_for_pickup' => 0,
    'completed' => 0,
    'declined' => 0
];

$count_query = "SELECT order_status, COUNT(*) as count FROM orders WHERE order_type = 'sublimation' GROUP BY order_status";
$count_result = mysqli_query($conn, $count_query);

while ($row = mysqli_fetch_assoc($count_result)) {
    if (!empty($row['order_status'])) {  // Skip empty status values
        $status_counts[$row['order_status']] = $row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image" href="../image/logo.png">
    <title>JXT - Sublimation Orders</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome & Custom Fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 JavaScript and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            overflow-x: hidden;
            /* Prevent horizontal scroll */
        }

        .order-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
            margin-bottom: 1rem;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .order-pending {
            border-left-color: #f6c23e;
        }

        .order-approved {
            border-left-color: #4e73df;
        }

        .order-in-process {
            border-left-color: #36b9cc;
        }

        .order-ready {
            border-left-color: #1cc88a;
        }

        .order-completed {
            border-left-color: #1cc88a;
        }

        .order-declined {
            border-left-color: #e74a3b;
        }

        .status-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-in-process {
            background-color: #e1f5fe;
            color: #0277bd;
        }

        .status-ready {
            background-color: #e0f7fa;
            color: #006064;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-declined {
            background-color: #f8d7da;
            color: #721c24;
        }

        .payment-badge {
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .payment-pending {
            background-color: #ffe0b2;
            color: #e65100;
        }

        .payment-partial {
            background-color: #e1bee7;
            color: #6a1b9a;
        }

        .payment-paid {
            background-color: #c8e6c9;
            color: #2e7d32;
        }

        .nav-pills .nav-link {
            color: #5a5c69;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
        }

        .nav-pills .nav-link.active {
            background-color: #4e73df;
            color: #fff;
        }

        .nav-pills .nav-link .badge {
            margin-left: 0.5rem;
        }

        .table th {
            font-weight: 600;
            background-color: #f8f9fc;
        }

        .dropdown-item.active,
        .dropdown-item:active {
            background-color: #4e73df;
        }

        .action-icon {
            cursor: pointer;
            padding: 0.4rem;
            border-radius: 0.25rem;
            color: #5a5c69;
            transition: all 0.2s;
        }

        .action-icon:hover {
            background-color: #eaecf4;
        }

        .search-box {
            max-width: 300px;
        }

        /* Add these styles to your existing CSS */

        /* Improved table styling */
        .table td,
        .table th {
            padding: 1rem;
            vertical-align: middle;
        }

        /* Card view for mobile devices */
        .order-card-mobile {
            display: none;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #4e73df;
        }

        .order-card-mobile .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e3e6f0;
        }

        .order-card-mobile .card-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .order-card-mobile .card-label {
            font-weight: 600;
            color: #5a5c69;
            flex: 1;
        }

        .order-card-mobile .card-value {
            flex: 2;
            text-align: right;
        }

        /* Status and payment badges */
        .status-badge,
        .payment-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        /* Action dropdown improvements */
        .dropdown-menu {
            min-width: 200px;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .table-container {
                padding: 0;
            }

            .table-responsive {
                border: none;
            }

            /* Hide table on mobile */
            .orders-table {
                display: none;
            }

            /* Show card view instead */
            .order-card-mobile {
                display: block;
            }

            /* Make dropdown wider on mobile */
            .dropdown-menu {
                min-width: 240px;
            }
        }

        /* Add these styles to your existing <style> section */
        .table {
            font-size: 0.85rem;
            /* Reduce overall font size */
        }

        .table td,
        .table th {
            padding: 0.5rem !important;
            /* Reduce cell padding by half */
            vertical-align: middle;
        }

        /* Make action button smaller */
        .btn-responsive {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        /* Make status and payment badges more compact */
        .status-badge,
        .payment-badge {
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
        }

        /* Reduce whitespace in customer info */
        .text-truncate-custom {
            max-width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Adjust column widths to be more compact */
        .col-id {
            width: 8%;
        }

        .col-type {
            width: 10%;
        }

        .col-customer {
            width: 15%;
        }

        .col-total {
            width: 8%;
        }

        .col-payment,
        .col-status {
            width: 10%;
        }

        .col-created,
        .col-completion {
            width: 10%;
        }

        .col-actions {
            width: 8%;
        }

        /* Hide more columns on medium screens */
        @media (max-width: 992px) {
            .d-lg-table-cell {
                display: none !important;
            }
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
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                            Sublimation Orders
                        </h1>
                    </div>

                    <!-- Search and Filter -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-center filters-row">
                                <div class="col-lg-4 col-md-6">
                                    <div class="input-group search-box w-100">
                                        <input type="text" class="form-control" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search_term); ?>">
                                        <button class="btn btn-outline-secondary" type="submit">
                                            <i class="fas fa-search fa-sm"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <div class="input-group w-100">
                                        <label class="input-group-text" for="status">Status</label>
                                        <select class="form-select" name="status" id="status" onchange="this.form.submit()">
                                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="forward_to_sublimator" <?php echo $status_filter == 'forward_to_sublimator' ? 'selected' : ''; ?>>Forwarded to Sublimator</option>
                                            <option value="in_process" <?php echo $status_filter == 'in_process' ? 'selected' : ''; ?>>In Process</option>
                                            <option value="printing_done" <?php echo $status_filter == 'printing_done' ? 'selected' : ''; ?>>Printing Done</option>
                                            <option value="ready_for_pickup" <?php echo $status_filter == 'ready_for_pickup' ? 'selected' : ''; ?>>Ready</option>
                                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="declined" <?php echo $status_filter == 'declined' ? 'selected' : ''; ?>>Declined</option>
                                        </select>
                                    </div>
                                </div>

                                <?php if ($status_filter != 'all'): ?>
                                    <div class="col-auto">
                                        <a href="manage_orders.php" class="btn btn-secondary w-100">Clear Filters</a>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Order status tabs -->
                    <div class="nav-pills-wrapper">
                        <ul class="nav nav-pills mb-4">
                            <li class="nav-item">
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status_filter == 'approved' ? 'active' : ''; ?>" href="?status=approved">
                                    Approved <span class="badge bg-primary"><?php echo $status_counts['approved']; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status_filter == 'forward_to_sublimator' ? 'active' : ''; ?>" href="?status=forward_to_sublimator">
                                    Forwarded <span class="badge bg-info"><?php echo $status_counts['forward_to_sublimator']; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status_filter == 'in_process' ? 'active' : ''; ?>" href="?status=in_process">
                                    In Process <span class="badge bg-info"><?php echo $status_counts['in_process']; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status_filter == 'printing_done' ? 'active' : ''; ?>" href="?status=printing_done">
                                    Printing Done <span class="badge bg-success"><?php echo $status_counts['printing_done']; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status_filter == 'ready_for_pickup' ? 'active' : ''; ?>" href="?status=ready_for_pickup">
                                    Ready <span class="badge bg-success"><?php echo $status_counts['ready_for_pickup']; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status_filter == 'completed' ? 'active' : ''; ?>" href="?status=completed">
                                    Completed <span class="badge bg-secondary"><?php echo $status_counts['completed']; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status_filter == 'declined' ? 'active' : ''; ?>" href="?status=declined">
                                    Declined <span class="badge bg-danger"><?php echo $status_counts['declined']; ?></span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Orders Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?php echo $status_filter == 'all' ? 'All Orders' : ucwords(str_replace('_', ' ', $status_filter)) . ' Orders'; ?>
                            </h6>
                        </div>
                        <div class="card-body p-0"> <!-- Removed padding to maximize table space -->
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th class="col-id">ID</th>
                                                <th class="col-type d-none d-md-table-cell">Type</th>
                                                <th class="col-customer">Customer</th>
                                                <th class="col-total d-none d-lg-table-cell">Total</th>
                                                <th class="col-payment">Payment</th>
                                                <th class="col-status">Status</th>
                                                <th class="col-created d-none d-md-table-cell">Created</th>
                                                <th class="col-completion d-none d-xl-table-cell">Est. Completion</th>
                                                <th class="col-actions">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($order = mysqli_fetch_assoc($result)):
                                                // Get payment details for this order
                                                $payment_query = "SELECT * FROM payments WHERE order_id = ? ORDER BY payment_date DESC LIMIT 1";
                                                $stmt_payment = mysqli_prepare($conn, $payment_query);
                                                mysqli_stmt_bind_param($stmt_payment, "i", $order['order_id']);
                                                mysqli_stmt_execute($stmt_payment);
                                                $payment_result = mysqli_stmt_get_result($stmt_payment);
                                                $payment = mysqli_fetch_assoc($payment_result);
                                            ?>
                                                <tr class="order-row">
                                                    <td>
                                                        <a href="view_order.php?id=<?php echo htmlspecialchars($order['order_id']); ?>" class="font-weight-bold">
                                                            #<?php echo htmlspecialchars($order['order_id']); ?>
                                                        </a>
                                                    </td>
                                                    <td class="d-none d-md-table-cell">
                                                        <?php echo ucfirst(htmlspecialchars($order['order_type'])); ?>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate-custom"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                                        <small class="text-muted d-block text-truncate-custom"><?php echo htmlspecialchars($order['phone_number']); ?></small>
                                                    </td>
                                                    <td class="d-none d-lg-table-cell">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                    <td>
                                                        <?php
                                                        $payment_class = '';
                                                        switch ($order['payment_status']) {
                                                            case 'pending':
                                                                $payment_class = 'payment-pending';
                                                                break;
                                                            case 'partial':
                                                            case 'downpayment_paid':
                                                                $payment_class = 'payment-partial';
                                                                break;
                                                            case 'paid':
                                                                $payment_class = 'payment-paid';
                                                                break;
                                                        }
                                                        $payment_status = str_replace('_', ' ', ucwords($order['payment_status']));
                                                        // Shorten payment status text on small screens
                                                        if (in_array($order['payment_status'], ['downpayment_paid'])) {
                                                            $payment_status = 'Partial';
                                                        }
                                                        ?>
                                                        <span class="payment-badge <?php echo $payment_class; ?>">
                                                            <?php echo $payment_status; ?>
                                                        </span>
                                                    </td>
                                                    <td>

                                                        <?php
                                                        $status_class = '';
                                                        switch ($order['order_status']) {
                                                            case 'pending_approval':
                                                                $status_class = 'status-pending';
                                                                $status_text = 'Pending';
                                                                break;
                                                            case 'approved':
                                                                $status_class = 'status-approved';
                                                                $status_text = 'Approved';
                                                                break;
                                                            case 'forward_to_sublimator':
                                                                $status_class = 'status-in-process';
                                                                $status_text = 'Forwarded';
                                                                break;
                                                            case 'in_process':
                                                                $status_class = 'status-in-process';
                                                                $status_text = 'In Process';
                                                                break;
                                                            case 'printing_done':
                                                                $status_class = 'status-ready';
                                                                $status_text = 'Printing Done';
                                                                break;
                                                            case 'ready_for_pickup':
                                                                $status_class = 'status-ready';
                                                                $status_text = 'Ready';
                                                                break;
                                                            case 'completed':
                                                                $status_class = 'status-completed';
                                                                $status_text = 'Completed';
                                                                break;
                                                            case 'declined':
                                                                $status_class = 'status-declined';
                                                                $status_text = 'Declined';
                                                                break;
                                                            default:
                                                                $status_text = str_replace('_', ' ', ucwords($order['order_status']));
                                                        }
                                                        ?>
                                                        <span class="status-badge <?php echo $status_class; ?>">
                                                            <?php echo $status_text; ?>
                                                        </span>
                                                    </td>
                                                    <td class="d-none d-md-table-cell">
                                                        <?php echo date('M d', strtotime($order['created_at'])); ?>
                                                    </td>
                                                    <td class="d-none d-xl-table-cell">
                                                        <?php
                                                        if ($order['completion_date']) {
                                                            echo date('M d', strtotime($order['completion_date']));
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>

                                                    <td>
                                                        <!-- Replace dropdown with icons in the actions column -->
                                                        <!-- View Details Icon - Always shown for all statuses -->
                                                        <a href="view_order.php?id=<?php echo $order['order_id']; ?>"
                                                            class="btn btn-sm btn-outline-secondary me-1"
                                                            data-bs-toggle="tooltip" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($order['order_status'] == 'approved'): ?>
                                                            <!-- Assign Sublimator Button - Only for approved orders -->
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-sm btn-outline-primary"
                                                                onclick="showAssignSublimator('<?php echo $order['order_id']; ?>')"
                                                                data-bs-toggle="tooltip" title="Assign Sublimator">
                                                                <i class="fas fa-user-plus"></i>
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if ($order['order_status'] == 'ready_for_pickup'): ?>
                                                            <!-- Mark Completed Icon - Only for ready_for_pickup orders -->
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-sm btn-outline-success"
                                                                onclick="markCompleted('<?php echo $order['order_id']; ?>')"
                                                                data-bs-toggle="tooltip" title="Mark Completed">
                                                                <i class="fas fa-check-double"></i>
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if ($order['order_status'] == 'pending_approval'): ?>
                                                            <!-- Keep Approve/Decline buttons for pending_approval orders -->
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-sm btn-outline-success me-1 approve-btn"
                                                                data-id="<?php echo $order['order_id']; ?>"
                                                                data-bs-toggle="tooltip" title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-sm btn-outline-danger decline-btn"
                                                                data-id="<?php echo $order['order_id']; ?>"
                                                                data-bs-toggle="tooltip" title="Decline">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if ($order['order_status'] == 'in_process'): ?>
                                                            <!-- Mark Ready for Pickup button for in_process orders -->
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-sm btn-outline-info"
                                                                onclick="markReady('<?php echo $order['order_id']; ?>')"
                                                                data-bs-toggle="tooltip" title="Mark Ready for Pickup">
                                                                <i class="fas fa-box"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <div class="d-flex justify-content-center mt-4">
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination">
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Previous">
                                                            <span aria-hidden="true">&laquo; Previous</span>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php
                                                // Calculate range of pages to show
                                                $start_page = max(1, $page - 2);
                                                $end_page = min($total_pages, $page + 2);

                                                // Show first page if not in range
                                                if ($start_page > 1) {
                                                    echo '<li class="page-item"><a class="page-link" href="?page=1&status=' . $status_filter . '&search=' . urlencode($search_term) . '">1</a></li>';
                                                    if ($start_page > 2) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                }

                                                // Show page numbers
                                                for ($i = $start_page; $i <= $end_page; $i++) {
                                                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                                                    echo '<a class="page-link" href="?page=' . $i . '&status=' . $status_filter . '&search=' . urlencode($search_term) . '">' . $i . '</a>';
                                                    echo '</li>';
                                                }

                                                // Show last page if not in range
                                                if ($end_page < $total_pages) {
                                                    if ($end_page < $total_pages - 1) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&status=' . $status_filter . '&search=' . urlencode($search_term) . '">' . $total_pages . '</a></li>';
                                                }
                                                ?>

                                                <?php if ($page < $total_pages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Next">
                                                            <span aria-hidden="true">Next &raquo;</span>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <div class="text-center p-4">
                                    <img src="../image/no-data.svg" alt="No Orders" style="max-width: 200px;" class="mb-3">
                                    <h5>No Orders Found</h5>
                                    <p class="text-muted">There are no orders matching your criteria.</p>
                                    <?php if (!empty($search_term) || $status_filter != 'all'): ?>
                                        <a href="manage_orders.php" class="btn btn-primary mt-2">Clear Filters</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusUpdateModal" tabindex="-1" role="dialog" aria-labelledby="statusUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusUpdateModalLabel">Update Order Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="statusUpdateForm">
                        <input type="hidden" id="modal_order_id" name="order_id">
                        <input type="hidden" id="modal_status" name="status">

                        <div class="form-group">
                            <label for="notes">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Enter any notes about this status change"></textarea>
                        </div>

                        <div id="statusConfirmationText" class="alert alert-info mt-3"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmStatusUpdate" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sublimator Assignment Modal -->
    <div class="modal fade" id="assignSublimatorModal" tabindex="-1" role="dialog" aria-labelledby="assignSublimatorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignSublimatorModalLabel">Assign Sublimator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="assignSublimatorForm">
                        <input type="hidden" id="order_id" name="order_id">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-group mb-3"> <label for="sublimator_id">Select Sublimator</label>
                            <select class="form-control" id="sublimator_id" name="sublimator_id" required>
                                <?php
                                // Fetch sublimators from database
                                $sublimator_query = "SELECT user_id, username, first_name, last_name FROM users WHERE role = 'Sublimator'";
                                $sublimator_result = mysqli_query($conn, $sublimator_query);
                                if (mysqli_num_rows($sublimator_result) > 0) {
                                    while ($sublimator = mysqli_fetch_assoc($sublimator_result)) {
                                        echo '<option value="' . $sublimator['user_id'] . '">' .
                                            htmlspecialchars($sublimator['first_name'] . ' ' . $sublimator['last_name']) .
                                            ' (' . htmlspecialchars($sublimator['username']) . ')</option>';
                                    }
                                } else {
                                    echo '<option value="">No sublimators available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="assign_notes">Notes (Optional)</label>
                            <textarea class="form-control" id="assign_notes" name="notes" rows="3" placeholder="Enter any notes about this assignment"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmAssignment" class="btn btn-primary">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Keep your other event handlers
        });

        // Function to mark an order as completed
        function markCompleted(orderId) {
            // First check if the order meets the conditions: fully paid and ready for pickup
            $.ajax({
                url: 'check_order_status.php',
                type: 'POST',
                data: {
                    order_id: orderId
                },
                success: function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;

                        // Check if order is ready for pickup and fully paid
                        if (data.status === 'success' && data.order_status === 'ready_for_pickup' &&
                            (data.payment_status === 'paid' || data.payment_status === 'fully_paid')) {

                            // Proceed with the confirmation dialog
                            Swal.fire({
                                title: 'Mark as Completed?',
                                text: "This will finalize the order and mark it as completed.",
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonColor: '#28a745',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, mark as completed'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Show loading state
                                    Swal.fire({
                                        title: 'Processing...',
                                        text: 'Updating order status',
                                        didOpen: () => {
                                            Swal.showLoading();
                                        },
                                        allowOutsideClick: false,
                                        showConfirmButton: false
                                    });

                                    // Send AJAX request
                                    $.ajax({
                                        url: 'handle_order_mark_completed.php',
                                        type: 'POST',
                                        data: {
                                            order_id: orderId,
                                            notes: 'Order marked as completed'
                                        },
                                        success: function(response) {
                                            try {
                                                const data = typeof response === 'string' ? JSON.parse(response) : response;

                                                if (data.status === 'success' || data.success === true) {
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Order Completed!',
                                                        text: 'The order has been marked as completed successfully.',
                                                        showConfirmButton: false,
                                                        timer: 1500
                                                    }).then(() => {
                                                        location.reload();
                                                    });
                                                } else {
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Error',
                                                        text: data.message || 'An error occurred while updating status.'
                                                    });
                                                }
                                            } catch (e) {
                                                console.error("Error parsing response:", e, response);
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Error',
                                                    text: 'Server error: Invalid response format'
                                                });
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            console.error("AJAX error:", xhr.responseText);
                                            let errorMessage = 'Server error';
                                            try {
                                                const response = JSON.parse(xhr.responseText);
                                                errorMessage = response.message || error;
                                            } catch (e) {
                                                errorMessage = xhr.responseText || error;
                                            }
                                            
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error',
                                                text: 'Server error: ' + errorMessage
                                            });
                                        }
                                    });
                                }
                            });
                        } else {
                            // Show error message about conditions not being met
                            let message = "This order cannot be marked as completed. ";
                            if (data.order_status !== 'ready_for_pickup') {
                                message += "Order must be in 'Ready for Pickup' status. ";
                            }
                            if (data.payment_status !== 'paid' && data.payment_status !== 'fully_paid') {
                                message += "Order must be fully paid.";
                            }

                            Swal.fire({
                                icon: 'warning',
                                title: 'Cannot Complete Order',
                                text: message,
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    } catch (e) {
                        console.error("Error checking order status:", e, response);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not verify order status. Please try again.'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error checking order status:", xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to check order status: ' + error
                    });
                }
            });
        }

        // Function to mark an order as in process
        function markInProcess(orderId) {
            // Show loading state while checking
            Swal.fire({
                title: 'Checking order...',
                text: 'Verifying if order can be processed',
                didOpen: () => {
                    Swal.showLoading();
                },
                allowOutsideClick: false,
                showConfirmButton: false
            });

            // First check if the order meets the conditions: partial payment and approved status
            $.ajax({
                url: 'check_order_status.php',
                type: 'POST',
                data: {
                    order_id: orderId
                },
                success: function(response) {
                    try {
                        console.log('Raw response:', response);
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        // Log all data for debugging
                        console.log('Order Check Response:', data);

                        // Close the loading dialog
                        Swal.close();

                        if (data.status === 'success' && data.can_process === true) {
                            // Proceed with the confirmation dialog
                            Swal.fire({
                                title: 'Mark as In Process?',
                                text: "This will update the order status to 'In Process'.",
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, mark as in process'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Show loading state
                                    Swal.fire({
                                        title: 'Processing...',
                                        text: 'Updating order status',
                                        didOpen: () => {
                                            Swal.showLoading();
                                        },
                                        allowOutsideClick: false,
                                        showConfirmButton: false
                                    });
                                    // Send AJAX request
                                    $.ajax({
                                        url: 'update_order_status.php',
                                        type: 'POST',
                                        data: {
                                            order_id: orderId,
                                            new_status: 'in_process',
                                            notes: 'Order moved to production.'
                                        },
                                        success: function(updateResponse) {
                                            console.log('Update response:', updateResponse);
                                            try {
                                                const updateData = typeof updateResponse === 'string' ? JSON.parse(updateResponse) : updateResponse;
                                                if (updateData.status === 'success' || updateData.success === true) {
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Status Updated!',
                                                        text: 'Order has been marked as in process.',
                                                        showConfirmButton: false,
                                                        timer: 1500
                                                    }).then(() => {
                                                        location.reload();
                                                    });
                                                } else {
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Error',
                                                        text: updateData.message || 'An error occurred while updating status.'
                                                    });
                                                }
                                            } catch (e) {
                                                console.error("Error parsing update response:", e, updateResponse);
                                                // Handle non-JSON responses
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Error',
                                                    text: 'An error occurred while updating status. Please try again.'
                                                });
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            console.error("AJAX error:", xhr.responseText);
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error',
                                                text: 'Server error: ' + error
                                            });
                                        }
                                    });
                                }
                            });
                        } else {
                            // Show error message from the server with more detail
                            let errorMessage = "This order cannot be processed at this time.";

                            if (data.reason) {
                                errorMessage = data.reason;
                            }

                            errorMessage += "\n\nDebug info:";
                            errorMessage += "\nOrder status: " + data.order_status;
                            errorMessage += "\nPayment status: " + data.payment_status;

                            Swal.fire({
                                icon: 'warning',
                                title: 'Cannot Process Order',
                                text: errorMessage,
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    } catch (e) {
                        console.error("Error checking order status:", e, response);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not verify order status: ' + e.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error checking order status:", xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to check order status: ' + error
                    });
                }
            });
        }

        // Function to mark an order as ready for pickup
        function markReady(orderId) {
            // Confirm with the user
            Swal.fire({
                title: 'Mark as Ready for Pickup?',
                text: "This will update the order status to 'Ready for Pickup'.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8', // Info blue color
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, mark as ready'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Updating order status',
                        didOpen: () => {
                            Swal.showLoading();
                        },
                        allowOutsideClick: false,
                        showConfirmButton: false
                    });

                    // Send AJAX request
                    $.ajax({
                        url: 'update_order_status.php',
                        type: 'POST',
                        data: {
                            order_id: orderId,
                            new_status: 'ready_for_pickup',
                            notes: 'Order is ready for pickup.'
                        },
                        success: function(response) {
                            try {
                                const data = typeof response === 'string' ? JSON.parse(response) : response;

                                if (data.status === 'success' || data.success === true) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Status Updated!',
                                        text: 'Order has been marked as ready for pickup.',
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message || 'An error occurred while updating status.'
                                    });
                                }
                            } catch (e) {
                                // Handle non-JSON responses
                                if (response === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Status Updated!',
                                        text: 'Order has been marked as ready for pickup.',
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'An error occurred while updating status: ' + response
                                    });
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX error:", xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Server error: ' + error
                            });
                        }
                    });
                }
            });
        }

        // Mark as paid button
        $('.mark-paid-btn').click(function(e) {
            e.preventDefault();
            const orderId = $(this).data('id');

            if (confirm('Mark this order as fully paid?')) {
                $.ajax({
                    url: 'update_payment_status.php',
                    type: 'POST',
                    data: {
                        order_id: orderId,
                        status: 'paid',
                        notes: 'Payment has been completed.'
                    },
                    success: function(response) {
                        if (response === 'success') {
                            location.reload();
                        } else {
                            alert('Error updating payment: ' + response);
                        }
                    }
                });
            }
        });

        // Add note button
        $('.add-note-btn').click(function(e) {
            e.preventDefault();
            const orderId = $(this).data('id');

            $('#note_order_id').val(orderId);

            const noteModal = new bootstrap.Modal(document.getElementById('addNoteModal'));
            noteModal.show();
        });

        // Add note action
        $('#addNoteBtn').click(function() {
            const orderId = $('#note_order_id').val();
            const note = $('#note_content').val();

            if (!note.trim()) {
                alert('Please enter a note.');
                return;
            }

            $.ajax({
                url: 'add_order_note.php',
                type: 'POST',
                data: {
                    order_id: orderId,
                    note: note
                },
                success: function(response) {
                    if (response === 'success') {
                        alert('Note added successfully.');
                        const noteModal = bootstrap.Modal.getInstance(document.getElementById('addNoteModal'));
                        noteModal.hide();
                    } else {
                        alert('Error adding note: ' + response);
                    }
                }
            });
        });

        // Function to show sublimator assignment modal
        function showAssignSublimator(orderId) {
            // Clear previous values
            $('#assign_notes').val('');
            $('#order_id').val(orderId);
            
            // Check if this order uses a template and get template creator
            $.ajax({
                url: 'get_order_template_info.php',
                type: 'POST',
                data: {
                    order_id: orderId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.has_template && response.creator_id) {
                        // If the order uses a template, ask if they want to assign to template creator
                        Swal.fire({
                            title: 'Template Creator Found',
                            html: `This order uses template "${response.template_name}" created by ${response.creator_name}.<br><br>Would you like to assign this order to the template creator?`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, assign to creator',
                            cancelButtonText: 'No, choose another sublimator'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Assign to template creator directly
                                assignToSublimator(orderId, response.creator_id, `Auto-assigned to template creator: ${response.creator_name}`);
                            } else {
                                // Show the modal to select a different sublimator
                                // Pre-select the template creator in the dropdown
                                $('#sublimator_id').val(response.creator_id);
                                var modal = new bootstrap.Modal(document.getElementById('assignSublimatorModal'));
                                modal.show();
                            }
                        });
                    } else {
                        // If no template or creator found, show the regular modal
                        var modal = new bootstrap.Modal(document.getElementById('assignSublimatorModal'));
                        modal.show();
                    }
                },
                error: function() {
                    // On error, just show the regular modal
                    var modal = new bootstrap.Modal(document.getElementById('assignSublimatorModal'));
                    modal.show();
                }
            });
        }
        
        // Function to directly assign to a sublimator without showing the modal
        function assignToSublimator(orderId, sublimatorId, notes) {
            // Show loading state
            Swal.fire({
                title: 'Assigning Sublimator...',
                text: 'Please wait',
                didOpen: () => {
                    Swal.showLoading();
                },
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            // Send assignment request
            $.ajax({
                url: 'assign_sublimator.php',
                type: 'POST',
                data: {
                    order_id: orderId,
                    sublimator_id: sublimatorId,
                    notes: notes,
                    csrf_token: $('input[name="csrf_token"]').val()
                },
                dataType: 'json',
                cache: false,
                success: function(response) {
                    try {
                        console.log('Response:', response); // Debug log
                        const data = typeof response === 'string' ? JSON.parse(response) : response;

                        if (data.success === true) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sublimator Assigned!',
                                text: data.message || 'The order has been assigned successfully.',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            console.error('Error response:', data);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to assign sublimator.'
                            });
                        }
                    } catch (e) {
                        console.error("Error parsing response:", e, response);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An unexpected error occurred. Please try again.'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", xhr.responseText);
                    let errorMessage = 'Server error';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || error;
                    } catch (e) {
                        errorMessage = xhr.responseText || error;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Server error: ' + errorMessage
                    });
                }
            });
        }
        
        // Handle sublimator assignment from modal
        $(document).ready(function() {
            $('#confirmAssignment').click(function(e) {
                e.preventDefault();

                // Get form values
                const orderId = $('#order_id').val();
                const sublimatorId = $('#sublimator_id').val();
                const notes = $('#assign_notes').val();
                const csrf_token = $('input[name="csrf_token"]').val();

                // Validate required fields
                if (!sublimatorId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please select a sublimator'
                    });
                    return;
                }

                if (!csrf_token) {
                    console.error('CSRF token is missing');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Security token missing. Please refresh the page.'
                    });
                    return;
                }

                if (!orderId) {
                    console.error('Order ID is missing');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Order information is missing. Please refresh the page.'
                    });
                    return;
                }

                // Use the assignToSublimator function for consistency
                assignToSublimator(orderId, sublimatorId, notes);
                
                // Close the modal
                var modal = bootstrap.Modal.getInstance(document.getElementById('assignSublimatorModal'));
                if (modal) {
                    modal.hide();
                }
            });
        });
    </script>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>