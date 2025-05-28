<?php
session_start();
header('Content-Type: application/json');
include '../db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'template_info_errors.log');

try {
    // Verify user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        throw new Exception('Authentication required');
    }
    
    // Check if order_id is provided
    if (!isset($_POST['order_id'])) {
        throw new Exception('Order ID is required');
    }
    
    $order_id = $_POST['order_id'];
    
    // Get template info for this order
    $query = "SELECT so.template_id, t.name AS template_name, t.added_by AS creator_id, 
              CONCAT(u.first_name, ' ', u.last_name) AS creator_name, u.user_id
              FROM sublimation_orders so
              JOIN templates t ON so.template_id = t.template_id
              JOIN users u ON t.added_by = u.user_id
              WHERE so.order_id = ? AND u.role = 'Sublimator'";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $template_info = mysqli_fetch_assoc($result);
        
        echo json_encode([
            'success' => true,
            'has_template' => true,
            'template_id' => $template_info['template_id'],
            'template_name' => $template_info['template_name'],
            'creator_id' => $template_info['creator_id'],
            'creator_name' => $template_info['creator_name']
        ]);
    } else {
        // Check if order exists but has no template
        $check_query = "SELECT order_id FROM sublimation_orders WHERE order_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $order_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            echo json_encode([
                'success' => true,
                'has_template' => false,
                'message' => 'Order exists but has no template or template creator is not a sublimator'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'has_template' => false,
                'message' => 'Order not found'
            ]);
        }
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
} 