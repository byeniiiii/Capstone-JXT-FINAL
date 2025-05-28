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
    $order_id = $_POST['order_id'];
    $template_id = isset($_POST['template_id']) ? $_POST['template_id'] : null;
    $total_amount = floatval($_POST['total_amount']);
    $full_name = $_POST['full_name'];
    $completion_date = $_POST['completion_date'];
    $customization = $_POST['customization'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $player_names = $_POST['player_name'] ?? [];
    $player_numbers = $_POST['player_number'] ?? [];
    $player_sizes = $_POST['player_size'] ?? [];
    $include_shorts = $_POST['include_shorts'] ?? [];
    $shorts_sizes = $_POST['shorts_size'] ?? [];
    $shorts_numbers = $_POST['shorts_number'] ?? [];
    $quantity = count($player_names);

    $players = [];
    for ($i = 0; $i < count($player_names); $i++) {
        $has_shorts = false;
        foreach ($include_shorts as $shorts_id) {
            if (strpos($shorts_id, (string)$i) !== false) {
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

    $conn->begin_transaction();
    try {
        $order_query = "INSERT INTO orders (
            order_id, customer_id, order_type, total_amount, order_status, payment_status, created_at
        ) VALUES (?, ?, 'sublimation', ?, 'pending_approval', 'unpaid', NOW())";
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param("sid", $order_id, $customer_id, $total_amount);
        $stmt->execute();
        $stmt->close();
            
        // 1. Insert into sublimation_orders (no player_details)
        $sub_query = "INSERT INTO sublimation_orders (
            order_id, template_id, custom_design, quantity, instructions, completion_date
        ) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sub_query);
        $stmt->bind_param("ssisss", 
            $order_id, $template_id, $customization, $quantity, $notes, $completion_date
        );
        $stmt->execute();
        // 2. Get the new sublimation_id
        $sublimation_id = $conn->insert_id;
        $stmt->close();

        // 3. Insert each player into sublimation_players
        $player_query = "INSERT INTO sublimation_players (
            sublimation_id, player_name, jersey_number, size, include_lower, order_id
        ) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($player_query);
        foreach ($players as $player) {
            $include_lower = $player['has_shorts'] ? 1 : 0;
            $stmt->bind_param(
                "isssis",
                $sublimation_id,
                $player['name'],
                $player['number'],
                $player['size'],
                $include_lower,
                $order_id
            );
            $stmt->execute();
        }
        $stmt->close();

        if (isset($_FILES['design_files']) && !empty($_FILES['design_files']['name'][0])) {
            $upload_dir = "uploads/order_designs/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_count = count($_FILES['design_files']['name']);
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['design_files']['error'][$i] === 0) {
                    $file_name = $order_id . '_' . time() . '_' . basename($_FILES['design_files']['name'][$i]);
                    $target_file = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES['design_files']['tmp_name'][$i], $target_file)) {
                        $file_query = "INSERT INTO order_files (order_id, file_path, upload_date) VALUES (?, ?, NOW())";
                        $stmt = $conn->prepare($file_query);
                        $stmt->bind_param("ss", $order_id, $target_file);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        // $history_query = "INSERT INTO order_status_history (order_id, status, updated_by, notes, created_at) VALUES (?, 'pending_approval', ?, 'Order submitted online', NOW())";
        // $stmt = $conn->prepare($history_query);
        // $stmt->bind_param("si", $order_id, $customer_id);
        // $stmt->execute();
        // $stmt->close();

        $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) VALUES (?, ?, ?, ?, NOW())";
        $title = "New Order Placed";
        $message = "Your sublimation order #$order_id has been submitted and is pending approval.";
        $stmt = $conn->prepare($notification_query);
        $stmt->bind_param("isss", $customer_id, $order_id, $title, $message);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['success'] = "Your order has been submitted successfully!";
        // header("Location: order_confirmation.php?order_id=$order_id");
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Order submission failed: " . $e->getMessage();
        header("Location: index.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>