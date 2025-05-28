<?php
session_start();
include '../db.php';

// Use session user ID instead of hardcoded value
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Sublimator') {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

$sublimator_id = $_SESSION['user_id'];

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Remove debug output
// echo $page . "<br>";
// echo $records_per_page . "<br>";
// echo $offset . "<br>";

// Define PHP functions
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'forward_to_sublimator':
            return 'info';
        case 'in_process':
            return 'warning';
        case 'printing_done':
            return 'primary';
        case 'ready_for_pickup':
            return 'success';
        default:
            return 'secondary';
    }
}

function formatStatus($status) {
    return implode(' ', array_map(function($word) {
        return ucfirst($word);
    }, explode('_', $status)));
}

// Get assigned sublimation orders
try {
    $query = "SELECT o.*, c.first_name, c.last_name, c.phone_number,
              s.completion_date, s.printing_type, s.quantity, s.size, s.color
              FROM orders o
              INNER JOIN sublimation_orders s ON o.order_id = s.order_id
              LEFT JOIN customers c ON o.customer_id = c.customer_id
              WHERE o.order_type = 'sublimation' 
              AND (o.order_status = 'forward_to_sublimator' 
                   OR o.order_status = 'in_process'
                   OR o.order_status = 'printing_done'
                   OR o.order_status = 'ready_for_pickup')
              AND s.sublimator_id = ?
              ORDER BY o.created_at DESC
              LIMIT ? OFFSET ?";

    // Prepare and execute the main query
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new mysqli_sql_exception("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "iii", $sublimator_id, $records_per_page, $offset);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new mysqli_sql_exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new mysqli_sql_exception("Could not get result set");
    }

    // Single count query for pagination
    $count_query = "SELECT COUNT(*) as total 
                    FROM orders o 
                    INNER JOIN sublimation_orders s ON o.order_id = s.order_id
                    WHERE o.order_type = 'sublimation'
                    AND (o.order_status = 'forward_to_sublimator' 
                         OR o.order_status = 'in_process'
                         OR o.order_status = 'printing_done'
                         OR o.order_status = 'ready_for_pickup')
                    AND s.sublimator_id = ?";
                    
    $count_stmt = mysqli_prepare($conn, $count_query);
    if (!$count_stmt) {
        throw new mysqli_sql_exception("Prepare failed for count query: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($count_stmt, "i", $sublimator_id);
    
    if (!mysqli_stmt_execute($count_stmt)) {
        throw new mysqli_sql_exception("Execute failed for count query: " . mysqli_stmt_error($count_stmt));
    }
    
    $count_result = mysqli_stmt_get_result($count_stmt);
    if (!$count_result) {
        throw new mysqli_sql_exception("Could not get count result set");
    }

    $total_records = mysqli_fetch_assoc($count_result)['total'];
    $total_pages = ceil($total_records / $records_per_page);

} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("Database query error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JXT - My Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <!-- Toast container for notifications -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="statusToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto" id="toastTitle">Status Update</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Status updated successfully.
            </div>
        </div>
    </div>

    <div id="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php //include 'topbar.php'; ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">My Sublimation Orders</h1>
                        
                        <!-- Filter dropdown -->
                        <div class="dropdown mb-4">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-filter fa-sm text-white-50 me-1"></i>
                                Filter by Status
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                <li><a class="dropdown-item active" href="#" data-status="all">All Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-status="forward_to_sublimator">Forwarded</a></li>
                                <li><a class="dropdown-item" href="#" data-status="in_process">In Process</a></li>
                                <li><a class="dropdown-item" href="#" data-status="ready_for_pickup">Ready for Pickup</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Manage Orders</h6>
                            <div class="dropdown no-arrow">
                                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                    <div class="dropdown-header">Order Actions:</div>
                                    <a class="dropdown-item" href="#" id="refreshOrders"><i class="fas fa-sync fa-sm fa-fw me-2 text-gray-400"></i>Refresh</a>
                                    <a class="dropdown-item" href="#" id="exportOrders"><i class="fas fa-download fa-sm fa-fw me-2 text-gray-400"></i>Export</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Status</th>
                                            <th>Details</th>
                                            <th>Created Date</th>
                                            <th>Est. Completion</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                                            <tr class="order-row" data-status="<?= htmlspecialchars($order['order_status']) ?>">
                                                <td class="fw-bold">#<?= htmlspecialchars($order['order_id']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                                                    <br>
                                                    <small class="text-muted"><i class="fas fa-phone-alt me-1"></i><?= htmlspecialchars($order['phone_number']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= getStatusBadgeClass($order['order_status']) ?> rounded-pill">
                                                        <?= formatStatus($order['order_status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($order['quantity'])): ?>
                                                        <span class="badge bg-light text-dark me-1"><i class="fas fa-hashtag me-1"></i><?= htmlspecialchars($order['quantity']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($order['size'])): ?>
                                                        <span class="badge bg-light text-dark me-1"><i class="fas fa-ruler me-1"></i><?= htmlspecialchars($order['size']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($order['color'])): ?>
                                                        <span class="badge bg-light text-dark"><i class="fas fa-palette me-1"></i><?= htmlspecialchars($order['color']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><i class="far fa-calendar-alt me-1"></i><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                                <td>
                                                    <?php if ($order['completion_date']): ?>
                                                        <i class="far fa-calendar-check me-1"></i><?= date('M d, Y', strtotime($order['completion_date'])) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not set</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <?php if ($order['order_status'] === 'forward_to_sublimator'): ?>
                                                            <button class="btn btn-sm btn-success" onclick="startProcessing('<?= $order['order_id'] ?>')">
                                                                <i class="fas fa-play me-1"></i> Start Process
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($order['order_status'] === 'in_process'): ?>
                                                            <button class="btn btn-sm btn-success" onclick="markReady('<?= $order['order_id'] ?>')">
                                                                <i class="fas fa-check me-1"></i> Mark Ready
                                                            </button>
                                                        <?php endif; ?>

                                                        <a href="view_order.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye me-1"></i> View
                                                        </a>
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
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page-1 ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page+1 ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        const filterLinks = document.querySelectorAll('.dropdown-item[data-status]');
        const orderRows = document.querySelectorAll('.order-row');
        const filterDropdown = document.getElementById('filterDropdown');
        
        filterLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active state
                filterLinks.forEach(item => item.classList.remove('active'));
                this.classList.add('active');
                
                const status = this.getAttribute('data-status');
                filterDropdown.textContent = this.textContent;
                
                // Filter rows
                orderRows.forEach(row => {
                    if (status === 'all' || row.getAttribute('data-status') === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
        
        // Refresh button
        document.getElementById('refreshOrders').addEventListener('click', function(e) {
            e.preventDefault();
            location.reload();
        });
    });

    function startProcessing(orderId) {
        if (confirm('Start processing this order?')) {
            const btn = event.target.closest('button');
            updateOrderStatus(orderId, 'in_process', btn);
        }
    }
    
    function markReady(orderId) {
        if (confirm('Mark this order as ready for pickup? This will notify the store staff.')) {
            const btn = event.target.closest('button');
            updateOrderStatus(orderId, 'ready_for_pickup', btn);
        }
    }

    function updateOrderStatus(orderId, status, btn) {
        // Show loading state
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
        
        fetch('update_sublimation_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + encodeURIComponent(orderId) + '&new_status=' + encodeURIComponent(status)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success toast
                const toast = document.getElementById('statusToast');
                const toastTitle = document.getElementById('toastTitle');
                const toastMessage = document.getElementById('toastMessage');
                
                // Clear previous classes
                toast.classList.remove('bg-danger', 'text-white', 'bg-success');
                
                toastTitle.textContent = 'Success';
                toastMessage.textContent = data.message || 'Status updated successfully';
                toast.classList.add('bg-success', 'text-white');
                
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                // Reload after a short delay
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                // Show error toast
                const toast = document.getElementById('statusToast');
                const toastTitle = document.getElementById('toastTitle');
                const toastMessage = document.getElementById('toastMessage');
                
                // Clear previous classes
                toast.classList.remove('bg-danger', 'text-white', 'bg-success');
                
                toastTitle.textContent = 'Error';
                toastMessage.textContent = data.message || 'Failed to update status';
                toast.classList.add('bg-danger', 'text-white');
                
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                // Reset button
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Show error toast
            const toast = document.getElementById('statusToast');
            const toastTitle = document.getElementById('toastTitle');
            const toastMessage = document.getElementById('toastMessage');
            
            // Clear previous classes
            toast.classList.remove('bg-danger', 'text-white', 'bg-success');
            
            toastTitle.textContent = 'Error';
            toastMessage.textContent = 'An error occurred while updating the order status.';
            toast.classList.add('bg-danger', 'text-white');
            
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Reset button
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    </script>
</body>
</html>