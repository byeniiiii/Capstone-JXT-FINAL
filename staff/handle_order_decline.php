<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Get request data
$order_id = $_POST['order_id'] ?? '';
$reason = $_POST['reason'] ?? '';
$user_id = $_SESSION['user_id'];

// Validate input
if (empty($order_id)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit();
}

if (empty($reason)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Reason for decline is required']);
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Update order status
    $update_order = $conn->prepare("UPDATE orders SET 
                                   order_status = 'declined',
                                   notes = CONCAT(IFNULL(notes,''), '\nDeclined Reason: ', ?),
                                   updated_at = NOW() 
                                   WHERE order_id = ?");
    $update_order->bind_param("ss", $reason, $order_id);
    $update_order->execute();
    
    // Check if order was updated
    if ($update_order->affected_rows === 0) {
        // Check if the order exists
        $check_order = $conn->prepare("SELECT order_id, customer_id FROM orders WHERE order_id = ?");
        $check_order->bind_param("s", $order_id);
        $check_order->execute();
        $result = $check_order->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Order not found");
        }
    }
    
    // Get customer information
    $get_customer = $conn->prepare("SELECT customer_id FROM orders WHERE order_id = ?");
    $get_customer->bind_param("s", $order_id);
    $get_customer->execute();
    $customer_result = $get_customer->get_result();
    $customer_id = $customer_result->fetch_assoc()['customer_id'];
    
    // Add to declined_orders table
    $add_declined = $conn->prepare("INSERT INTO declined_orders 
                                  (order_id, reason, declined_by, created_at) 
                                  VALUES (?, ?, ?, NOW())");
    $add_declined->bind_param("ssi", $order_id, $reason, $user_id);
    $add_declined->execute();
    
    // Add to order status history
    $add_history = $conn->prepare("INSERT INTO order_status_history 
                                 (order_id, status, updated_by, notes, created_at) 
                                 VALUES (?, 'declined', ?, ?, NOW())");
    $decline_note = "Order declined. Reason: " . $reason;
    $add_history->bind_param("sis", $order_id, $user_id, $decline_note);
    $add_history->execute();
    
    // Create activity log
    $log_query = $conn->prepare("INSERT INTO activity_logs 
                              (user_id, user_type, action_type, description, created_at) 
                              VALUES (?, ?, ?, ?, NOW())");
    $user_type = $_SESSION['role'] == 'Admin' ? 'admin' : 'customer';
    $action_type = 'order_decline';
    $description = "Order #$order_id declined: $reason";
    $log_query->bind_param("isss", $user_id, $user_type, $action_type, $description);
    $log_query->execute();
    
    // Create customer notification
    $notification_title = "Order Declined";
    $notification_message = "Your order #$order_id was declined: $reason";
    
    $add_notification = $conn->prepare("INSERT INTO notifications 
                                      (customer_id, order_id, title, message, is_read, created_at) 
                                      VALUES (?, ?, ?, ?, 0, NOW())");
    $add_notification->bind_param("isss", $customer_id, $order_id, $notification_title, $notification_message);
    $add_notification->execute();
    
    // Commit the transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => "Order #$order_id has been declined"
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
