<?php
session_start();
include '../db.php';

// Set proper content type for JSON response
header('Content-Type: application/json');

// Check if user is logged in with appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Manager' && $_SESSION['role'] != 'Admin')) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if required parameters are present
if (!isset($_POST['action']) || !isset($_POST['payment_id']) || !isset($_POST['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit();
}

$action = $_POST['action'];
$payment_id = (int)$_POST['payment_id'];
$order_id = $_POST['order_id'];

// Start transaction
$conn->begin_transaction();

try {
    if ($action === 'approve') {
        // Get payment details
        $payment_query = "SELECT * FROM payments WHERE payment_id = ?";
        $stmt = $conn->prepare($payment_query);
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Payment not found");
        }
        
        $payment = $result->fetch_assoc();
        $payment_type = $payment['payment_type'];
        $amount = $payment['amount'];
        
        // Update payment status to confirmed
        $update_payment = "UPDATE payments SET payment_status = 'confirmed' WHERE payment_id = ?";
        $stmt = $conn->prepare($update_payment);
        $stmt->bind_param("i", $payment_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update payment status");
        }
        
        // Update order based on payment type
        if ($payment_type === 'downpayment') {
            $update_order = "UPDATE orders SET 
                payment_status = 'downpayment_paid',
                order_status = IF(order_status = 'pending_approval', 'approved', order_status),
                downpayment_amount = ?,
                updated_at = NOW()
                WHERE order_id = ?";
            
            $stmt = $conn->prepare($update_order);
            $stmt->bind_param("ds", $amount, $order_id);
            
        } else if ($payment_type === 'full_payment') {
            $update_order = "UPDATE orders SET 
                payment_status = 'fully_paid',
                updated_at = NOW()
                WHERE order_id = ?";
            
            $stmt = $conn->prepare($update_order);
            $stmt->bind_param("s", $order_id);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update order");
        }
        
        // Get customer ID for notification
        $customer_query = "SELECT customer_id FROM orders WHERE order_id = ?";
        $stmt = $conn->prepare($customer_query);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $customer_id = $order['customer_id'];
        
        // Create notification for customer
        $title = "Payment Confirmed";
        $message = "Your payment of ₱" . number_format($amount, 2) . " for Order #$order_id has been confirmed.";
        
        $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) 
                              VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($notification_query);
        $stmt->bind_param("isss", $customer_id, $order_id, $title, $message);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create notification");
        }
        
        // Commit transaction
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Payment has been approved successfully']);
        
    } elseif ($action === 'reject') {
        // Validate rejection reason
        if (!isset($_POST['reason']) || empty($_POST['reason'])) {
            throw new Exception("Rejection reason is required");
        }
        
        $reason = $_POST['reason'];
        
        // Update payment status to rejected
        $update_payment = "UPDATE payments SET payment_status = 'rejected' WHERE payment_id = ?";
        $stmt = $conn->prepare($update_payment);
        $stmt->bind_param("i", $payment_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to reject payment");
        }
        
        // Get customer ID for notification
        $customer_query = "SELECT customer_id FROM orders WHERE order_id = ?";
        $stmt = $conn->prepare($customer_query);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $customer_id = $order['customer_id'];
        
        // Create notification for customer
        $title = "Payment Rejected";
        $message = "Your payment for Order #$order_id was rejected: $reason";
        
        $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) 
                              VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($notification_query);
        $stmt->bind_param("isss", $customer_id, $order_id, $title, $message);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create notification");
        }
        
        // Commit transaction
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Payment has been rejected']);
        
    } else {
        throw new Exception("Invalid action specified");
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();