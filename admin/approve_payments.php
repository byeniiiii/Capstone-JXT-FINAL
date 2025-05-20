<?php
// Add these lines at the very top of approve_payments.php, right after <?php

// Start the session
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Include database connection
include '../db.php';
include 'sidebar.php';
include 'topbar.php';
// Check if payment_history table exists
$check_table_query = "SHOW TABLES LIKE 'payment_history'";
$table_exists = $conn->query($check_table_query);

if ($table_exists->num_rows == 0) {
    // Create payment_history table if it doesn't exist
    $create_table_query = "CREATE TABLE payment_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        payment_id INT NOT NULL,
        previous_status VARCHAR(20) NOT NULL,
        new_status VARCHAR(20) NOT NULL,
        notes TEXT,
        changed_at DATETIME NOT NULL,
        changed_by INT
    )";
    $conn->query($create_table_query);
}

// Process payment status changes if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id']) && isset($_POST['status'])) {
    $payment_id = $_POST['payment_id'];
    $new_status = $_POST['status'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get payment information
        $get_payment = "SELECT p.*, o.customer_id, o.order_id, o.total_amount 
                       FROM payments p
                       JOIN orders o ON p.order_id = o.order_id
                       WHERE p.payment_id = ?";
        $stmt = $conn->prepare($get_payment);
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment_result = $stmt->get_result();
        $payment_data = $payment_result->fetch_assoc();
        
        if (!$payment_data) {
            throw new Exception("Payment not found");
        }
        
        // Update payment status
        $update_query = "UPDATE payments SET status = ?, updated_at = NOW() WHERE payment_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_status, $payment_id);
        $stmt->execute();
        
        // If payment is approved, update the order's payment status
        if ($new_status == 'approved') {
            $order_update = "UPDATE orders SET payment_status = 'paid' WHERE order_id = ?";
            $stmt = $conn->prepare($order_update);
            $stmt->bind_param("s", $payment_data['order_id']);
            $stmt->execute();
        } elseif ($new_status == 'rejected') {
            // If payment is rejected, ensure order stays as unpaid
            $order_update = "UPDATE orders SET payment_status = 'unpaid' WHERE order_id = ?";
            $stmt = $conn->prepare($order_update);
            $stmt->bind_param("s", $payment_data['order_id']);
            $stmt->execute();
        }
        
        // Add entry to payment history
        $history_query = "INSERT INTO payment_history 
                         (payment_id, previous_status, new_status, notes, changed_at, changed_by) 
                         VALUES (?, ?, ?, ?, NOW(), ?)";
        $stmt = $conn->prepare($history_query);
        $previous_status = $payment_data['status'];
        $user_id = $_SESSION['user_id'] ?? 0;
        $stmt->bind_param("isssi", $payment_id, $previous_status, $new_status, $notes, $user_id);
        $stmt->execute();
        
        // Create notification for the customer
        $customer_id = $payment_data['customer_id'];
        $order_id = $payment_data['order_id'];
        
        $title = "Payment Status Update";
        if ($new_status == 'approved') {
            $message = "Your payment of ₱" . number_format($payment_data['amount'], 2) . " for order #$order_id has been approved. Thank you for your payment!";
        } else {
            $message = "Your payment for order #$order_id has been rejected. Reason: $notes. Please contact support for assistance.";
        }
        
        // Check if notifications table exists
        $check_notif_table = "SHOW TABLES LIKE 'notifications'";
        $notif_table_exists = $conn->query($check_notif_table);
        
        if ($notif_table_exists->num_rows > 0) {
            $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) 
                                VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($notification_query);
            $stmt->bind_param("isss", $customer_id, $order_id, $title, $message);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['success_message'] = "Payment #$payment_id has been " . ($new_status == 'approved' ? 'approved' : 'rejected') . " successfully.";
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['error_message'] = "Error updating payment status: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header("Location: approve_payments.php");
    exit();
}

// Filtering options
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Build query based on filters
$query = "SELECT p.*, o.order_id, o.order_type, o.total_amount,
         CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
         c.email, c.phone_number
         FROM payments p
         JOIN orders o ON p.order_id = o.order_id
         JOIN customers c ON o.customer_id = c.customer_id
         WHERE 1=1";

if ($status_filter !== 'all') {
    $query .= " AND p.status = '$status_filter'";
}

