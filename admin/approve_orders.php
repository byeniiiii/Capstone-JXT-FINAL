<?php
// Start the session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database connection
include '../db.php';

// Process status changes if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update order status
        $update_query = "UPDATE orders SET order_status = ? WHERE order_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ss", $new_status, $order_id);
        $stmt->execute();
        
        // Add entry to order status history
        $history_query = "INSERT INTO order_status_history (order_id, status, notes, changed_at, changed_by) 
                         VALUES (?, ?, ?, NOW(), ?)";
        $stmt = $conn->prepare($history_query);
        $user_id = $_SESSION['user_id'];
        $stmt->bind_param("sssi", $order_id, $new_status, $notes, $user_id);
        $stmt->execute();
        
        // Create notification for the customer
        $get_customer_query = "SELECT customer_id FROM orders WHERE order_id = ?";
        $stmt = $conn->prepare($get_customer_query);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($customer = $result->fetch_assoc()) {
            $customer_id = $customer['customer_id'];
            $title = "Order Status Updated";
            $message = "Your order #$order_id has been updated to: " . ucfirst(str_replace('_', ' ', $new_status));
            
            $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) 
                                  VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($notification_query);
            $stmt->bind_param("isss", $customer_id, $order_id, $title, $message);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['success_message'] = "Order #$order_id status has been updated to " . ucfirst(str_replace('_', ' ', $new_status));
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['error_message'] = "Error updating order status: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header("Location: approve_orders.php");
    exit();
}

// Filtering options
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending_approval';
$order_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query based on filters
$query = "SELECT o.*, 
          CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
          c.phone_number, c.email
          FROM orders o
          JOIN customers c ON o.customer_id = c.customer_id
          WHERE 1=1";

if ($status_filter !== 'all') {
    $query .= " AND o.order_status = '$status_filter'";
}

if ($order_type !== 'all') {
    $query .= " AND o.order_type = '$order_type'";
}

$query .= " ORDER BY o.created_at DESC";

// Execute query
$result = $conn->query($query);

// Include header
$page_title = "Approve Orders";
include 'header.php';
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Order Approval</h1>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']); 
        ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error_message'];
        unset($_SESSION['error_message']); 
        ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Orders</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="approve_orders.php" class="row">
                <div class="col-md-5 mb-3">
                    <label for="status">Order Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending_approval" <?= $status_filter === 'pending_approval' ? 'selected' : '' ?>>Pending Approval</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-5 mb-3">
                    <label for="type">Order Type</label>
                    <select class="form-control" id="type" name="type">
                        <option value="all" <?= $order_type === 'all' ? 'selected' : '' ?>>All Types</option>
                        <option value="sublimation" <?= $order_type === 'sublimation' ? 'selected' : '' ?>>Sublimation</option>
                        <option value="tailoring" <?= $order_type === 'tailoring' ? 'selected' : '' ?>>Tailoring</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <?= $result->num_rows ?> Orders 
                <?= $status_filter !== 'all' ? '(' . ucfirst(str_replace('_', ' ', $status_filter)) . ')' : '' ?>
                <?= $order_type !== 'all' ? '- ' . ucfirst($order_type) : '' ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="ordersTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="#" data-toggle="modal" data-target="#orderModal<?= $row['order_id'] ?>">
                                            <?= $row['order_id'] ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['customer_name']) ?><br>
                                        <small class="text-muted"><?= $row['email'] ?></small>
                                    </td>
                                    <td>
                                        <?php if ($row['order_type'] === 'sublimation'): ?>
                                            <span class="badge badge-info">Sublimation</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Tailoring</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <?php
                                            switch ($row['order_status']) {
                                                case 'pending_approval':
                                                    echo '<span class="badge badge-warning">Pending Approval</span>';
                                                    break;
                                                case 'approved':
                                                    echo '<span class="badge badge-success">Approved</span>';
                                                    break;
                                                case 'rejected':
                                                    echo '<span class="badge badge-danger">Rejected</span>';
                                                    break;
                                                case 'in_progress':
                                                    echo '<span class="badge badge-primary">In Progress</span>';
                                                    break;
                                                case 'completed':
                                                    echo '<span class="badge badge-info">Completed</span>';
                                                    break;
                                                case 'delivered':
                                                    echo '<span class="badge badge-success">Delivered</span>';
                                                    break;
                                                case 'cancelled':
                                                    echo '<span class="badge badge-dark">Cancelled</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge badge-secondary">Unknown</span>';
                                            }
                                        ?>
                                    </td>
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#orderModal<?= $row['order_id'] ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if ($row['order_status'] === 'pending_approval'): ?>
                                            <button class="btn btn-sm btn-success mt-1" data-toggle="modal" data-target="#approveModal<?= $row['order_id'] ?>">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger mt-1" data-toggle="modal" data-target="#rejectModal<?= $row['order_id'] ?>">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <h5>No orders found with the selected filters</h5>
                    <p class="text-muted">Try changing your filter criteria or check back later</p>
                    <a href="approve_orders.php" class="btn btn-outline-primary">Reset Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Reset result pointer
