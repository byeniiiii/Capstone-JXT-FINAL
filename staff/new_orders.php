<?php
// filepath: c:\xampp\htdocs\capstone_jxt\staff\new_orders.php
include '../db.php';
session_start();

// Check if staff is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Staff' && $_SESSION['role'] != 'Manager')) {
    header("Location: ../index.php");
    exit();
}

$search_result = [];
$search_performed = false;
$error_message = '';
$success_message = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
$order_type = isset($_GET['order_type']) ? $_GET['order_type'] : null;

// Handle customer search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_performed = true;
    
    // Search for existing customer by name, email or phone
    $search_query = "SELECT customer_id, first_name, last_name, email, phone_number 
                    FROM customers 
                    WHERE first_name LIKE '%$search_term%' 
                    OR last_name LIKE '%$search_term%' 
                    OR email LIKE '%$search_term%' 
                    OR phone_number LIKE '%$search_term%' 
                    LIMIT 10";
    
    $search_result = mysqli_query($conn, $search_query);
}

// Handle new customer creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_customer'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($phone)) {
        $error_message = "First name, last name, and phone number are required.";
    } else {
        // Generate a random password for the account
        $password = bin2hex(random_bytes(6)); // 12 character password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate a unique username based on their name and a random string
        $random_suffix = substr(bin2hex(random_bytes(4)), 0, 6); // 6 character random string
        $base_username = strtolower($first_name . "." . $last_name);
        // Remove special characters and spaces
        $base_username = preg_replace('/[^a-z0-9]/', '', $base_username);
        $username = $base_username . $random_suffix;

        // Check if email or phone already exists
        $check_query = "SELECT customer_id FROM customers WHERE email = '$email' OR phone_number = '$phone'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "A customer with this email or phone number already exists.";
        } else {
            // Insert new customer
            $insert_query = "INSERT INTO customers (first_name, last_name, email, phone_number, address, password, username, created_at) 
                           VALUES ('$first_name', '$last_name', '$email', '$phone', '$address', '$hashed_password', '$username', NOW())";
            
            if (mysqli_query($conn, $insert_query)) {
                $customer_id = mysqli_insert_id($conn);
                $success_message = "Customer created successfully!";
                
                // Redirect to step 2 (order type selection)
                header("Location: new_orders.php?step=2&customer_id=$customer_id");
                exit();
            } else {
                $error_message = "Error creating customer: " . mysqli_error($conn);
            }
        }
    }
}

// Handle selecting a customer from search results
if (isset($_GET['select_customer']) && !empty($_GET['select_customer'])) {
    $customer_id = mysqli_real_escape_string($conn, $_GET['select_customer']);
    
    // Get customer name for display
    $name_query = "SELECT first_name, last_name FROM customers WHERE customer_id = '$customer_id'";
    $name_result = mysqli_query($conn, $name_query);
    
    if ($customer = mysqli_fetch_assoc($name_result)) {
        // Redirect to step 2 (order type selection)
        header("Location: new_orders.php?step=2&customer_id=$customer_id");
        exit();
    } else {
        $error_message = "Customer not found.";
    }
}

// Handle order type selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_order_type'])) {
    $customer_id = mysqli_real_escape_string($conn, $_POST['customer_id']);
    $order_type = mysqli_real_escape_string($conn, $_POST['order_type']);
    
    // Generate a unique order ID
    function generateOrderId($conn) {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $order_id = '';
            for ($i = 0; $i < 5; $i++) {
                $order_id .= $characters[rand(0, strlen($characters) - 1)];
            }
            
            // Check if this order ID already exists
            $check_query = "SELECT 1 FROM orders WHERE order_id = '$order_id'";
            $check_result = mysqli_query($conn, $check_query);
        } while (mysqli_num_rows($check_result) > 0);
        
        return $order_id;
    }

    $order_id = generateOrderId($conn);
    
    // Create initial order record
    $insert_query = "INSERT INTO orders (order_id, customer_id, order_type, order_status, total_amount, created_at) 
                    VALUES ('$order_id', '$customer_id', '$order_type', 'approved', 0.00, NOW())";
    
    if (mysqli_query($conn, $insert_query)) {
        // Redirect to step 3 (order details)
        header("Location: new_orders.php?step=3&customer_id=$customer_id&order_id=$order_id&order_type=$order_type");
        exit();
    } else {
        $error_message = "Error creating order: " . mysqli_error($conn);
    }
}

