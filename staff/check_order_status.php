<?php
// Prevent any output before our JSON response
header('Content-Type: application/json');

session_start();
include '../db.php';

// Check if staff is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Staff' && $_SESSION['role'] != 'Manager')) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Check if order_id parameter is present
if (!isset($_POST['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing order ID']);
    exit();
}

$order_id = mysqli_real_escape_string($conn, $_POST['order_id']);

try {
    // Get order details with more complete info
    $order_query = "SELECT o.*, c.first_name, c.last_name 
                   FROM orders o
                   LEFT JOIN customers c ON o.customer_id = c.customer_id
                   WHERE o.order_id = ?";
    $stmt = mysqli_prepare($conn, $order_query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to execute statement: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    
    if (!$order) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Order not found'
        ]);
        exit();
    }
    
    // Normalize values for comparison
    $order_status = strtolower(trim($order['order_status']));
    $payment_status = strtolower(trim($order['payment_status']));
    
    // Check for any payments in the payments table
    $payment_query = "SELECT * FROM payments WHERE order_id = ? ORDER BY payment_date DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $payment_query);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $payment_result = mysqli_stmt_get_result($stmt);
    $payment = mysqli_fetch_assoc($payment_result);
    
    // Determine if order can be processed
    $can_process = false;
    $reason = "";
    
    // Check if order is in approved status
    if ($order_status != 'approved') {
        $reason = "Order must be in 'Approved' status.";
    }
    // Check if payment status is acceptable with more flexible matching
    else if (!in_array($payment_status, ['downpayment_paid', 'fully_paid', 'paid', 'partial'])) {
        // Double check if we have a payment record regardless of order payment_status
        if ($payment && $payment['payment_status'] == 'confirmed') {
            $can_process = true; // If we have a confirmed payment, we can process
        } else {
            $reason = "Order must have a partial payment or downpayment.";
        }
    }
    else {
        $can_process = true;
    }
    
    // Return order data with more debug info
    echo json_encode([
        'status' => 'success',
        'order_status' => $order['order_status'],
        'payment_status' => $order['payment_status'],
        'normalized_payment_status' => $payment_status,
        'total_amount' => $order['total_amount'],
        'downpayment_amount' => $order['downpayment_amount'],
        'can_process' => $can_process,
        'reason' => $reason,
        'payment_record' => $payment ? true : false,
        'payment_record_status' => $payment ? $payment['payment_status'] : null,
        'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
        'debug_info' => [
            'raw_order_status' => $order['order_status'],
            'normalized_order_status' => $order_status,
            'raw_payment_status' => $order['payment_status'], 
            'normalized_payment_status' => $payment_status,
            'status_check' => $order_status == 'approved',
            'payment_check' => in_array($payment_status, ['downpayment_paid', 'fully_paid', 'paid', 'partial']),
            'has_payment_record' => $payment ? true : false
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
