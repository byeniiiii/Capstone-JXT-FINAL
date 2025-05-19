<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];

    // Update the order status to "completed"
    $query = "UPDATE orders SET order_status = 'completed' WHERE order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $order_id);

    if ($stmt->execute()) {
        // Redirect back to the orders page with a success message
        header("Location: orders.php?success=Order marked as completed");
    } else {
        // Redirect back to the orders page with an error message
        header("Location: orders.php?error=Failed to mark order as completed");
    }

    $stmt->close();
    $conn->close();
} else {
    // Redirect back to the orders page if the request is invalid
    header("Location: orders.php?error=Invalid request");
    exit();
}