// Handle sublimation order details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sublimation'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $customer_id = mysqli_real_escape_string($conn, $_POST['customer_id']);
    $print_type = mysqli_real_escape_string($conn, $_POST['print_type']);
    $size = mysqli_real_escape_string($conn, $_POST['size']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $quantity = (int)$_POST['quantity'];
    $total_amount = (float)$_POST['total_amount'];
    $instructions = mysqli_real_escape_string($conn, $_POST['instructions'] ?? '');
    $completion_date = mysqli_real_escape_string($conn, $_POST['completion_date']);
    
    // Upload design file if provided
    $design_file = "";
    if (isset($_FILES['design_file']) && $_FILES['design_file']['error'] == 0) {
        $upload_dir = "../uploads/designs/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = $order_id . '_' . basename($_FILES['design_file']['name']);
        $target_file = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['design_file']['tmp_name'], $target_file)) {
            $design_file = $filename;
        }
    }
    
    // Update the order with sublimation details
    $update_query = "UPDATE orders SET 
                    printing_type = '$print_type',
                    size = '$size',
                    color = '$color',
                    quantity = $quantity,
                    total_amount = $total_amount,
                    instructions = '$instructions',
                    completion_date = '$completion_date',
                    design_file = '$design_file',
                    updated_at = NOW()
                    WHERE order_id = '$order_id'";
    
    if (mysqli_query($conn, $update_query)) {
        // Redirect to step 4 (payment)
        header("Location: new_orders.php?step=4&customer_id=$customer_id&order_id=$order_id&order_type=sublimation");
        exit();
    } else {
        $error_message = "Error saving sublimation details: " . mysqli_error($conn);
    }
}

