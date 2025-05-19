<?php
session_start();

// If the user is not logged in, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
include '../db.php';

// Status filter (default to 'all')
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page

// Build the query with filters
$where_clauses = [];
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
            WHEN o.order_type = 'sublimation' THEN s.completion_date
            WHEN o.order_type = 'tailoring' THEN t.completion_date
            ELSE NULL
         END AS completion_date
         FROM orders o
         LEFT JOIN customers c ON o.customer_id = c.customer_id
         LEFT JOIN sublimation_orders s ON o.order_id = s.order_id AND o.order_type = 'sublimation'
         LEFT JOIN tailoring_orders t ON o.order_id = t.order_id AND o.order_type = 'tailoring'
         $where_clause
         ORDER BY 
            CASE 
                WHEN o.order_status = 'pending_approval' THEN 1
                WHEN o.order_status = 'approved' THEN 2
                WHEN o.order_status = 'in_process' THEN 3
                WHEN o.order_status = 'ready_for_pickup' THEN 4
                WHEN o.order_status = 'completed' THEN 5
                ELSE 6
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
    'all' => $total_records,
    'pending_approval' => 0,
    'approved' => 0,
    'in_process' => 0,
    'ready_for_pickup' => 0,
    'completed' => 0,
    'declined' => 0
];

$count_query = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
$count_result = mysqli_query($conn, $count_query);

