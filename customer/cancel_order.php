<?php
// filepath: c:\xampp\htdocs\capstone_jxt\customer\cancel_order.php
session_start();
header('Content-Type: application/json');
include '../db.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['customer_id'])) {
        throw new Exception('User not authenticated');
    }

    // Get and validate input
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['order_id'])) {
        throw new Exception('Order ID is required');
    }

    $order_id = $data['order_id'];
    $customer_id = $_SESSION['customer_id'];

    // Begin transaction
    mysqli_begin_transaction($conn);

    // Verify order belongs to customer and is in pending status
    $check_query = "SELECT order_id FROM orders 
                   WHERE order_id = ? AND customer_id = ? 
                   AND order_status = 'pending_approval'";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "si", $order_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        throw new Exception('Order not found or cannot be cancelled');
    }

    // Delete related records first (maintain referential integrity)
    // Delete from sublimation_players if exists
    $delete_players = "DELETE sp FROM sublimation_players sp 
                      JOIN sublimation_orders so ON sp.sublimation_id = so.sublimation_id 
                      WHERE so.order_id = ?";
    $stmt = mysqli_prepare($conn, $delete_players);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);

    // Delete from sublimation_orders if exists
    $delete_sublimation = "DELETE FROM sublimation_orders WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sublimation);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);

    // Delete from tailoring_orders if exists
    $delete_tailoring = "DELETE FROM tailoring_orders WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $delete_tailoring);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);

    // Finally delete the main order
    $delete_order = "DELETE FROM orders WHERE order_id = ? AND customer_id = ?";
    $stmt = mysqli_prepare($conn, $delete_order);
    mysqli_stmt_bind_param($stmt, "si", $order_id, $customer_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to delete order');
    }

    // Commit transaction
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && mysqli_ping($conn)) {
        mysqli_rollback($conn);
    }
    
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
?>