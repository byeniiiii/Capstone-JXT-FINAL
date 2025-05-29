<?php
session_start();
include '../db.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Manager', 'Admin', 'Staff'])) {
    echo "<div class='alert alert-danger'>Unauthorized access</div>";
    exit();
}

// Function to log user activity
function logActivity($conn, $user_id, $action_type, $description) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action_type, description, created_at) 
                          VALUES (?, ?, ?, ?, NOW())");
    $user_type = $_SESSION['role'] ?? 'Unknown';
    $stmt->bind_param("isss", $user_id, $user_type, $action_type, $description);
    $stmt->execute();
    $stmt->close();
}

// Check if payment ID is provided
if (!isset($_POST['payment_id']) || empty($_POST['payment_id'])) {
    echo "<div class='alert alert-danger'>No payment specified</div>";
    exit();
}

$payment_id = (int)$_POST['payment_id'];

// Log activity if requested
if (isset($_POST['log_activity']) && $_POST['log_activity'] === true) {
    $user_id = $_POST['user_id'] ?? $_SESSION['user_id'];
    $action_type = $_POST['action_type'] ?? 'view_payment_details';
    $description = $_POST['description'] ?? "Viewed details for payment #$payment_id";
    
    logActivity($conn, $user_id, $action_type, $description);
}

// Get payment details
$query = "SELECT p.*, 
          o.order_id, o.order_type, o.total_amount, o.order_status, o.payment_status AS order_payment_status,
          CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
          c.email, c.phone_number, c.address,
          CONCAT(u.first_name, ' ', u.last_name) AS staff_name,
          DATE_FORMAT(p.payment_date, '%M %d, %Y %h:%i %p') AS formatted_date,
          DATE_FORMAT(p.created_at, '%M %d, %Y %h:%i %p') AS created_date
          FROM payments p
          JOIN orders o ON p.order_id = o.order_id
          JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN users u ON p.received_by = u.user_id
          WHERE p.payment_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-danger'>Payment not found</div>";
    exit();
}

$payment = $result->fetch_assoc();

// Get payment history from order_status_history
$history_query = "SELECT h.*, 
                 CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                 DATE_FORMAT(h.created_at, '%M %d, %Y %h:%i %p') AS formatted_date
                 FROM order_status_history h
                 LEFT JOIN users u ON h.updated_by = u.user_id
                 WHERE h.order_id = ? AND h.status LIKE 'payment%'
                 ORDER BY h.created_at DESC";

$stmt = $conn->prepare($history_query);
$stmt->bind_param("s", $payment['order_id']);
$stmt->execute();
$history_result = $stmt->get_result();

// Format payment type for display
$payment_type_display = ucfirst(str_replace('_', ' ', $payment['payment_type']));

// Get status class for badges
function getStatusClass($status) {
    switch ($status) {
        case 'confirmed':
            return 'bg-success';
        case 'pending':
            return 'bg-warning text-dark';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Payment #<?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Payment Information</h6>
                <table class="table table-sm">
                    <tr>
                        <td class="fw-bold">Amount:</td>
                        <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Payment Type:</td>
                        <td><?php echo $payment_type_display; ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Payment Method:</td>
                        <td><?php echo ucfirst($payment['payment_method']); ?></td>
                    </tr>
                    <?php if (!empty($payment['transaction_reference'])): ?>
                    <tr>
                        <td class="fw-bold">Reference #:</td>
                        <td><?php echo htmlspecialchars($payment['transaction_reference']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="fw-bold">Status:</td>
                        <td>
                            <span class="badge <?php echo getStatusClass($payment['payment_status']); ?>">
                                <?php echo ucfirst($payment['payment_status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Payment Date:</td>
                        <td><?php echo $payment['formatted_date']; ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Received By:</td>
                        <td><?php echo htmlspecialchars($payment['staff_name'] ?? 'N/A'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Order Information</h6>
                <table class="table table-sm">
                    <tr>
                        <td class="fw-bold">Order ID:</td>
                        <td><?php echo htmlspecialchars($payment['order_id']); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Customer:</td>
                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Contact:</td>
                        <td><?php echo htmlspecialchars($payment['phone_number']); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Order Type:</td>
                        <td><?php echo ucfirst($payment['order_type']); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Order Status:</td>
                        <td><?php echo ucwords(str_replace('_', ' ', $payment['order_status'])); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Payment Status:</td>
                        <td><?php echo ucwords(str_replace('_', ' ', $payment['order_payment_status'])); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Order Total:</td>
                        <td>₱<?php echo number_format($payment['total_amount'], 2); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if (!empty($payment['screenshot_path']) && file_exists('../' . $payment['screenshot_path'])): ?>
        <div class="mb-4">
            <h6 class="text-muted mb-3">Payment Screenshot</h6>
            <div class="text-center">
                <img src="<?php echo '../' . htmlspecialchars($payment['screenshot_path']); ?>" 
                     class="img-fluid rounded" style="max-height: 300px;" 
                     alt="Payment Screenshot">
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($history_result->num_rows > 0): ?>
        <div class="mt-4">
            <h6 class="text-muted mb-3">Payment History</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Updated By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($history = $history_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $history['formatted_date']; ?></td>
                            <td>
                                <span class="badge <?php echo getStatusClass(str_replace('payment_', '', $history['status'])); ?>">
                                    <?php echo ucwords(str_replace(['payment_', '_'], ['', ' '], $history['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($history['user_name'] ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($history['notes']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="mt-4 d-flex justify-content-between">
            <a href="view_order.php?id=<?php echo $payment['order_id']; ?>" class="btn btn-primary">
                <i class="fas fa-eye me-1"></i> View Order
            </a>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="fas fa-times me-1"></i> Close
            </button>
        </div>
    </div>
</div>
