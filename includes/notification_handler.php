<?php
/**
 * Handles creating and managing notifications for both staff and customers
 */

// Function to create staff notification(s)
function createStaffNotification($message, $order_id = null, $conn, $roles = ['Staff', 'Manager']) {
    // Get staff IDs based on roles
    $role_list = "'" . implode("','", $roles) . "'";
    $staffQuery = "SELECT user_id FROM users WHERE role IN ($role_list)";
    $staffResult = $conn->query($staffQuery);
    
    while ($staff = $staffResult->fetch_assoc()) {
        $query = "INSERT INTO staff_notifications (staff_id, order_id, message, created_at) 
                 VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $staff['user_id'], $order_id, $message);
        $stmt->execute();
    }
}

// Function to create customer notification
function createCustomerNotification($customer_id, $title, $message, $order_id, $conn) {
    $query = "INSERT INTO notifications (customer_id, title, order_id, message, created_at) 
              VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $customer_id, $title, $order_id, $message);
    return $stmt->execute();
}

// Function to notify staff about new order
function notifyNewOrder($order_id, $customer_name, $order_type, $conn) {
    $message = "New $order_type order #$order_id received from $customer_name";
    createStaffNotification($message, $order_id, $conn, ['Staff']);
}

// Function to notify staff about payment submission
function notifyPaymentSubmission($order_id, $amount, $payment_type, $conn) {
    $message = "New payment of ₱$amount ($payment_type) submitted for Order #$order_id";
    createStaffNotification($message, $order_id, $conn, ['Manager']);
}

// Function to notify staff about order cancellation
function notifyCancellationRequest($order_id, $reason, $conn) {
    $message = "Cancellation requested for Order #$order_id. Reason: $reason";
    createStaffNotification($message, $order_id, $conn, ['Manager', 'Staff']);
}

// Function to notify customer about order status change
function notifyOrderStatusChange($order_id, $new_status, $customer_id, $conn) {
    $title = "Order Status Updated";
    $message = "Your Order #$order_id has been updated to: " . ucfirst(str_replace('_', ' ', $new_status));
    createCustomerNotification($customer_id, $title, $message, $order_id, $conn);
}

// Function to notify staff about measurement submission
function notifyMeasurementSubmission($order_id, $order_type, $conn) {
    $message = "New measurements submitted for $order_type Order #$order_id";
    createStaffNotification($message, $order_id, $conn, ['Staff']);
}

// Function to mark notification as read
function markNotificationRead($notification_id, $staff_id, $conn) {
    $query = "UPDATE staff_notifications SET is_read = 1 
              WHERE notification_id = ? AND staff_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notification_id, $staff_id);
    return $stmt->execute();
}

// Function to get unread notifications count
function getUnreadNotificationsCount($staff_id, $conn) {
    $query = "SELECT COUNT(*) as count FROM staff_notifications 
              WHERE staff_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}
?>
