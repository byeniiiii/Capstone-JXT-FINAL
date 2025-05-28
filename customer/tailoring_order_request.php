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
$service_type = $description = $measurements = $fabric_type = $fabric_color = '';
$instructions = $completion_date = '';
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

// Function to check if order_id exists in the database
function orderIdExists($conn, $order_id) {
    $sql = "SELECT 1 FROM orders WHERE order_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Function to generate a unique order ID with 5 random characters
function generateUniqueOrderId($conn) {
    $prefix = 'TO';
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed similar-looking characters I,O,0,1
    
    do {
        $random = '';
        // Generate 5 random alphanumeric characters
        for ($i = 0; $i < 5; $i++) {
            $random .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        $order_id = $prefix . $random;
    } while (orderIdExists($conn, $order_id));
    
    return $order_id;
}

// Get order_id from URL parameter or generate new one
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';

// Generate new order ID if empty or it already exists in the database
if (empty($order_id) || orderIdExists($conn, $order_id)) {
    $order_id = generateUniqueOrderId($conn);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form inputs
    $order_id = $_POST['order_id'];
    $service_type = $_POST['service_type'];
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $measurements = isset($_POST['measurements']) ? $_POST['measurements'] : '';
    $fabric_type = isset($_POST['fabric_type']) ? $_POST['fabric_type'] : '';
    $fabric_color = isset($_POST['fabric_color']) ? $_POST['fabric_color'] : '';
    $instructions = isset($_POST['instructions']) ? $_POST['instructions'] : '';
    $completion_date = isset($_POST['completion_date']) ? $_POST['completion_date'] : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $needs_seamstress = isset($_POST['needs_seamstress']) ? 1 : 0;
    
    // Validation
    if (empty($order_id)) $errors[] = "Order ID is required";
    if (empty($service_type)) $errors[] = "Service type is required";
    if (empty($completion_date)) $errors[] = "Completion date is required";
    
    // Conditional validation based on service type
    if (in_array($service_type, ['alterations', 'repairs', 'resize'])) {
        if (empty($description)) $errors[] = "Description is required for this service type";
    }
    
    if ($service_type == 'custom made') {
        if (empty($fabric_type)) $errors[] = "Fabric type is required for custom made orders";
        if (empty($fabric_color)) $errors[] = "Fabric color is required for custom made orders";
    }
    
    if (empty($measurements) && !isset($_FILES['measurements_file']['name'])) {
        $errors[] = "Either measurements details or a measurements file is required";
    }
    
    // Handle file upload if provided
    $measurements_file = "";
    if (isset($_FILES['measurements_file']) && $_FILES['measurements_file']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['measurements_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "File format not allowed. Please upload JPG, JPEG, PNG or PDF files only.";
        } else {
            $upload_dir = "uploads/measurements/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = $order_id . '_measurements_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['measurements_file']['tmp_name'], $destination)) {
                $measurements_file = $destination;
            } else {
                $errors[] = "Failed to upload the file. Please try again.";
            }
        }
    }

    // If no errors, insert into database
    if (empty($errors)) {
        // First create an order record
        $order_sql = "INSERT INTO orders (order_id, customer_id, order_type, order_status, 
                      total_amount, downpayment_amount, payment_method, payment_status) 
                      VALUES (?, ?, 'tailoring', 'pending_approval', 0.00, 0.00, 'cash', 'pending')";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("si", $order_id, $customer_id);
        
        if ($order_stmt->execute()) {
            // Then create the tailoring order record
            $tailoring_sql = "INSERT INTO tailoring_orders 
                            (order_id, service_type, description, measurements, fabric_type, 
                            fabric_color, instructions, completion_date, quantity, measurements_file, needs_seamstress) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $tailoring_stmt = $conn->prepare($tailoring_sql);
            $tailoring_stmt->bind_param(
                "ssssssssisi", 
                $order_id, 
                $service_type, 
                $description, 
                $measurements, 
                $fabric_type, 
                $fabric_color,
                $instructions, 
                $completion_date, 
                $quantity, 
                $measurements_file, 
                $needs_seamstress
            );
            
            if ($tailoring_stmt->execute()) {
                $success = true;
                
                // Create notification for the new order
                $notification_sql = "INSERT INTO notifications 
                                    (customer_id, order_id, title, message, is_read, created_at) 
                                    VALUES (?, ?, 'New Tailoring Order', 
                                    'Your tailoring order #".$order_id." has been received and is pending review.', 0, NOW())";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("is", $customer_id, $order_id);
                $notification_stmt->execute();
                
                // Redirect after 2 seconds
                header("refresh:2;url=track_order.php?order_id=".$order_id);
            } else {
                $errors[] = "Error creating tailoring order: " . $tailoring_stmt->error;
            }
        } else {
            $errors[] = "Error creating order: " . $order_stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailoring Order Request | JX Tailoring</title>
    
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
        .service-icon {
            font-size: 2rem;
            color: #ff7d00;
            margin-bottom: 15px;
        }
        .service-card {
            cursor: pointer;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .service-card:hover {
            border-color: #ff7d00;
            transform: translateY(-5px);
        }
        .service-card.selected {
            border-color: #ff7d00;
            background-color: #fff8f0;
        }
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease-out;
        }
        .animate-active {
            opacity: 1;
            transform: translateY(0);
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
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container order-form-container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h4 class="mb-0"><i class="fas fa-tshirt me-2"></i>Submit Tailoring Order</h4>
                <a href="index.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
            </div>
            <div class="card-body p-4">
                
                <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle me-2"></i> Your tailoring order has been successfully submitted! Redirecting to order tracking...
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
                
                <form action="tailoring_order_request.php" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                    <!-- Order ID and Basic Information -->
                    <div class="form-section animate-on-scroll">
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
                                        value="<?php echo $completion_date; ?>" required min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                                </div>
                                <div class="form-text">Please allow at least 3 days for processing.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service Type Selection -->
                    <div class="form-section animate-on-scroll">
                        <h5 class="form-section-title"><i class="fas fa-clipboard-list me-2 text-primary"></i>Service Type</h5>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="service-card" onclick="selectService('alterations', this)">
                                    <div class="service-icon"><i class="fas fa-cut"></i></div>
                                    <h6>Alterations</h6>
                                    <p class="small text-muted mb-0">Modify existing clothing</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="service-card" onclick="selectService('repairs', this)">
                                    <div class="service-icon"><i class="fas fa-tools"></i></div>
                                    <h6>Repairs</h6>
                                    <p class="small text-muted mb-0">Fix damaged clothing</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="service-card" onclick="selectService('resize', this)">
                                    <div class="service-icon"><i class="fas fa-compress-arrows-alt"></i></div>
                                    <h6>Resize</h6>
                                    <p class="small text-muted mb-0">Adjust clothing size</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="service-card" onclick="selectService('custom made', this)">
                                    <div class="service-icon"><i class="fas fa-tshirt"></i></div>
                                    <h6>Custom Made</h6>
                                    <p class="small text-muted mb-0">Create new clothing</p>
                                </div>
                            </div>
                            <input type="hidden" name="service_type" id="service_type" value="<?php echo $service_type; ?>" required>
                        </div>
                    </div>
                    
                    <!-- Basic Details (shown for all service types) -->
                    <div class="form-section animate-on-scroll">
                        <h5 class="form-section-title"><i class="fas fa-list-ul me-2 text-primary"></i>Basic Details</h5>
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-sort-amount-up"></i></span>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" max="100" value="<?php echo $quantity; ?>" required>
                            </div>
                        </div>
                        
                        <!-- Description field (visible for alterations, repairs, resize) -->
                        <div class="mb-3 simple-service-field" style="display: none;">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo $description; ?></textarea>
                            <div class="form-text">
                                Please describe what you need in detail. For example:
                                <ul>
                                    <li>For alterations: "Shorten sleeves by 2 inches, take in waist by 1 inch"</li>
                                    <li>For repairs: "Fix torn seam on left shoulder, replace missing button"</li>
                                    <li>For resize: "Reduce waist size from 34 to 32 inches, keep length the same"</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="instructions" class="form-label">Special Instructions</label>
                            <textarea class="form-control" id="instructions" name="instructions" rows="3"><?php echo $instructions; ?></textarea>
                            <div class="form-text">Any additional information or special requirements.</div>
                        </div>
                    </div>
                    
                    <!-- Custom Made Details (only visible when Custom Made is selected) -->
                    <div class="form-section animate-on-scroll custom-made-field" style="display: none;">
                        <h5 class="form-section-title"><i class="fas fa-tshirt me-2 text-primary"></i>Custom Made Details</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="fabric_type" class="form-label">Fabric Type <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-scroll"></i></span>
                                    <select class="form-select" id="fabric_type" name="fabric_type">
                                        <option value="" disabled selected>Select fabric type</option>
                                        <option value="Cotton" <?php if($fabric_type == 'Cotton') echo 'selected'; ?>>Cotton</option>
                                        <option value="Linen" <?php if($fabric_type == 'Linen') echo 'selected'; ?>>Linen</option>
                                        <option value="Silk" <?php if($fabric_type == 'Silk') echo 'selected'; ?>>Silk</option>
                                        <option value="Wool" <?php if($fabric_type == 'Wool') echo 'selected'; ?>>Wool</option>
                                        <option value="Polyester" <?php if($fabric_type == 'Polyester') echo 'selected'; ?>>Polyester</option>
                                        <option value="Denim" <?php if($fabric_type == 'Denim') echo 'selected'; ?>>Denim</option>
                                        <option value="Chiffon" <?php if($fabric_type == 'Chiffon') echo 'selected'; ?>>Chiffon</option>
                                        <option value="Other" <?php if($fabric_type == 'Other') echo 'selected'; ?>>Other (specify in instructions)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="fabric_color" class="form-label">Fabric Color <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-palette"></i></span>
                                    <input type="text" class="form-control" id="fabric_color" name="fabric_color" value="<?php echo $fabric_color; ?>">
                                    <span class="input-group-text p-0 border-0 bg-transparent">
                                        <input type="color" class="form-control form-control-color" id="colorPicker" title="Choose color">
                                    </span>
                                </div>
                                <div id="colorPreview" class="mt-2 d-flex align-items-center" style="display: none;">
                                    <span>Selected color: </span>
                                    <div class="color-preview ms-2" id="previewBox"></div>
                                    <span id="colorCode"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="needs_seamstress" name="needs_seamstress" value="1" <?php if($needs_seamstress) echo 'checked'; ?>>
                            <label class="form-check-label" for="needs_seamstress">
                                I need an appointment with a seamstress for measurements
                            </label>
                            <div class="form-text">Check this if you'd like us to schedule an appointment for taking measurements.</div>
                        </div>
                    </div>
                    
                    <!-- Measurements Section -->
                    <div class="form-section animate-on-scroll">
                        <h5 class="form-section-title"><i class="fas fa-ruler me-2 text-primary"></i>Measurements</h5>
                        
                        <div class="mb-3">
                            <label for="measurements" class="form-label">Measurements Details</label>
                            <textarea class="form-control" id="measurements" name="measurements" rows="4"><?php echo $measurements; ?></textarea>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i> Enter your measurements or describe how we should measure your garment.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="measurements_file" class="form-label">Upload Measurements File</label>
                            <input type="file" class="form-control" id="measurements_file" name="measurements_file">
                            <div class="form-text">Upload a file with your measurements or a reference image. Accepted formats: JPG, JPEG, PNG, PDF.</div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="reset" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-undo me-1"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Submit Tailoring Order
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
        
        // Color picker functionality
        document.getElementById('colorPicker').addEventListener('input', function(e) {
            document.getElementById('fabric_color').value = e.target.value;
            document.getElementById('previewBox').style.backgroundColor = e.target.value;
            document.getElementById('colorCode').textContent = e.target.value;
            document.getElementById('colorPreview').style.display = 'flex';
        });
        
        // Service type selection
        function selectService(service, element) {
            // Clear previous selection
            document.querySelectorAll('.service-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Set new selection
            element.classList.add('selected');
            document.getElementById('service_type').value = service;
            
            // Show/hide relevant fields
            if (service === 'custom made') {
                document.querySelectorAll('.custom-made-field').forEach(el => {
                    el.style.display = 'block';
                });
                document.querySelectorAll('.simple-service-field').forEach(el => {
                    el.style.display = 'none';
                });
            } else {
                document.querySelectorAll('.custom-made-field').forEach(el => {
                    el.style.display = 'none';
                });
                document.querySelectorAll('.simple-service-field').forEach(el => {
                    el.style.display = 'block';
                });
            }
            
            // Add required attributes based on service type
            if (service === 'custom made') {
                document.getElementById('fabric_type').setAttribute('required', '');
                document.getElementById('fabric_color').setAttribute('required', '');
                document.getElementById('description').removeAttribute('required');
            } else {
                document.getElementById('fabric_type').removeAttribute('required');
                document.getElementById('fabric_color').removeAttribute('required');
                document.getElementById('description').setAttribute('required', '');
            }
        }
        
        // Scroll animation
        function checkScroll() {
            const elements = document.querySelectorAll('.animate-on-scroll');
            const windowHeight = window.innerHeight;
            
            elements.forEach(element => {
                const elementPosition = element.getBoundingClientRect().top;
                if (elementPosition < windowHeight - 50) {
                    element.classList.add('animate-active');
                }
            });
        }
        
        // Initialize form based on existing values
        window.addEventListener('DOMContentLoaded', function() {
            // Set initial service type if available
            const initialService = '<?php echo $service_type; ?>';
            if (initialService) {
                document.querySelectorAll('.service-card').forEach(card => {
                    if (card.querySelector('h6').textContent.toLowerCase() === initialService) {
                        selectService(initialService, card);
                    }
                });
            }
            
            // Initialize color preview if available
            const initialColor = '<?php echo $fabric_color; ?>';
            if (initialColor && initialColor.startsWith('#')) {
                document.getElementById('colorPicker').value = initialColor;
                document.getElementById('previewBox').style.backgroundColor = initialColor;
                document.getElementById('colorCode').textContent = initialColor;
                document.getElementById('colorPreview').style.display = 'flex';
            }
            
            // Check scroll animations
            checkScroll();
        });
        
        // Check on scroll
        window.addEventListener('scroll', checkScroll);
    </script>
</body>
</html>
