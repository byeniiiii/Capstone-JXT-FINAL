<?php
session_start();
include '../db.php';

// Hardcoding $sublimator_id for demo/testing. Replace with actual user id logic as needed.
$sublimator_id = 1; // <-- Set this to a valid sublimator user_id from your database

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get assigned sublimation orders
$query = "SELECT o.*, c.first_name, c.last_name, c.phone_number,
          s.completion_date, s.design_type, s.printing_type
          FROM orders o
          LEFT JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN sublimation_orders s ON o.order_id = s.order_id
          WHERE o.order_type = 'sublimation'
          AND s.sublimator_id = ?
          ORDER BY 
            CASE 
                WHEN o.order_status = 'forwarded_to_sublimator' THEN 1
                WHEN o.order_status = 'in_process' THEN 2
                ELSE 3
            END,
            o.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $sublimator_id, $records_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM orders o 
                JOIN sublimation_orders s ON o.order_id = s.order_id 
                WHERE s.sublimator_id = ?";
$stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($stmt, "i", $sublimator_id);
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);
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
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'topbar.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">My Sublimation Orders</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Manage Orders</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Status</th>
                                            <th>Design Type</th>
                                            <th>Created Date</th>
                                            <th>Est. Completion</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars($order['phone_number']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= getStatusBadgeClass($order['order_status']) ?>">
                                                        <?= formatStatus($order['order_status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($order['design_type']) ?></td>
                                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                                <td>
                                                    <?= $order['completion_date'] ? date('M d, Y', strtotime($order['completion_date'])) : 'N/A' ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <?php if ($order['order_status'] === 'forwarded_to_sublimator'): ?>
                                                            <button class="btn btn-sm btn-success" onclick="startProcessing('<?= $order['order_id'] ?>')">
                                                                Start Processing
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($order['order_status'] === 'in_process'): ?>
                                                            <button class="btn btn-sm btn-primary" onclick="markCompleted('<?= $order['order_id'] ?>')">
                                                                Mark as Completed
                                                            </button>
                                                        <?php endif; ?>

                                                        <a href="view_order.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-info">
                                                            View Details
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
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
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
    function startProcessing(orderId) {
        if(confirm('Start processing this order?')) {
            updateOrderStatus(orderId, 'in_process');
        }
    }

    function markCompleted(orderId) {
        if(confirm('Mark this order as completed? This will notify the store staff.')) {
            updateOrderStatus(orderId, 'completed_by_sublimator');
        }
    }

    function updateOrderStatus(orderId, status) {
        fetch('update_sublimation_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }

    function getStatusBadgeClass(status) {
        switch(status) {
            case 'forwarded_to_sublimator': return 'info';
            case 'in_process': return 'warning';
            case 'completed_by_sublimator': return 'success';
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
