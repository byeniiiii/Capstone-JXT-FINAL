<?php
session_start();
header('Content-Type: application/json');
include '../db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'sublimator_errors.log');

// Check if user is logged in and is a sublimator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Sublimator') {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit();
}

$sublimator_id = $_SESSION['user_id'];

try {
    // Validate inputs
    if (!isset($_POST['order_id']) || !isset($_POST['new_status'])) {
        throw new Exception('Missing required parameters');
    }
    
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    // Validate status
    $allowed_statuses = ['in_process', 'printing_done', 'ready_for_pickup'];
    if (!in_array($new_status, $allowed_statuses)) {
        throw new Exception('Invalid status value');
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Check if the order exists and is assigned to this sublimator
    $check_query = "SELECT o.order_id, o.order_status 
                   FROM orders o
                   JOIN sublimation_orders so ON o.order_id = so.order_id
                   WHERE o.order_id = ? AND so.sublimator_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "si", $order_id, $sublimator_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        throw new Exception('Order not found or not assigned to you');
    }
    
    $order_data = mysqli_fetch_assoc($check_result);
    $current_status = $order_data['order_status'];
    
    // Validate status transition
    $valid_transition = false;
    
    if ($current_status === 'forward_to_sublimator' && $new_status === 'in_process') {
        $valid_transition = true;
    } else if ($current_status === 'in_process' && $new_status === 'printing_done') {
        $valid_transition = true;
    } else if ($current_status === 'printing_done' && $new_status === 'ready_for_pickup') {
        $valid_transition = true;
    }
    
    if (!$valid_transition) {
        throw new Exception('Invalid status transition from ' . $current_status . ' to ' . $new_status);
    }
    
    // Get sublimator name for notes
    $sublimator_query = "SELECT CONCAT(first_name, ' ', last_name) AS sublimator_name 
                        FROM users WHERE user_id = ?";
    $sublimator_stmt = mysqli_prepare($conn, $sublimator_query);
    mysqli_stmt_bind_param($sublimator_stmt, "i", $sublimator_id);
    mysqli_stmt_execute($sublimator_stmt);
    $sublimator_result = mysqli_stmt_get_result($sublimator_stmt);
    $sublimator_name = mysqli_fetch_assoc($sublimator_result)['sublimator_name'];
    
    // Update order status
    $update_query = "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ss", $new_status, $order_id);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Failed to update order status: ' . mysqli_error($conn));
    }
    
    // Generate status note
    $status_note = '';
    switch ($new_status) {
        case 'in_process':
            $status_note = "Order is now in process by $sublimator_name";
            break;
        case 'printing_done':
            $status_note = "Printing completed by $sublimator_name";
            break;
        case 'ready_for_pickup':
            $status_note = "Order marked as ready for pickup by $sublimator_name";
            break;
    }
    
    // Add to order status history
    $history_query = "INSERT INTO order_status_history (order_id, status, updated_by, notes) 
                     VALUES (?, ?, ?, ?)";
    $history_stmt = mysqli_prepare($conn, $history_query);
    mysqli_stmt_bind_param($history_stmt, "ssis", $order_id, $new_status, $sublimator_id, $status_note);
    
    if (!mysqli_stmt_execute($history_stmt)) {
        throw new Exception('Failed to update order history: ' . mysqli_error($conn));
    }
    
    // Create notification for staff
    $staff_notification_query = "INSERT INTO staff_notifications 
                               (staff_id, order_id, message, created_at, is_read) 
                               SELECT user_id, ?, ?, NOW(), 0 
                               FROM users 
                               WHERE role IN ('Admin', 'Staff', 'Manager')";
    $notification_message = "Order #$order_id has been updated to " . str_replace('_', ' ', $new_status) . " by $sublimator_name";
    $staff_notification_stmt = mysqli_prepare($conn, $staff_notification_query);
    mysqli_stmt_bind_param($staff_notification_stmt, "ss", $order_id, $notification_message);
    
    mysqli_stmt_execute($staff_notification_stmt);
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully to ' . str_replace('_', ' ', $new_status)
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
