<?php
// filepath: c:\xampp\htdocs\capstone_jxt\customer\cancel_order.php
include '../db.php';
session_start();

// Check if the customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $customer_id = $_SESSION['customer_id'];
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);

    // Verify that the order belongs to the customer and is in a cancellable state
    $query = "SELECT order_id FROM orders WHERE order_id = ? AND customer_id = ? AND order_status = 'pending_approval'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $order_id, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Begin transaction
        mysqli_begin_transaction($conn);

        try {
            // Delete related records in sublimation_orders or tailoring_orders
            $delete_sublimation = "DELETE FROM sublimation_orders WHERE order_id = ?";
            $stmt = $conn->prepare($delete_sublimation);
            $stmt->bind_param("s", $order_id);
            $stmt->execute();

            $delete_tailoring = "DELETE FROM tailoring_orders WHERE order_id = ?";
            $stmt = $conn->prepare($delete_tailoring);
            $stmt->bind_param("s", $order_id);
            $stmt->execute();

            // Delete the order itself
            $delete_order = "DELETE FROM orders WHERE order_id = ? AND customer_id = ?";
            $stmt = $conn->prepare($delete_order);
            $stmt->bind_param("si", $order_id, $customer_id);
            $stmt->execute();

            // Commit transaction
            mysqli_commit($conn);

            $response['success'] = true;
            $response['message'] = 'Order canceled successfully.';
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $response['message'] = 'Failed to cancel the order. Please try again.';
        }
    } else {
        $response['message'] = 'Order not found or cannot be canceled.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>