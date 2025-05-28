<?php
include '../db.php';
session_start();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Manager')) {
    header('Location: ../index.php');
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];

// Check if request is AJAX and POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $order_id = isset($_POST['order_id']) ? mysqli_real_escape_string($conn, $_POST['order_id']) : '';
    
    // Validate order ID
    if (empty($order_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
        exit;
    }
    
    // Check if order exists
    $check_query = "SELECT * FROM orders WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }
    
    $order = mysqli_fetch_assoc($result);
    
    if ($action === 'approve') {
        // Get additional notes if provided
        $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
        
        // Update order status to approved
        $update_query = "UPDATE orders SET order_status = 'approved', manager_id = ? WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $order_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Add to order status history
            $history_notes = !empty($notes) ? 'Order approved by admin/manager: ' . $notes : 'Order approved by admin/manager';
            $history_query = "INSERT INTO order_status_history (order_id, status, updated_by, notes) 
                              VALUES (?, 'approved', ?, ?)";
            $stmt = mysqli_prepare($conn, $history_query);
            mysqli_stmt_bind_param($stmt, "sis", $order_id, $user_id, $history_notes);
            mysqli_stmt_execute($stmt);
            
            // Create a notification for the customer
            $notification_message = !empty($notes) ? 
                "Your order #{$order_id} has been approved. Note: {$notes}" : 
                "Your order #{$order_id} has been approved.";
            
            $customer_id = $order['customer_id'];
            $notification_query = "INSERT INTO notifications (title, customer_id, order_id, message) 
                                   VALUES ('Order Approved', ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $notification_query);
            mysqli_stmt_bind_param($stmt, "iss", $customer_id, $order_id, $notification_message);
            mysqli_stmt_execute($stmt);
            
            // Create an activity log entry
            $log_query = "INSERT INTO activity_logs (user_id, user_type, action_type, description) 
                          VALUES (?, 'admin', 'order_approval', CONCAT('Order #', ?, ' approved'))";
            $stmt = mysqli_prepare($conn, $log_query);
            mysqli_stmt_bind_param($stmt, "is", $user_id, $order_id);
            mysqli_stmt_execute($stmt);
            
            echo json_encode(['status' => 'success', 'message' => 'Order has been approved successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to approve order']);
        }
    } 
    else if ($action === 'decline') {
        // Get the reason for declining
        $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : '';
        
        if (empty($reason)) {
            echo json_encode(['status' => 'error', 'message' => 'Reason for declining is required']);
            exit;
        }
        
        // Update order status to declined
        $update_query = "UPDATE orders SET order_status = 'declined', manager_id = ?, notes = CONCAT(IFNULL(notes, ''), '\nDeclined Reason: ', ?) WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $reason, $order_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Add to declined_orders table
            $decline_query = "INSERT INTO declined_orders (order_id, reason, declined_by, created_at) 
                              VALUES (?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $decline_query);
            mysqli_stmt_bind_param($stmt, "ssi", $order_id, $reason, $user_id);
            mysqli_stmt_execute($stmt);
            
            // Add to order status history
            $history_query = "INSERT INTO order_status_history (order_id, status, updated_by, notes) 
                              VALUES (?, 'declined', ?, ?)";
            $stmt = mysqli_prepare($conn, $history_query);
            mysqli_stmt_bind_param($stmt, "sis", $order_id, $user_id, $reason);
            mysqli_stmt_execute($stmt);
            
            // Create a notification for the customer
            $customer_id = $order['customer_id'];
            $notification_query = "INSERT INTO notifications (title, customer_id, order_id, message) 
                                   VALUES ('Order Declined', ?, ?, CONCAT('Your order #', ?, ' was declined: ', ?))";
            $stmt = mysqli_prepare($conn, $notification_query);
            mysqli_stmt_bind_param($stmt, "isss", $customer_id, $order_id, $order_id, $reason);
            mysqli_stmt_execute($stmt);
            
            // Create an activity log entry
            $log_query = "INSERT INTO activity_logs (user_id, user_type, action_type, description) 
                          VALUES (?, 'admin', 'order_decline', CONCAT('Order #', ?, ' declined: ', ?))";
            $stmt = mysqli_prepare($conn, $log_query);
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $order_id, $reason);
            mysqli_stmt_execute($stmt);
            
            echo json_encode(['status' => 'success', 'message' => 'Order has been declined successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to decline order']);
        }
    } 
    else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
} 