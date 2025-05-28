<?php
session_start();
include '../db.php'; // Add this line to include database connection

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager', 'Staff'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize variables
$success_message = '';
$error_message = '';
$payment_id = 0;
$payment_details = null;
$order_info = null;

// Handle form submission for processing payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $order_id = $_POST['order_id'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $transaction_reference = $_POST['transaction_reference'] ?? '';

    // Validate form data
    if (empty($order_id)) {
        $error_message = "Please select an order.";
    } elseif (empty($payment_method)) {
        $error_message = "Please select a payment method.";
    } elseif ($amount_paid <= 0) {
        $error_message = "Please enter a valid amount.";
    } elseif ($payment_method == 'gcash' && empty($transaction_reference)) {
        $error_message = "Transaction reference is required for GCash payments.";
    } else {
        // Get order details
        $order_query = $conn->prepare("SELECT o.*, c.first_name, c.last_name FROM orders o 
                                       JOIN customers c ON o.customer_id = c.customer_id 
                                       WHERE o.order_id = ?");
        $order_query->bind_param("s", $order_id);
        $order_query->execute();
        $order_result = $order_query->get_result();
        
        if ($order_result->num_rows === 0) {
            $error_message = "Order not found.";
        } else {
            $order = $order_result->fetch_assoc();
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Determine payment type based on amount and remaining balance
                $remaining_balance = $order['total_amount'];
                
                // Check if there are previous payments
                $prev_payments = $conn->prepare("SELECT SUM(amount) as paid_amount FROM payments WHERE order_id = ?");
                $prev_payments->bind_param("s", $order_id);
                $prev_payments->execute();
                $prev_result = $prev_payments->get_result();
                $prev_payment = $prev_result->fetch_assoc();
                
                if ($prev_payment && $prev_payment['paid_amount'] > 0) {
                    $remaining_balance -= $prev_payment['paid_amount'];
                }
                
                // Validate payment amount
                if ($amount_paid > $remaining_balance) {
                    throw new Exception("Payment amount exceeds the remaining balance (₱" . number_format($remaining_balance, 2) . ")");
                }
                
                // Determine payment type
                $payment_type = ($amount_paid >= $remaining_balance) ? 'full_payment' : 'downpayment';
                
                // Process file upload for GCash
                $screenshot_path = null;
                if ($payment_method == 'gcash' && isset($_FILES['gcash_screenshot']) && $_FILES['gcash_screenshot']['error'] == 0) {
                    $upload_dir = '../uploads/payment_screenshots/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['gcash_screenshot']['name'], PATHINFO_EXTENSION);
                    $file_name = 'payment_' . $order_id . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES['gcash_screenshot']['tmp_name'], $target_file)) {
                        $screenshot_path = $target_file;
                    } else {
                        throw new Exception("Failed to upload the screenshot.");
                    }
                }
                
                // Insert payment record
                $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, payment_type, payment_method, transaction_reference, received_by, payment_date) 
                                      VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("sdsssi", $order_id, $amount_paid, $payment_type, $payment_method, $transaction_reference, $_SESSION['user_id']);
                $stmt->execute();
                
                // Get the payment ID for receipt
                $payment_id = $conn->insert_id;
                
                // Update order payment status
                $total_paid = ($prev_payment['paid_amount'] ?? 0) + $amount_paid;
                $payment_status = ($total_paid >= $order['total_amount']) ? 'fully_paid' : 'downpayment_paid';
                
                // Update order status as well if necessary
                $order_status = $order['order_status'];
                if ($payment_status == 'downpayment_paid' && $order['order_status'] == 'approved') {
                    $order_status = 'in_process';
                }
                
                $update_order = $conn->prepare("UPDATE orders SET payment_status = ?, order_status = ? WHERE order_id = ?");
                $update_order->bind_param("sss", $payment_status, $order_status, $order_id);
                $update_order->execute();
                
                // Record payment in history log
                $notes = "Payment of ₱" . number_format($amount_paid, 2) . " received via $payment_method";
                $log_payment = $conn->prepare("INSERT INTO order_status_history (order_id, status, updated_by, notes) 
                                            VALUES (?, ?, ?, ?)");
                $log_payment->bind_param("ssis", $order_id, $payment_status, $_SESSION['user_id'], $notes);
                $log_payment->execute();
                
                // Create notification for customer
                $notification_msg = "Your payment of ₱" . number_format($amount_paid, 2) . " for Order #$order_id has been received.";
                $notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, is_read, created_at) 
                                        VALUES (?, ?, ?, 0, NOW())");
                $link = "track_order.php?order_id=" . $order_id;
                $notify->bind_param("iss", $order['customer_id'], $notification_msg, $link);
                $notify->execute();
                
                // Commit transaction
                $conn->commit();
                
                // Get payment details for receipt
                $payment_query = $conn->prepare("SELECT p.*, 
                                              CONCAT(u.first_name, ' ', u.last_name) AS staff_name,
                                              DATE_FORMAT(p.payment_date, '%M %d, %Y %h:%i %p') AS formatted_date
                                              FROM payments p
                                              LEFT JOIN users u ON p.received_by = u.user_id
                                              WHERE p.payment_id = ?");
                $payment_query->bind_param("i", $payment_id);
                $payment_query->execute();
                $payment_result = $payment_query->get_result();
                if ($payment_result->num_rows > 0) {
                    $payment_details = $payment_result->fetch_assoc();
                }
                
                $order_info = $order;
                $success_message = "Payment processed successfully!";
                
            } catch (Exception $e) {
                // Rollback in case of error
                $conn->rollback();
                $error_message = "Error processing payment: " . $e->getMessage();
                $payment_id = 0;
            }
        }
    }
}

