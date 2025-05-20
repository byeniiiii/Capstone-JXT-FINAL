<?php
// Error reporting settings
error_reporting(0);
ini_set('display_errors', 0);

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
$notes = $_POST['notes'] ?? '';
$user_id = $_SESSION['user_id'];

// Validate order ID
if (empty($order_id)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Check order current status and payment status
    $check_query = $conn->prepare("SELECT order_status, payment_status, customer_id, order_type FROM orders WHERE order_id = ?");
    $check_query->bind_param("s", $order_id);
    $check_query->execute();
    $result = $check_query->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Order not found");
    }
    
    $order_data = $result->fetch_assoc();
    
    // Verify order is ready for pickup and fully paid
    if ($order_data['order_status'] !== 'ready_for_pickup') {
        throw new Exception("Order must be in 'Ready for Pickup' status to mark as completed");
    }
    
    if ($order_data['payment_status'] !== 'fully_paid') {
        throw new Exception("Order must be fully paid before marking as completed");
    }
    
    // Update order status to completed
    $update_order = $conn->prepare("UPDATE orders SET 
                                   order_status = 'completed',
                                   updated_at = NOW() 
                                   WHERE order_id = ?");
    $update_order->bind_param("s", $order_id);
    $update_order->execute();
    
    // Add entry to order history
    if (empty($notes)) {
        $notes = "Order marked as completed by " . $_SESSION['first_name'] . " " . ($_SESSION['last_name'] ?? '');
    }
    
    $add_history = $conn->prepare("INSERT INTO order_status_history 
                                 (order_id, status, updated_by, notes, created_at) 
                                 VALUES (?, 'completed', ?, ?, NOW())");
    $add_history->bind_param("sis", $order_id, $user_id, $notes);
    $add_history->execute();
    
    // Add note
    $add_note = $conn->prepare("INSERT INTO notes 
                             (order_id, user_id, note, created_at) 
                             VALUES (?, ?, ?, NOW())");
    $add_note->bind_param("sis", $order_id, $user_id, $notes);
    $add_note->execute();
    
    // Create customer notification
    $notification_title = "Order Completed";
    $notification_message = "Your " . ucfirst($order_data['order_type']) . " order #$order_id has been marked as completed. Thank you for your business!";
    
    $add_notification = $conn->prepare("INSERT INTO notifications 
                                      (customer_id, order_id, title, message, is_read, created_at) 
                                      VALUES (?, ?, ?, ?, 0, NOW())");
    $add_notification->bind_param("isss", $order_data['customer_id'], $order_id, $notification_title, $notification_message);
    $add_notification->execute();
    
    // Commit the transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => "Order #$order_id has been marked as completed"
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
