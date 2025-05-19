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

// Check if required parameters are present
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit();
}

$order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
$status = mysqli_real_escape_string($conn, $_POST['status']);
$notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
$user_id = $_SESSION['user_id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Check if this is a sublimation order being approved
    if ($status === 'approved') {
        // Check if it's a sublimation order with a template
        $check_query = "SELECT s.template_id, t.added_by 
                       FROM sublimation_orders s
                       LEFT JOIN templates t ON s.template_id = t.template_id
                       WHERE s.order_id = ? AND s.template_id IS NOT NULL";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $order_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if ($row = mysqli_fetch_assoc($check_result)) {
            $sublimator_id = $row['added_by'];
            if ($sublimator_id) {
                // Update sublimation_orders table
                $update_query = "UPDATE sublimation_orders 
                               SET sublimator_id = ?
                               WHERE order_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("is", $sublimator_id, $order_id);
                $stmt->execute();

                // Update order status to forwarded
                $status = 'forward_to_sublimator';

                // Create notification for sublimator
                $notify_query = "INSERT INTO notifications (user_id, order_id, message, type) 
                               VALUES (?, ?, 'New sublimation order assigned to you', 'order_assignment')";
                $stmt = $conn->prepare($notify_query);
                $stmt->bind_param("is", $sublimator_id, $order_id);
                $stmt->execute();
            }
        }
    }

    // Update order status
    $update_order = "UPDATE orders 
                    SET order_status = ?,
                        updated_at = NOW() 
                    WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $update_order);
    mysqli_stmt_bind_param($stmt, "ss", $status, $order_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error updating order status");
    }
    
    // Get customer ID for notification
    $customer_query = "SELECT customer_id FROM orders WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $customer_query);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    
    if (!$order) {
        throw new Exception("Order not found");
    }
    
    $customer_id = $order['customer_id'];
    
    // Create a notification for the customer
    $title = "";
    $message = "";
    
    switch ($status) {
        case 'ready_for_pickup':
            $title = "Order Ready for Pickup";
            $message = "Your order #$order_id is now ready for pickup.";
            break;
        case 'forward_to_sublimator':
            $title = "Order Forwarded to Sublimator";
            $message = "Your order #$order_id has been forwarded to our sublimator.";
            break;
        case 'in_process':
            $title = "Order In Process";
            $message = "Your order #$order_id is now being processed.";
            break;
        case 'completed':
            $title = "Order Completed";
            $message = "Your order #$order_id has been completed.";
            break;
        case 'declined':
            $title = "Order Declined";
            $message = "Your order #$order_id has been declined.";
            break;
        default:
            $title = "Order Update";
            $message = "Your order #$order_id has been updated to: " . str_replace('_', ' ', ucwords($status));
    }
    
    $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) 
                         VALUES (?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $notification_query);
    mysqli_stmt_bind_param($stmt, "isss", $customer_id, $order_id, $title, $message);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error creating notification");
    }
    
    // Add order note if provided
    if (!empty($notes)) {
        $note_query = "INSERT INTO notes (order_id, user_id, note, created_at) 
                      VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $note_query);
        mysqli_stmt_bind_param($stmt, "sis", $order_id, $user_id, $notes);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error adding order note");
        }
    }

    // For sublimation orders, no need to update the sublimation_orders table status
    // since we don't have a status column in sublimation_orders table
    // We only need to update the orders table which is already done above
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode(['status' => 'success', 'message' => 'Order status updated successfully']);
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
// No closing PHP tag to prevent accidental whitespace