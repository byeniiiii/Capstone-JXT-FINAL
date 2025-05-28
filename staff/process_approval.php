<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['action'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $updateQuery = "UPDATE orders SET order_status = 'approved', payment_status = 'pending' WHERE order_id = '$order_id'";
        if (mysqli_query($conn, $updateQuery)) {
            echo "Order approved!";
        } else {
            http_response_code(500);
            echo "Error: " . mysqli_error($conn);
        }
        exit;
    }

    if ($action === 'decline' && isset($_POST['reason'])) {
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        $updateQuery = "UPDATE orders SET order_status = 'declined' WHERE order_id = '$order_id'";
        if (mysqli_query($conn, $updateQuery)) {
            // Optionally, save $reason to a decline log table here
            echo "Order declined!";
        } else {
            http_response_code(500);
            echo "Error: " . mysqli_error($conn);
        }
        exit;
    }
}
http_response_code(400);
echo "Invalid request.";
exit;