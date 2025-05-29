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
$design_details = $fabric_type = $special_instructions = '';
$quantity = 1;
$needs_seamstress = 0;
$completion_date = '';
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
    $design_details = $_POST['design_details'];
    $fabric_type = $_POST['fabric_type'];
    $special_instructions = isset($_POST['special_instructions']) ? $_POST['special_instructions'] : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $needs_seamstress = isset($_POST['needs_seamstress']) ? 1 : 0;
    $completion_date = isset($_POST['completion_date']) ? $_POST['completion_date'] : '';
    $seamstress_appointment = ($needs_seamstress && isset($_POST['seamstress_appointment'])) ? $_POST['seamstress_appointment'] : NULL;
    
    // Validation
    if (empty($order_id)) $errors[] = "Order ID is required";
    if (empty($design_details)) $errors[] = "Design details are required";
    if (empty($fabric_type)) $errors[] = "Fabric type is required";
    if (empty($completion_date)) $errors[] = "Completion date is required";
    
    // Handle body measurement file upload
    $body_measurement_file = "";
    if (isset($_FILES['body_measurement_file']) && $_FILES['body_measurement_file']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $filename = $_FILES['body_measurement_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "File format not allowed. Please upload JPG, JPEG, PNG, PDF, DOC or DOCX files only.";
        } else {
            $upload_dir = "../uploads/measurements/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = $order_id . '_body_measurements_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['body_measurement_file']['tmp_name'], $destination)) {
                $body_measurement_file = $destination;
            } else {
                $errors[] = "Failed to upload body measurement file. Please try again.";
            }
        }
    } else if (!$needs_seamstress) {
        $errors[] = "Please either upload body measurement file or request a seamstress appointment";
    }
    
    // Handle reference image upload
    $reference_image = "";
    if (isset($_FILES['reference_image']) && $_FILES['reference_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['reference_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "Reference image format not allowed. Please upload JPG, JPEG or PNG files only.";
        } else {
            $upload_dir = "../uploads/references/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = $order_id . '_reference_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['reference_image']['tmp_name'], $destination)) {
                $reference_image = $destination;
            } else {
                $errors[] = "Failed to upload reference image. Please try again.";
            }
        }
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
                          VALUES (?, ?, 'tailoring', 'pending_approval', 0.00, 0.00, 'cash', 'pending')";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param("si", $order_id, $customer_id);
            
            if (!$order_stmt->execute()) {
                $errors[] = "Error creating order: " . $order_stmt->error;
            }
        }
        
        // If no errors, continue with creating tailoring and custom_made records
        if (empty($errors)) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Create the tailoring order record
                $tailoring_sql = "INSERT INTO tailoring_orders 
                                (order_id, service_type, description, quantity, completion_date, 
                                needs_seamstress, seamstress_appointment) 
                                VALUES (?, 'custom made', ?, ?, ?, ?, ?)";
                
                $tailoring_stmt = $conn->prepare($tailoring_sql);
                $tailoring_stmt->bind_param(
                    "ssisis", 
                    $order_id, 
                    $design_details,
                    $quantity, 
                    $completion_date,
                    $needs_seamstress,
                    $seamstress_appointment
                );
                
                $tailoring_stmt->execute();
                
                // Create the custom_made record
                $custom_made_sql = "INSERT INTO custom_made 
                                   (order_id, design_details, body_measurement_file, fabric_type, quantity, reference_image, special_instructions) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $custom_made_stmt = $conn->prepare($custom_made_sql);
                $custom_made_stmt->bind_param(
                    "ssssiss", 
                    $order_id, 
                    $design_details, 
                    $body_measurement_file, 
                    $fabric_type, 
                    $quantity, 
                    $reference_image, 
                    $special_instructions
                );
                
                $custom_made_stmt->execute();
                
                // Create notification for the new order
                $notification_sql = "INSERT INTO notifications 
                                    (customer_id, order_id, title, message, is_read, created_at) 
                                    VALUES (?, ?, 'New Custom Made Order', 
                                    'Your custom made clothing order #".$order_id." has been received and is pending review.', 0, NOW())";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("is", $customer_id, $order_id);
                $notification_stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                $success = true;
                
                // Don't redirect immediately, show modal instead
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var successModal = new bootstrap.Modal(document.getElementById('orderSuccessModal'));
                        successModal.show();
                    });
                </script>";
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
    <title>Custom Made Clothing Order | JXT Tailoring</title>
    
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
        .reference-preview {
            width: 100%;
            max-height: 200px;
            border-radius: 5px;
            display: none;
            object-fit: contain;
            margin-top: 10px;
            border: 1px solid #dee2e6;
        }
        .fabric-option {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 10px;
        }
        .fabric-option:hover {
            border-color: #ff7d00;
            background-color: #fff8f0;
        }
        .fabric-option.selected {
            border-color: #ff7d00;
            background-color: #fff8f0;
        }
    </style>