if ($result && $result->num_rows > 0) {
    $result->data_seek(0);

    // Create modals for each order
    while ($row = $result->fetch_assoc()): 
        // Get order status history
        $history_query = "SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
                        FROM order_status_history h
                        LEFT JOIN users u ON h.changed_by = u.user_id
                        WHERE h.order_id = ?
                        ORDER BY h.changed_at DESC";
        $stmt = $conn->prepare($history_query);
        $stmt->bind_param("s", $row['order_id']);
        $stmt->execute();
        $history_result = $stmt->get_result();
?>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal<?= $row['order_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel<?= $row['order_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel<?= $row['order_id'] ?>">
                    Order #<?= $row['order_id'] ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="font-weight-bold mb-0">Order Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th>Order ID:</th>
                                        <td><?= $row['order_id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Type:</th>
                                        <td>
                                            <?php if ($row['order_type'] === 'sublimation'): ?>
                                                <span class="badge badge-info">Sublimation</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Tailoring</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Date:</th>
                                        <td><?= date('F d, Y h:i A', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <?php
                                                switch ($row['order_status']) {
                                                    case 'pending_approval':
                                                        echo '<span class="badge badge-warning">Pending Approval</span>';
                                                        break;
                                                    case 'approved':
                                                        echo '<span class="badge badge-success">Approved</span>';
                                                        break;
                                                    case 'rejected':
                                                        echo '<span class="badge badge-danger">Rejected</span>';
                                                        break;
                                                    case 'in_progress':
                                                        echo '<span class="badge badge-primary">In Progress</span>';
                                                        break;
                                                    case 'completed':
                                                        echo '<span class="badge badge-info">Completed</span>';
                                                        break;
                                                    case 'delivered':
                                                        echo '<span class="badge badge-success">Delivered</span>';
                                                        break;
                                                    case 'cancelled':
                                                        echo '<span class="badge badge-dark">Cancelled</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge badge-secondary">Unknown</span>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Payment:</th>
                                        <td>
                                            <?php if ($row['payment_status'] === 'paid'): ?>
                                                <span class="badge badge-success">Paid</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Amount:</th>
                                        <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="font-weight-bold mb-0">Customer Information</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> <?= htmlspecialchars($row['customer_name']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($row['phone_number']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <!-- Status History -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="font-weight-bold mb-0">Order History</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if ($history_result->num_rows > 0): ?>
                                        <?php while ($history = $history_result->fetch_assoc()): ?>
                                            <div class="list-group-item py-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <strong>
                                                        <?php
                                                            switch ($history['status']) {
                                                                case 'pending_approval':
                                                                    echo '<span class="badge badge-warning">Pending Approval</span>';
                                                                    break;
                                                                case 'approved':
                                                                    echo '<span class="badge badge-success">Approved</span>';
                                                                    break;
                                                                case 'rejected':
                                                                    echo '<span class="badge badge-danger">Rejected</span>';
                                                                    break;
                                                                case 'in_progress':
                                                                    echo '<span class="badge badge-primary">In Progress</span>';
                                                                    break;
                                                                case 'completed':
                                                                    echo '<span class="badge badge-info">Completed</span>';
                                                                    break;
                                                                case 'delivered':
                                                                    echo '<span class="badge badge-success">Delivered</span>';
                                                                    break;
                                                                case 'cancelled':
                                                                    echo '<span class="badge badge-dark">Cancelled</span>';
                                                                    break;
                                                                default:
                                                                    echo '<span class="badge badge-secondary">Unknown</span>';
                                                            }
                                                        ?>
                                                    </strong>
                                                    <small class="text-muted">
                                                        <?= date('M d, Y h:i A', strtotime($history['changed_at'])) ?>
                                                    </small>
                                                </div>
                                                <?php if (!empty($history['notes'])): ?>
                                                    <p class="mb-1 mt-2"><?= nl2br(htmlspecialchars($history['notes'])) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($history['changed_by_name'])): ?>
                                                    <small class="text-muted">By: <?= htmlspecialchars($history['changed_by_name']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="list-group-item py-3 text-center text-muted">
                                            No history available
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <?php if ($row['order_status'] === 'pending_approval'): ?>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#approveModal<?= $row['order_id'] ?>" data-dismiss="modal">
                        <i class="fas fa-check"></i> Approve Order
                    </button>
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal<?= $row['order_id'] ?>" data-dismiss="modal">
                        <i class="fas fa-times"></i> Reject Order
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Approve Order Modal -->
<?php if ($row['order_status'] === 'pending_approval'): ?>
<div class="modal fade" id="approveModal<?= $row['order_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel<?= $row['order_id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approveModalLabel<?= $row['order_id'] ?>">
                    Approve Order #<?= $row['order_id'] ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="approve_orders.php">
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                    <input type="hidden" name="status" value="approved">
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                        <h5>Are you sure you want to approve this order?</h5>
                        <p class="text-muted">This will notify the customer that their order has been approved and will be processed.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes<?= $row['order_id'] ?>">Notes (Optional)</label>
                        <textarea class="form-control" id="notes<?= $row['order_id'] ?>" name="notes" rows="3" placeholder="Add any additional notes or instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Order Modal -->
<div class="modal fade" id="rejectModal<?= $row['order_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel<?= $row['order_id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectModalLabel<?= $row['order_id'] ?>">
                    Reject Order #<?= $row['order_id'] ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="approve_orders.php">
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                    <input type="hidden" name="status" value="rejected">
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-times-circle text-danger fa-4x mb-3"></i>
                        <h5>Are you sure you want to reject this order?</h5>
                        <p class="text-muted">The customer will be notified that their order has been rejected.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="reject_notes<?= $row['order_id'] ?>">Reason for Rejection</label>
                        <textarea class="form-control" id="reject_notes<?= $row['order_id'] ?>" name="notes" rows="3" placeholder="Please provide a reason for rejecting this order..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endwhile; ?>
<?php } ?>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#ordersTable').DataTable({
            order: [[3, 'desc']], // Sort by date column descending
            pageLength: 10,
            language: {
                search: "Search orders:"
            }
        });
    });
</script>

<?php
// Include footer
include 'footer.php';
?>