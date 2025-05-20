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
    // Get order details to check status and payment status
    $order_query = "SELECT o.order_status, o.payment_status, o.total_amount FROM orders o WHERE o.order_id = ?";
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
    
    // Return order data
    echo json_encode([
        'status' => 'success',
        'order_status' => $order['order_status'],
        'payment_status' => $order['payment_status'],
        'total_amount' => $order['total_amount']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