// Get orders with pending payments
$pending_orders_query = "SELECT o.order_id, o.order_type, o.total_amount, o.payment_status, 
                        o.downpayment_amount, o.created_at, o.order_status,
                        CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                        (SELECT SUM(amount) FROM payments WHERE order_id = o.order_id) AS amount_paid
                        FROM orders o
                        JOIN customers c ON o.customer_id = c.customer_id
                        WHERE o.payment_status IN ('pending', 'downpayment_paid')
                        AND o.order_status != 'pending_approval'
                        ORDER BY o.created_at DESC";
$pending_orders = $conn->query($pending_orders_query);

// Get recent payments
$recent_payments_query = "SELECT p.*, o.order_type, o.total_amount, 
                         CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                         CONCAT(u.first_name, ' ', u.last_name) AS staff_name
                         FROM payments p
                         JOIN orders o ON p.order_id = o.order_id
                         JOIN customers c ON o.customer_id = c.customer_id
                         LEFT JOIN users u ON p.received_by = u.user_id
                         ORDER BY p.payment_date DESC
                         LIMIT 10";
$recent_payments = $conn->query($recent_payments_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" href="../image/logo.png">
    <title>JXT Sublimator</title>

    <!-- Custom fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
    :root {
        --primary: #D98324;
        --primary-dark: #b46c1e;
        --secondary: #443627;
        --light: #EFDCAB;
        --white: #ffffff;
        --gray: #6b7280;
        --light-gray: #f1f5f9;
        --success: #10b981;
        --info: #0ea5e9;
        --warning: #f59e0b;
        --danger: #ef4444;
    }

    /* General Styling */
    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--white);
        color: var(--secondary);
    }

    .container-fluid {
        padding: 1.5rem;
    }

    /* Card Styling */
    .card {
        border: none;
        border-radius: 8px;
        background-color: var(--white);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
        transition: box-shadow 0.2s ease;
    }

    .card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background-color: var(--white);
        border-bottom: 1px solid #e5e7eb;
        padding: 1rem 1.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Form Styling */
    .form-control, .form-select {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 0.5rem 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(217, 131, 36, 0.1);
        outline: none;
    }

    .form-label {
        font-weight: 500;
        color: var(--secondary);
        margin-bottom: 0.5rem;
    }

    .input-group-text {
        background-color: var(--light-gray);
        border: 1px solid #d1d5db;
        border-radius: 6px 0 0 6px;
        color: var(--gray);
    }

    /* Button Styling */
    .btn {
        font-weight: 500;
        border-radius: 6px;
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
    }

    .btn-primary {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-info {
        background-color: var(--info);
        border-color: var(--info);
        color: var(--white);
    }

    .btn-info:hover {
        background-color: #0284c7;
        border-color: #0284c7;
    }

    .btn-success {
        background-color: var(--success);
        border-color: var(--success);
    }

    .btn-success:hover {
        background-color: #059669;
        border-color: #059669;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    /* Table Styling */
    .table-custom {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background-color: var(--white);
        border-radius: 8px;
        overflow: hidden;
    }

    .table-custom thead th {
        background-color: var(--light-gray);
        color: var(--secondary);
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
    }

    .table-custom tbody tr {
        transition: background-color 0.2s ease;
    }

    .table-custom tbody tr:nth-child(even) {
        background-color: #f8fafc;
    }

    .table-custom tbody tr:hover {
        background-color: #f1f5f9;
    }

    .table-custom tbody td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        color: var(--gray);
        font-size: 0.9rem;
        vertical-align: middle;
    }

    .table-custom tbody td.text-center {
        text-align: center;
    }

    /* Payment Status Badges */
    .payment-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 4px;
        text-transform: uppercase;
        min-width: 60px;
        text-align: center;
    }

    .payment-pending {
        background-color: #fef3c7;
        color: #b45309;
    }

    .payment-partial {
        background-color: #bfdbfe;
        color: #1e40af;
    }

    .payment-paid {
        background-color: #d1fae5;
        color: #065f46;
    }

    /* Receipt Styling */
    .receipt {
        background-color: var(--white);
        border-radius: 8px;
        padding: 1.5rem;
        border: 1px solid #e5e7eb;
    }

    .receipt-header {
        text-align: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px dashed #d1d5db;
    }

    .receipt-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 0.25rem;
    }

    .receipt-subtitle {
        font-size: 0.9rem;
        color: var(--gray);
    }

    .receipt-id, .receipt-date {
        font-size: 0.85rem;
        color: var(--gray);
        margin: 0.2rem 0;
    }

    .receipt-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .receipt-label {
        font-weight: 600;
        color: var(--secondary);
    }

    .receipt-value {
        color: var(--gray);
    }

    .receipt-total {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px dashed #d1d5db;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .receipt-footer {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px dashed #d1d5db;
        color: var(--gray);
        font-size: 0.8rem;
    }

    .receipt-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1.5rem;
    }

    /* Order Info Row */
    .order-info-row {
        background-color: var(--light-gray);
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    /* Responsive Adjustments */
    @media (max-width: 992px) {
        .row > div {
            margin-bottom: 1.5rem;
        }
        .table-custom thead th,
        .table-custom tbody td {
            font-size: 0.8rem;
            padding: 0.5rem;
        }
        .btn-sm {
            padding: 0.2rem 0.4rem;
        }
    }

    @media (max-width: 576px) {
        .receipt {
            padding: 1rem;
        }
        .receipt-title {
            font-size: 1.25rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
    }
</style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <?php include 'notifications.php'; ?>

                        <!-- User Info -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                                    <i class='fas fa-user-circle' style="font-size:20px; margin-left: 10px;"></i>

                                </span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage Payments</h1>
                    </div>

                    <?php if ($payment_id > 0 && $payment_details && $order_info): ?>
                    <!-- Payment Receipt -->
                    <div class="row">
                        <div class="col-lg-8 mx-auto">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Payment Receipt</h6>
                                    <div class="dropdown">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" 
                                           data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                                            <li><a class="dropdown-item" href="#" onclick="printReceipt()">
                                                <i class="fas fa-print fa-sm fa-fw me-2 text-gray-400"></i> Print Receipt
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="emailReceipt()">
                                                <i class="fas fa-envelope fa-sm fa-fw me-2 text-gray-400"></i> Email Receipt
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="receipt" id="printableReceipt">
                                        <div class="receipt-header">
                                            <h2 class="receipt-title">JX Tailoring</h2>
                                            <p class="receipt-subtitle">Official Payment Receipt</p>
                                            <p class="receipt-date"><?php echo $payment_details['formatted_date']; ?></p>
                                            <p class="receipt-id">Receipt #<?php echo str_pad($payment_details['payment_id'], 6, '0', STR_PAD_LEFT); ?></p>
                                        </div>
                                        <div class="receipt-body">
                                            <div class="receipt-row">
                                                <span class="receipt-label">Customer:</span>
                                                <span class="receipt-value"><?php echo htmlspecialchars($order_info['first_name'] . ' ' . $order_info['last_name']); ?></span>
                                            </div>
                                            <div class="receipt-row">
                                                <span class="receipt-label">Order ID:</span>
                                                <span class="receipt-value"><?php echo htmlspecialchars($order_info['order_id']); ?></span>
                                            </div>
                                            <div class="receipt-row">
                                                <span class="receipt-label">Order Type:</span>
                                                <span class="receipt-value"><?php echo ucfirst(htmlspecialchars($order_info['order_type'])); ?></span>
                                            </div>
                                            <div class="receipt-row">
                                                <span class="receipt-label">Payment Method:</span>
                                                <span class="receipt-value"><?php echo ucfirst(htmlspecialchars($payment_details['payment_method'])); ?></span>
                                            </div>
                                            <?php if (!empty($payment_details['transaction_reference'])): ?>
                                            <div class="receipt-row">
                                                <span class="receipt-label">Reference #:</span>
                                                <span class="receipt-value"><?php echo htmlspecialchars($payment_details['transaction_reference']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <div class="receipt-row">
                                                <span class="receipt-label">Payment Type:</span>
                                                <span class="receipt-value">
                                                    <?php echo ($payment_details['payment_type'] == 'full_payment') ? 'Full Payment' : 'Downpayment'; ?>
                                                </span>
                                            </div>
                                            <div class="receipt-row">
                                                <span class="receipt-label">Received By:</span>
                                                <span class="receipt-value"><?php echo htmlspecialchars($payment_details['staff_name']); ?></span>
                                            </div>
                                            <div class="receipt-row receipt-total">
                                                <span class="receipt-label">Amount Paid:</span>
                                                <span class="receipt-value">₱<?php echo number_format($payment_details['amount'], 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="receipt-footer">
                                            <p>Thank you for your business!</p>
                                            <p>JX Tailoring • Phone: (123) 456-7890 • Email: info@jxtailoring.com</p>
                                        </div>
                                    </div>
                                    <div class="receipt-actions">
                                        <button type="button" class="btn btn-primary" onclick="printReceipt()">
                                            <i class="fas fa-print mr-1"></i> Print Receipt
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="newPayment()">
                                            <i class="fas fa-plus mr-1"></i> New Payment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <!-- Process Payment Form -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Process Payment</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST" enctype="multipart/form-data" id="paymentForm">
                                    <div class="mb-3">
                                        <label for="order_id" class="form-label">Select Order</label>
                                        <select class="form-select" id="order_id" name="order_id" required>
                                            <option value="">-- Select Order --</option>
                                            <?php 
                                            mysqli_data_seek($pending_orders, 0);
                                            while ($order = $pending_orders->fetch_assoc()): ?>
                                                <option value="<?php echo $order['order_id']; ?>" 
                                                        data-total="<?php echo $order['total_amount']; ?>"
                                                        data-paid="<?php echo $order['amount_paid'] ?? 0; ?>">
                                                    #<?php echo $order['order_id']; ?> - <?php echo $order['customer_name']; ?> 
                                                    (₱<?php echo number_format($order['total_amount'], 2); ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="row order-info-row" style="display: none;">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Total Amount</label>
                                            <div class="form-control" id="total_display">₱0.00</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Amount Paid</label>
                                            <div class="form-control" id="paid_display">₱0.00</div>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Remaining Balance</label>
                                            <div class="form-control" id="remaining_display">₱0.00</div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method</label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <option value="">-- Select Payment Method --</option>
                                            <option value="cash">Cash</option>
                                            <option value="gcash">GCash</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="transaction_reference_group" style="display: none;">
                                        <label for="transaction_reference" class="form-label">Transaction Reference</label>
                                        <input type="text" class="form-control" id="transaction_reference" name="transaction_reference" placeholder="Enter transaction reference">
                                    </div>
                                    <div class="mb-3" id="gcash_screenshot_group" style="display: none;">
                                        <label for="gcash_screenshot" class="form-label">GCash Screenshot</label>
                                        <input type="file" class="form-control" id="gcash_screenshot" name="gcash_screenshot" accept="image/*">
                                        <small class="form-text text-muted">Upload screenshot of GCash payment confirmation.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="amount_paid" class="form-label">Amount Paid</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" id="amount_paid" name="amount_paid" step="0.01" min="0" required placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Process Payment</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Recent Payments Table -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Payments</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-custom" id="recentPaymentsTable">
                                        <thead>
                                            <tr>
                                                <th>Payment ID</th>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($recent_payments->num_rows > 0): ?>
                                                <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                        <td><?php echo $payment['order_id']; ?></td>
                                                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                                        <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                                        <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-info view-payment" 
                                                                    data-id="<?php echo $payment['payment_id']; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-primary view-receipt" 
                                                                    data-id="<?php echo $payment['payment_id']; ?>">
                                                                <i class="fas fa-receipt"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No recent payments found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Orders Pending Payment Table -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Orders Pending Payment</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-custom" id="pendingPaymentsTable">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Total</th>
                                                <th>Paid</th>
                                                <th>Remaining</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            mysqli_data_seek($pending_orders, 0);
                                            if ($pending_orders->num_rows > 0): 
                                                while ($order = $pending_orders->fetch_assoc()): 
                                                    $amount_paid = $order['amount_paid'] ?? 0;
                                                    $remaining = $order['total_amount'] - $amount_paid;

                                                    // Check for pending payment
                                                    $payment_query = $conn->prepare("SELECT payment_id, payment_method, payment_status 
                                                                            FROM payments 
                                                                            WHERE order_id = ? AND payment_status = 'pending_verification'");
                                                    $payment_query->bind_param("s", $order['order_id']);
                                                    $payment_query->execute();
                                                    $payment_result = $payment_query->get_result();
                                                    $pending_payment = $payment_result->num_rows > 0 ? $payment_result->fetch_assoc() : null;
                                            ?>
                                                <tr>
                                                    <td><?php echo $order['order_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                    <td>₱<?php echo number_format($amount_paid, 2); ?></td>
                                                    <td>₱<?php echo number_format($remaining, 2); ?></td>
                                                    <td>
                                                        <?php 
                                                        $status_class = '';
                                                        $status_text = '';
                                                        switch ($order['payment_status']) {
                                                            case 'pending':
                                                                $status_class = 'payment-pending';
                                                                $status_text = 'Pending';
                                                                break;
                                                            case 'downpayment_paid':
                                                                $status_class = 'payment-partial';
                                                                $status_text = 'Partial';
                                                                break;
                                                            case 'fully_paid':
                                                                $status_class = 'payment-paid';
                                                                $status_text = 'Paid';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="payment-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($pending_payment): ?>
                                                            <button class="btn btn-sm btn-success approve-payment" data-id="<?php echo $pending_payment['payment_id']; ?>" title="Approve Payment">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-sm btn-primary process-payment" data-id="<?php echo $order['order_id']; ?>">
                                                            <i class="fas fa-cash-register"></i>
                                                        </button>
                                                        <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No orders pending payment</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- /.container-fluid -->

                <!-- Modal for Viewing Receipt -->
                <div class="modal fade" id="viewReceiptModal" tabindex="-1" aria-labelledby="viewReceiptModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="viewReceiptModalLabel">Payment Receipt</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="receiptContent">
                                    <!-- Receipt will be dynamically loaded here -->
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="printModalReceipt()">
                                    <i class="fas fa-print mr-1"></i> Print Receipt
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End of Main Content -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Modal for Viewing Payment Details -->
    <div class="modal fade" id="viewPaymentModal" tabindex="-1" aria-labelledby="viewPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPaymentModalLabel">Payment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="paymentDetailsContent">
                        <!-- Payment details will be dynamically loaded here -->
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="js/jquery.easing.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom scripts -->
    <script>
    $(document).ready(function() {
        // Format currency
        function formatCurrency(amount) {
            return '₱' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
        
        // Update order information when an order is selected
        $('#order_id').change(function() {
            const selectedOption = $(this).find('option:selected');
            
            if (selectedOption.val()) {
                const total = parseFloat(selectedOption.data('total'));
                const paid = parseFloat(selectedOption.data('paid') || 0);
                const remaining = total - paid;
                
                $('#total_display').text(formatCurrency(total));
                $('#paid_display').text(formatCurrency(paid));
                $('#remaining_display').text(formatCurrency(remaining));
                
                $('#amount_paid').val(remaining.toFixed(2));
                
                $('.order-info-row').show();
            } else {
                $('.order-info-row').hide();
            }
        });
        
        // Toggle reference and screenshot fields based on payment method
        $('#payment_method').change(function() {
            const method = $(this).val();
            
            if (method === 'gcash') {
                $('#transaction_reference_group').show();
                $('#gcash_screenshot_group').show();
                $('#transaction_reference').attr('required', true);
                $('#gcash_screenshot').attr('required', true);
            } else {
                $('#transaction_reference_group').hide();
                $('#gcash_screenshot_group').hide();
                $('#transaction_reference').attr('required', false);
                $('#gcash_screenshot').attr('required', false);
            }
        });
        
        // Handle Process Payment button clicks
        $('.process-payment').click(function() {
            const orderId = $(this).data('id');
            $('#order_id').val(orderId).trigger('change');
            $('#order_id').focus();
            $('html, body').animate({
                scrollTop: $("#paymentForm").offset().top - 100
            }, 500);
        });
        
        // Form validation
        $('#paymentForm').submit(function(e) {
            const orderId = $('#order_id').val();
            const paymentMethod = $('#payment_method').val();
            const amountPaid = parseFloat($('#amount_paid').val() || 0);
            
            if (!orderId) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select an order',
                    confirmButtonColor: '#d33'
                });
                $('#order_id').focus();
                return false;
            }
            
            if (!paymentMethod) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a payment method',
                    confirmButtonColor: '#d33'
                });
                $('#payment_method').focus();
                return false;
            }
            
            if (amountPaid <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please enter a valid amount',
                    confirmButtonColor: '#d33'
                });
                $('#amount_paid').focus();
                return false;
            }
            
            if (paymentMethod === 'gcash') {
                const reference = $('#transaction_reference').val();
                const screenshot = $('#gcash_screenshot').val();
                
                if (!reference) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter a transaction reference for GCash payment',
                        confirmButtonColor: '#d33'
                    });
                    $('#transaction_reference').focus();
                    return false;
                }
                
                if (!screenshot) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please upload a screenshot of the GCash transaction',
                        confirmButtonColor: '#d33'
                    });
                    $('#gcash_screenshot').focus();
                    return false;
                }
            }
            
            return true;
        });

        // Handle "View Payment" button click
        $('.view-payment').click(function() {
            const paymentId = $(this).data('id');
            $('#paymentDetailsContent').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);

            $.ajax({
                url: 'fetch_payment_details.php',
                method: 'POST',
                data: { payment_id: paymentId },
                success: function(response) {
                    $('#paymentDetailsContent').html(response);
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load payment details. Please try again.',
                        confirmButtonColor: '#d33'
                    });
                }
            });

            $('#viewPaymentModal').modal('show');
        });

        // Handle Approve Payment button click
        $('.approve-payment').click(function() {
            const paymentId = $(this).data('id');
            const orderId = $(this).data('order-id');
            const orderTotal = $(this).data('order-total');
            
            Swal.fire({
                title: 'Approve Payment?',
                text: 'Are you sure you want to approve this payment?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, approve it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'process_payment_approval.php',
                        type: 'POST',
                        data: { 
                            payment_id: paymentId,
                            order_id: orderId,
                            order_total: orderTotal,
                            action: 'approve'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Approved!',
                                    text: response.message,
                                    confirmButtonColor: '#28a745'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message,
                                    confirmButtonColor: '#d33'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'There was an error approving the payment.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    });
                }
            });
        });

        // Handle "View Receipt" button click
        $('.view-receipt').click(function() {
            const paymentId = $(this).data('id');
            $('#receiptContent').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);

            $.ajax({
                url: 'fetch_payment_receipt.php',
                method: 'POST',
                data: { payment_id: paymentId },
                success: function(response) {
                    $('#receiptContent').html(response);
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load payment receipt. Please try again.',
                        confirmButtonColor: '#d33'
                    });
                }
            });

            $('#viewReceiptModal').modal('show');
        });
    });

    // Print receipt function - Global function
    function printReceipt() {
        const printContents = document.getElementById('printableReceipt').innerHTML;
        const originalContents = document.body.innerHTML;
        
        document.body.innerHTML = `
            <html>
                <head>
                    <title>Payment Receipt</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            line-height: 1.6;
                            margin: 0;
                            padding: 20px;
                        }
                        .receipt-header {
                            text-align: center;
                            margin-bottom: 25px;
                            padding-bottom: 15px;
                            border-bottom: 1px solid #ddd;
                        }
                        .receipt-title {
                            font-size: 24px;
                            font-weight: 700;
                            margin-bottom: 5px;
                        }
                        .receipt-subtitle {
                            font-size: 14px;
                        }
                        .receipt-row {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 12px;
                        }
                        .receipt-label {
                            font-weight: 600;
                        }
                        .receipt-value {
                            text-align: right;
                        }
                        .receipt-total {
                            margin-top: 20px;
                            padding-top: 15px;
                            border-top: 1px solid #ddd;
                            font-weight: 700;
                            font-size: 18px;
                        }
                        .receipt-footer {
                            text-align: center;
                            margin-top: 30px;
                            font-size: 14px;
                        }
                    </style>
                </head>
                <body>
                    ${printContents}
                </body>
            </html>
        `;
        
        window.print();
        document.body.innerHTML = originalContents;
    }

    // New payment function - Global function
    function newPayment() {
        window.location.href = 'manage_payments.php';
    }

    // Email receipt function - Global function
    function emailReceipt() {
        alert('Email functionality will be implemented soon.');
    }

    // Handle "Print Receipt" button click in modal - Global function
    function printModalReceipt() {
        const printContents = document.getElementById('modalPrintableReceipt').innerHTML;
        const originalContents = document.body.innerHTML;
        
        document.body.innerHTML = `
            <html>
                <head>
                    <title>Payment Receipt</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            line-height: 1.6;
                            margin: 0;
                            padding: 20px;
                        }
                        .receipt-header {
                            text-align: center;
                            margin-bottom: 25px;
                            padding-bottom: 15px;
                            border-bottom: 1px solid #ddd;
                        }
                        .receipt-title {
                            font-size: 24px;
                            font-weight: 700;
                            margin-bottom: 5px;
                        }
                        .receipt-subtitle {
                            font-size: 14px;
                        }
                        .receipt-row {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 12px;
                        }
                        .receipt-label {
                            font-weight: 600;
                        }
                        .receipt-value {
                            text-align: right;
                        }
                        .receipt-total {
                            margin-top: 20px;
                            padding-top: 15px;
                            border-top: 1px solid #ddd;
                            font-weight: 700;
                            font-size: 18px;
                        }
                        .receipt-footer {
                            text-align: center;
                            margin-top: 30px;
                            font-size: 14px;
                        }
                    </style>
                </head>
                <body>
                    ${printContents}
                </body>
            </html>
        `;
        
        window.print();
        document.body.innerHTML = originalContents;
    }
    </script>

    <!-- Begin Page Content -->
    </div>
    
    <footer class="footer text-center py-3">
        <span>Copyright &copy; JXT Tailoring and Printing Services</span>
    </footer>
    <!-- End of Footer -->
</div>
<!-- End of Content Wrapper -->
</div>
</div>
<!-- End of Page Wrapper -->
</body>
</html>
