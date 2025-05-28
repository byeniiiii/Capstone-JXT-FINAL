<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../index.php");
    exit();
}

$errors = [];
$response = ['status' => 'error', 'message' => ''];

// Generate a unique order ID (5 characters)
$order_id = substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', 2)), 0, 5);

// Get form data
$customer_id = $_POST['customer_id'];
$design_details = $_POST['design_details'];
$fabric_type = $_POST['fabric_type'];
$quantity = $_POST['quantity'];
$special_instructions = $_POST['special_instructions'] ?? '';
$completion_date = $_POST['completion_date'];
$needs_seamstress = isset($_POST['needs_seamstress']) ? 1 : 0;
$seamstress_appointment = $needs_seamstress && isset($_POST['seamstress_appointment']) 
    ? $_POST['seamstress_appointment'] : null;

// File handling for body measurements
$body_measurement_file = null;
if (isset($_FILES['body_measurement_file']) && $_FILES['body_measurement_file']['error'] === 0) {
    $upload_dir = '../uploads/measurements/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['body_measurement_file']['name'], PATHINFO_EXTENSION));
    $new_filename = 'measurement_' . $order_id . '_' . time() . '.' . $file_extension;
    $destination = $upload_dir . $new_filename;
    
    if (move_uploaded_file($_FILES['body_measurement_file']['tmp_name'], $destination)) {
        $body_measurement_file = $destination;
    } else {
        $errors[] = "Failed to upload measurement file.";
    }
}

// File handling for reference image
$reference_image = null;
if (isset($_FILES['reference_image']) && $_FILES['reference_image']['error'] === 0) {
    $upload_dir = '../uploads/made_to_orders/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['reference_image']['name'], PATHINFO_EXTENSION));
    $new_filename = 'reference_' . $order_id . '_' . time() . '.' . $file_extension;
    $destination = $upload_dir . $new_filename;
    
    if (move_uploaded_file($_FILES['reference_image']['tmp_name'], $destination)) {
        $reference_image = $new_filename;
    } else {
        $errors[] = "Failed to upload reference image.";
    }
}

if (empty($errors)) {
    try {
        $conn->begin_transaction();
        
        // Insert into orders table
        $order_sql = "INSERT INTO orders (order_id, customer_id, order_type, total_amount, downpayment_amount, 
                     payment_method, payment_status, order_status, staff_id) 
                     VALUES (?, ?, 'tailoring', 0.00, 0.00, 'cash', 'pending', 'pending_approval', ?)";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("sii", $order_id, $customer_id, $_SESSION['user_id']);
        $order_stmt->execute();
        
        // Insert into tailoring_orders table
        $tailoring_sql = "INSERT INTO tailoring_orders (order_id, service_type, completion_date, needs_seamstress, 
                        seamstress_appointment) VALUES (?, 'custom made', ?, ?, ?)";
        $tailoring_stmt = $conn->prepare($tailoring_sql);
        $tailoring_stmt->bind_param("ssss", $order_id, $completion_date, $needs_seamstress, $seamstress_appointment);
        $tailoring_stmt->execute();
        
        // Insert into custom_made table
        $custom_sql = "INSERT INTO custom_made (order_id, design_details, body_measurement_file, fabric_type, 
                      quantity, reference_image, special_instructions) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $custom_stmt = $conn->prepare($custom_sql);
        $custom_stmt->bind_param("ssssiss", $order_id, $design_details, $body_measurement_file, 
                               $fabric_type, $quantity, $reference_image, $special_instructions);
        $custom_stmt->execute();
        
        // Create notification for staff
        $notification_sql = "INSERT INTO staff_notifications (staff_id, order_id, message, is_read) 
                           VALUES (?, ?, ?, 0)";
        $notification_stmt = $conn->prepare($notification_sql);
        $message = "New custom order #" . $order_id . " has been created";
        $notification_stmt->bind_param("iss", $_SESSION['user_id'], $order_id, $message);
        $notification_stmt->execute();
        
        $conn->commit();
        
        header("Location: custom_orders.php?success=1");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: custom_orders.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    $error_message = implode(", ", $errors);
    header("Location: custom_orders.php?error=" . urlencode($error_message));
    exit();
}
