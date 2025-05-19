<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['amount'], $_POST['payment_type'], $_POST['payment_method'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

    // Update order status to Paid
    $updateQuery = "UPDATE orders SET order_status = 'Processing', payment_status = 'Paid' WHERE order_id = '$order_id'";

    if (mysqli_query($conn, $updateQuery)) {
        echo "Payment successful! Your order is now being processed.";
    } else {
        echo "Error processing payment: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request!";
}
?>