if ($date_from && $date_to) {
    $query .= " AND DATE(p.created_at) BETWEEN '$date_from' AND '$date_to'";
}

$query .= " ORDER BY p.created_at DESC";

// Execute query
$result = $conn->query($query);

// Include header
$page_title = "Approve Payments";

?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payment Approval</h1>
        <a href="payment_reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> Generate Payment Report
        </a>
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
            <h6 class="m-0 font-weight-bold text-primary">Filter Payments</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="approve_payments.php" class="row">
                <div class="col-md-3 mb-3">
                    <label for="status">Payment Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="date_from">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $date_from ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="date_to">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $date_to ?>">
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2">Apply Filters</button>
                    <a href="approve_payments.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <?= $result->num_rows ?> Payments 
                <?= $status_filter !== 'all' ? '(' . ucfirst($status_filter) . ')' : '' ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="paymentsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $row['payment_id'] ?></td>
                                    <td>
                                        <a href="approve_orders.php?order_id=<?= $row['order_id'] ?>">
                                            <?= $row['order_id'] ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['customer_name']) ?><br>
                                        <small class="text-muted"><?= $row['email'] ?></small>
                                    </td>
                                    <td class="text-right">₱<?= number_format($row['amount'], 2) ?></td>
                                    <td><?= ucfirst($row['payment_method']) ?></td>
                                    <td>
                                        <?= $row['reference_number'] ?>
                                    </td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <?php
                                            switch ($row['status']) {
                                                case 'pending':
                                                    echo '<span class="badge badge-warning">Pending</span>';
                                                    break;
                                                case 'approved':
                                                    echo '<span class="badge badge-success">Approved</span>';
                                                    break;
                                                case 'rejected':
                                                    echo '<span class="badge badge-danger">Rejected</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge badge-secondary">Unknown</span>';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#paymentModal<?= $row['payment_id'] ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success mt-1" data-toggle="modal" data-target="#approvePaymentModal<?= $row['payment_id'] ?>">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger mt-1" data-toggle="modal" data-target="#rejectPaymentModal<?= $row['payment_id'] ?>">
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
                    <div class="mb-3">
                        <i class="fas fa-money-bill-wave fa-4x text-gray-300"></i>
                    </div>
                    <h5>No payments found with the selected filters</h5>
                    <p class="text-muted">Try changing your filter criteria or check back later</p>
                    <a href="approve_payments.php" class="btn btn-outline-primary">Reset Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Reset result pointer
if ($result && $result->num_rows > 0) {
    $result->data_seek(0);

    // Create modals for each payment
    while ($row = $result->fetch_assoc()): 
        
        // Get payment proof image if available
        $proof_image = !empty($row['proof_of_payment']) ? '../uploads/payment_proofs/' . basename($row['proof_of_payment']) : '';
        
        // Get payment history
        $history_query = "SELECT ph.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
                         FROM payment_history ph
                         LEFT JOIN users u ON ph.changed_by = u.user_id
                         WHERE ph.payment_id = ?
                         ORDER BY ph.changed_at DESC";
        $stmt = $conn->prepare($history_query);
        $stmt->bind_param("i", $row['payment_id']);
        $stmt->execute();
        $history_result = $stmt->get_result();
?>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentModal<?= $row['payment_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel<?= $row['payment_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel<?= $row['payment_id'] ?>">
                    Payment #<?= $row['payment_id'] ?> Details
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
                                <h6 class="font-weight-bold mb-0">Payment Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th>Payment ID:</th>
                                        <td>#<?= $row['payment_id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Order ID:</th>
                                        <td><?= $row['order_id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Amount:</th>
                                        <td>₱<?= number_format($row['amount'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Payment Method:</th>
                                        <td><?= ucfirst($row['payment_method']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Reference Number:</th>
                                        <td><?= $row['reference_number'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date:</th>
                                        <td><?= date('F d, Y h:i A', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <?php
                                                switch ($row['status']) {
                                                    case 'pending':
                                                        echo '<span class="badge badge-warning">Pending</span>';
                                                        break;
                                                    case 'approved':
                                                        echo '<span class="badge badge-success">Approved</span>';
                                                        break;
                                                    case 'rejected':
                                                        echo '<span class="badge badge-danger">Rejected</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge badge-secondary">Unknown</span>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php if (!empty($row['notes'])): ?>
                                    <tr>
                                        <th>Notes:</th>
                                        <td><?= nl2br(htmlspecialchars($row['notes'])) ?></td>
                                    </tr>
                                    <?php endif; ?>
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
                        <?php if (!empty($proof_image)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="font-weight-bold mb-0">Proof of Payment</h6>
                            </div>
                            <div class="card-body text-center">
                                <img src="<?= $proof_image ?>" alt="Payment Proof" class="img-fluid mb-2 border" style="max-height: 300px;">
                                <div class="mt-2">
                                    <a href="<?= $proof_image ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-external-link-alt"></i> View Full Image
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Payment History -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="font-weight-bold mb-0">Payment History</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if ($history_result->num_rows > 0): ?>
                                        <?php while ($history = $history_result->fetch_assoc()): ?>
                                            <div class="list-group-item py-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <span class="badge badge-secondary"><?= ucfirst($history['previous_status']) ?></span>
                                                        <i class="fas fa-long-arrow-alt-right mx-2"></i>
                                                        <?php
                                                            switch ($history['new_status']) {
                                                                case 'pending':
                                                                    echo '<span class="badge badge-warning">Pending</span>';
                                                                    break;
                                                                case 'approved':
                                                                    echo '<span class="badge badge-success">Approved</span>';
                                                                    break;
                                                                case 'rejected':
                                                                    echo '<span class="badge badge-danger">Rejected</span>';
                                                                    break;
                                                                default:
                                                                    echo '<span class="badge badge-secondary">Unknown</span>';
                                                            }
                                                        ?>
                                                    </div>
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
                <?php if ($row['status'] === 'pending'): ?>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#approvePaymentModal<?= $row['payment_id'] ?>" data-dismiss="modal">
                        <i class="fas fa-check"></i> Approve Payment
                    </button>
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectPaymentModal<?= $row['payment_id'] ?>" data-dismiss="modal">
                        <i class="fas fa-times"></i> Reject Payment
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Approve Payment Modal -->
<?php if ($row['status'] === 'pending'): ?>
<div class="modal fade" id="approvePaymentModal<?= $row['payment_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="approvePaymentModalLabel<?= $row['payment_id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approvePaymentModalLabel<?= $row['payment_id'] ?>">
                    Approve Payment #<?= $row['payment_id'] ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="approve_payments.php">
                <div class="modal-body">
                    <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                   <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>

                    
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                        <h5>Are you sure you want to approve this payment?</h5>
                        <p class="text-muted">This will mark the payment as approved and update the order payment status to paid.</p>
                        
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-6 text-left"><strong>Order:</strong> <?= $row['order_id'] ?></div>
                                <div class="col-6 text-right"><strong>Amount:</strong> ₱<?= number_format($row['amount'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="approve_notes<?= $row['payment_id'] ?>">Notes (Optional)</label>
                        <textarea class="form-control" id="approve_notes<?= $row['payment_id'] ?>" name="notes" rows="3" placeholder="Add any additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Payment Modal -->
<div class="modal fade" id="rejectPaymentModal<?= $row['payment_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="rejectPaymentModalLabel<?= $row['payment_id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectPaymentModalLabel<?= $row['payment_id'] ?>">
                    Reject Payment #<?= $row['payment_id'] ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="approve_payments.php">
                <div class="modal-body">
                    <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                    <input type="hidden" name="status" value="rejected">
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-times-circle text-danger fa-4x mb-3"></i>
                        <h5>Are you sure you want to reject this payment?</h5>
                        <p class="text-muted">The customer will be notified that their payment has been rejected.</p>
                        
                        <div class="alert alert-warning">
                            <div class="row">
                                <div class="col-6 text-left"><strong>Order:</strong> <?= $row['order_id'] ?></div>
                                <div class="col-6 text-right"><strong>Amount:</strong> ₱<?= number_format($row['amount'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reject_notes<?= $row['payment_id'] ?>">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_notes<?= $row['payment_id'] ?>" name="notes" rows="3" placeholder="Please provide a reason for rejecting this payment..." required></textarea>
                        <small class="form-text text-muted">This reason will be shared with the customer.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Payment
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
        $('#paymentsTable').DataTable({
            order: [[6, 'desc']], // Sort by date column descending
            pageLength: 10,
            language: {
                search: "Search payments:"
            }
        });
    });
</script>

<?php
// Include footer
include 'footer.php';
?>