<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['order_id']) || !isset($_POST['sublimator_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$order_id = $_POST['order_id'];
$sublimator_id = $_POST['sublimator_id'];
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

// Start transaction
$conn->begin_transaction();

try {    // Verify order is in approved status
    $check_query = "SELECT order_status FROM orders WHERE order_id = ? AND order_status = 'approved'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("This order cannot be forwarded to sublimator. Order must be in 'approved' status.");
    }

    // Update sublimation_orders table
    $update_query = "UPDATE sublimation_orders 
                    SET sublimator_id = ?
                    WHERE order_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("is", $sublimator_id, $order_id);
    $stmt->execute();

    // Update orders table status
    $update_order = "UPDATE orders 
                    SET order_status = 'forward_to_sublimator',
                        updated_at = NOW() 
                    WHERE order_id = ?";
    $stmt = $conn->prepare($update_order);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();

    // Create notification for sublimator
    $notify_query = "INSERT INTO notifications (user_id, order_id, message, type) 
                    VALUES (?, ?, 'New sublimation order assigned to you', 'order_assignment')";
    $stmt = $conn->prepare($notify_query);
    $stmt->bind_param("is", $sublimator_id, $order_id);
    $stmt->execute();

    // Log the assignment
    if (!empty($notes)) {
        $log_query = "INSERT INTO order_logs (order_id, user_id, action, notes) 
                     VALUES (?, ?, 'assigned_to_sublimator', ?)";
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param("sis", $order_id, $_SESSION['user_id'], $notes);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
