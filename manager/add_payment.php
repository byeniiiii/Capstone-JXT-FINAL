<?php
// Include database connection
include '../db.php';

// Start session
session_start();

// Check if the user is logged in and is a Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $reference_number = isset($_POST['reference_number']) ? mysqli_real_escape_string($conn, $_POST['reference_number']) : null;
    
    // Get customer_id from the order
    $order_query = "SELECT customer_id, total_amount, payment_status FROM orders WHERE order_id = '$order_id'";
    $order_result = mysqli_query($conn, $order_query);
    
    if ($order_result && mysqli_num_rows($order_result) > 0) {
        $order = mysqli_fetch_assoc($order_result);
        $customer_id = $order['customer_id'];
        $total_amount = $order['total_amount'];
        
        // Add payment to database
        $query = "INSERT INTO payments (order_id, customer_id, amount, payment_method, reference_number, status, payment_date) 
                  VALUES ('$order_id', '$customer_id', '$amount', '$payment_method', " . 
                  ($reference_number ? "'$reference_number'" : "NULL") . ", 'Confirmed', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $payment_id = mysqli_insert_id($conn);
            
            // Update the payment confirmation details
            $update_query = "UPDATE payments 
                            SET confirmed_by = '{$_SESSION['user_id']}', confirmation_date = NOW() 
                            WHERE payment_id = '$payment_id'";
            mysqli_query($conn, $update_query);
            
            // Check if this payment completes the order payment
            $payments_query = "SELECT SUM(amount) as total_paid FROM payments WHERE order_id = '$order_id'";
            $payments_result = mysqli_query($conn, $payments_query);
            $payments = mysqli_fetch_assoc($payments_result);
            $total_paid = $payments['total_paid'];
            
            // Update order payment status if needed
            if ($total_paid >= $total_amount) {
                $order_update_query = "UPDATE orders SET payment_status = 'Paid', payment_date = NOW() WHERE order_id = '$order_id'";
            } else {
                $order_update_query = "UPDATE orders SET payment_status = 'Partial' WHERE order_id = '$order_id'";
            }
            mysqli_query($conn, $order_update_query);
            
            header("Location: manage_payments.php?success=Payment added successfully");
            exit();
        } else {
            header("Location: manage_payments.php?error=Failed to add payment: " . mysqli_error($conn));
            exit();
        }
    } else {
        header("Location: manage_payments.php?error=Invalid order selected");
        exit();
    }
} else {
    header("Location: manage_payments.php");
    exit();
}
?>