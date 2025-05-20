<?php
// Include database connection
include('../db.php');
session_start();

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables for form persistence on error
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
$alteration_type = $instructions = $measurements = '';
$measurement_method = 'upload';
$quantity = 1;
$needs_seamstress = 0;
$errors = [];
$success = false;

// Get customer details from session
$customer_id = $_SESSION['customer_id'];
$query = "SELECT first_name, last_name FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$customer_name = $customer ? $customer['first_name'] . ' ' . $customer['last_name'] : 'Guest';

// Redirect if order_id is not provided
if (empty($order_id)) {
    header("Location: select_service.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form inputs
    $order_id = $_POST['order_id'];
    $alteration_type = $_POST['alteration_type'];
    $measurement_method = $_POST['measurement_method'];
    $measurements = isset($_POST['measurements']) ? $_POST['measurements'] : '';
    $instructions = isset($_POST['instructions']) ? $_POST['instructions'] : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $needs_seamstress = isset($_POST['needs_seamstress']) ? 1 : 0;
    $completion_date = isset($_POST['completion_date']) ? $_POST['completion_date'] : '';
    $seamstress_appointment = ($needs_seamstress && isset($_POST['seamstress_appointment'])) ? $_POST['seamstress_appointment'] : NULL;
    
    // Calculate fee - 150 pesos per clothing item
    $fee_per_item = 150.00;
    $total_amount = $fee_per_item * $quantity;
    $downpayment_amount = $total_amount * 0.5; // 50% downpayment
    
    // Validation
    if (empty($order_id)) $errors[] = "Order ID is required";
    if (empty($alteration_type)) $errors[] = "Alteration type is required";
    if (empty($completion_date)) $errors[] = "Completion date is required";
    
    // Add quantity validation
    if ($quantity < 1) {
        $errors[] = "Quantity must be at least 1";
    }
    if ($quantity > 100) {
        $errors[] = "Quantity cannot exceed 100";
    }
    
    if ($measurement_method == 'manual' && empty($measurements)) {
        $errors[] = "Measurements are required when selecting manual entry";
    }
    
    // Handle file upload if provided
    $measurements_file = "";
    if ($measurement_method == 'upload' && isset($_FILES['measurement_file']) && $_FILES['measurement_file']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['measurement_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "File format not allowed. Please upload JPG, JPEG, PNG or PDF files only.";
        } else {
            $upload_dir = "../uploads/measurements/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = $order_id . '_measurements_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['measurement_file']['tmp_name'], $destination)) {
                $measurements_file = $destination;
            } else {
                $errors[] = "Failed to upload the file. Please try again.";
            }
        }
    } else if ($measurement_method == 'upload') {
        $errors[] = "Please upload a measurement file";
    }

    // If no errors, insert into database
    if (empty($errors)) {
        // First create an order record if it doesn't exist
        $order_check = "SELECT * FROM orders WHERE order_id = ?";
        $check_stmt = $conn->prepare($order_check);
        $check_stmt->bind_param("s", $order_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            $order_sql = "INSERT INTO orders (order_id, customer_id, order_type, order_status, 
                          total_amount, downpayment_amount, payment_method, payment_status) 
                          VALUES (?, ?, 'tailoring', 'pending_approval', ?, ?, 'cash', 'pending')";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param("sidd", $order_id, $customer_id, $total_amount, $downpayment_amount);
            
            if (!$order_stmt->execute()) {
                $errors[] = "Error creating order: " . $order_stmt->error;
            }
        }
        
        // If no errors, continue with creating tailoring and alteration orders
        if (empty($errors)) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Create the tailoring order record
                $tailoring_sql = "INSERT INTO tailoring_orders 
                                (order_id, service_type, description, quantity, completion_date, 
                                needs_seamstress, seamstress_appointment) 
                                VALUES (?, 'alterations', ?, ?, ?, ?, ?)";
                
                $tailoring_stmt = $conn->prepare($tailoring_sql);
                $tailoring_stmt->bind_param(
                    "ssisis", 
                    $order_id, 
                    $instructions,
                    $quantity, 
                    $completion_date,
                    $needs_seamstress,
                    $seamstress_appointment
                );
                
                $tailoring_stmt->execute();
                
                // Create the alterations record
                $alterations_sql = "INSERT INTO alterations 
                                   (order_id, alteration_type, measurement_method, measurements, measurement_file, instructions) 
                                   VALUES (?, ?, ?, ?, ?, ?)";
                
                $alterations_stmt = $conn->prepare($alterations_sql);
                $alterations_stmt->bind_param(
                    "ssssss", 
                    $order_id, 
                    $alteration_type, 
                    $measurement_method, 
                    $measurements, 
                    $measurements_file, 
                    $instructions
                );
                
                $alterations_stmt->execute();
                
                // Create notification for the new order
                $notification_sql = "INSERT INTO notifications 
                                    (customer_id, order_id, title, message, is_read, created_at) 
                                    VALUES (?, ?, 'New Alteration Order', 
                                    'Your alteration order #".$order_id." has been received and is pending review.', 0, NOW())";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("is", $customer_id, $order_id);
                $notification_stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                $success = true;
                
                // Redirect after 3 seconds
                header("refresh:3;url=index.php");
            } catch (Exception $e) {
                // Roll back the transaction if something failed
                $conn->rollback();
                $errors[] = "Error creating order: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alteration Order | JX Tailoring</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom styles -->
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
            font-family: 'Poppins', sans-serif;
        }
        .order-form-container {
            max-width: 800px;
            margin: 40px auto;
        }
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
        }
        .card-header {
            background-color: #343a40;
            color: white;
            font-weight: 600;
            border-top-left-radius: 0.5rem !important;
            border-top-right-radius: 0.5rem !important;
        }
        .btn-primary {
            background-color: #ff7d00;
            border-color: #ff7d00;
        }
        .btn-primary:hover {
            background-color: #e06c00;
            border-color: #e06c00;
        }
        .form-label {
            font-weight: 500;
        }
        .form-text {
            font-size: 0.8rem;
        }
        .success-message {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .form-section-title {
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .alteration-type-card {
            cursor: pointer;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        .alteration-type-card:hover {
            border-color: #ff7d00;
            transform: translateY(-5px);
        }
        .alteration-type-card.selected {
            border-color: #ff7d00;
            background-color: #fff8f0;
        }
        .alteration-icon {
            font-size: 1.8rem;
            color: #ff7d00;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container order-form-container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h4 class="mb-0"><i class="fas fa-cut me-2"></i>Alteration Order</h4>
                <a href="select_service.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
            </div>
            <div class="card-body p-4">
                
                <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle me-2"></i> 
                    <strong>Thank you! Your alteration order has been successfully submitted.</strong>
                    <p class="mb-0 mt-2">Order ID: <strong><?php echo $order_id; ?></strong></p>
                    <p class="mb-0">You'll receive updates about your order status. Redirecting to home page...</p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong><i class="fas fa-exclamation-triangle me-2"></i>Please correct the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form action="alteration.php" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                    <!-- Order ID and Basic Information -->
                    <div class="form-section">
                        <h5 class="form-section-title"><i class="fas fa-info-circle me-2 text-primary"></i>Order Information</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="order_id" class="form-label">Order ID</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-hashtag"></i></span>
                                    <input type="text" class="form-control bg-light" id="order_id" value="<?php echo htmlspecialchars($order_id); ?>" readonly>
                                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                                </div>
                                <div class="form-text text-muted">Order ID is automatically assigned and cannot be changed.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="completion_date" class="form-label">Required Completion Date <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="date" class="form-control" id="completion_date" name="completion_date" 
                                        value="<?php echo htmlspecialchars($completion_date ?? ''); ?>" required 
                                        min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                                </div>
                                <div class="form-text">Please allow at least 3 days for processing.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alteration Type Selection -->
                    <div class="form-section">
                        <h5 class="form-section-title"><i class="fas fa-cut me-2 text-primary"></i>Alteration Type</h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="alteration-type-card" onclick="selectAlterationType('resize', this)">
                                    <div class="alteration-icon"><i class="fas fa-compress-arrows-alt"></i></div>
                                    <h6>Resize</h6>
                                    <p class="small text-muted mb-0">Adjust clothing size</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alteration-type-card" onclick="selectAlterationType('repair', this)">
                                    <div class="alteration-icon"><i class="fas fa-tools"></i></div>
                                    <h6>Repair</h6>
                                    <p class="small text-muted mb-0">Fix damaged clothing</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alteration-type-card" onclick="selectAlterationType('modify', this)">
                                    <div class="alteration-icon"><i class="fas fa-pen-fancy"></i></div>
                                    <h6>Modify</h6>
                                    <p class="small text-muted mb-0">Change style/design</p>
                                </div>
                            </div>
                            <input type="hidden" name="alteration_type" id="alteration_type" value="<?php echo htmlspecialchars($alteration_type); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Basic Details -->
                    <div class="form-section">
                        <h5 class="form-section-title"><i class="fas fa-list-ul me-2 text-primary"></i>Order Details</h5>
                          <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-sort-amount-up"></i></span>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" max="100" value="<?php echo $quantity; ?>" required onchange="calculatePrice()">
                            </div>
                        </div>
                        
                        <!-- Price Calculation Section -->
                        <div class="mb-3">
                            <label class="form-label">Price Calculation</label>
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Alteration Fee:</span>
                                        <span>₱150.00 per item</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Quantity:</span>
                                        <span id="quantity-display">1</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total Amount:</span>
                                        <span id="total-amount">₱150.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span>Required Downpayment (50%):</span>
                                        <span id="downpayment-amount">₱75.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="instructions" class="form-label">Instructions <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="instructions" name="instructions" rows="4" required><?php echo htmlspecialchars($instructions); ?></textarea>
                            <div class="form-text">
                                Please describe in detail what alterations you need. For example:
                                <ul>
                                    <li>For resize: "Reduce waist size from 34 to 32 inches, keep length the same"</li>
                                    <li>For repair: "Fix torn seam on left shoulder, replace missing button"</li>
                                    <li>For modify: "Shorten sleeves by 2 inches, change round neck to V-neck"</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Measurements Section -->
                    <div class="form-section">
                        <h5 class="form-section-title"><i class="fas fa-ruler me-2 text-primary"></i>Measurements</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Measurement Method <span class="text-danger">*</span></label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="measurement_method" id="upload_method" value="upload" <?php if($measurement_method != 'manual') echo 'checked'; ?> onchange="toggleMeasurementMethod()">
                                <label class="form-check-label" for="upload_method">
                                    Upload Measurement File
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="measurement_method" id="manual_method" value="manual" <?php if($measurement_method == 'manual') echo 'checked'; ?> onchange="toggleMeasurementMethod()">
                                <label class="form-check-label" for="manual_method">
                                    Enter Measurements Manually
                                </label>
                            </div>
                        </div>
                        
                        <div id="upload_section" class="mb-3" style="display: <?php echo ($measurement_method != 'manual') ? 'block' : 'none'; ?>">
                            <label for="measurement_file" class="form-label">Upload Measurements File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="measurement_file" name="measurement_file">
                            <div class="form-text">Upload a file with your measurements or a reference image. Accepted formats: JPG, JPEG, PNG, PDF.</div>
                        </div>
                        
                        <div id="manual_section" class="mb-3" style="display: <?php echo ($measurement_method == 'manual') ? 'block' : 'none'; ?>">
                            <label for="measurements" class="form-label">Manual Measurements <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="measurements" name="measurements" rows="4"><?php echo htmlspecialchars($measurements); ?></textarea>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i> Enter your measurements with proper units (inches/cm).
                                <br>Example: Chest: 40 inches, Waist: 32 inches, Hips: 42 inches, Inseam: 30 inches
                            </div>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="needs_seamstress" name="needs_seamstress" value="1" <?php if($needs_seamstress) echo 'checked'; ?> onchange="toggleSeamstressOptions()">
                            <label class="form-check-label" for="needs_seamstress">
                                I need an appointment with a seamstress for measurements
                            </label>
                            <div class="form-text">Check this if you'd like us to schedule an appointment for taking measurements.</div>
                        </div>

                        <!-- Add this new appointment date field -->
                        <div id="appointment_section" class="mb-3" style="display: <?php echo ($needs_seamstress) ? 'block' : 'none'; ?>">
                            <label for="seamstress_appointment" class="form-label">Select Appointment Date <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="date" class="form-control" id="seamstress_appointment" name="seamstress_appointment" 
                                    min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                                    <?php if($needs_seamstress) echo 'required'; ?>>
                            </div>
                            <div class="form-text">Please select a preferred date for your measurement appointment.</div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="select_service.php" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Submit Alteration Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap & JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
        
        // Alteration type selection
        function selectAlterationType(type, element) {
            // Clear previous selection
            document.querySelectorAll('.alteration-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Set new selection
            element.classList.add('selected');
            document.getElementById('alteration_type').value = type;
        }
        
        // Toggle measurement method
        function toggleMeasurementMethod() {
            const uploadMethod = document.getElementById('upload_method');
            const uploadSection = document.getElementById('upload_section');
            const manualSection = document.getElementById('manual_section');
            
            if (uploadMethod.checked) {
                uploadSection.style.display = 'block';
                manualSection.style.display = 'none';
            } else {
                uploadSection.style.display = 'none';
                manualSection.style.display = 'block';
            }
        }

        // Toggle seamstress options
        function toggleSeamstressOptions() {
            const needsSeamstress = document.getElementById('needs_seamstress');
            const appointmentSection = document.getElementById('appointment_section');
            const appointmentInput = document.getElementById('seamstress_appointment');
            
            if (needsSeamstress.checked) {
                appointmentSection.style.display = 'block';
                appointmentInput.setAttribute('required', '');
            } else {
                appointmentSection.style.display = 'none';
                appointmentInput.removeAttribute('required');
            }
        }
          // Price calculation function
        function calculatePrice() {
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            const feePerItem = 150;
            const totalAmount = quantity * feePerItem;
            const downpayment = totalAmount * 0.5;
            
            document.getElementById('quantity-display').textContent = quantity;
            document.getElementById('total-amount').textContent = '₱' + totalAmount.toFixed(2);
            document.getElementById('downpayment-amount').textContent = '₱' + downpayment.toFixed(2);
        }
        
        // Initialize form based on existing values
        window.addEventListener('DOMContentLoaded', function() {
            // Set initial alteration type if available
            const initialType = '<?php echo $alteration_type; ?>';
            if (initialType) {
                document.querySelectorAll('.alteration-type-card').forEach(card => {
                    const cardTitle = card.querySelector('h6').textContent.toLowerCase();
                    if (cardTitle === initialType.toLowerCase()) {
                        selectAlterationType(initialType, card);
                    }
                });
            }
            // Add initialization for seamstress options
            toggleSeamstressOptions();
            
            // Initialize price calculation
            calculatePrice();
            
            // Add event listener for quantity changes
            document.getElementById('quantity').addEventListener('input', calculatePrice);
        });
    </script>
</body>
</html>