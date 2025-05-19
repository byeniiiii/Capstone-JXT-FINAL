<?php
// Include database connection
include '../db.php';

// Start session
session_start();

// Check if the user is logged in and is a Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    echo "Unauthorized access";
    exit();
}

// Check if payment ID is provided
if (!isset($_POST['payment_id']) || empty($_POST['payment_id'])) {
    echo "Invalid payment ID";
    exit();
}

$payment_id = mysqli_real_escape_string($conn, $_POST['payment_id']);

// Fetch payment details with related information
$query = "SELECT p.*, c.first_name, c.last_name, c.email, c.phone_number, 
          o.order_id as order_number, o.order_date, o.total_amount, o.status as order_status,
          u.first_name as confirmed_by_first_name, u.last_name as confirmed_by_last_name
          FROM payments p 
          JOIN orders o ON p.order_id = o.order_id 
          JOIN customers c ON o.customer_id = c.customer_id 
          LEFT JOIN users u ON p.confirmed_by = u.user_id
          WHERE p.payment_id = '$payment_id'";
          
$result = mysqli_query($conn, $query);

$pending_query = "SELECT COUNT(*) as pending FROM payments WHERE status = 'Pending'";

if ($result && mysqli_num_rows($result) > 0) {
    $payment = mysqli_fetch_assoc($result);
?>
    <div class="row">
        <div class="col-md-6">
            <h5 class="mb-3">Payment Information</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Payment ID</th>
                    <td><?php echo $payment['payment_id']; ?></td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                </tr>
                <tr>
                    <th>Payment Method</th>
                    <td><?php echo $payment['payment_method']; ?></td>
                </tr>
                <tr>
                    <th>Reference Number</th>
                    <td><?php echo $payment['reference_number'] ? $payment['reference_number'] : 'N/A'; ?></td>
                </tr>
                <tr>
                    <th>Payment Date</th>
                    <td><?php echo date('F d, Y h:i A', strtotime($payment['payment_date'])); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <?php if ($payment['status'] == 'Confirmed'): ?>
                            <span class="badge badge-success">Confirmed</span>
                        <?php elseif ($payment['status'] == 'Pending'): ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php elseif ($payment['status'] == 'Failed'): ?>
                            <span class="badge badge-danger">Failed</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($payment['confirmed_by']): ?>
                <tr>
                    <th>Confirmed By</th>
                    <td><?php echo $payment['confirmed_by_first_name'] . ' ' . $payment['confirmed_by_last_name']; ?></td>
                </tr>
                <tr>
                    <th>Confirmation Date</th>
                    <td><?php echo date('F d, Y h:i A', strtotime($payment['confirmation_date'])); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="col-md-6">
            <h5 class="mb-3">Customer & Order Information</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Customer Name</th>
                    <td><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo $payment['email']; ?></td>
                </tr>
                <tr>
                    <th>Phone Number</th>
                    <td><?php echo $payment['phone_number']; ?></td>
                </tr>
                <tr>
                    <th>Order Number</th>
                    <td>#<?php echo $payment['order_number']; ?></td>
                </tr>
                <tr>
                    <th>Order Date</th>
                    <td><?php echo date('F d, Y', strtotime($payment['order_date'])); ?></td>
                </tr>
                <tr>
                    <th>Order Amount</th>
                    <td>₱<?php echo number_format($payment['total_amount'], 2); ?></td>
                </tr>
                <tr>
                    <th>Order Status</th>
                    <td>
                        <?php if ($payment['order_status'] == 'Completed'): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php elseif ($payment['order_status'] == 'Processing'): ?>
                            <span class="badge badge-info">Processing</span>
                        <?php elseif ($payment['order_status'] == 'Pending'): ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><?php echo $payment['order_status']; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
<?php
} else {
    echo '<div class="alert alert-danger">Payment record not found</div>';
}
?>