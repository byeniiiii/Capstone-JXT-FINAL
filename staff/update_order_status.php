<?php
// Prevent any output before our JSON response
header('Content-Type: application/json');

session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get request data
$order_id = $_POST['order_id'] ?? '';
$new_status = $_POST['new_status'] ?? '';
$notes = $_POST['notes'] ?? '';
$user_id = $_SESSION['user_id'];

// Validate input
if (empty($order_id) || empty($new_status)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID and status are required']);
    exit();
}

// Validate status
$valid_statuses = ['approved', 'in_process', 'ready_for_pickup', 'completed', 'declined', 'forward_to_sublimator', 'printing_done'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check current order status and payment status
    $check_sql = "SELECT o.*, c.customer_id, c.first_name, c.last_name 
                 FROM orders o 
                 JOIN customers c ON o.customer_id = c.customer_id 
                 WHERE o.order_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('s', $order_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Order not found');
    }

    $order_data = $result->fetch_assoc();

    // Validate status transition
    $current_status = $order_data['order_status'];
    $payment_status = strtolower($order_data['payment_status']);

    // Define valid status transitions
    $valid_transitions = [
        'pending_approval' => ['approved', 'declined'],
        'approved' => ['in_process', 'forward_to_sublimator'],
        'forward_to_sublimator' => ['in_process', 'printing_done'],
        'in_process' => ['printing_done', 'ready_for_pickup'],
        'printing_done' => ['ready_for_pickup'],
        'ready_for_pickup' => ['completed']
    ];

    // Check if the status transition is valid
    if (!isset($valid_transitions[$current_status]) || !in_array($new_status, $valid_transitions[$current_status])) {
        throw new Exception("Invalid status transition from '$current_status' to '$new_status'");
    }

    // Additional validations based on status
    if ($new_status === 'in_process') {
        if (!in_array($payment_status, ['downpayment_paid', 'fully_paid', 'paid'])) {
            throw new Exception('Order requires at least a downpayment to be processed');
        }
    } else if ($new_status === 'completed') {
        if (!in_array($payment_status, ['fully_paid', 'paid'])) {
            throw new Exception('Order must be fully paid to be marked as completed');
        }
    }

    // Update order status with appropriate staff/manager ID
    $update_sql = "UPDATE orders SET 
                   order_status = ?,
                   staff_id = ?,
                   updated_at = NOW() ";
    
    // Add manager_id for completed status
    if ($new_status === 'completed') {
        $update_sql .= ", manager_id = ? ";
    }
    
    $update_sql .= "WHERE order_id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    
    if ($new_status === 'completed') {
        $update_stmt->bind_param('siis', $new_status, $user_id, $user_id, $order_id);
    } else {
        $update_stmt->bind_param('sis', $new_status, $user_id, $order_id);
    }
    
    if (!$update_stmt->execute()) {
        throw new Exception("Error updating order status: " . $update_stmt->error);
    }

    // Add entry to order status history
    $history_sql = "INSERT INTO order_status_history (order_id, status, updated_by, notes) 
                   VALUES (?, ?, ?, ?)";
    $history_stmt = $conn->prepare($history_sql);
    $history_stmt->bind_param('ssis', $order_id, $new_status, $user_id, $notes);
    
    if (!$history_stmt->execute()) {
        throw new Exception("Error adding status history: " . $history_stmt->error);
    }

    // Create customer notification
    $notification_title = 'Order Status Update';
    $notification_message = "Your order #{$order_id} ";

    switch ($new_status) {
        case 'approved':
            $notification_message .= "has been approved.";
            break;
        case 'forward_to_sublimator':
            $notification_message .= "has been forwarded to the sublimator.";
            break;
        case 'in_process':
            $notification_message .= "is now being processed.";
            break;
        case 'printing_done':
            $notification_message .= "printing is completed.";
            break;
        case 'ready_for_pickup':
            $notification_message .= "is now ready for pickup.";
            break;
        case 'completed':
            $notification_message .= "has been completed.";
            break;
        case 'declined':
            $notification_message .= "has been declined.";
            if ($notes) {
                $notification_message .= " Reason: " . $notes;
            }
            break;
    }

    $notification_sql = "INSERT INTO notifications (customer_id, order_id, title, message) 
                       VALUES (?, ?, ?, ?)";
    $notification_stmt = $conn->prepare($notification_sql);
    $notification_stmt->bind_param('isss', 
        $order_data['customer_id'],
        $order_id,
        $notification_title,
        $notification_message
    );

    if (!$notification_stmt->execute()) {
        throw new Exception("Error creating notification: " . $notification_stmt->error);
    }

    // If order is completed, add activity log
    if ($new_status === 'completed') {
        $activity_sql = "INSERT INTO activity_logs 
                        (user_id, user_type, action_type, description) 
                        VALUES (?, 'staff', 'order_complete', ?)";
        $activity_stmt = $conn->prepare($activity_sql);
        $description = "Completed order #{$order_id} for " . 
                      $order_data['first_name'] . ' ' . $order_data['last_name'];
        $activity_stmt->bind_param('is', $user_id, $description);
        $activity_stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['status' => 'success', 'success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>