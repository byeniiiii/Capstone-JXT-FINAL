<?php
session_start();
include '../db.php';

// Check if user is logged in and has appropriate role
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

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Payment not found";
    exit();
}

$payment = $result->fetch_assoc();

// Format the receipt number with leading zeros
$receipt_number = str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT);

// Generate the receipt HTML
?>

<div class="receipt" id="modalPrintableReceipt">
    <div class="receipt-header">
        <h2 class="receipt-title">JX Tailoring</h2>
        <p class="receipt-subtitle">Official Payment Receipt</p>
        <p class="receipt-date"><?php echo $payment['formatted_date']; ?></p>
        <p class="receipt-id">Receipt #<?php echo $receipt_number; ?></p>
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
            <span class="receipt-value">
                <?php echo ($payment['payment_type'] == 'full_payment') ? 'Full Payment' : 'Downpayment'; ?>
            </span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Received By:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($payment['staff_name']); ?></span>
        </div>
        <div class="receipt-row receipt-total">
            <span class="receipt-label">Amount Paid:</span>
            <span class="receipt-value">₱<?php echo number_format($payment['amount'], 2); ?></span>
        </div>
    </div>
    <div class="receipt-footer">
        <p>Thank you for your business!</p>
        <p>JX Tailoring • Phone: (123) 456-7890 • Email: info@jxtailoring.com</p>
    </div>
</div> 