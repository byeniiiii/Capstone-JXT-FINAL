<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sublimator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$order_id = $data['order_id'];
$status = $data['status'];
$sublimator_id = $_SESSION['user_id'];

// Verify that this sublimator is assigned to this order
$verify_query = "SELECT sublimator_id FROM sublimation_orders WHERE order_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_data = $result->fetch_assoc();

if (!$order_data || $order_data['sublimator_id'] != $sublimator_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Not assigned to this order']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Update orders table
    $update_order = "UPDATE orders 
                    SET order_status = ?,
                        updated_at = NOW() 
                    WHERE order_id = ?";
    $stmt = $conn->prepare($update_order);
    $stmt->bind_param("ss", $status, $order_id);
    $stmt->execute();

    // Update sublimation_orders table
    $update_sublimation = "UPDATE sublimation_orders 
                          SET status = ? 
                          WHERE order_id = ? 
                          AND sublimator_id = ?";
    $stmt = $conn->prepare($update_sublimation);
    $stmt->bind_param("ssi", $status, $order_id, $sublimator_id);
    $stmt->execute();

    // Log the status change
    $log_query = "INSERT INTO order_logs (order_id, user_id, action) 
                 VALUES (?, ?, ?)";
    $action = $status === 'in_process' ? 'started_processing' : 'completed_sublimation';
    $stmt = $conn->prepare($log_query);
    $stmt->bind_param("sis", $order_id, $sublimator_id, $action);
    $stmt->execute();

    // Create notification for staff if order is completed
    if ($status === 'completed_by_sublimator') {
        // Get staff members who should be notified
        $staff_query = "SELECT user_id FROM users WHERE role = 'staff'";
        $staff_result = mysqli_query($conn, $staff_query);
        
        while ($staff = mysqli_fetch_assoc($staff_result)) {
            $notify_query = "INSERT INTO notifications (user_id, order_id, message, type) 
                           VALUES (?, ?, 'Sublimation order completed and ready for review', 'sublimation_completed')";
            $stmt = $conn->prepare($notify_query);
            $stmt->bind_param("is", $staff['user_id'], $order_id);
            $stmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
