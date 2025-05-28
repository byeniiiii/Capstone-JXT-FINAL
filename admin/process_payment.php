<?php
// Debug log to check if process_payment.php is being called
$log_file = "../debug_payments.log";
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Process Payment Called\n", FILE_APPEND);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_data = print_r($_POST, true);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - POST Data: " . $post_data . "\n", FILE_APPEND);
}

include '../db.php';
session_start();

// Check if user is logged in and has admin or manager role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Manager')) {
    header('Location: ../index.php');
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
    $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : '';
    
    // Validate payment_id
    if ($payment_id <= 0) {
        $_SESSION['payment_error'] = 'Invalid payment ID';
        header('Location: approve_payments.php');
        exit;
    }
    
    // Validate order_id
    if (empty($order_id)) {
        $_SESSION['payment_error'] = 'Invalid order ID';
        header('Location: approve_payments.php');
        exit;
    }
    
    // First, check if payment exists
    $check_query = "SELECT * FROM payments WHERE payment_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $payment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        $_SESSION['payment_error'] = 'Payment not found';
        header('Location: approve_payments.php');
        exit;
    }
    
    $payment = mysqli_fetch_assoc($result);
    
    // Check if order exists
    $check_order_query = "SELECT * FROM orders WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $check_order_query);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $order_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($order_result) == 0) {
        $_SESSION['payment_error'] = 'Order not found';
        header('Location: approve_payments.php');
        exit;
    }
    
    $order = mysqli_fetch_assoc($order_result);
    
    // Get customer info for notifications
    $customer_id = $order['customer_id'];
    
    if ($action === 'approve') {
        // Get additional data
        $payment_type = isset($_POST['payment_type']) ? $_POST['payment_type'] : '';
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        $order_total = isset($_POST['order_total']) ? (float)$_POST['order_total'] : 0;
        $note = isset($_POST['note']) ? $_POST['note'] : '';
        
        // Update payment status to confirmed
        $update_payment_query = "UPDATE payments SET payment_status = 'confirmed', received_by = ? WHERE payment_id = ?";
        $stmt = mysqli_prepare($conn, $update_payment_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $payment_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update order payment status based on payment type and amount
            $order_payment_status = 'pending';
            if ($payment_type == 'full_payment' || $amount >= $order_total) {
                $order_payment_status = 'fully_paid';
            } else {
                $order_payment_status = 'downpayment_paid';
            }
            
            $update_order_query = "UPDATE orders SET payment_status = ? WHERE order_id = ?";
            $stmt = mysqli_prepare($conn, $update_order_query);
            mysqli_stmt_bind_param($stmt, "ss", $order_payment_status, $order_id);
            mysqli_stmt_execute($stmt);
            
            // Add to payment history
            $history_query = "INSERT INTO payment_history (payment_id, previous_status, new_status, notes, changed_at, changed_by) 
                             VALUES (?, 'pending', 'confirmed', ?, NOW(), ?)";
            $stmt = mysqli_prepare($conn, $history_query);
            mysqli_stmt_bind_param($stmt, "isi", $payment_id, $note, $user_id);
            mysqli_stmt_execute($stmt);
            
            // Create a notification for the customer
            $notification_query = "INSERT INTO notifications (title, customer_id, order_id, message, created_at) 
                                  VALUES ('Payment Confirmed', ?, ?, CONCAT('Your payment of ₱', ?, ' for Order #', ?, ' has been confirmed.'), NOW())";
            $stmt = mysqli_prepare($conn, $notification_query);
            $formatted_amount = number_format($amount, 2);
            mysqli_stmt_bind_param($stmt, "isss", $customer_id, $order_id, $formatted_amount, $order_id);
            mysqli_stmt_execute($stmt);
            
            // Log the activity
            $log_query = "INSERT INTO activity_logs (user_id, user_type, action_type, description, created_at) 
                         VALUES (?, 'admin', 'payment_confirmation', CONCAT('Payment #', ?, ' confirmed for Order #', ?), NOW())";
            $stmt = mysqli_prepare($conn, $log_query);
            mysqli_stmt_bind_param($stmt, "iis", $user_id, $payment_id, $order_id);
            mysqli_stmt_execute($stmt);
            
            // Add a note to the order
            $note_query = "INSERT INTO notes (order_id, user_id, note, created_at) 
                          VALUES (?, ?, CONCAT('Payment of ₱', ?, ' confirmed by ', ?), NOW())";
            $stmt = mysqli_prepare($conn, $note_query);
            mysqli_stmt_bind_param($stmt, "siss", $order_id, $user_id, $formatted_amount, $user_name);
            mysqli_stmt_execute($stmt);
            
            $_SESSION['payment_success'] = 'Payment has been approved successfully!';
            header('Location: approve_payments.php?action_completed=true&payment_id=' . $payment_id);
            exit;
        } else {
            $_SESSION['payment_error'] = 'Failed to approve payment: ' . mysqli_error($conn);
            header('Location: approve_payments.php');
            exit;
        }
    } 
    else if ($action === 'reject') {
        // Get the reason for rejection
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
        
        if (empty($reason)) {
            $_SESSION['payment_error'] = 'A reason must be provided for rejection';
            header('Location: approve_payments.php');
            exit;
        }
        
        // Update payment status to rejected
        $update_query = "UPDATE payments SET payment_status = 'rejected', received_by = ? WHERE payment_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $payment_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Add to payment history
            $history_query = "INSERT INTO payment_history (payment_id, previous_status, new_status, notes, changed_at, changed_by) 
                             VALUES (?, 'pending', 'rejected', ?, NOW(), ?)";
            $stmt = mysqli_prepare($conn, $history_query);
            mysqli_stmt_bind_param($stmt, "isi", $payment_id, $reason, $user_id);
            mysqli_stmt_execute($stmt);
            
            // Create a notification for the customer
            $notification_query = "INSERT INTO notifications (title, customer_id, order_id, message, created_at) 
                                  VALUES ('Payment Rejected', ?, ?, CONCAT('Your payment for Order #', ?, ' was rejected: ', ?), NOW())";
            $stmt = mysqli_prepare($conn, $notification_query);
            mysqli_stmt_bind_param($stmt, "isss", $customer_id, $order_id, $order_id, $reason);
            mysqli_stmt_execute($stmt);
            
            // Log the activity
            $log_query = "INSERT INTO activity_logs (user_id, user_type, action_type, description, created_at) 
                         VALUES (?, 'admin', 'payment_rejection', CONCAT('Payment #', ?, ' rejected for Order #', ?), NOW())";
            $stmt = mysqli_prepare($conn, $log_query);
            mysqli_stmt_bind_param($stmt, "iis", $user_id, $payment_id, $order_id);
            mysqli_stmt_execute($stmt);
            
            // Add a note to the order
            $note_query = "INSERT INTO notes (order_id, user_id, note, created_at) 
                          VALUES (?, ?, CONCAT('Payment rejected: ', ?), NOW())";
            $stmt = mysqli_prepare($conn, $note_query);
            mysqli_stmt_bind_param($stmt, "sis", $order_id, $user_id, $reason);
            mysqli_stmt_execute($stmt);
            
            $_SESSION['payment_success'] = 'Payment has been rejected successfully!';
            header('Location: approve_payments.php?action_completed=true&payment_id=' . $payment_id);
            exit;
        } else {
            $_SESSION['payment_error'] = 'Failed to reject payment: ' . mysqli_error($conn);
            header('Location: approve_payments.php');
            exit;
        }
    } 
    else {
        $_SESSION['payment_error'] = 'Invalid action';
        header('Location: approve_payments.php');
        exit;
    }
} else {
    header('Location: approve_payments.php');
    exit;
} 