<?php
// Prevent any output before our JSON response
header('Content-Type: application/json');

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
$status = $_POST['status'] ?? '';
$notes = $_POST['notes'] ?? '';
$user_id = $_SESSION['user_id'];

// Validate input
if (empty($order_id) || empty($status)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Order ID and status are required']);
    exit();
}

// Validate status
$valid_statuses = ['approved', 'in_process', 'ready_for_pickup', 'completed'];
if (!in_array($status, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid status value']);
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Update order status
    $update_order = $conn->prepare("UPDATE orders SET 
                                    order_status = ?,
                                    updated_at = NOW() 
                                    WHERE order_id = ?");
    $update_order->bind_param("ss", $status, $order_id);
    $update_order->execute();
    
    // Check if order was updated
    if ($update_order->affected_rows === 0) {
        // Check if the order exists
        $check_order = $conn->prepare("SELECT order_id, order_status, customer_id FROM orders WHERE order_id = ?");
        $check_order->bind_param("s", $order_id);
        $check_order->execute();
        $result = $check_order->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Order not found");
        }
    }
    
    // Get customer information
    $get_customer = $conn->prepare("SELECT customer_id, order_type FROM orders WHERE order_id = ?");
    $get_customer->bind_param("s", $order_id);
    $get_customer->execute();
    $customer_result = $get_customer->get_result();
    $order_data = $customer_result->fetch_assoc();
    $customer_id = $order_data['customer_id'];
    $order_type = $order_data['order_type'];
    
    // Add entry to order history
    if (empty($notes)) {
        $notes = "Order status updated to " . str_replace('_', ' ', $status);
    }
    
    $add_history = $conn->prepare("INSERT INTO order_status_history 
                                 (order_id, status, updated_by, notes, created_at) 
                                 VALUES (?, ?, ?, ?, NOW())");
    $add_history->bind_param("siss", $order_id, $user_id, $notes);
    $add_history->execute();
    
    // Create customer notification
    $notification_title = "Order Status Update";
    $notification_message = "";
    
    switch ($status) {
        case 'in_process':
            $notification_title = "Order In Process";
            $notification_message = "Your " . ucfirst($order_type) . " order #$order_id is now being processed.";
            break;
        case 'ready_for_pickup':
            $notification_title = "Order Ready for Pickup";
            $notification_message = "Your order #$order_id is now ready for pickup.";
            break;
        case 'completed':
            $notification_title = "Order Completed";
            $notification_message = "Your order #$order_id has been marked as completed. Thank you for your business!";
            break;
        default:
            $notification_message = "Your " . ucfirst($order_type) . " order #$order_id status has been updated to " . str_replace('_', ' ', $status);
    }
    
    // Add note to orders table
    $update_note = $conn->prepare("INSERT INTO notes (order_id, user_id, note, created_at) VALUES (?, ?, ?, NOW())");
    $update_note->bind_param("sis", $order_id, $user_id, $notes);
    $update_note->execute();
    
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
        'message' => "Order status updated to " . str_replace('_', ' ', $status)
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