<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get search term and status filter
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build the query with filters
$where_clauses = ["o.order_type = 'sublimation'"];
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

$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

$where_clause = implode(" AND ", $where_clauses);

// Main query for orders
$query = "SELECT o.*, c.first_name, c.last_name, c.phone_number,
          s.sublimation_id, s.printing_type, s.sublimator_id,
          t.template_id, t.name as template_name, t.image_path as template_image,
          sp.player_name, sp.jersey_number, sp.size as jersey_size,
          COALESCE(u.first_name, '') as sublimator_fname,
          COALESCE(u.last_name, '') as sublimator_lname
          FROM orders o
          LEFT JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN sublimation_orders s ON o.order_id = s.order_id
          LEFT JOIN users u ON s.sublimator_id = u.user_id
          LEFT JOIN templates t ON s.template_id = t.template_id
          LEFT JOIN sublimation_players sp ON s.sublimation_id = sp.sublimation_id
          WHERE $where_clause
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
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    die("Error preparing query: " . mysqli_error($conn));
}

if (!empty($params)) {
    if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
        die("Error binding parameters: " . mysqli_stmt_error($stmt));
    }
}

if (!mysqli_stmt_execute($stmt)) {
    die("Error executing query: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);
if ($result === false) {
    die("Error getting results: " . mysqli_error($conn));
}

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total FROM orders o WHERE " . $where_clause;
$stmt = mysqli_prepare($conn, $count_query);
if (!$stmt) {
    die("Error preparing count query: " . mysqli_error($conn));
}

if (!empty($params)) {
    // Remove the limit and offset parameters
    array_pop($params);
    array_pop($params);
    $types = substr($types, 0, -2);
    if (!empty($params)) {
        if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
            die("Error binding parameters: " . mysqli_stmt_error($stmt));
        }
    }
}

if (!mysqli_stmt_execute($stmt)) {
    die("Error executing count query: " . mysqli_stmt_error($stmt));
}

$count_result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get list of available sublimators
$sublimators_query = "SELECT user_id, first_name, last_name FROM users WHERE role = 'sublimator'";
$sublimators_result = mysqli_query($conn, $sublimators_query);
$sublimators = mysqli_fetch_all($sublimators_result, MYSQLI_ASSOC);

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

$count_query = "SELECT order_status, COUNT(*) as count FROM orders o WHERE o.order_type = 'sublimation' GROUP BY order_status";
$count_result = mysqli_query($conn, $count_query);

while ($row = mysqli_fetch_assoc($count_result)) {
    $status_counts[$row['order_status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JXT - Sublimation Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            overflow-x: hidden;
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

        .text-truncate-custom {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .payment-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .payment-pending { background-color: #ffeeba; color: #856404; }
        .payment-partial { background-color: #d4edda; color: #155724; }
        .payment-paid { background-color: #cce5ff; color: #004085; }
        
        .status-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #e8f5e9; color: #2e7d32; }
        .status-in-process { background-color: #e1f5fe; color: #0277bd; }
        .status-ready { background-color: #e0f7fa; color: #006064; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-declined { background-color: #f8d7da; color: #721c24; }
        
        .btn-responsive {
            white-space: nowrap;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }

        /* Card color variations */
        .border-pending { border-left-color: #ffc107; }
        .border-approved { border-left-color: #28a745; }
        .border-in-process { border-left-color: #007bff; }
        .border-ready { border-left-color: #6c757d; }
        .border-completed { border-left-color: #17a2b8; }
        .border-declined { border-left-color: #dc3545; }

        /* Table styles */
        .table {
            font-size: 0.85rem;
        }

        .table td, .table th {
            padding: 0.5rem !important;
            vertical-align: middle;
        }

        /* Column widths */
        .col-id { width: 8%; }
        .col-type { width: 10%; }
        .col-customer { width: 15%; }
        .col-total { width: 8%; }
        .col-payment, .col-status { width: 10%; }
        .col-created, .col-completion { width: 10%; }
        .col-actions { width: 8%; }

        /* Mobile card view */
        .order-card-mobile {
            display: none;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid #4e73df;
        }

        @media (max-width: 992px) {
            .d-lg-table-cell {
                display: none !important;
            }
        }

        /* Dropdown improvements */
        .dropdown-menu {
            min-width: 200px;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
        }

        .dropdown-item.active, .dropdown-item:active {
            background-color: #4e73df;
        }

        /* Action buttons */
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
    </style>

    <!-- Add SweetAlert2 library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'topbar.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Sublimation Orders</h1>

                    <!-- Status Filter Cards -->
                    <div class="row mb-4">                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2 <?= $status_filter == 'approved' ? 'bg-light' : '' ?>">
                                <a href="?status=approved" class="text-decoration-none">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Approved Orders
                                                    <span class="badge bg-success text-dark float-end"><?= $status_counts['approved'] ?></span>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2 <?= $status_filter == 'in_process' ? 'bg-light' : '' ?>">
                                <a href="?status=in_process" class="text-decoration-none">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                    In Process
                                                    <span class="badge bg-info text-dark float-end"><?= $status_counts['in_process'] ?></span>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-spinner fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2 <?= $status_filter == 'ready_for_pickup' ? 'bg-light' : '' ?>">
                                <a href="?status=ready_for_pickup" class="text-decoration-none">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Ready for Pickup
                                                    <span class="badge bg-success text-dark float-end"><?= $status_counts['ready_for_pickup'] ?></span>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-check fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-secondary shadow h-100 py-2 <?= $status_filter == 'completed' ? 'bg-light' : '' ?>">
                                <a href="?status=completed" class="text-decoration-none">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                                    Completed
                                                    <span class="badge bg-secondary text-dark float-end"><?= $status_counts['completed'] ?></span>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-flag-checkered fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?php echo $status_filter == 'all' ? 'All Sublimation Orders' : ucwords(str_replace('_', ' ', $status_filter)) . ' Orders'; ?>
                            </h6>
                            <?php if (!empty($search_term) || $status_filter != 'all'): ?>
                                <a href="sublimation_orders.php" class="btn btn-sm btn-outline-primary">Clear Filters</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if (mysqli_num_rows($result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="col-id">ID</th>
                                            <th class="col-type d-none d-md-table-cell">Template</th>
                                            <th class="col-customer">Customer</th>
                                            <th class="col-total d-none d-lg-table-cell">Total</th>
                                            <th class="col-payment">Payment</th>
                                            <th class="col-status">Status</th>
                                            <th class="col-sublimator d-none d-lg-table-cell">Sublimator</th>
                                            <th class="col-created d-none d-md-table-cell">Created</th>
                                            <th class="col-actions">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                                            <tr class="order-row">
                                                <td>
                                                    <a href="view_order.php?id=<?= htmlspecialchars($order['order_id']) ?>" class="font-weight-bold">
                                                        #<?= htmlspecialchars($order['order_id']) ?>
                                                    </a>
                                                </td>
                                                <td class="d-none d-md-table-cell">
                                                    <?php if ($order['template_name']): ?>
                                                        <div class="text-truncate-custom">
                                                            <?= htmlspecialchars($order['template_name']) ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Custom Design</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="text-truncate-custom">
                                                        <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                                                    </div>
                                                    <small class="text-muted d-block text-truncate-custom">
                                                        <?= htmlspecialchars($order['phone_number']) ?>
                                                    </small>
                                                </td>
                                                <td class="d-none d-lg-table-cell">
                                                    ₱<?= number_format($order['total_amount'], 2) ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $payment_class = '';
                                                    switch ($order['payment_status']) {
                                                        case 'pending':
                                                            $payment_class = 'payment-pending';
                                                            break;
                                                        case 'downpayment_paid':
                                                            $payment_class = 'payment-partial';
                                                            break;
                                                        case 'fully_paid':
                                                            $payment_class = 'payment-paid';
                                                            break;
                                                    }
                                                    $payment_status = str_replace('_', ' ', ucwords($order['payment_status']));
                                                    if ($order['payment_status'] === 'downpayment_paid') {
                                                        $payment_status = 'Partial';
                                                    }
                                                    ?>
                                                    <span class="payment-badge <?= $payment_class ?>">
                                                        <?= $payment_status ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($order['order_status']) {
                                                        case 'pending_approval':
                                                            $status_class = 'status-pending';
                                                            break;
                                                        case 'approved':
                                                            $status_class = 'status-approved';
                                                            break;
                                                        case 'in_process':
                                                            $status_class = 'status-in-process';
                                                            break;
                                                        case 'ready_for_pickup':
                                                            $status_class = 'status-ready';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'status-completed';
                                                            break;
                                                        case 'declined':
                                                            $status_class = 'status-declined';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="status-badge <?= $status_class ?>">
                                                        <?= str_replace('_', ' ', ucwords($order['order_status'])) ?>
                                                    </span>
                                                </td>
                                                <td class="d-none d-lg-table-cell">
                                                    <?php 
                                                    if ($order['sublimator_id']) {
                                                        echo htmlspecialchars($order['sublimator_fname'] . ' ' . $order['sublimator_lname']);
                                                    } else {
                                                        echo '<span class="text-muted">Unassigned</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="d-none d-md-table-cell">
                                                    <?= date('M d', strtotime($order['created_at'])) ?>
                                                </td>
                                                <td>                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-light dropdown-toggle btn-responsive" type="button" id="actionDropdown<?= $order['order_id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown<?= $order['order_id'] ?>">
                                                            <li>
                                                                <a class="dropdown-item" href="view_order.php?id=<?= $order['order_id'] ?>">
                                                                    <i class="fas fa-eye fa-sm fa-fw mr-2 text-gray-400"></i> View Details
                                                                </a>
                                                            </li>
                                                            
                                                            <?php if ($order['order_status'] === 'pending_approval'): ?>
                                                            <li>
                                                                <a class="dropdown-item text-primary" href="#" onclick="approveOrder('<?= $order['order_id'] ?>')">
                                                                    <i class="fas fa-check fa-sm fa-fw mr-2 text-primary"></i> Approve Order
                                                                </a>
                                                            </li>                                            <?php endif; ?>                                            <?php if ($order['order_status'] === 'approved' && !$order['sublimator_id']): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="assignSublimator('<?= $order['order_id'] ?>', '<?= $order['template_id'] ?>')">
                                                                    <i class="fas fa-share fa-sm fa-fw mr-2 text-gray-400"></i> Forward to Sublimator
                                                                </a>
                                                            </li>
                                                            <?php endif; ?>

                                                            <?php if ($order['order_status'] === 'completed_by_sublimator'): ?>
                                                            <li>
                                                                <a class="dropdown-item text-primary mark-ready" href="#" onclick="markReadyForPickup('<?= $order['order_id'] ?>')">
                                                                    <i class="fas fa-check fa-sm fa-fw mr-2 text-primary"></i> Mark Ready for Pickup
                                                                </a>
                                                            </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-center mt-4">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= ($page - 1) ?>&status=<?= $status_filter ?>&search=<?= urlencode($search_term) ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
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
                                                <a class="page-link" href="?page=<?= ($page + 1) ?>&status=<?= $status_filter ?>&search=<?= urlencode($search_term) ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
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
                                    <a href="sublimation_orders.php" class="btn btn-primary mt-2">Clear Filters</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Sublimator Modal -->
    <div class="modal fade" id="assignSublimatorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Forward to Sublimator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">                    <form id="assignSublimatorForm" method="post" onsubmit="return false;">
                        <input type="hidden" id="order_id" name="order_id">
                        <div class="form-group">
                            <label>Select Sublimator</label>
                            <select class="form-control" name="user_id" id="sublimator_select" required>
                                <option value="">Choose a sublimator...</option>
                                <?php foreach ($sublimators as $sublimator): ?>
                                    <option value="<?= $sublimator['user_id'] ?>"><?= htmlspecialchars($sublimator['first_name'] . ' ' . $sublimator['last_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mt-3">
                            <label>Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitAssignment()">Forward Order</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>    <script>
    function approveOrder(orderId) {
        if(confirm('Are you sure you want to approve this order?')) {
            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: 'approved'
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'An error occurred while approving the order.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
            });
        }
    }    function assignSublimator(orderId, templateId) {
        document.getElementById('order_id').value = orderId;
        
        if (templateId) {
            fetch('get_template_creator.php?template_id=' + templateId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.user_id) {
                        const sublimatorSelect = document.getElementById('sublimator_select');
                        sublimatorSelect.value = data.user_id;
                        Swal.fire({
                            title: 'Template Creator Found',
                            text: `This order will be forwarded to ${data.name}. Would you like to proceed?`,
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, forward',
                            cancelButtonText: 'Choose different sublimator'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                submitAssignment();
                            } else if (result.dismiss === Swal.DismissReason.cancel) {
                                new bootstrap.Modal(document.getElementById('assignSublimatorModal')).show();
                            }
                        });
                    } else {
                        new bootstrap.Modal(document.getElementById('assignSublimatorModal')).show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    new bootstrap.Modal(document.getElementById('assignSublimatorModal')).show();
                });
        } else {
            new bootstrap.Modal(document.getElementById('assignSublimatorModal')).show();
        }
    }function submitAssignment() {
        const form = document.getElementById('assignSublimatorForm');
        const sublimatorSelect = document.getElementById('sublimator_select');
        const orderId = document.getElementById('order_id').value;
        
        if (!sublimatorSelect.value) {
            alert('Please select a sublimator');
            return;
        }
        
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('user_id', sublimatorSelect.value);
        formData.append('notes', form.querySelector('textarea[name="notes"]').value);

        const submitButton = document.querySelector('#assignSublimatorModal .btn-primary');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

        fetch('assign_sublimator.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert(data.message || 'An error occurred while assigning the sublimator.');
                submitButton.disabled = false;
                submitButton.innerHTML = 'Forward Order';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again later.');
            submitButton.disabled = false;
            submitButton.innerHTML = 'Forward Order';
        });
    }

    function markReadyForPickup(orderId) {
        if(confirm('Mark this order as ready for pickup?')) {
            const button = event.target;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: 'ready_for_pickup'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'An error occurred while updating the order status.');
                    button.disabled = false;
                    button.innerHTML = 'Mark Ready for Pickup';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
                button.disabled = false;
                button.innerHTML = 'Mark Ready for Pickup';
            });
        }
    }

    function getStatusBadgeClass(status) {
        switch(status) {
            case 'pending_approval': return 'warning';
            case 'approved': return 'primary';
            case 'forwarded_to_sublimator': return 'info';
            case 'in_process': return 'info';
            case 'completed_by_sublimator': return 'secondary';
            case 'ready_for_pickup': return 'success';
            case 'completed': return 'secondary';
            case 'declined': return 'danger';
            default: return 'secondary';
        }
    }

    function formatStatus(status) {
        return status.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }
    </script>
</body>
</html>
