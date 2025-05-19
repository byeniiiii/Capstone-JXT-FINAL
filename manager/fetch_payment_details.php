<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
    $payment_id = intval($_POST['payment_id']);

    // Fetch payment details
    $query = $conn->prepare("SELECT p.*, 
                             CONCAT(u.first_name, ' ', u.last_name) AS staff_name,
                             CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                             DATE_FORMAT(p.payment_date, '%M %d, %Y %h:%i %p') AS formatted_date
                             FROM payments p
                             JOIN orders o ON p.order_id = o.order_id
                             JOIN customers c ON o.customer_id = c.customer_id
                             LEFT JOIN users u ON p.received_by = u.user_id
                             WHERE p.payment_id = ?");
    $query->bind_param("i", $payment_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $payment = $result->fetch_assoc();
        ?>
        <div class="receipt">
            <div class="receipt-header">
                <h2 class="receipt-title">Payment Details</h2>
                <p class="receipt-date"><?php echo htmlspecialchars($payment['formatted_date']); ?></p>
            </div>
            <div class="receipt-body">
                <div class="receipt-row">
                    <span class="receipt-label">Payment ID:</span>
                    <span class="receipt-value"><?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Order ID:</span>
                    <span class="receipt-value"><?php echo htmlspecialchars($payment['order_id']); ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Customer:</span>
                    <span class="receipt-value"><?php echo htmlspecialchars($payment['customer_name']); ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Amount Paid:</span>
                    <span class="receipt-value">₱<?php echo number_format($payment['amount'], 2); ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Payment Method:</span>
                    <span class="receipt-value"><?php echo ucfirst(htmlspecialchars($payment['payment_method'])); ?></span>
                </div>
                <?php if (!empty($payment['transaction_reference'])): ?>
                <div class="receipt-row">
                    <span class="receipt-label">Transaction Reference:</span>
                    <span class="receipt-value"><?php echo htmlspecialchars($payment['transaction_reference']); ?></span>
                </div>
                <?php endif; ?>
                <div class="receipt-row">
                    <span class="receipt-label">Received By:</span>
                    <span class="receipt-value"><?php echo htmlspecialchars($payment['staff_name']); ?></span>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Payment details not found.</div>';
    }
} else {
    echo '<div class="alert alert-danger">Invalid request.</div>';
}
?>
