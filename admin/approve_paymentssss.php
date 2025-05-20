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

<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fc;
        color: #5a5c69;
        overflow-x: hidden;
    }
    
    /* Improved card styling */
    .card {
        border: none !important;
        border-radius: 1rem;
        background-color: #ffffff !important;
        color: #5a5c69 !important;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1) !important;
        transition: all 0.3s ease;
        margin-bottom: 1.8rem;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.15) !important;
    }

    .card-header {
        background: linear-gradient(to right, #f8f9fc, #ffffff) !important;
        border-bottom: 1px solid rgba(227, 230, 240, 0.7);
        padding: 1.25rem 1.5rem;
        border-top-left-radius: 1rem !important;
        border-top-right-radius: 1rem !important;
        font-weight: 600;
    }

    .card-body {
        padding: 1.5rem;
    }
    
    /* Enhanced table styling */
    .table {
        color: #5a5c69;
        border-radius: 0.75rem;
        overflow: hidden;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table-bordered {
        border: none;
    }
    
    .table-bordered th, 
    .table-bordered td {
        border: none;
        border-bottom: 1px solid #e3e6f0;
    }
    
    .table th {
        background: linear-gradient(to right, #f1f3fa, #f8f9fc);
        border-bottom: 2px solid #e3e6f0;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.82rem;
        letter-spacing: 0.05rem;
        white-space: nowrap;
        padding: 1rem 1.25rem;
        color: #4e73df;
    }
    
    .table td {
        vertical-align: middle;
        padding: 1rem 1.25rem;
        transition: background-color 0.2s;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(78, 115, 223, 0.05);
        transform: scale(1.01);
        box-shadow: 0 0.15rem 0.5rem rgba(58, 59, 69, 0.1);
        z-index: 10;
        position: relative;
    }
    
    /* Button styling */
    .btn {
        border-radius: 0.75rem;
        padding: 0.475rem 1rem;
        font-weight: 500;
        letter-spacing: 0.03rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        text-transform: none;
    }

    .btn::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.2);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .btn:hover::after {
        opacity: 1;
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .btn-sm {
        border-radius: 0.5rem;
        padding: 0.35rem 0.7rem;
        margin: 0 0.2rem;
    }
    
    .btn-primary {
        background: linear-gradient(45deg, #4e73df, #6086ef) !important;
        border-color: #4e73df !important;
        font-weight: 500;
    }

    .btn-primary:hover {
        background: linear-gradient(45deg, #385ac1, #4e73df) !important;
        border-color: #385ac1 !important;
    }
    
    .btn-success {
        background: linear-gradient(45deg, #1cc88a, #20e8a6) !important;
        border-color: #1cc88a !important;
        font-weight: 500;
    }
    
    .btn-success:hover {
        background: linear-gradient(45deg, #16a678, #1cc88a) !important;
        border-color: #16a678 !important;
    }
    
    .btn-danger {
        background: linear-gradient(45deg, #e74a3b, #f5695d) !important;
        border-color: #e74a3b !important;
        font-weight: 500;
    }
    
    .btn-danger:hover {
        background: linear-gradient(45deg, #d52a1a, #e74a3b) !important;
        border-color: #d52a1a !important;
    }
    
    .btn-info {
        background: linear-gradient(45deg, #36b9cc, #4dd4e9) !important;
        border-color: #36b9cc !important;
        font-weight: 500;
    }
    
    .btn-info:hover {
        background: linear-gradient(45deg, #2a96a5, #36b9cc) !important;
        border-color: #2a96a5 !important;
    }
    
    .btn-outline-primary {
        color: #4e73df;
        border-color: #4e73df;
        border-width: 2px;
        font-weight: 500;
    }
    
    .btn-outline-primary:hover {
        color: #fff;
        background: linear-gradient(45deg, #4e73df, #6086ef) !important;
        border-color: #4e73df;
    }
    
    .modal-footer .btn {
        min-width: 120px;
        transition: all 0.3s;
    }
    
    /* Badge styling */
    .badge {
        font-weight: 600;
        padding: 0.4em 0.8em;
        border-radius: 30px;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .badge.badge-warning {
        background: linear-gradient(45deg, #f6c23e, #ffcf61) !important;
        color: #fff;
    }

    .badge.badge-danger {
        background: linear-gradient(45deg, #e74a3b, #f5695d) !important;
        color: #fff;
    }

    .badge.badge-info {
        background: linear-gradient(45deg, #36b9cc, #4dd4e9) !important;
        color: #fff;
    }

    .badge.badge-success {
        background: linear-gradient(45deg, #1cc88a, #20e8a6) !important;
        color: #fff;
    }

    .badge.badge-secondary {
        background: linear-gradient(45deg, #858796, #a3a4b0) !important;
        color: #fff;
    }
    
    /* Modal styling */
    .modal-content {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        overflow: hidden;
    }
    
    .modal-header {
        border-bottom: 1px solid rgba(227, 230, 240, 0.7);
        padding: 1.5rem;
    }
    
    .modal-body {
        padding: 2rem;
    }
    
    .modal-footer {
        border-top: 1px solid rgba(227, 230, 240, 0.7);
        padding: 1.25rem 1.5rem;
    }
    
    /* Form controls */
    .form-control {
        border-radius: 0.75rem;
        border-color: #e3e6f0;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
        transition: all 0.2s;
        box-shadow: none;
    }
    
    .form-control:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    
    select.form-control {
        height: calc(1.5em + 1.2rem + 2px);
        background-position: right 1rem center;
    }
    
    label {
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }
    
    /* Alerts */
    .alert {
        border-radius: 0.75rem;
        border: none;
        padding: 1rem 1.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 1.5rem;
    }
    
    .alert-success {
        background: linear-gradient(45deg, #dffff1, #f0fff8);
        border-left: 4px solid #1cc88a;
    }
    
    .alert-danger {
        background: linear-gradient(45deg, #ffeaea, #fff8f7);
        border-left: 4px solid #e74a3b;
    }
    
    /* Animation effects */
    .fade-in {
        animation: fadeIn 0.6s ease-in-out;
    }
    
    .slide-in {
        animation: slideIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .payment-row {
        transition: all 0.3s;
    }
    
    .payment-row:hover {
        background-color: rgba(78, 115, 223, 0.05);
    }
    
    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f8f9fc;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: linear-gradient(#c1c1c1, #d4d4d4);
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(#a8a8a8, #bbbbbb);
    }
    
    /* Icon styling */
    .icon-circle {
        height: 3.5rem;
        width: 3.5rem;
        border-radius: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(45deg, #4e73df, #6086ef);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        margin-right: 1rem;
    }
    
    .icon-circle i {
        font-size: 1.75rem;
        color: white;
    }
    
    /* Background gradients */
    .bg-gradient-primary {
        background-color: #4e73df;
        background-image: linear-gradient(45deg, #4e73df 10%, #224abe 90%);
        background-size: cover;
    }
    
    .bg-gradient-success {
        background-color: #1cc88a;
        background-image: linear-gradient(45deg, #1cc88a 10%, #13855c 90%);
        background-size: cover;
    }
    
    .bg-gradient-danger {
        background-color: #e74a3b;
        background-image: linear-gradient(45deg, #e74a3b 10%, #be2617 90%);
        background-size: cover;
    }
    
    .bg-gradient-warning {
        background-color: #f6c23e;
        background-image: linear-gradient(45deg, #f6c23e 10%, #dda20a 90%);
        background-size: cover;
    }
    
    /* Text styles */
    h1, h2, h3, h4, h5, h6 {
        font-weight: 600;
    }
    
    .text-muted {
        color: #858796 !important;
    }
    
    .text-primary {
        color: #4e73df !important;
    }
    
    .text-xsmall {
        font-size: 0.75rem;
    }
    
    .breadcrumb {
        background: transparent;
        padding: 0.25rem 0;
        margin-top: 0.25rem;
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        color: #b7b9cc;
    }
    
    .breadcrumb-item.active {
        color: #858796;
    }
</style>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4 slide-in">
        <div class="d-flex align-items-center">
            <div class="icon-circle bg-gradient-primary text-white mr-3">
                <i class="fas fa-money-check-alt"></i>
            </div>
            <div>
                <h1 class="h3 mb-0 text-gray-800">Payment Approval</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Payment Approval</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm slide-in" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <strong><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></strong>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm slide-in" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></strong>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card shadow mb-4 slide-in" style="animation-delay: 0.1s">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter mr-1"></i> Filter Payments
            </h6>
            <a class="btn btn-sm btn-link text-muted p-0" data-toggle="collapse" href="#filterCollapse" role="button" aria-expanded="true" aria-controls="filterCollapse">
                <i class="fas fa-chevron-down"></i>
            </a>
        </div>
        <div class="card-body collapse show" id="filterCollapse">
            <form method="GET" action="approve_payments.php" class="row">
                <div class="col-md-3 mb-3">
                    <label for="status" class="font-weight-bold text-dark small text-uppercase">
                        <i class="fas fa-tag mr-1"></i> Payment Status
                    </label>
                    <select class="form-control shadow-sm" id="status" name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Declined</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="date_from" class="font-weight-bold text-dark small text-uppercase">
                        <i class="fas fa-calendar-alt mr-1"></i> Date From
                    </label>
                    <input type="date" class="form-control shadow-sm" id="date_from" name="date_from" value="<?= $date_from ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="date_to" class="font-weight-bold text-dark small text-uppercase">
                        <i class="fas fa-calendar-alt mr-1"></i> Date To
                    </label>
                    <input type="date" class="form-control shadow-sm" id="date_to" name="date_to" value="<?= $date_to ?>">
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2 shadow-sm">
                        <i class="fas fa-search mr-1"></i> Apply Filters
                    </button>
                    <a href="approve_payments.php" class="btn btn-secondary shadow-sm">
                        <i class="fas fa-undo mr-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments List -->
    <div class="card shadow mb-4 slide-in" style="animation-delay: 0.2s">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-credit-card mr-1"></i> 
                <?php if ($result->num_rows > 0): ?>
                    <?= $result->num_rows ?> Payment<?= $result->num_rows !== 1 ? 's' : '' ?>
                    <?php if ($status_filter !== 'all'): ?>
                        <span class="badge <?php 
                            switch ($status_filter) {
                                case 'pending': echo 'badge-warning'; break;
                                case 'approved': echo 'badge-success'; break;
                                case 'rejected': echo 'badge-danger'; break;
                                default: echo 'badge-secondary';
                            }
                        ?> ml-2"><?= ucfirst($status_filter) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    Payment Records
                <?php endif; ?>
            </h6>
            <div>
                <a href="payment_reports.php" class="btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-download fa-sm mr-1"></i> Generate Report
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="paymentsTable" width="100%" cellspacing="0">
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
                                <tr class="payment-row fade-in" style="animation-delay: <?= $delay = ($delay ?? 0) + 0.05 ?>s">
                                    <td class="font-weight-bold">#<?= $row['payment_id'] ?></td>
                                    <td>
                                        <a href="approve_orders.php?order_id=<?= $row['order_id'] ?>" class="text-primary">
                                            <?= $row['order_id'] ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar mr-2 bg-gray-200 rounded-circle text-center" style="width: 30px; height: 30px; line-height: 30px;">
                                                <i class="fas fa-user text-gray-500" style="font-size: 0.8rem;"></i>
                                            </div>
                                            <div>
                                                <span class="font-weight-bold"><?= htmlspecialchars($row['customer_name']) ?></span><br>
                                                <small class="text-muted"><?= $row['email'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-right font-weight-bold">₱<?= number_format($row['amount'], 2) ?></td>
                                    <td><span class="badge badge-info"><?= ucfirst($row['payment_method']) ?></span></td>
                                    <td>
                                        <span class="text-monospace"><?= $row['reference_number'] ?></span>
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
                                                    echo '<span class="badge badge-danger">Declined</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge badge-secondary">Unknown</span>';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center">
                                            <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#paymentModal<?= $row['payment_id'] ?>" title="View Payment Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($row['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success" onclick="confirmApprove(<?= $row['payment_id'] ?>)" title="Approve Payment">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectPaymentModal<?= $row['payment_id'] ?>" title="Decline Payment">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 fade-in">
                    <div class="mb-4">
                        <div class="empty-state-animation mb-4">
                            <i class="fas fa-money-bill-wave fa-5x text-gray-300"></i>
                            <div class="empty-state-wave"></div>
                        </div>
                        <h4 class="text-gray-800 mb-3">No payments found</h4>
                        <p class="text-muted mb-4">No payments match your current filter criteria</p>
                    </div>
                    <div class="d-flex justify-content-center">
                        <a href="approve_payments.php" class="btn btn-outline-primary mr-2 shadow-sm">
                            <i class="fas fa-sync-alt mr-1"></i> Reset Filters
                        </a>
                    </div>
                </div>
                
                <style>
                    .empty-state-animation {
                        position: relative;
                        height: 120px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    .empty-state-wave {
                        position: absolute;
                        width: 100px;
                        height: 100px;
                        border-radius: 50%;
                        background: rgba(78, 115, 223, 0.1);
                        animation: wave 2s infinite ease-in-out;
                    }
                    
                    @keyframes wave {
                        0% {
                            transform: scale(0.5);
                            opacity: 0.3;
                        }
                        50% {
                            transform: scale(1.2);
                            opacity: 0.1;
                        }
                        100% {
                            transform: scale(0.5);
                            opacity: 0.3;
                        }
                    }
                </style>
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
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title font-weight-bold" id="paymentModalLabel<?= $row['payment_id'] ?>">
                    <i class="fas fa-receipt mr-2"></i>Payment #<?= $row['payment_id'] ?> Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
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
                    <button type="button" class="btn btn-success" onclick="confirmApprove(<?= $row['payment_id'] ?>)">
                        <i class="fas fa-check"></i> Approve Payment
                    </button>
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectPaymentModal<?= $row['payment_id'] ?>" data-dismiss="modal">
                        <i class="fas fa-times"></i> Decline Payment
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
            <div class="modal-header bg-gradient-success text-white">
                <h5 class="modal-title font-weight-bold" id="approvePaymentModalLabel<?= $row['payment_id'] ?>">
                    <i class="fas fa-check-circle mr-2"></i>Approve Payment #<?= $row['payment_id'] ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="approve_payments.php">
                <div class="modal-body">
                    <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                    <input type="hidden" name="status" value="approved">
                    
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
            <div class="modal-header bg-gradient-danger text-white">
                <h5 class="modal-title font-weight-bold" id="rejectPaymentModalLabel<?= $row['payment_id'] ?>">
                    <i class="fas fa-times-circle mr-2"></i>Decline Payment #<?= $row['payment_id'] ?>
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
                        <h5>Are you sure you want to decline this payment?</h5>
                        <p class="text-muted">The customer will be notified that their payment has been declined.</p>
                        
                        <div class="alert alert-warning">
                            <div class="row">
                                <div class="col-6 text-left"><strong>Order:</strong> <?= $row['order_id'] ?></div>
                                <div class="col-6 text-right"><strong>Amount:</strong> ₱<?= number_format($row['amount'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reject_notes<?= $row['payment_id'] ?>" class="font-weight-bold">Reason for Declining <span class="text-danger">*</span></label>
                        <p class="text-muted small">Please provide a clear explanation for why this payment is being declined. This reason will be shared with the customer.</p>
                        <textarea class="form-control" id="reject_notes<?= $row['payment_id'] ?>" name="notes" rows="4" placeholder="Please provide a detailed reason for declining this payment..." required></textarea>
                        <div class="invalid-feedback">
                            Please provide a reason for declining this payment.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmReject<?= $row['payment_id'] ?>">
                        <i class="fas fa-times"></i> Decline Payment
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
        // Initialize DataTable with enhanced options
        $('#paymentsTable').DataTable({
            order: [[6, 'desc']], // Sort by date column descending
            paging: false,        // Disable client-side paging (we're using server-side)
            searching: true,      // Keep searching
            info: false,          // Remove "Showing X of Y entries"
            responsive: true,     // Make table responsive
            dom: '<"top d-flex justify-content-between align-items-center"lf>rt<"bottom"ip>', // Custom layout
            language: {
                search: "",
                searchPlaceholder: "Search payments...",
                emptyTable: "No payment records found",
                zeroRecords: "No matching payments found"
            },
            drawCallback: function() {
                // Add hover effect to rows after draw
                $('#paymentsTable tbody tr').hover(
                    function() { $(this).addClass('bg-light'); },
                    function() { $(this).removeClass('bg-light'); }
                );
            }
        });
        
        // Add search icon to search input
        $('.dataTables_filter input')
            .addClass('form-control-sm shadow-sm ml-2')
            .css({
                'borderRadius': '0.75rem', 
                'paddingLeft': '30px'
            });
        
        $('.dataTables_filter label').prepend('<i class="fas fa-search position-absolute ml-3" style="margin-top: 10px; color: #b7b9cc;"></i>');
        
        // Debug modal interactions
        $('.modal').on('show.bs.modal', function (e) {
            console.log('Modal opening: ' + $(this).attr('id'));
        });
        
        // Ensure submit buttons work
        $('form').on('submit', function() {
            console.log('Form submitted: ' + $(this).serialize());
            return true; // Allow form submission
        });
    });
    
    // Function to confirm payment approval
    function confirmApprove(paymentId) {
        if (confirm("Are you sure you want to approve this payment?\nThis will mark the payment as approved and update the order status.")) {
            // Create a form to submit
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'approve_payments.php';
            
            // Create input for payment_id
            var paymentIdInput = document.createElement('input');
            paymentIdInput.type = 'hidden';
            paymentIdInput.name = 'payment_id';
            paymentIdInput.value = paymentId;
            form.appendChild(paymentIdInput);
            
            // Create input for status
            var statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = 'approved';
            form.appendChild(statusInput);
            
            // Create input for notes (optional)
            var notesInput = document.createElement('input');
            notesInput.type = 'hidden';
            notesInput.name = 'notes';
            notesInput.value = 'Payment approved by administrator';
            form.appendChild(notesInput);
            
            // Submit the form
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<?php
// Include footer
include 'footer.php';
?>