while ($row = mysqli_fetch_assoc($count_result)) {
    $status_counts[$row['order_status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image" href="../image/logo.png">
    <title>JXT - Manage Orders</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome & Custom Fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }
        
        .order-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
            margin-bottom: 1rem;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
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
        
        .dropdown-item.active, .dropdown-item:active {
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
        .table td, .table th {
            padding: 1rem;
            vertical-align: middle;
        }
        
        /* Card view for mobile devices */
        .order-card-mobile {
            display: none;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        .status-badge, .payment-badge {
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
            font-size: 0.85rem; /* Reduce overall font size */
        }

        .table td, .table th {
            padding: 0.5rem !important; /* Reduce cell padding by half */
            vertical-align: middle;
        }

        /* Make action button smaller */
        .btn-responsive {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        /* Make status and payment badges more compact */
        .status-badge, .payment-badge {
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
        .col-payment, .col-status {
            width: 10%;
        }
        .col-created, .col-completion {
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
                            Manage Orders
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
                                            <option value="in_process" <?php echo $status_filter == 'in_process' ? 'selected' : ''; ?>>In Process</option>
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
                                <a class="nav-link <?php echo $status_filter == 'all' ? 'active' : ''; ?>" href="?status=all">
                                    All <span class="badge bg-secondary"><?php echo $status_counts['all']; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status_filter == 'approved' ? 'active' : ''; ?>" href="?status=approved">
                                    Approved <span class="badge bg-primary"><?php echo $status_counts['approved']; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $status_filter == 'in_process' ? 'active' : ''; ?>" href="?status=in_process">
                                    In Process <span class="badge bg-info"><?php echo $status_counts['in_process']; ?></span>
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
                                                            case 'in_process':
                                                                $status_class = 'status-in-process';
                                                                $status_text = 'In Process';
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
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-light dropdown-toggle btn-responsive" type="button" id="actionDropdown<?php echo $order['order_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                                Actions
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown<?php echo $order['order_id']; ?>">
                                                                <li>
                                                                    <a class="dropdown-item" href="view_order.php?id=<?php echo $order['order_id']; ?>">
                                                                        <i class="fas fa-eye fa-sm fa-fw mr-2 text-gray-400"></i> View Details
                                                                    </a>        
                                                                </li>
                                                                <?php if ($order['order_status'] == 'pending_approval'): ?>
                                                                <li>
                                                                    <a class="dropdown-item text-success approve-btn" href="#" data-id="<?php echo $order['order_id']; ?>">
                                                                        <i class="fas fa-check fa-sm fa-fw mr-2 text-success"></i> Approve
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger decline-btn" href="#" data-id="<?php echo $order['order_id']; ?>">
                                                                        <i class="fas fa-times fa-sm fa-fw mr-2 text-danger"></i> Decline
                                                                    </a>
                                                                </li>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($order['order_status'] == 'approved'): ?>
                                                                <li>
                                                                    <a class="dropdown-item text-primary process-btn" href="#" data-id="<?php echo $order['order_id']; ?>">
                                                                        <i class="fas fa-cogs fa-sm fa-fw mr-2 text-primary"></i> Mark In Process
                                                                    </a>
                                                                </li>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($order['order_status'] == 'in_process'): ?>
                                                                <li>
                                                                    <a class="dropdown-item text-info" href="javascript:void(0)" onclick="markReady('<?php echo $order['order_id']; ?>')">
                                                                        <i class="fas fa-box fa-sm fa-fw mr-2 text-info"></i> Mark Ready for Pickup
                                                                    </a>
                                                                </li>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($order['order_status'] == 'ready_for_pickup'): ?>
                                                                <li>
                                                                    <a class="dropdown-item text-success complete-btn" href="#" data-id="<?php echo $order['order_id']; ?>">
                                                                        <i class="fas fa-check-double fa-sm fa-fw mr-2 text-success"></i> Mark Completed
                                                                    </a>
                                                                </li>
                                                                <?php endif; ?>
                                                                
                                                                <!-- Payment meta section removed as requested -->
                                                                
                                                            </ul>
                                                        </div>
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
                                        
                                        if (data.status === 'success') {
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
                                    } catch(e) {
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
            } catch(e) {
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
    // First check if the order meets the conditions: partial payment and approved status
    $.ajax({
        url: 'check_order_status.php',
        type: 'POST',
        data: {
            order_id: orderId
        },
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                
                // Check if order is approved and has partial payment
                if (data.status === 'success' && data.order_status === 'approved' && 
                   (data.payment_status === 'partial' || data.payment_status === 'downpayment_paid')) {
                    
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
                                    status: 'in_process',
                                    notes: 'Order moved to production.'
                                },
                                success: function(response) {
                                    try {
                                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                                        
                                        if (data.status === 'success') {
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
                                                text: data.message || 'An error occurred while updating status.'
                                            });
                                        }
                                    } catch(e) {
                                        // Handle non-JSON responses
                                        if (response === 'success') {
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
                } else {
                    // Show error message about conditions not being met
                    let message = "This order cannot be marked as in process. ";
                    if (data.order_status !== 'approved') {
                        message += "Order must be in 'Approved' status. ";
                    }
                    if (data.payment_status !== 'partial' && data.payment_status !== 'downpayment_paid') {
                        message += "Order must have a partial payment or downpayment.";
                    }
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Process Order',
                        text: message,
                        confirmButtonColor: '#3085d6'
                    });
                }
            } catch(e) {
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
    <?php
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update the order status to completed
        $update_order = "UPDATE orders SET 
                        order_status = 'completed',
                        -- completed_at = NOW(),
                        -- completed_by = ?,
                        updated_at = NOW()
                        WHERE order_id = ?";
                        
        $stmt = $conn->prepare($update_order);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        // Make sure $user_id and $order_id are defined and not null
        if (isset($user_id) || isset( $order_id)) {
            throw new Exception("User ID or Order ID is missing");
        }
        
        $stmt->bind_param("is", $user_id, $order_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update order status: " . $stmt->error);
        }
        
        // Get customer details for notification
        $customer_query = "SELECT c.customer_id, c.first_name, c.last_name, o.order_type 
                          FROM orders o
                          JOIN customers c ON o.customer_id = c.customer_id
                          WHERE o.order_id = ?";
                          
        $stmt = $conn->prepare($customer_query);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer_data = $result->fetch_assoc();
        
        if (!$customer_data) {
            throw new Exception("Customer information not found");
        }
        
        // Create a notification for the customer
        $title = "Order Completed";
        $message = "Great news! Your " . ucfirst($customer_data['order_type']) . " order (#$order_id) has been marked as completed.";
        
        $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) 
                              VALUES (?, ?, ?, ?, NOW())";
                              
        $stmt = $conn->prepare($notification_query);
        $stmt->bind_param("isss", $customer_data['customer_id'], $order_id, $title, $message);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create notification");
        }
        
        // Optional: Add a note about the completion
        $note = "Order marked as completed by " . $_SESSION['first_name'] . " " . ($_SESSION['last_name'] ?? '');
        $note_query = "INSERT INTO notes (order_id, user_id, note, created_at)
                      VALUES (?, ?, ?, NOW())";
                      
        $stmt = $conn->prepare($note_query);
        $stmt->bind_param("sis", $order_id, $user_id, $note);
        $stmt->execute(); // We don't need to throw an exception if this fails
        
        // Commit the transaction
        $conn->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'Order marked as completed successfully']);
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

?>
</script>