// Handle tailoring order details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tailoring'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $customer_id = mysqli_real_escape_string($conn, $_POST['customer_id']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $completion_date = mysqli_real_escape_string($conn, $_POST['completion_date']);
    $needs_seamstress = isset($_POST['needs_seamstress']) ? 1 : 0;
    
    // For price and downpayment data to update in orders table
    $price = (float)$_POST['price'];
    $downpayment = (float)$_POST['downpayment'];
    $quantity = (int)$_POST['quantity'];
    $instructions = mysqli_real_escape_string($conn, $_POST['special_instructions'] ?? '');
    
    // Handle seamstress appointment if needed
    $seamstress_appointment = null;
    if ($needs_seamstress && !empty($_POST['appointment_date']) && !empty($_POST['appointment_time'])) {
        $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
        $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
        $seamstress_appointment = $appointment_date . ' ' . $appointment_time;
    }
    
    // Start a transaction to ensure data consistency
    mysqli_begin_transaction($conn);
    
    try {
        // Update the main orders table first
        $update_order_query = "UPDATE orders SET 
                              quantity = $quantity,
                              total_amount = $price,
                              downpayment_amount = $downpayment,
                              instructions = '$instructions',
                              completion_date = '$completion_date',
                              updated_at = NOW()
                              WHERE order_id = '$order_id'";
        
        if (!mysqli_query($conn, $update_order_query)) {
            throw new Exception("Error updating order: " . mysqli_error($conn));
        }
        
        // Insert into tailoring_orders table
        $insert_tailoring_query = "INSERT INTO tailoring_orders (
                                  order_id,
                                  service_type,
                                  completion_date,
                                  needs_seamstress,
                                  seamstress_appointment
                                ) VALUES (
                                  '$order_id',
                                  '$service_type',
                                  '$completion_date',
                                  $needs_seamstress,
                                  " . ($seamstress_appointment ? "'$seamstress_appointment'" : "NULL") . "
                                )";
        
        if (!mysqli_query($conn, $insert_tailoring_query)) {
            throw new Exception("Error creating tailoring order: " . mysqli_error($conn));
        }
        
        // Based on service type, insert into the appropriate specialized table
        if ($service_type == 'custom made') {
            $design_details = mysqli_real_escape_string($conn, $_POST['design_details'] ?? '');
            $fabric_type = mysqli_real_escape_string($conn, $_POST['fabric_type'] ?? '');
            $special_instructions = mysqli_real_escape_string($conn, $_POST['special_instructions'] ?? '');
            
            // Handle file uploads for custom made orders
            $body_measurement_file = "";
            if (isset($_FILES['measurement_file']) && $_FILES['measurement_file']['error'] == 0) {
                $upload_dir = "../uploads/measurements/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $filename = $order_id . '_measurements_' . basename($_FILES['measurement_file']['name']);
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['measurement_file']['tmp_name'], $target_file)) {
                    $body_measurement_file = $filename;
                }
            }
            
            // Handle reference image upload
            $reference_image = "";
            if (isset($_FILES['reference_image']) && $_FILES['reference_image']['error'] == 0) {
                $upload_dir = "../uploads/references/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $filename = $order_id . '_reference_' . basename($_FILES['reference_image']['name']);
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['reference_image']['tmp_name'], $target_file)) {
                    $reference_image = $filename;
                }
            }
            
            // Insert into custom_made table
            $insert_custom_query = "INSERT INTO custom_made (
                                  order_id,
                                  design_details,
                                  body_measurement_file,
                                  fabric_type,
                                  quantity,
                                  reference_image,
                                  special_instructions
                                ) VALUES (
                                  '$order_id',
                                  '$design_details',
                                  '$body_measurement_file',
                                  '$fabric_type',
                                  $quantity,
                                  '$reference_image',
                                  '$special_instructions'
                                )";
            
            if (!mysqli_query($conn, $insert_custom_query)) {
                throw new Exception("Error creating custom order details: " . mysqli_error($conn));
            }
            
        } elseif ($service_type == 'alterations' || $service_type == 'resize' || $service_type == 'repairs') {
            $alteration_type = mysqli_real_escape_string($conn, $_POST['alteration_type'] ?? '');
            
            // Determine if measurements were uploaded or manually entered
            $measurement_method = 'manual';
            $measurements = null;
            $measurement_file = null;
            
            if (isset($_FILES['measurement_file']) && $_FILES['measurement_file']['error'] == 0) {
                // File was uploaded
                $measurement_method = 'upload';
                
                $upload_dir = "../uploads/measurements/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $filename = $order_id . '_measurements_' . basename($_FILES['measurement_file']['name']);
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['measurement_file']['tmp_name'], $target_file)) {
                    $measurement_file = $filename;
                }
            } elseif (isset($_POST['measurements']) && is_array($_POST['measurements'])) {
                // Manual measurements were entered
                $measurements_array = [];
                foreach ($_POST['measurements'] as $key => $value) {
                    if (!empty($value)) {
                        $measurements_array[$key] = floatval($value);
                    }
                }
                $measurements = json_encode($measurements_array);
            }
            
            // Insert into alterations table
            $insert_alteration_query = "INSERT INTO alterations (
                                      order_id,
                                      alteration_type,
                                      measurement_method,
                                      measurements,
                                      measurement_file,
                                      instructions
                                    ) VALUES (
                                      '$order_id',
                                      '$alteration_type',
                                      '$measurement_method',
                                      " . ($measurements ? "'$measurements'" : "NULL") . ",
                                      " . ($measurement_file ? "'$measurement_file'" : "NULL") . ",
                                      '$instructions'
                                    )";
            
            if (!mysqli_query($conn, $insert_alteration_query)) {
                throw new Exception("Error creating alteration details: " . mysqli_error($conn));
            }
        }
        
        // If everything successful, commit transaction
        mysqli_commit($conn);
        
        // Redirect to step 4 (payment)
        header("Location: new_orders.php?step=4&customer_id=$customer_id&order_id=$order_id&order_type=tailoring");
        exit();
        
    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        mysqli_rollback($conn);
        $error_message = $e->getMessage();
    }
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $customer_id = mysqli_real_escape_string($conn, $_POST['customer_id']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $amount_paid = (float)$_POST['amount_paid'];
    $reference = mysqli_real_escape_string($conn, $_POST['reference_number'] ?? '');
    $received_by = $_SESSION['user_id'];
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get order details to calculate payment status
        $order_query = "SELECT total_amount FROM orders WHERE order_id = '$order_id'";
        $order_result = mysqli_query($conn, $order_query);
        $order = mysqli_fetch_assoc($order_result);
        
        // Insert payment record
        $payment_query = "INSERT INTO payments (order_id, amount, payment_type, payment_method, transaction_reference, received_by, created_at) 
                         VALUES ('$order_id', $amount_paid, 'downpayment', '$payment_method', '$reference', $received_by, NOW())";
        
        if (!mysqli_query($conn, $payment_query)) {
            throw new Exception("Error creating payment record");
        }
        
        // Determine payment status
        $payment_status = ($amount_paid >= $order['total_amount']) ? 'fully_paid' : 'downpayment_paid';
        
        // Update order status
        $update_query = "UPDATE orders SET 
                        payment_status = '$payment_status',
                        order_status = 'in_process',
                        downpayment_amount = $amount_paid,
                        updated_at = NOW()
                        WHERE order_id = '$order_id'";
        
        if (!mysqli_query($conn, $update_query)) {
            throw new Exception("Error updating order status");
        }
        
        // Create notification
        $notification_msg = "New walk-in payment for Order #$order_id";
        $notify_query = "INSERT INTO notifications (customer_id, order_id, title, message, created_at) 
                        VALUES ($customer_id, '$order_id', 'Payment Processed', '$notification_msg', NOW())";
        
        if (!mysqli_query($conn, $notify_query)) {
            throw new Exception("Error creating notification");
        }
        
        // Log activity
        $activity = "Processed $payment_method payment of ₱$amount_paid for Order #$order_id";
        $log_query = "INSERT INTO activity_logs (user_id, activity, timestamp) 
                     VALUES ('{$_SESSION['user_id']}', '$activity', NOW())";
        
        if (!mysqli_query($conn, $log_query)) {
            throw new Exception("Error logging activity");
        }
        
        // If everything is successful, commit the transaction
        mysqli_commit($conn);
        $success_message = "Payment processed successfully!";
        
        // Redirect to confirmation step
        header("Location: new_orders.php?step=5&customer_id=$customer_id&order_id=$order_id");
        exit();
    
    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        mysqli_rollback($conn);
        $error_message = $e->getMessage();
    }
}

