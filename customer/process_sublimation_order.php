<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Function to generate a unique order ID
function generateOrderID($conn) {
    $prefix = 'JXT';
    $year = date('y');
    $month = date('m');
    
    do {
        $randomStr = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        $order_id = $prefix . $year . $month . $randomStr;
        
        // Check if this ID already exists
        $check_query = "SELECT order_id FROM orders WHERE order_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
    } while ($exists);
    
    return $order_id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate unique order ID
    $order_id = generateOrderID($conn);
    
    // Get form data
    $template_id = isset($_POST['template_id']) ? $_POST['template_id'] : null;
    $total_amount = floatval($_POST['total_amount']);
    $full_name = $_POST['full_name'];
    $completion_date = $_POST['completion_date'];
    $customization = $_POST['customization'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Get players data
    $player_names = $_POST['player_name'] ?? [];
    $player_numbers = $_POST['player_number'] ?? [];
    $player_sizes = $_POST['player_size'] ?? [];
    $include_shorts = $_POST['include_shorts'] ?? [];
    $shorts_sizes = $_POST['shorts_size'] ?? [];
    $shorts_numbers = $_POST['shorts_number'] ?? [];
    
    // Calculate total quantity (total number of jerseys)
    $quantity = count($player_names);
    
    // Prepare player details as JSON
    $players = [];
    for ($i = 0; $i < count($player_names); $i++) {
        // Check if this player ID is in the include_shorts array
        $has_shorts = false;
        foreach ($include_shorts as $shorts_id) {
            if (strpos($shorts_id, $i) !== false) {
                $has_shorts = true;
                break;
            }
        }
        
        $player = [
            'name' => $player_names[$i],
            'number' => $player_numbers[$i],
            'size' => $player_sizes[$i],
            'has_shorts' => $has_shorts,
            'shorts_size' => $shorts_sizes[$i] ?? '',
            'shorts_number' => $shorts_numbers[$i] ?? ''
        ];
        $players[] = $player;
    }
    $player_details_json = json_encode($players);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert order into orders table
        $order_query = "INSERT INTO orders (
            order_id, customer_id, order_type, total_amount, order_status, payment_status, created_at
        ) VALUES (?, ?, 'sublimation', ?, 'pending_approval', 'unpaid', NOW())";
        
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param("sid", $order_id, $customer_id, $total_amount);
        $stmt->execute();
        
        // Insert sublimation details
        $sub_query = "INSERT INTO sublimation_orders (
            order_id, template_id, quantity, customization, 
            player_details, required_date, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sub_query);
        $stmt->bind_param("sisssss", 
            $order_id, $template_id, $quantity, $customization, 
            $player_details_json, $completion_date, $notes
        );
        $stmt->execute();
        
        // Handle file uploads if any
        if (isset($_FILES['design_files']) && !empty($_FILES['design_files']['name'][0])) {
            $upload_dir = "uploads/order_designs/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_count = count($_FILES['design_files']['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['design_files']['error'][$i] === 0) {
                    $file_name = $order_id . '_' . time() . '_' . basename($_FILES['design_files']['name'][$i]);
                    $target_file = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['design_files']['tmp_name'][$i], $target_file)) {
                        // Insert file record
                        $file_query = "INSERT INTO order_files (order_id, file_path, upload_date) VALUES (?, ?, NOW())";
                        $stmt = $conn->prepare($file_query);
                        $stmt->bind_param("ss", $order_id, $target_file);
                        $stmt->execute();
                    }
                }
            }
        }
        
        // Add initial status history
        $history_query = "INSERT INTO order_status_history (order_id, status, notes, changed_at) VALUES (?, 'pending_approval', 'Order submitted online', NOW())";
        $stmt = $conn->prepare($history_query);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        
        // Create a notification for the customer
        $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) VALUES (?, ?, ?, ?, NOW())";
        $title = "New Order Placed";
        $message = "Your sublimation order #$order_id has been submitted and is pending approval.";
        $stmt = $conn->prepare($notification_query);
        $stmt->bind_param("isss", $customer_id, $order_id, $title, $message);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to confirmation page
        header("Location: order_confirmation.php?order_id=$order_id");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Order submission failed: " . $e->getMessage();
        header("Location: index.php");
        exit;
    }
} else {
    // If someone tries to access this file directly without POST data
    header("Location: index.php");
    exit;
}
?>