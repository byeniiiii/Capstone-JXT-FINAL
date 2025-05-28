<?php
session_start();
header('Content-Type: application/json');
include '../db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'sublimator_assign_errors.log');

// Debug session info
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));

try {
    // Get parameters from POST data
    if (!isset($_POST['order_id']) || !isset($_POST['sublimator_id'])) {
        throw new Exception('Missing required parameters in POST data');
    }
    
    $order_id = $_POST['order_id'];
    $sublimator_id = $_POST['sublimator_id'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('Invalid security token');
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Validate inputs
    if (empty($order_id) || empty($sublimator_id)) {
        throw new Exception('Missing required parameters');
    }
    
    // Check if sublimator exists
    $sublimator_query = "SELECT CONCAT(first_name, ' ', last_name) AS sublimator_name FROM users WHERE user_id = ? AND role = 'Sublimator'";
    $stmt = mysqli_prepare($conn, $sublimator_query);
    mysqli_stmt_bind_param($stmt, "i", $sublimator_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('Invalid sublimator ID');
    }
    
    $row = mysqli_fetch_assoc($result);
    $sublimator_name = $row['sublimator_name'];
    
    // Check current order status and payment status
    $order_query = "SELECT order_status, payment_status FROM orders WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $order_query);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('Order not found');
    }
    
    $order_data = mysqli_fetch_assoc($result);
    
    error_log("Order status: " . $order_data['order_status'] . ", Payment status: " . $order_data['payment_status']);
    
    // Validate order status and payment status
    if ($order_data['order_status'] !== 'approved') {
        throw new Exception('Order must be in approved status to assign a sublimator');
    }
    
    if ($order_data['payment_status'] !== 'downpayment_paid' && $order_data['payment_status'] !== 'fully_paid') {
        throw new Exception('Order must have at least a downpayment before assigning a sublimator');
    }
    
    // Update orders table with sublimator assignment
    $update_order = "UPDATE orders 
                    SET order_status = 'forward_to_sublimator',
                        updated_at = NOW(),
                        notes = CASE 
                            WHEN notes IS NULL OR notes = '' THEN ?
                            ELSE CONCAT(notes, '\n', ?)
                        END
                    WHERE order_id = ?";

    $stmt = mysqli_prepare($conn, $update_order);
    $note_text = "Assigned to sublimator: " . $sublimator_name;    
    if ($notes) {
        $note_text .= "\nNotes: " . $notes;
    }
    
    mysqli_stmt_bind_param($stmt, "sss", $note_text, $note_text, $order_id);    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update order: ' . mysqli_error($conn));    
    }
    
    // Check if a record already exists in sublimation_orders
    $check_sql = "SELECT sublimation_id, template_id FROM sublimation_orders WHERE order_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $order_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    error_log("Checking if order exists: " . $order_id . " - Found: " . mysqli_num_rows($check_result));
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing record
        $order_data = mysqli_fetch_assoc($check_result);
        $template_id = $order_data['template_id'];
        $update_sublimation = "UPDATE sublimation_orders 
                              SET sublimator_id = ?, 
                                  completion_date = DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
                              WHERE order_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_sublimation);
        mysqli_stmt_bind_param($stmt, "is", $sublimator_id, $order_id);
        
        error_log("Updating existing sublimation order: " . $order_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            $error_msg = "Failed to update sublimation order record: " . mysqli_error($conn);
            error_log($error_msg);
            throw new Exception($error_msg);
        }
    } else {
        // Insert new record
        $insert_sublimation = "INSERT INTO sublimation_orders (order_id, sublimator_id, completion_date, quantity, printing_type)
                              VALUES (?, ?, DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY), 1, 'sublimation')";
        
        $stmt = mysqli_prepare($conn, $insert_sublimation);
        mysqli_stmt_bind_param($stmt, "si", $order_id, $sublimator_id);
        
        error_log("Inserting new sublimation order: " . $order_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            $error_msg = "Failed to create sublimation order record: " . mysqli_error($conn);
            error_log($error_msg);
            throw new Exception($error_msg);
        }
    }
    
    // Add to order status history
    $history_sql = "INSERT INTO order_status_history (order_id, status, updated_by, notes) 
                    VALUES (?, 'forward_to_sublimator', ?, ?)";
    $history_stmt = mysqli_prepare($conn, $history_sql);
    mysqli_stmt_bind_param($history_stmt, "sis", $order_id, $_SESSION['user_id'], $note_text);
    
    if (!mysqli_stmt_execute($history_stmt)) {
        $error_msg = "Failed to update order history: " . mysqli_error($conn);
        error_log($error_msg);
        throw new Exception($error_msg);
    }
    
    // Create notification for the sublimator
    $notification_sql = "INSERT INTO staff_notifications 
                        (staff_id, order_id, message, created_at, is_read) 
                        VALUES (?, ?, ?, NOW(), 0)";
    $notification_stmt = mysqli_prepare($conn, $notification_sql);
    
    // Get order details for notification
    $order_details_sql = "SELECT o.order_id, o.total_amount, c.first_name, c.last_name, 
                          CASE WHEN so.template_id IS NOT NULL THEN 
                            (SELECT name FROM templates WHERE template_id = so.template_id) 
                          ELSE 'Custom design' END AS design_info
                          FROM orders o
                          JOIN customers c ON o.customer_id = c.customer_id
                          LEFT JOIN sublimation_orders so ON o.order_id = so.order_id
                          WHERE o.order_id = ?";
    $order_details_stmt = mysqli_prepare($conn, $order_details_sql);
    mysqli_stmt_bind_param($order_details_stmt, "s", $order_id);
    mysqli_stmt_execute($order_details_stmt);
    $order_details_result = mysqli_stmt_get_result($order_details_stmt);
    $order_details = mysqli_fetch_assoc($order_details_result);
    
    $customer_name = $order_details['first_name'] . ' ' . $order_details['last_name'];
    $design_info = $order_details['design_info'];
    $total_amount = $order_details['total_amount'];
    
    $notification_message = "You have been assigned a new sublimation order #$order_id for $customer_name. ";
    $notification_message .= "Design: $design_info. Total amount: ₱" . number_format($total_amount, 2);
    
    mysqli_stmt_bind_param($notification_stmt, "iss", $sublimator_id, $order_id, $notification_message);
    
    if (!mysqli_stmt_execute($notification_stmt)) {
        $error_msg = "Failed to create notification: " . mysqli_error($conn);
        error_log($error_msg);
        // Don't throw exception here, as this is not critical to the main flow
    }
    
    // Commit the transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sublimator assigned successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    error_log("Error in assign_sublimator.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