// Get customer details if customer_id is set
$customer_name = "";
if ($customer_id) {
    $customer_query = "SELECT CONCAT(first_name, ' ', last_name) AS customer_name FROM customers WHERE customer_id = '$customer_id'";
    $customer_result = mysqli_query($conn, $customer_query);
    if ($customer_data = mysqli_fetch_assoc($customer_result)) {
        $customer_name = $customer_data['customer_name'];
    }
}

// Get order details if order_id is set
$order_details = [];
if ($order_id) {
    $order_query = "SELECT * FROM orders WHERE order_id = '$order_id'";
    $order_result = mysqli_query($conn, $order_query);
    if ($order_data = mysqli_fetch_assoc($order_result)) {
        $order_details = $order_data;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="../image/logo.png">
    <title>JXT Admin - Walk-in Order</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .content-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 25px;
        }

        .card-header-custom {
            background-color: #D98324;
            color: white;
            border-radius: 8px 8px 0 0;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .search-container {
            margin-bottom: 20px;
        }

        .customer-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .customer-card:hover {
            border-color: #D98324;
            background-color: rgba(217, 131, 36, 0.05);
            transform: translateY(-2px);
        }

        .customer-details {
            margin-bottom: 10px;
        }

        .btn-primary-custom {
            background-color: #D98324;
            border-color: #D98324;
            color: white;
        }

        .btn-primary-custom:hover {
            background-color: #c27420;
            border-color: #c27420;
            color: white;
        }

        .or-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 30px 0;
        }

        .or-divider::before,
        .or-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }

        .or-divider-text {
            padding: 0 10px;
            color: #6c757d;
            font-weight: 600;
        }

        .select-customer-btn {
            padding: 6px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .select-customer-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .back-btn {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .back-btn:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }

        /* Process steps */
        .step-nav {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .step-nav::before {
            content: "";
            position: absolute;
            top: 15px;
            left: 50px;
            right: 50px;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .step-item {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #fff;
            border: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 5px;
            font-weight: 600;
            color: #6c757d;
        }

        .step-title {
            font-size: 0.8rem;
            color: #6c757d;
            white-space: nowrap;
        }

        .step-item.active .step-number {
            background-color: #D98324;
            border-color: #D98324;
            color: white;
        }

        .step-item.active .step-title {
            color: #D98324;
            font-weight: 600;
        }

        .step-item.complete .step-number {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }

        .step-item.complete .step-title {
            color: #28a745;
        }

        /* Alert styling */
        .alert-custom {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .alert-success-custom {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger-custom {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        /* Payment method styles */
        .payment-method-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .payment-method-card:hover {
            border-color: #D98324;
            background-color: #fff8f0;
        }

        .payment-method-card.selected {
            border-color: #D98324;
            background-color: #fff8f0;
        }

        /* Order details summary */
        .order-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 600;
            color: #495057;
        }

        .summary-value {
            color: #212529;
        }

        .total-row {
            font-size: 1.1rem;
            color: #D98324;
            font-weight: 600;
        }

        /* Confirmation page */
        .confirmation-section {
            text-align: center;
            padding: 30px;
        }

        .confirmation-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .receipt-section {
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 30px;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 1px dashed #dee2e6;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">
                        <?php include 'notification.php'; ?>

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                                </span>
                                <i class='fas fa-user-circle' style="font-size:20px;"></i>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                            <i class="fas fa-user-plus mr-2"></i> Walk-in Customer Order
                        </h1>
                    </div>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger-custom">
                            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success-custom">
                            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success_message) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Process Steps -->
                    <div class="step-nav">
                        <div class="step-item <?= ($step >= 1) ? 'complete' : '' ?> <?= ($step == 1) ? 'active' : '' ?>">
                            <div class="step-number">1</div>
                            <div class="step-title">Customer</div>
                        </div>
                        <div class="step-item <?= ($step >= 2) ? 'complete' : '' ?> <?= ($step == 2) ? 'active' : '' ?>">
                            <div class="step-number">2</div>
                            <div class="step-title">Order Type</div>
                        </div>
                        <div class="step-item <?= ($step >= 3) ? 'complete' : '' ?> <?= ($step == 3) ? 'active' : '' ?>">
                            <div class="step-number">3</div>
                            <div class="step-title">Order Details</div>
                        </div>
                        <div class="step-item <?= ($step >= 4) ? 'complete' : '' ?> <?= ($step == 4) ? 'active' : '' ?>">
                            <div class="step-number">4</div>
                            <div class="step-title">Payment</div>
                        </div>
                        <div class="step-item <?= ($step >= 5) ? 'complete' : '' ?> <?= ($step == 5) ? 'active' : '' ?>">
                            <div class="step-number">5</div>
                            <div class="step-title">Confirmation</div>
                        </div>
                    </div>

                    <?php if ($step == 1): // Step 1: Select or Create Customer ?>
                    <div class="content-card">
                        <div class="card-header-custom">
                            <i class="fas fa-search mr-2"></i> Find Existing Customer
                        </div>
                        <div class="card-body">
                            <form action="" method="GET" class="search-container">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search by name, email or phone number" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                    <button class="btn btn-primary-custom" type="submit">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </form>

                            <?php if ($search_performed): ?>
                                <?php if (mysqli_num_rows($search_result) > 0): ?>
                                    <div class="search-results">
                                        <h5 class="mb-3">Search Results:</h5>
                                        <?php while ($customer = mysqli_fetch_assoc($search_result)): ?>
                                            <div class="customer-card">
                                                <div class="row">
                                                    <div class="col-md-9">
                                                        <div class="customer-details">
                                                            <h6><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></h6>
                                                            <small class="text-muted">
                                                                <i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($customer['email']) ?><br>
                                                                <i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($customer['phone_number']) ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                                                        <a href="?select_customer=<?= $customer['customer_id'] ?>" class="select-customer-btn">
                                                            <i class="fas fa-check mr-1"></i> Select
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle mr-2"></i> No customers found matching your search. Please create a new customer below.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="or-divider">
                        <span class="or-divider-text">OR</span>
                    </div>

                    <div class="content-card">
                        <div class="card-header-custom">
                            <i class="fas fa-user-plus mr-2"></i> Create New Walk-in Customer
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                        <div class="form-text">Optional, but recommended for order updates</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                </div>

                                <div class="mt-4 d-flex justify-content-between">
                                    <a href="dashboard.php" class="btn back-btn">
                                        <i class="fas fa-arrow-left mr-1"></i> Back
                                    </a>
                                    <button type="submit" name="create_customer" class="btn btn-primary-custom">
                                        <i class="fas fa-plus-circle mr-1"></i> Create Customer & Continue
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($step == 2): // Step 2: Select Order Type ?>
                    <div class="content-card">
                        <div class="card-header-custom">
                            <i class="fas fa-list-alt mr-2"></i> Select Order Type
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer_id) ?>">
                                <div class="mb-3">
                                    <label for="order_type" class="form-label">Order Type *</label>
                                    <select class="form-select" id="order_type" name="order_type" required>
                                        <option value="">Select Order Type</option>
                                        <option value="sublimation">Sublimation</option>
                                        <option value="tailoring">Tailoring</option>
                                    </select>
                                </div>
                                <div class="mt-4 d-flex justify-content-between">
                                    <a href="new_orders.php?step=1" class="btn back-btn">
                                        <i class="fas fa-arrow-left mr-1"></i> Back
                                    </a>
                                    <button type="submit" name="select_order_type" class="btn btn-primary-custom">
                                        <i class="fas fa-arrow-right mr-1"></i> Continue
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($step == 3 && $order_type == 'sublimation'): // Step 3: Sublimation Order Details ?>
                    <div class="content-card">
                        <div class="card-header-custom">
                            <i class="fas fa-tshirt mr-2"></i> Sublimation Order Details
                        </div>
                        <div class="card-body">
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer_id) ?>">
                                <div class="mb-4">
                                    <label for="print_type" class="form-label">Select Template *</label>
                                    <select class="form-select" id="print_type" name="print_type" required onchange="showTemplatePreview('sublimation', this.value)">
                                        <option value="">Select a template</option>
                                        <option value="t-shirt-basic" data-img="../assets/templates/sublimation/t-shirt-basic.jpg">T-Shirt (Basic Design)</option>
                                        <option value="t-shirt-full" data-img="../assets/templates/sublimation/t-shirt-full.jpg">T-Shirt (Full Print)</option>
                                        <option value="mug-standard" data-img="../assets/templates/sublimation/mug-standard.jpg">Coffee Mug (Standard)</option>
                                        <option value="mug-magic" data-img="../assets/templates/sublimation/mug-magic.jpg">Coffee Mug (Heat Sensitive)</option>
                                        <option value="cap-print" data-img="../assets/templates/sublimation/cap-print.jpg">Cap/Hat Print</option>
                                        <option value="tote-bag" data-img="../assets/templates/sublimation/tote-bag.jpg">Tote Bag</option>
                                        <option value="custom" data-img="../assets/templates/sublimation/custom.jpg">Custom Design</option>
                                    </select>
                                    
                                    <div id="template-preview-sublimation" class="mt-3 template-preview">
                                        <p class="text-muted small">Select a template to see preview</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="size" class="form-label">Size *</label>
                                        <input type="text" class="form-control" id="size" name="size" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="color" class="form-label">Color *</label>
                                        <input type="text" class="form-control" id="color" name="color" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="quantity" class="form-label">Quantity *</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="total_amount" class="form-label">Total Amount *</label>
                                        <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="instructions" class="form-label">Special Instructions</label>
                                    <textarea class="form-control" id="instructions" name="instructions" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="completion_date" class="form-label">Completion Date *</label>
                                    <input type="date" class="form-control" id="completion_date" name="completion_date" required>
                                </div>
                                <div class="mb-3">
                                    <label for="design_file" class="form-label">Upload Design File</label>
                                    <input type="file" class="form-control" id="design_file" name="design_file">
                                </div>
                                <div class="mt-4 d-flex justify-content-between">
                                    <a href="new_orders.php?step=2&customer_id=<?= htmlspecialchars($customer_id) ?>" class="btn back-btn">
                                        <i class="fas fa-arrow-left mr-1"></i> Back
                                    </a>
                                    <button type="submit" name="save_sublimation" class="btn btn-primary-custom">
                                        <i class="fas fa-save mr-1"></i> Save & Continue
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($step == 3 && $order_type == 'tailoring'): // Step 3: Tailoring Order Details ?>
                    <div class="content-card">
                        <div class="card-header-custom">
                            <i class="fas fa-cut mr-2"></i> Tailoring Order Details
                        </div>
                        <div class="card-body">
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer_id) ?>">
                                
                                <div class="mb-4">
                                    <label for="service_type" class="form-label">Service Type *</label>
                                    <select class="form-control" id="service_type" name="service_type" required onchange="toggleServiceFields(this.value)">
                                        <option value="">Select Service Type</option>
                                        <option value="alterations">Alterations</option>
                                        <option value="repairs">Repairs</option>
                                        <option value="resize">Resize</option>
                                        <option value="custom made">Custom Made</option>
                                    </select>
                                </div>
                                
                                <!-- Fields that appear for Alterations, Repairs, or Resize -->
                                <div id="alteration-fields" style="display: none;">
                                    <div class="mb-4">
                                        <label for="alteration_type" class="form-label">Alteration Type *</label>
                                        <select class="form-control" id="alteration_type" name="alteration_type">
                                            <option value="">Select Alteration Type</option>
                                            <option value="hem">Hem Adjustment</option>
                                            <option value="shorten">Shorten</option>
                                            <option value="lengthen">Lengthen</option>
                                            <option value="waist_adjustment">Waist Adjustment</option>
                                            <option value="zipper_repair">Zipper Repair</option>
                                            <option value="patch">Patch</option>
                                            <option value="resize_larger">Resize - Larger</option>
                                            <option value="resize_smaller">Resize - Smaller</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Common fields for all tailoring orders -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="completion_date" class="form-label">Required Completion Date *</label>
                                        <input type="date" class="form-control" id="completion_date" name="completion_date" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="quantity" class="form-label">Quantity *</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                                    </div>
                                </div>
                                
                                <!-- Custom Made specific fields -->
                                <div id="custom-made-fields" style="display: none;">
                                    <div class="mb-4">
                                        <label for="fabric_type" class="form-label">Fabric Type</label>
                                        <select class="form-control" id="fabric_type" name="fabric_type">
                                            <option value="">Select Fabric</option>
                                            <option value="cotton">Cotton</option>
                                            <option value="polyester">Polyester</option>
                                            <option value="silk">Silk</option>
                                            <option value="wool">Wool</option>
                                            <option value="linen">Linen</option>
                                            <option value="denim">Denim</option>
                                            <option value="blend">Blend</option>
                                            <option value="customer_supplied">Customer Supplied</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="design_details" class="form-label">Design Details</label>
                                        <textarea class="form-control" id="design_details" name="design_details" rows="3" placeholder="Describe the design details, style preferences, etc."></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reference_image" class="form-label">Reference Image (optional)</label>
                                        <input type="file" class="form-control" id="reference_image" name="reference_image" accept="image/*">
                                        <div class="form-text">Upload a reference image for the design (JPG, JPEG, PNG)</div>
                                    </div>
                                </div>
                                
                                <!-- Measurements section - common but with different fields shown based on service type -->
                                <div class="mb-3">
                                    <label class="form-label">Measurements</label>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="measurement_method" id="manual_measurements" value="manual" checked onchange="toggleMeasurementMethod(this.value)">
                                                    <label class="form-check-label" for="manual_measurements">Enter Measurements</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="measurement_method" id="upload_measurements" value="upload" onchange="toggleMeasurementMethod(this.value)">
                                                <label class="form-check-label" for="upload_measurements">Upload Measurements</label>
                                            </div>
                                        </div>
                                        
                                        <div id="manual-measurement-fields">
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <label for="chest" class="form-label">Chest (inches)</label>
                                                    <input type="number" class="form-control" id="chest" name="measurements[chest]" step="0.1">
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <label for="waist" class="form-label">Waist (inches)</label>
                                                    <input type="number" class="form-control" id="waist" name="measurements[waist]" step="0.1">
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <label for="hip" class="form-label">Hip (inches)</label>
                                                    <input type="number" class="form-control" id="hip" name="measurements[hip]" step="0.1">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <label for="shoulder" class="form-label">Shoulder (inches)</label>
                                                    <input type="number" class="form-control" id="shoulder" name="measurements[shoulder]" step="0.1">
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <label for="sleeve" class="form-label">Sleeve Length (inches)</label>
                                                    <input type="number" class="form-control" id="sleeve" name="measurements[sleeve]" step="0.1">
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <label for="inseam" class="form-label">Inseam (inches)</label>
                                                    <input type="number" class="form-control" id="inseam" name="measurements[inseam]" step="0.1">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <label for="neck" class="form-label">Neck (inches)</label>
                                                    <input type="number" class="form-control" id="neck" name="measurements[neck]" step="0.1">
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <label for="length" class="form-label">Length (inches)</label>
                                                    <input type="number" class="form-control" id="length" name="measurements[length]" step="0.1">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div id="upload-measurement-fields" style="display: none;">
                                            <div class="mb-2">
                                                <label for="measurement_file" class="form-label">Upload Measurement Sheet *</label>
                                                <input type="file" class="form-control" id="measurement_file" name="measurement_file" accept=".pdf,.jpg,.jpeg,.png">
                                                <div class="form-text">Upload a measurement sheet (PDF, JPG, JPEG, PNG)</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="special_instructions" class="form-label">Special Instructions</label>
                                    <textarea class="form-control" id="special_instructions" name="special_instructions" rows="2" placeholder="Any special instructions or requirements"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="needs_seamstress" name="needs_seamstress" onchange="toggleSeamstressFields(this.checked)">
                                        <label class="form-check-label" for="needs_seamstress">
                                            Requires Seamstress Appointment
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="seamstress-fields" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="appointment_date" class="form-label">Appointment Date</label>
                                            <input type="date" class="form-control" id="appointment_date" name="appointment_date">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="appointment_time" class="form-label">Appointment Time</label>
                                            <input type="time" class="form-control" id="appointment_time" name="appointment_time">
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="price" class="form-label">Price (₱) *</label>
                                        <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="downpayment" class="form-label">Downpayment Required (₱) *</label>
                                        <input type="number" class="form-control" id="downpayment" name="downpayment" min="0" step="0.01" required>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="new_orders.php?step=2&customer_id=<?= htmlspecialchars($customer_id) ?>" class="btn back-btn">
                                        <i class="fas fa-arrow-left mr-1"></i> Back
                                    </a>
                                    <button type="submit" name="save_tailoring" class="btn btn-primary-custom">
                                        <i class="fas fa-save me-2"></i> Save Tailoring Order
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($step == 4): // Step 4: Payment ?>
                    <div class="content-card">
                        <div class="card-header-custom">
                            <i class="fas fa-credit-card mr-2"></i> Payment
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer_id) ?>">
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method *</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="">Select Payment Method</option>
                                        <option value="cash">Cash</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="gcash">GCash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="amount_paid" class="form-label">Amount Paid *</label>
                                    <input type="number" step="0.01" class="form-control" id="amount_paid" name="amount_paid" required>
                                </div>
                                <div class="mb-3">
                                    <label for="reference_number" class="form-label">Reference Number</label>
                                    <input type="text" class="form-control" id="reference_number" name="reference_number">
                                </div>
                                <div class="mt-4 d-flex justify-content-between">
                                    <a href="new_orders.php?step=3&customer_id=<?= htmlspecialchars($customer_id) ?>&order_id=<?= htmlspecialchars($order_id) ?>&order_type=<?= htmlspecialchars($order_type) ?>" class="btn back-btn">
                                        <i class="fas fa-arrow-left mr-1"></i> Back
                                    </a>
                                    <button type="submit" name="process_payment" class="btn btn-primary-custom">
                                        <i class="fas fa-check-circle mr-1"></i> Process Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($step == 5): // Step 5: Confirmation ?>
                    <div class="content-card">
                        <div class="card-header-custom">
                            <i class="fas fa-check-circle mr-2"></i> Confirmation
                        </div>
                        <div class="card-body">
                            <div class="confirmation-section">
                                <div class="confirmation-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h4>Order Confirmed!</h4>
                                <p>Your order has been successfully processed.</p>
                                <div class="receipt-section">
                                    <div class="receipt-header">
                                        <h5>Order Receipt</h5>
                                    </div>
                                    <div class="order-summary">
                                        <div class="summary-row">
                                            <div class="summary-label">Order ID:</div>
                                            <div class="summary-value"><?= htmlspecialchars($order_id) ?></div>
                                        </div>
                                        <div class="summary-row">
                                            <div class="summary-label">Customer:</div>
                                            <div class="summary-value"><?= htmlspecialchars($customer_name) ?></div>
                                        </div>
                                        <div class="summary-row">
                                            <div class="summary-label">Order Type:</div>
                                            <div class="summary-value"><?= htmlspecialchars($order_type) ?></div>
                                        </div>
                                        <div class="summary-row">
                                            <div class="summary-label">Total Amount:</div>
                                            <div class="summary-value">₱<?= number_format($order_details['total_amount'], 2) ?></div>
                                        </div>
                                        <div class="summary-row">
                                            <div class="summary-label">Amount Paid:</div>
                                            <div class="summary-value">₱<?= number_format($order_details['downpayment_amount'], 2) ?></div>
                                        </div>
                                        <div class="summary-row">
                                            <div class="summary-label">Payment Status:</div>
                                            <div class="summary-value"><?= htmlspecialchars($order_details['payment_status']) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="dashboard.php" class="btn btn-primary-custom">
                                        <i class="fas fa-home mr-1"></i> Back to Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Footer -->
                <?php include 'footer.php'; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS and jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Set minimum date for completion date (today + 3 days)
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            today.setDate(today.getDate() + 3); // Minimum 3 days from now
            
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            
            const minDate = `${yyyy}-${mm}-${dd}`;
            
            // Set minimum date for completion date and appointment date
            document.getElementById('completion_date').setAttribute('min', minDate);
            if (document.getElementById('appointment_date')) {
                document.getElementById('appointment_date').setAttribute('min', minDate);
            }
            
            // Set default completion date to 7 days from now
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 7);
            const defYyyy = defaultDate.getFullYear();
            const defMm = String(defaultDate.getMonth() + 1).padStart(2, '0');
            const defDd = String(defaultDate.getDate()).padStart(2, '0');
            document.getElementById('completion_date').value = `${defYyyy}-${defMm}-${defDd}`;
            
            // Calculate downpayment as 50% of price
            document.getElementById('price').addEventListener('input', function() {
                const price = parseFloat(this.value) || 0;
                document.getElementById('downpayment').value = (price * 0.5).toFixed(2);
            });
        });
        
        // Toggle fields based on service type
        function toggleServiceFields(serviceType) {
            const alterationFields = document.getElementById('alteration-fields');
            const customMadeFields = document.getElementById('custom-made-fields');
            
            if (serviceType === 'custom made') {
                alterationFields.style.display = 'none';
                customMadeFields.style.display = 'block';
            } else if (serviceType === 'alterations' || serviceType === 'repairs' || serviceType === 'resize') {
                alterationFields.style.display = 'block';
                customMadeFields.style.display = 'none';
            } else {
                alterationFields.style.display = 'none';
                customMadeFields.style.display = 'none';
            }
        }
        
        // Toggle measurement method
        function toggleMeasurementMethod(method) {
            const manualFields = document.getElementById('manual-measurement-fields');
            const uploadFields = document.getElementById('upload-measurement-fields');
            
            if (method === 'manual') {
                manualFields.style.display = 'block';
                uploadFields.style.display = 'none';
            } else {
                manualFields.style.display = 'none';
                uploadFields.style.display = 'block';
            }
        }
        
        // Toggle seamstress appointment fields
        function toggleSeamstressFields(needsSeamstress) {
            const seamstressFields = document.getElementById('seamstress-fields');
            
            if (needsSeamstress) {
                seamstressFields.style.display = 'block';
            } else {
                seamstressFields.style.display = 'none';
            }
        }
    </script>
</body>
</html>