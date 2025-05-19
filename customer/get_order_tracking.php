<?php
// Include database connection
include '../db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    // Return error if not logged in
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

// Get the customer ID from session
$customer_id = $_SESSION['customer_id'];

// Get order ID from the request
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No order ID provided']);
    exit();
}

$order_id = $_GET['id'];

// Prepare query to get order status
$query = "SELECT o.order_status as status, o.payment_status, o.status_history, o.created_at, o.updated_at
          FROM orders o
          WHERE o.order_id = ? AND o.customer_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("si", $order_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // If no order found or order doesn't belong to this customer
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Order not found or access denied']);
    exit();
}

// Get the data
$order = $result->fetch_assoc();

// Return order tracking data as JSON
header('Content-Type: application/json');
echo json_encode([
    'status' => $order['status'],
    'payment_status' => $order['payment_status'],
    'status_history' => $order['status_history'] ? json_decode($order['status_history']) : null,
    'created_at' => $order['created_at'],
    'updated_at' => $order['updated_at']
]);
?>
