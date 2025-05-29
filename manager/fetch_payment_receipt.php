<?php
session_start();
include '../db.php';

// Check if user is logged in and has appropriate role
<<<<<<< HEAD
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
    $action_type = $_POST['action_type'] ?? 'view_payment_receipt';
    $description = $_POST['description'] ?? "Viewed receipt for payment #$payment_id";
    
    logActivity($conn, $user_id, $action_type, $description);
}

// Get payment details
$query = "SELECT p.*, 
          o.order_id, o.order_type, o.total_amount, o.customer_id,
          CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
          c.email, c.phone_number, c.address,
          CONCAT(u.first_name, ' ', u.last_name) AS staff_name,
          DATE_FORMAT(p.payment_date, '%M %d, %Y %h:%i %p') AS formatted_date
          FROM payments p
          JOIN orders o ON p.order_id = o.order_id
          JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN users u ON p.received_by = u.user_id
          WHERE p.payment_id = ?";
=======
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager', 'Staff'])) {
    echo "Unauthorized access";
    exit();
}

// Check if payment_id is provided
if (!isset($_POST['payment_id']) || empty($_POST['payment_id'])) {
    echo "No payment ID provided";
    exit();
}

$payment_id = intval($_POST['payment_id']);

// Get payment details with user information
$query = "SELECT p.*, 
           o.order_type, o.total_amount,
           CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
           CONCAT(u.first_name, ' ', u.last_name) AS staff_name,
           DATE_FORMAT(p.payment_date, '%M %d, %Y %h:%i %p') AS formatted_date
           FROM payments p
           JOIN orders o ON p.order_id = o.order_id
           JOIN customers c ON o.customer_id = c.customer_id
           LEFT JOIN users u ON p.received_by = u.user_id
           WHERE p.payment_id = ?";
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
<<<<<<< HEAD
    echo "<div class='alert alert-danger'>Payment not found</div>";
=======
    echo "Payment not found";
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
    exit();
}

$payment = $result->fetch_assoc();

<<<<<<< HEAD
// Format payment type for display
$payment_type_display = ucfirst(str_replace('_', ' ', $payment['payment_type']));

// Get total paid amount for this order
$total_paid_query = "SELECT SUM(amount) as total_paid FROM payments WHERE order_id = ? AND payment_status = 'confirmed'";
$stmt = $conn->prepare($total_paid_query);
$stmt->bind_param("s", $payment['order_id']);
$stmt->execute();
$total_paid_result = $stmt->get_result();
$total_paid = $total_paid_result->fetch_assoc()['total_paid'] ?? 0;

// Calculate remaining balance
$remaining_balance = $payment['total_amount'] - $total_paid;
=======
// Format the receipt number with leading zeros
$receipt_number = str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT);

// Generate the receipt HTML
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
?>

<div class="receipt" id="modalPrintableReceipt">
    <div class="receipt-header">
<<<<<<< HEAD
        <h2 class="receipt-title">JXT Tailoring</h2>
        <p class="receipt-subtitle">Official Payment Receipt</p>
        <p class="receipt-date"><?php echo $payment['formatted_date']; ?></p>
        <p class="receipt-id">Receipt #<?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></p>
=======
        <h2 class="receipt-title">JX Tailoring</h2>
        <p class="receipt-subtitle">Official Payment Receipt</p>
        <p class="receipt-date"><?php echo $payment['formatted_date']; ?></p>
        <p class="receipt-id">Receipt #<?php echo $receipt_number; ?></p>
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
    </div>
    <div class="receipt-body">
        <div class="receipt-row">
            <span class="receipt-label">Customer:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($payment['customer_name']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Order ID:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($payment['order_id']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Order Type:</span>
            <span class="receipt-value"><?php echo ucfirst(htmlspecialchars($payment['order_type'])); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Payment Method:</span>
            <span class="receipt-value"><?php echo ucfirst(htmlspecialchars($payment['payment_method'])); ?></span>
        </div>
        <?php if (!empty($payment['transaction_reference'])): ?>
        <div class="receipt-row">
            <span class="receipt-label">Reference #:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($payment['transaction_reference']); ?></span>
        </div>
        <?php endif; ?>
        <div class="receipt-row">
            <span class="receipt-label">Payment Type:</span>
<<<<<<< HEAD
            <span class="receipt-value"><?php echo $payment_type_display; ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Payment Status:</span>
            <span class="receipt-value"><?php echo ucfirst($payment['payment_status']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Received By:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($payment['staff_name'] ?? 'N/A'); ?></span>
=======
            <span class="receipt-value">
                <?php echo ($payment['payment_type'] == 'full_payment') ? 'Full Payment' : 'Downpayment'; ?>
            </span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Received By:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($payment['staff_name']); ?></span>
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
        </div>
        <div class="receipt-row receipt-total">
            <span class="receipt-label">Amount Paid:</span>
            <span class="receipt-value">₱<?php echo number_format($payment['amount'], 2); ?></span>
        </div>
<<<<<<< HEAD
        
        <div class="receipt-section mt-4">
            <h5 class="mb-3">Order Summary</h5>
            <div class="receipt-row">
                <span class="receipt-label">Order Total:</span>
                <span class="receipt-value">₱<?php echo number_format($payment['total_amount'], 2); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Total Paid:</span>
                <span class="receipt-value">₱<?php echo number_format($total_paid, 2); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Remaining Balance:</span>
                <span class="receipt-value">₱<?php echo number_format($remaining_balance, 2); ?></span>
            </div>
        </div>
    </div>
    <div class="receipt-footer">
        <p>Thank you for your business!</p>
        <p>JXT Tailoring • Phone: (123) 456-7890 • Email: info@jxtailoring.com</p>
=======
    </div>
    <div class="receipt-footer">
        <p>Thank you for your business!</p>
        <p>JX Tailoring • Phone: (123) 456-7890 • Email: info@jxtailoring.com</p>
>>>>>>> b08fcef7c437beb1ee54987e98882524ea2bfc8b
    </div>
</div> 