<?php
// filepath: c:\xampp\htdocs\capstone_jxt\customer\payment_full.php
include '../db.php';
session_start();

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: ../login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];
$error = '';
$success = '';
$order_id = '';
$order = null;
$remaining_balance = 0;

// Get all staff members
$staff_query = "SELECT user_id, CONCAT(first_name, ' ', last_name) as staff_name FROM users WHERE role = 'staff'";
$staff_result = mysqli_query($conn, $staff_query);

// Get order details if order_id is provided
if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $order_id = mysqli_real_escape_string($conn, $_GET['order_id']);
    
    // Verify this order belongs to the customer and is ready for pickup
    $query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone_number 
              FROM orders o
              JOIN customers c ON o.customer_id = c.customer_id
              WHERE o.order_id = ? AND o.customer_id = ? AND o.order_status = 'ready_for_pickup' AND o.payment_status != 'fully_paid'";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $order_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $order = mysqli_fetch_assoc($result);
        $total_amount = $order['total_amount'];
        $downpayment_amount = $order['downpayment_amount'] ?: 0;
        $remaining_balance = $total_amount - $downpayment_amount;
    } else {
        $error = "Invalid order or the order is not ready for final payment.";
    }
} else {
    $error = "No order specified.";
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $payment_reference = isset($_POST['payment_reference']) ? mysqli_real_escape_string($conn, $_POST['payment_reference']) : '';
    $received_by = isset($_POST['received_by']) ? mysqli_real_escape_string($conn, $_POST['received_by']) : NULL;
    
    // Handle file upload for GCash
    $screenshot_path = '';
    if ($payment_method === 'gcash' && isset($_FILES['payment_screenshots'])) {
        $file = $_FILES['payment_screenshots'];
        $file_name = time() . '_' . $file['name'];
        $target_dir = "../uploads/payments_screenshots/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $screenshot_path = 'uploads/payments/' . $file_name;
        } else {
            throw new Exception("Failed to upload payment screenshot");
        }
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update order payment status
        $update_query = "UPDATE orders SET 
                         payment_status = 'fully_paid',
                         updated_at = NOW()
                         WHERE order_id = ? AND customer_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $order_id, $customer_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to update order payment status");
        }
        
        // Record the payment
        $payment_query = "INSERT INTO payments 
                         (order_id, amount, payment_method, transaction_reference, 
                          payment_type, payment_date, received_by, screenshot_path) 
                     VALUES (?, ?, ?, ?, 'full', NOW(), ?, ?)";
    
        $stmt = mysqli_prepare($conn, $payment_query);
        mysqli_stmt_bind_param($stmt, "sdssss", $order_id, $remaining_balance, 
                          $payment_method, $payment_reference, $received_by, $screenshot_path);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to record payment");
        }
        
        // Add notification for the customer
        $notification_query = "INSERT INTO notifications 
                              (customer_id, order_id, title, message, created_at) 
                              VALUES (?, ?, 'Payment Received', 'Your payment of ₱" . number_format($remaining_balance, 2) . " for order #$order_id has been received. Thank you!', NOW())";
        
        $stmt = mysqli_prepare($conn, $notification_query);
        mysqli_stmt_bind_param($stmt, "is", $customer_id, $order_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to create notification");
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Set success message
        $success = "Payment successful! Your order is paid in full.";
        
        // After successful payment, regenerate the order details
        $query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone_number 
                  FROM orders o
                  JOIN customers c ON o.customer_id = c.customer_id
                  WHERE o.order_id = ? AND o.customer_id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $order_id, $customer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($result);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

// Format order dates for display
if ($order) {
    $created_date = date('F j, Y', strtotime($order['created_at']));
    $expected_completion = isset($order['completion_date']) ? date('F j, Y', strtotime($order['completion_date'])) : 'To be determined';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Remaining Balance - JXT Tailoring</title>
    <link rel="icon" type="image/png" href="../image/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .payment-container {
            max-width: 950px;
            margin: 40px auto;
            padding: 30px;
        }
        
        .order-summary {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .order-id {
            font-weight: 700;
            color: #333;
            font-size: 1.2rem;
        }
        
        .order-date {
            color: #777;
            font-size: 0.9rem;
        }
        
        .order-detail {
            margin-bottom: 8px;
        }
        
        .order-label {
            font-weight: 600;
            color: #555;
            min-width: 150px;
            display: inline-block;
        }
        
        .order-value {
            color: #333;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed #eee;
        }
        
        .amount-row:last-child {
            border-bottom: none;
            border-top: 2px solid #ddd;
            font-weight: 700;
            font-size: 1.1rem;
            color: #000;
            margin-top: 10px;
            padding-top: 15px;
        }
        
        .payment-options {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .payment-method {
            margin-bottom: 25px;
        }
        
        .method-label {
            cursor: pointer;
            border: 2px solid #e9ecef;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .method-label:hover {
            border-color: #D98324;
        }
        
        .method-input:checked + .method-label {
            border-color: #D98324;
            background-color: #fef8f1;
        }
        
        .method-input {
            position: absolute;
            opacity: 0;
        }
        
        .method-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            color: #555;
        }
        
        .method-input:checked + .method-label .method-icon {
            color: #D98324;
        }
        
        .method-info {
            flex: 1;
        }
        
        .method-title {
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .method-description {
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 0;
        }
        
        .payment-details {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            border-left: 3px solid #D98324;
        }
        
        .btn-primary {
            background-color: #D98324;
            border-color: #D98324;
            padding: 10px 25px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #c27420;
            border-color: #c27420;
        }
        
        .alert {
            border-radius: 5px;
        }
        
        .payment-success {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .payment-success h4 {
            color: #2e7d32;
            margin-bottom: 10px;
        }
        
        .payment-success p {
            color: #1b5e20;
            margin-bottom: 15px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .receipt-logo {
            max-width: 120px;
            margin-bottom: 15px;
        }

        .receipt-title {
            font-weight: 700;
            color: #D98324;
            margin-bottom: 5px;
        }

        .receipt-subtitle {
            color: #777;
            font-size: 0.9rem;
        }

        .receipt-info {
            margin-bottom: 25px;
        }

        .receipt-section {
            margin-bottom: 25px;
        }

        .receipt-section-title {
            font-weight: 600;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ddd;
            color: #777;
            font-size: 0.9rem;
        }

        .receipt-actions {
            margin-top: 25px;
            text-align: center;
        }

        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        #payment_screenshot {
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.9rem;
        }

        #received_by {
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 0.9rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container payment-container">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Pay Remaining Balance</h1>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                    </div>
                    
                    <?php if (empty($order_id)): ?>
                        <div class="text-center mt-4">
                            <a href="track_order.php" class="btn btn-outline-secondary">Return to Orders</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="payment-success">
                        <h4><i class="fas fa-check-circle me-2"></i> Payment Successful!</h4>
                        <p>Your payment for order #<?= htmlspecialchars($order_id) ?> has been processed successfully.</p>
                        
                        <!-- BIR-style Receipt -->
                        <div class="receipt" id="printableReceipt">
                            <div class="receipt-header">
                                <img src="../image/logo.png" alt="JXT Tailoring Logo" class="receipt-logo">
                                <h1 class="receipt-title">JXT Tailoring</h1>
                                <p class="receipt-address">123 Main Street, Cebu City<br>Phone: (032) 123-4567<br>TIN: 123-456-789-000<br>VAT Registered</p>
                                <div class="receipt-separator"></div>
                                <h2 class="receipt-subtitle">OFFICIAL RECEIPT</h2>
                                <div class="receipt-id">Receipt No: <?php echo sprintf('OR-%06d', mysqli_insert_id($conn)); ?></div>
                                <div class="receipt-date">Date: <?php echo date('F d, Y h:i A'); ?></div>
                            </div>
                            
                            <div class="receipt-customer-info">
                                <p><strong>Sold To:</strong> <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></p>
                                <p><strong>Address:</strong> <?= htmlspecialchars($order['address'] ?? 'N/A') ?></p>
                                <p><strong>TIN:</strong> N/A</p>
                                <p><strong>Contact:</strong> <?= htmlspecialchars($order['phone_number']) ?></p>
                            </div>
                            
                            <div class="receipt-details">
                                <table class="receipt-table">
                                    <thead>
                                        <tr>
                                            <th>Qty</th>
                                            <th>Description</th>
                                            <th>Unit Price</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td><?= ucfirst(htmlspecialchars($order['order_type'])) ?> Services<br>
                                                Order #<?= htmlspecialchars($order_id) ?><br>
                                                Final Payment</td>
                                            <td>₱<?= number_format($remaining_balance, 2) ?></td>
                                            <td>₱<?= number_format($remaining_balance, 2) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <div class="receipt-computation">
                                    <div class="receipt-row">
                                        <div class="receipt-label">VATable Sales:</div>
                                        <div class="receipt-value">₱<?= number_format($remaining_balance / 1.12, 2) ?></div>
                                    </div>
                                    <div class="receipt-row">
                                        <div class="receipt-label">VAT Amount (12%):</div>
                                        <div class="receipt-value">₱<?= number_format($remaining_balance - ($remaining_balance / 1.12), 2) ?></div>
                                    </div>
                                    <div class="receipt-row receipt-total-row">
                                        <div class="receipt-label">Total Amount:</div>
                                        <div class="receipt-value receipt-total">₱<?= number_format($remaining_balance, 2) ?></div>
                                    </div>
                                    
                                    <div class="payment-details">
                                        <div class="receipt-row">
                                            <div class="receipt-label">Payment Method:</div>
                                            <div class="receipt-value"><?= ucfirst(htmlspecialchars($payment_method)) ?></div>
                                        </div>
                                        <?php if (!empty($payment_reference)): ?>
                                        <div class="receipt-row">
                                            <div class="receipt-label">Reference No:</div>
                                            <div class="receipt-value"><?= htmlspecialchars($payment_reference) ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <div class="receipt-row">
                                            <div class="receipt-label">Previous Payment:</div>
                                            <div class="receipt-value">₱<?= number_format($downpayment_amount, 2) ?></div>
                                        </div>
                                        <div class="receipt-row">
                                            <div class="receipt-label">Total Order Amount:</div>
                                            <div class="receipt-value">₱<?= number_format($order['total_amount'], 2) ?></div>
                                        </div>
                                        <div class="receipt-row">
                                            <div class="receipt-label">Balance Due:</div>
                                            <div class="receipt-value">₱0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="receipt-footer">
                                <div class="receipt-signatures">
                                    <div class="receipt-signature-box">
                                        <div class="signature-line"></div>
                                        <p>Cashier's Signature</p>
                                    </div>
                                    <div class="receipt-signature-box">
                                        <div class="signature-line"></div>
                                        <p>Customer's Signature</p>
                                    </div>
                                </div>
                                <div class="receipt-disclaimer">
                                    <p>"This serves as an Official Receipt"</p>
                                    <p>THIS DOCUMENT IS NOT VALID FOR CLAIMING INPUT TAX</p>
                                    <p>Keep this receipt for future reference</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="receipt-action">
                            <button class="btn btn-primary" onclick="printReceipt()">
                                <i class="fas fa-print me-2"></i> Print Receipt
                            </button>
                            <a href="track_order.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-box me-2"></i> View Orders
                            </a>
                        </div>
                    </div>
                <?php elseif ($order): ?>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="order-summary">
                                <div class="order-header">
                                    <div class="order-id">Order #<?= htmlspecialchars($order_id) ?></div>
                                    <div class="order-date"><?= $created_date ?></div>
                                </div>
                                
                                <div class="order-detail">
                                    <span class="order-label">Service Type:</span>
                                    <span class="order-value"><?= htmlspecialchars(ucfirst($order['order_type'])) ?></span>
                                </div>
                                
                                <div class="order-detail">
                                    <span class="order-label">Customer:</span>
                                    <span class="order-value"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></span>
                                </div>
                                
                                <div class="order-detail">
                                    <span class="order-label">Contact:</span>
                                    <span class="order-value"><?= htmlspecialchars($order['phone_number']) ?></span>
                                </div>
                                
                                <div class="order-detail mb-4">
                                    <span class="order-label">Status:</span>
                                    <span class="order-value">
                                        <span class="status-badge status-ready">Ready for Pickup</span>
                                    </span>
                                </div>
                                
                                <h5 class="mb-3">Payment Summary</h5>
                                
                                <div class="amount-row">
                                    <div>Order Total</div>
                                    <div>₱<?= number_format($order['total_amount'], 2) ?></div>
                                </div>
                                
                                <div class="amount-row">
                                    <div>Downpayment</div>
                                    <div>-₱<?= number_format($downpayment_amount, 2) ?></div>
                                </div>
                                
                                <div class="amount-row">
                                    <div>Remaining Balance</div>
                                    <div>₱<?= number_format($remaining_balance, 2) ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="payment-options">
                                <h5 class="mb-4">Payment Method</h5>
                                
                                <form method="post" id="paymentForm" enctype="multipart/form-data">
                                    <div class="payment-method">
                                        <input type="radio" class="method-input" name="payment_method" id="cash" value="cash" required>
                                        <label class="method-label" for="cash">
                                            <div class="method-icon">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </div>
                                            <div class="method-info">
                                                <h6 class="method-title">Cash Payment</h6>
                                                <p class="method-description">Pay in cash when you pick up your order.</p>
                                            </div>
                                        </label>
                                        
                                        <div class="payment-details" id="cash-details">
                                            <div class="mb-3">
                                                <label for="received_by" class="form-label">Received By</label>
                                                <select class="form-control" id="received_by" name="received_by">
                                                    <option value="">Select Staff Member</option>
                                                    <?php
                                                    $staff_query = "SELECT user_id, CONCAT(first_name, ' ', last_name) as staff_name 
                                                                  FROM users WHERE role = 'staff'";
                                                    $staff_result = mysqli_query($conn, $staff_query);
                                                    while ($staff = mysqli_fetch_assoc($staff_result)) {
                                                        echo "<option value='" . $staff['user_id'] . "'>" . 
                                                             htmlspecialchars($staff['staff_name']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-method">
                                        <input type="radio" class="method-input" name="payment_method" id="gcash" value="gcash">
                                        <label class="method-label" for="gcash">
                                            <div class="method-icon">
                                                <i class="fas fa-mobile-alt"></i>
                                            </div>
                                            <div class="method-info">
                                                <h6 class="method-title">GCash</h6>
                                                <p class="method-description">Pay securely using GCash mobile wallet.</p>
                                            </div>
                                        </label>
                                        
                                        <div class="payment-details" id="gcash-details">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 mb-3 mb-md-0">
                                                    <img src="../image/gcash-qr.jpg" alt="GCash QR" class="img-fluid rounded">
                                                </div>
                                                <div class="col-md-8">
                                                    <p class="mb-2">Please scan the QR code or use the following details:</p>
                                                    <p class="mb-1"><strong>Account Name:</strong> JXT Tailoring</p>
                                                    <p class="mb-1"><strong>Mobile Number:</strong> 09123456789</p>
                                                    <p class="mb-3 small text-muted">After sending payment, please provide the following:</p>
                                                    
                                                    <div class="mb-3">
                                                        <label for="gcash-reference" class="form-label">GCash Reference Number</label>
                                                        <input type="text" class="form-control" id="gcash-reference" 
                                                               name="payment_reference" placeholder="e.g. 1234567890">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="payment_screenshot" class="form-label">Upload Payment Screenshot</label>
                                                        <input type="file" class="form-control" id="payment_screenshot" 
                                                               name="payment_screenshot" accept="image/*">
                                                        <div class="form-text">Please upload a screenshot of your GCash payment confirmation.</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 pt-3 border-top">
                                        <button type="submit" name="submit_payment" class="btn btn-primary btn-lg w-100">
                                            Pay ₱<?= number_format($remaining_balance, 2) ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* BIR-style Receipt CSS */
        .receipt {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
            font-family: 'Arial', sans-serif;
            line-height: 1.5;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .receipt-logo {
            max-width: 120px;
            margin-bottom: 0.5rem;
        }
        
        .receipt-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            color: #000;
        }
        
        .receipt-subtitle {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0.5rem 0;
            text-transform: uppercase;
            color: #000;
        }
        
        .receipt-address {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .receipt-separator {
            border-bottom: 2px solid #000;
            width: 100%;
            margin: 1rem 0;
        }
        
        .receipt-id, .receipt-date {
            font-size: 0.95rem;
            color: #000;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .receipt-customer-info {
            margin: 1.5rem 0;
            border: 1px solid #ccc;
            padding: 1rem;
            border-radius: 5px;
        }
        
        .receipt-customer-info p {
            margin: 0.25rem 0;
            font-size: 0.95rem;
        }
        
        .receipt-details {
            margin-bottom: 1.5rem;
        }
        
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        
        .receipt-table th, .receipt-table td {
            border: 1px solid #ccc;
            padding: 0.5rem;
            text-align: center;
        }
        
        .receipt-table th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        
        .receipt-table td:nth-child(2) {
            text-align: left;
        }
        
        .receipt-table td:nth-child(3),
        .receipt-table td:nth-child(4) {
            text-align: right;
        }
        
        .receipt-computation {
            border: 1px solid #ccc;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed #ccc;
        }
        
        .payment-details {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #000;
        }
        
        .receipt-label {
            font-weight: 500;
            color: #333;
        }
        
        .receipt-value {
            text-align: right;
            font-weight: 600;
        }
        
        .receipt-total-row {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .receipt-total {
            font-size: 1.25rem;
            font-weight: 700;
            color: #000;
        }
        
        .receipt-signatures {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
        }
        
        .receipt-signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 0.5rem;
            height: 2rem;
        }
        
        .receipt-footer {
            margin-top: 2rem;
            border-top: 1px solid #ccc;
            padding-top: 1rem;
        }
        
        .receipt-disclaimer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: #333;
        }
        
        .receipt-disclaimer p:nth-child(2) {
            font-weight: 700;
            font-style: italic;
        }
        
        .receipt-action {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            .receipt, .receipt * {
                visibility: visible;
            }
            .receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
                padding: 15px;
            }
            .receipt-action {
                display: none;
            }
            .navbar, .container > .row > .col-md-8 > .alert, .container > .row > .col-md-8 > .text-center {
                display: none;
            }
        }
    </style>
    
    <script>
        function printReceipt() {
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Get the receipt content
            const receiptContent = document.getElementById('printableReceipt').outerHTML;
            
            // Create the print document
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>JXT Tailoring - Payment Receipt</title>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                        body {
                            font-family: 'Arial', sans-serif;
                            line-height: 1.5;
                            margin: 0;
                            padding: 20px;
                        }
                        
                        .receipt {
                            background-color: white;
                            padding: 20px;
                            max-width: 800px;
                            margin: 0 auto;
                            box-shadow: none;
                        }
                        
                        .receipt-header {
                            text-align: center;
                            margin-bottom: 20px;
                        }
                        
                        .receipt-logo {
                            max-width: 120px;
                            margin-bottom: 8px;
                        }
                        
                        .receipt-title {
                            font-size: 24px;
                            font-weight: bold;
                            margin-bottom: 5px;
                            text-transform: uppercase;
                        }
                        
                        .receipt-subtitle {
                            font-size: 18px;
                            font-weight: bold;
                            margin: 10px 0;
                            text-transform: uppercase;
                        }
                        
                        .receipt-address {
                            font-size: 14px;
                            margin-bottom: 8px;
                            line-height: 1.4;
                        }
                        
                        .receipt-separator {
                            border-bottom: 2px solid #000;
                            width: 100%;
                            margin: 15px 0;
                        }
                        
                        .receipt-id, .receipt-date {
                            font-size: 14px;
                            font-weight: 500;
                            margin-bottom: 5px;
                        }
                        
                        .receipt-customer-info {
                            margin: 20px 0;
                            border: 1px solid #ccc;
                            padding: 15px;
                            border-radius: 5px;
                        }
                        
                        .receipt-customer-info p {
                            margin: 5px 0;
                            font-size: 14px;
                        }
                        
                        .receipt-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 20px;
                        }
                        
                        .receipt-table th, .receipt-table td {
                            border: 1px solid #ccc;
                            padding: 8px;
                            text-align: center;
                        }
                        
                        .receipt-table th {
                            background-color: #f2f2f2;
                            font-weight: 600;
                        }
                        
                        .receipt-table td:nth-child(2) {
                            text-align: left;
                        }
                        
                        .receipt-table td:nth-child(3),
                        .receipt-table td:nth-child(4) {
                            text-align: right;
                        }
                        
                        .receipt-computation {
                            border: 1px solid #ccc;
                            padding: 15px;
                            border-radius: 5px;
                            margin: 15px 0;
                        }
                        
                        .receipt-row {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 8px;
                            padding-bottom: 8px;
                            border-bottom: 1px dashed #ccc;
                        }
                        
                        .payment-details {
                            margin-top: 20px;
                            padding-top: 20px;
                            border-top: 1px solid #000;
                        }
                        
                        .receipt-total-row {
                            border-top: 2px solid #000;
                            border-bottom: 2px solid #000;
                            padding-top: 8px;
                            margin-top: 8px;
                        }
                        
                        .receipt-signatures {
                            display: flex;
                            justify-content: space-between;
                            margin: 30px 0;
                        }
                        
                        .receipt-signature-box {
                            width: 45%;
                            text-align: center;
                        }
                        
                        .signature-line {
                            border-bottom: 1px solid #000;
                            margin-bottom: 8px;
                            height: 40px;
                        }
                        
                        .receipt-footer {
                            margin-top: 30px;
                            border-top: 1px solid #ccc;
                            padding-top: 15px;
                        }
                        
                        .receipt-disclaimer {
                            text-align: center;
                            margin-top: 20px;
                            font-size: 12px;
                        }
                        
                        .receipt-disclaimer p:nth-child(2) {
                            font-weight: bold;
                            font-style: italic;
                        }
                        
                        .receipt-action {
                            display: none;
                        }
                    </style>
                </head>
                <body>
                    ${receiptContent}
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() {
                                window.close();
                            }, 500);
                        };
                    <\/script>
                </body>
                </html>
            `);
            
            printWindow.document.close();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const gcashDetails = document.getElementById('gcash-details');
            const cashDetails = document.getElementById('cash-details');
            const gcashReference = document.getElementById('gcash-reference');
            const paymentScreenshot = document.getElementById('payment_screenshot');
            const receivedBy = document.getElementById('received_by');
            
            paymentMethods.forEach(function(method) {
                method.addEventListener('change', function() {
                    // Hide all details first
                    gcashDetails.style.display = 'none';
                    cashDetails.style.display = 'none';
                    
                    // Reset required fields
                    gcashReference.required = false;
                    paymentScreenshot.required = false;
                    receivedBy.required = false;
                    
                    // Show selected method details
                    if (this.id === 'gcash') {
                        gcashDetails.style.display = 'block';
                        gcashReference.required = true;
                        paymentScreenshot.required = true;
                    } else if (this.id === 'cash') {
                        cashDetails.style.display = 'block';
                        receivedBy.required = true;
                    }
                });
            });
            
            // Form validation
            document.getElementById('paymentForm').addEventListener('submit', function(e) {
                const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
                
                if (!selectedMethod) {
                    e.preventDefault();
                    alert('Please select a payment method');
                    return;
                }
                
                if (selectedMethod.value === 'gcash') {
                    if (!gcashReference.value.trim()) {
                        e.preventDefault();
                        alert('Please enter the GCash reference number');
                        return;
                    }
                    if (!paymentScreenshot.files.length) {
                        e.preventDefault();
                        alert('Please upload a payment screenshot');
                        return;
                    }
                } else if (selectedMethod.value === 'cash') {
                    if (!receivedBy.value) {
                        e.preventDefault();
                        alert('Please select the staff member who received the payment');
                        return;
                    }
                }
            });
        });
    </script>
</body>
</html>