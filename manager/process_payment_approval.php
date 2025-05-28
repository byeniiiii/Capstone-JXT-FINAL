    <?php
    // filepath: c:\xampp\htdocs\capstone_jxt\manager\process_payment_approval.php
    session_start();
    include '../db.php';

    // Set proper content type for JSON response
    header('Content-Type: application/json');

    // Enable error reporting for debugging (remove in production)
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // Check if user is logged in and has appropriate role
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Manager' && $_SESSION['role'] != 'Admin')) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Log incoming request data for debugging
    $logfile = fopen("payment_approval_log.txt", "a");
    fwrite($logfile, date('Y-m-d H:i:s') . " - Request: " . json_encode($_POST) . "\n");

    // Check if required parameters are present
    if (!isset($_POST['action']) || !isset($_POST['payment_id']) || !isset($_POST['order_id'])) {
        fwrite($logfile, date('Y-m-d H:i:s') . " - Error: Missing required parameters\n");
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        fclose($logfile);
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
            $order_total = isset($_POST['order_total']) ? (float)$_POST['order_total'] : 0;
            
            // Update payment status to confirmed
            $update_payment = "UPDATE payments SET payment_status = 'confirmed' WHERE payment_id = ?";
            $stmt = $conn->prepare($update_payment);
            $stmt->bind_param("i", $payment_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update payment status: " . $conn->error);
            }
            
            // Update order payment status based on payment type
            $new_payment_status = '';
            $new_order_status = '';
            
            if ($payment_type === 'downpayment') {
                $new_payment_status = 'downpayment_paid';
                $new_order_status = 'approved';
                
                $update_order = "UPDATE orders SET 
                    payment_status = ?, 
                    order_status = IF(order_status = 'pending_approval', ?, order_status), 
                    downpayment_amount = ?,
                    updated_at = NOW()
                    WHERE order_id = ?";
                
                $stmt = $conn->prepare($update_order);
                $stmt->bind_param("ssds", $new_payment_status, $new_order_status, $amount, $order_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update order status: " . $conn->error);
                }
            } 
            elseif ($payment_type === 'full_payment') {
                $new_payment_status = 'fully_paid';
                
                $update_order = "UPDATE orders SET 
                    payment_status = ?,
                    updated_at = NOW()
                    WHERE order_id = ?";
                
                $stmt = $conn->prepare($update_order);
                $stmt->bind_param("ss", $new_payment_status, $order_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update order status: " . $conn->error);
                }
            }
            else {
                // Default case - get amount and determine status
                $payments_query = "SELECT SUM(amount) as total_paid 
                                FROM payments 
                                WHERE order_id = ? 
                                AND payment_status = 'confirmed'";
                
                $stmt = $conn->prepare($payments_query);
                $stmt->bind_param("s", $order_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $payment_sum = $result->fetch_assoc();
                $total_paid = $payment_sum['total_paid'] + $amount;
                
                if ($total_paid >= $order_total) {
                    $new_payment_status = 'fully_paid';
                } else {
                    $new_payment_status = 'partially_paid';
                }
                
                $update_order = "UPDATE orders SET 
                    payment_status = ?,
                    updated_at = NOW()
                    WHERE order_id = ?";
                
                $stmt = $conn->prepare($update_order);
                $stmt->bind_param("ss", $new_payment_status, $order_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update order status: " . $conn->error);
                }
            }
            
            // Add approval note if provided
            if (isset($_POST['note']) && !empty($_POST['note'])) {
                $note = $_POST['note'];
                $note_query = "INSERT INTO notes (order_id, user_id, note, created_at)
                            VALUES (?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($note_query);
                $stmt->bind_param("sis", $order_id, $user_id, $note);
                
                $stmt->execute(); // Don't throw exception if this fails
            }
            
            // Get customer ID for notification
            $customer_query = "SELECT customer_id FROM orders WHERE order_id = ?";
            $stmt = $conn->prepare($customer_query);
            $stmt->bind_param("s", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            $customer_id = $order['customer_id'];
            
            // Create notification for customer
            $title = "Payment Confirmed";
            $message = "Your payment of ₱" . number_format($amount, 2) . " for Order #$order_id has been confirmed.";
            
            $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) 
                                VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($notification_query);
            $stmt->bind_param("isss", $customer_id, $order_id, $title, $message);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create notification: " . $conn->error);
            }
            
            // Commit all changes
            $conn->commit();
            fwrite($logfile, date('Y-m-d H:i:s') . " - Success: Payment approved\n");
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Payment has been approved successfully.',
                'payment_status' => $new_payment_status,
                'order_status' => $new_order_status
            ]);
            
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
                throw new Exception("Failed to reject payment: " . $conn->error);
            }
            
            // Add rejection note
            $note_query = "INSERT INTO notes (order_id, user_id, note, created_at)
                        VALUES (?, ?, ?, NOW())";
            $note = "Payment rejected: " . $reason;
            
            $stmt = $conn->prepare($note_query);
            $stmt->bind_param("sis", $order_id, $user_id, $note);
            if (!$stmt->execute()) {
                fwrite($logfile, date('Y-m-d H:i:s') . " - Warning: Failed to add note: " . $conn->error . "\n");
                // Continue even if note fails
            }
            
            // Get customer ID for notification
            $customer_query = "SELECT customer_id FROM orders WHERE order_id = ?";
            $stmt = $conn->prepare($customer_query);
            $stmt->bind_param("s", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            $customer_id = $order['customer_id'];
            
            // Create notification for customer
            $title = "Payment Rejected";
            $message = "Your payment for Order #$order_id was rejected: $reason";
            
            $notification_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) 
                                VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($notification_query);
            $stmt->bind_param("isss", $customer_id, $order_id, $title, $message);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create notification: " . $conn->error);
            }
            
            // Commit all changes
            $conn->commit();
            fwrite($logfile, date('Y-m-d H:i:s') . " - Success: Payment rejected\n");
            
            echo json_encode(['status' => 'success', 'message' => 'Payment has been rejected.']);
            
        } else {
            throw new Exception("Invalid action specified");
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        fwrite($logfile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n");
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    fclose($logfile);
    $conn->close();