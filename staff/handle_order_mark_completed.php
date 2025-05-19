<?php
// Error reporting settings
error_reporting(0);
ini_set('display_errors', 0);

session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

// Get order ID from POST request
$order_id = isset($_POST['order_id']) ? $_POST['order_id'] : null;
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Update the order status to completed
    $update_order = "UPDATE orders SET 
                    order_status = 'completed',
                    completed_at = NOW(),
                    completed_by = ?,
                    updated_at = NOW()
                    WHERE order_id = ?";
                    
    $stmt = $conn->prepare($update_order);
    if (!$stmt) {
        throw new Exception("Database error occurred");
    }
    
    $stmt->bind_param("is", $user_id, $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error occurred");
    }
    
    // Get customer details for notification
    $customer_query = "SELECT c.customer_id, c.first_name, c.last_name, o.order_type 
                      FROM orders o
                      JOIN customers c ON o.customer_id = c.customer_id
                      WHERE o.order_id = ?";
                      
    $stmt = $conn->prepare($customer_query);
    if (!$stmt) {
        throw new Exception("Database error occurred");
    }
    
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer_data = $result->fetch_assoc();
    
    if (!$customer_data) {
        throw new Exception("Customer information not found");
    }
    
    // Create a notification for the customer
    $title = "Order Completed";
    $message = "Great news! Your " . ucfirst($customer_data['order_type']) . " order (#$order_id) has been marked as completed.";
    
    $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) 
                          VALUES (?, ?, ?, ?, NOW())";
                          
    $stmt = $conn->prepare($notification_query);
    if (!$stmt) {
        throw new Exception("Database error occurred");
    }
    
    $stmt->bind_param("isss", $customer_data['customer_id'], $order_id, $title, $message);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error occurred");
    }
    
    // Optional: Add a note about the completion
    $note = "Order marked as completed by " . $_SESSION['first_name'] . " " . ($_SESSION['last_name'] ?? '');
    $note_query = "INSERT INTO notes (order_id, user_id, note, created_at)
                  VALUES (?, ?, ?, NOW())";
                  
    $stmt = $conn->prepare($note_query);
    if (!$stmt) {
        throw new Exception("Database error occurred");
    }
    
    $stmt->bind_param("sis", $order_id, $user_id, $note);
    $stmt->execute(); // We don't need to throw an exception if this fails
    
    // Commit the transaction
    $conn->commit();
    
    echo json_encode(['status' => 'success', 'message' => 'Order marked as completed successfully']);
    
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
}
?>