</head>
<body>
    <div class="container order-form-container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h4 class="mb-0"><i class="fas fa-tshirt me-2"></i>Custom Made Clothing Order</h4>
                <a href="select_service.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
            </div>
            <div class="card-body p-4">
                
                <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle me-2"></i> 
                    <strong>Thank you! Your custom made clothing order has been successfully submitted.</strong>
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
                
                <form action="custom_made.php" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
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
                                        min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                                </div>
                                <div class="form-text">Please allow at least 7 days for custom made clothing.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Design Details -->
                    <div class="form-section">
                        <h5 class="form-section-title"><i class="fas fa-pencil-ruler me-2 text-primary"></i>Design Details</h5>
                        
                        <div class="mb-3">
                            <label for="design_details" class="form-label">Design Details <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="design_details" name="design_details" rows="4" required><?php echo htmlspecialchars($design_details); ?></textarea>
                            <div class="form-text">
                                Describe in detail what you want us to create. Include:
                                <ul>
                                    <li>Type of garment (shirt, dress, suit, etc.)</li>
                                    <li>Style details (collar type, sleeve length, etc.)</li>
                                    <li>Any specific features (pockets, buttons, etc.)</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reference_image" class="form-label">Reference Image</label>
                            <input type="file" class="form-control" id="reference_image" name="reference_image" accept="image/*" onchange="previewReference(this)">
                            <div class="form-text">Upload an image to show us what you want. Accepted formats: JPG, JPEG, PNG.</div>
                            <img id="reference_preview" class="reference-preview" src="#" alt="Reference preview">
                        </div>
                    </div>
                    
                    <!-- Fabric and Quantity -->
                    <div class="form-section">
                        <h5 class="form-section-title"><i class="fas fa-scroll me-2 text-primary"></i>Fabric & Quantity</h5>
                        
                        <div class="mb-3">
                            <label for="fabric_type" class="form-label">Fabric Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="fabric_type" name="fabric_type" required>
                                <option value="" disabled selected>Select fabric type</option>
                                <option value="Cotton" <?php if($fabric_type == 'Cotton') echo 'selected'; ?>>Cotton</option>
                                <option value="Linen" <?php if($fabric_type == 'Linen') echo 'selected'; ?>>Linen</option>
                                <option value="Silk" <?php if($fabric_type == 'Silk') echo 'selected'; ?>>Silk</option>
                                <option value="Wool" <?php if($fabric_type == 'Wool') echo 'selected'; ?>>Wool</option>
                                <option value="Polyester" <?php if($fabric_type == 'Polyester') echo 'selected'; ?>>Polyester</option>
                                <option value="Denim" <?php if($fabric_type == 'Denim') echo 'selected'; ?>>Denim</option>
                                <option value="Chiffon" <?php if($fabric_type == 'Chiffon') echo 'selected'; ?>>Chiffon</option>
                                <option value="Satin" <?php if($fabric_type == 'Satin') echo 'selected'; ?>>Satin</option>
                                <option value="Other" <?php if($fabric_type == 'Other') echo 'selected'; ?>>Other (specify in instructions)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-sort-amount-up"></i></span>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" max="100" value="<?php echo $quantity; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Measurements Section -->
                    <div class="form-section">
                        <h5 class="form-section-title"><i class="fas fa-ruler me-2 text-primary"></i>Body Measurements</h5>
                        
                        <div class="mb-3">
                            <p class="mb-2">Please select one of the following options:</p>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="needs_seamstress" name="needs_seamstress" value="1" <?php if($needs_seamstress) echo 'checked'; ?> onchange="toggleMeasurementOptions()">
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
                            
                            <div id="measurement_upload" <?php if($needs_seamstress) echo 'style="display: none;"'; ?>>
                                <label for="body_measurement_file" class="form-label">Upload Body Measurement File</label>
                                <input type="file" class="form-control" id="body_measurement_file" name="body_measurement_file">
                                <div class="form-text">Upload a file with your body measurements. Accepted formats: JPG, JPEG, PNG, PDF, DOC, DOCX.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Special Instructions -->
                    <div class="form-section">
                        <h5 class="form-section-title"><i class="fas fa-clipboard-list me-2 text-primary"></i>Additional Information</h5>
                        
                        <div class="mb-3">
                            <label for="special_instructions" class="form-label">Special Instructions</label>
                            <textarea class="form-control" id="special_instructions" name="special_instructions" rows="3"><?php echo htmlspecialchars($special_instructions); ?></textarea>
                            <div class="form-text">Any additional information, preferences, or special requirements.</div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="select_service.php" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Submit Custom Made Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="orderSuccessModal" tabindex="-1" aria-labelledby="orderSuccessModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="orderSuccessModalLabel"><i class="fas fa-check-circle me-2"></i> Order Submitted Successfully!</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center py-4">
            <div class="mb-4">
              <i class="fas fa-tshirt fa-3x text-success mb-3"></i>
              <h4>Thank you for your order!</h4>
              <p class="mb-0">Your custom made clothing order has been submitted.</p>
              <p class="mb-3">Order ID: <strong><?php echo $order_id; ?></strong></p>
              <div class="alert alert-light border">
                <p class="mb-1"><strong>Next steps:</strong></p>
                <ul class="text-start mb-0">
                  <li>Our staff will review your order details</li>
                  <li>You'll receive a notification when your order is approved</li>
                  <?php if ($needs_seamstress): ?>
                    <li>We'll confirm your seamstress appointment date</li>
                  <?php endif; ?>
                  <li>Payment will be collected once your order is approved</li>
                </ul>
              </div>
            </div>
            <div class="d-flex justify-content-center mt-2">
              <a href="index.php" class="btn btn-primary me-2">
                <i class="fas fa-home me-1"></i> Go to Dashboard
              </a>
              <a href="orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-list-alt me-1"></i> View My Orders
              </a>
            </div>
          </div>
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
        
        // Preview reference image
        function previewReference(input) {
            const preview = document.getElementById('reference_preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
        
        // Toggle measurement options
        function toggleMeasurementOptions() {
            const needsSeamstress = document.getElementById('needs_seamstress');
            const measurementUpload = document.getElementById('measurement_upload');
            const appointmentSection = document.getElementById('appointment_section');
            const appointmentInput = document.getElementById('seamstress_appointment');
            
            if (needsSeamstress.checked) {
                measurementUpload.style.display = 'none';
                appointmentSection.style.display = 'block';
                document.getElementById('body_measurement_file').removeAttribute('required');
                appointmentInput.setAttribute('required', '');
            } else {
                measurementUpload.style.display = 'block';
                appointmentSection.style.display = 'none';
                document.getElementById('body_measurement_file').setAttribute('required', '');
                appointmentInput.removeAttribute('required');
            }
        }
        
        // Initialize form
        window.addEventListener('DOMContentLoaded', function() {
            toggleMeasurementOptions();
            
            // Check if success is true and show modal
            <?php if ($success): ?>
            var successModal = new bootstrap.Modal(document.getElementById('orderSuccessModal'));
            successModal.show();
            
            // Handle modal dismissal
            document.getElementById('orderSuccessModal').addEventListener('hidden.bs.modal', function () {
                window.location.href = 'index.php';
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>