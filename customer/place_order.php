<?php
include '../db.php';
session_start(); // Start the session
    
// Add after session_start() but before any HTML output
// Handle walk-in customers coming from staff interface
if (isset($_GET['walk_in']) && $_GET['walk_in'] == 'true' && isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
    
    // If we have temp session variables from new_orders.php, use those
    if (isset($_SESSION['temp_customer_id']) && isset($_SESSION['temp_customer_name'])) {
        $_SESSION['customer_id'] = $_SESSION['temp_customer_id'];
        $_SESSION['customer_name'] = $_SESSION['temp_customer_name'];
        
        // Clean up temp variables
        unset($_SESSION['temp_customer_id']);
        unset($_SESSION['temp_customer_name']);
    } else {
        // Otherwise fetch customer data from database
        $customer_query = "SELECT customer_id, CONCAT(first_name, ' ', last_name) AS customer_name 
                          FROM customers WHERE customer_id = ?";
        $stmt = $conn->prepare($customer_query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($customer = $result->fetch_assoc()) {
            $_SESSION['customer_id'] = $customer['customer_id'];
            $_SESSION['customer_name'] = $customer['customer_name'];
        }
    }
    
    // Add a flag to identify this as a staff-initiated walk-in order
    $_SESSION['walk_in'] = true;
}

// Ensure user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Get customer details from session
$customer_id = $_SESSION['customer_id'];

// Get full customer details from database to ensure we have first and last name
$customer_query = "SELECT first_name, last_name FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($customer_data = $result->fetch_assoc()) {
    $first_name = $customer_data['first_name'];
    $last_name = $customer_data['last_name'];
    $customer_name = $first_name . " " . $last_name;
} else {
    // Fallback to session data if database query fails
    $first_name = $_SESSION['first_name'];
    $last_name = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : "";
    $customer_name = $first_name . " " . $last_name;
}

// Function to generate a random 5-character alphanumeric order ID
function generateOrderID($length = 5) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomID = '';
    for ($i = 0; $i < $length; $i++) {
        $randomID .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomID;
}

// Function to check if order ID already exists
function isOrderIDExists($conn, $order_id) {
    $check = mysqli_query($conn, "SELECT order_id FROM orders WHERE order_id = '$order_id'");
    return mysqli_num_rows($check) > 0;
}

// Generate a unique order ID
function generateUniqueOrderID($conn) {
    do {
        $id = generateOrderID();
    } while (isOrderIDExists($conn, $id));
    return $id;
}    // Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = generateUniqueOrderID($conn); // Unique 5-char order ID
    
    // Sanitize and validate inputs
    $order_type = mysqli_real_escape_string($conn, $_POST['order_type']);
    $total_amount = floatval($_POST['total_amount']); // Ensure it's a valid number
    
    // Add template_id handling if provided
    $template_id = isset($_POST['template_id']) ? mysqli_real_escape_string($conn, $_POST['template_id']) : null;
    
    // Validate order type
    if (!in_array($order_type, ['sublimation', 'tailoring'])) {
        die("Invalid order type");
    }
    
    // Validate total amount
    if ($total_amount < 100) {
        die("Minimum order amount is ₱100.00");
    }
    
    // Use prepared statement for better security
    $query = "INSERT INTO orders (order_id, customer_id, order_type, total_amount) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssd", $order_id, $customer_id, $order_type, $total_amount);
    
    if ($stmt->execute()) {
        // Redirect to the appropriate order form with proper parameters
        if ($order_type == 'sublimation') {
            $redirect_url = "sublimation_order_request.php?order_id=$order_id";
            if ($template_id) {
                $redirect_url .= "&template_id=$template_id";
            }
            header("Location: $redirect_url");
        } elseif ($order_type == 'tailoring') {
            header("Location: select_service.php?order_id=$order_id");
        }
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

$selected_template_id = $_GET['template_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order | JX Tailoring</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
            font-family: 'Poppins', sans-serif;
        }
        .order-container {
            max-width: 1200px;
            margin: 40px auto;
        }
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            height: 100%;
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
        .step {
            display: flex;
            margin-bottom: 20px;
            align-items: center;
        }
        .step-number {
            background-color: #ff7d00;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
            flex-shrink: 0;
        }
        .step-content {
            flex-grow: 1;
        }
        .step-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #343a40;
        }
        .step-desc {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .contact-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ff7d00;
        }
        .type-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .type-card.selected {
            border-color: #ff7d00;
            background-color: #fff8f0;
        }
        .type-card:hover {
            cursor: pointer;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-color: #ffac4b;
        }
        .input-group-text {
            background-color: #ff7d00;
            color: white;
            border-color: #ff7d00;
        }
        h6 i {
            color: #ff7d00;
        }
        .card-header h4 i {
            color: #ffac4b;
        }
        .form-check-input:checked {
            background-color: #ff7d00;
            border-color: #ff7d00;
        }
        .btn-light:hover {
            color: #ff7d00;
        }
        .form-text i {
            color: #ff7d00;
        }
        /* Add these new styles */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease-out;
        }
        
        .animate-active {
            opacity: 1;
            transform: translateY(0);
        }
        
        .type-card {
            position: relative;
            overflow: hidden;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .type-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 0;
            background: rgba(255, 125, 0, 0.05);
            transition: height 0.3s ease;
            z-index: 0;
        }
        
        .type-card:hover:before {
            height: 100%;
        }
        
        .type-card.selected {
            border-color: #ff7d00;
            background-color: #fff8f0;
            transform: scale(1.02);
        }
        
        .type-card.selected:after {
            content: '\f00c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            top: 10px;
            right: 10px;
            color: #ff7d00;
            font-size: 16px;
        }
        
        .type-card:hover {
            cursor: pointer;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 125, 0, 0.1);
            border-color: #ffac4b;
        }
        
        .type-img {
            width: 70px;
            height: 70px;
            margin-right: 15px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }
        
        .type-card:hover .type-img {
            transform: scale(1.05);
        }
        
        .type-content {
            flex-grow: 1;
            z-index: 1;
        }
        
        .step {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards;
        }
        
        .step:nth-child(1) { animation-delay: 0.1s; }
        .step:nth-child(2) { animation-delay: 0.3s; }
        .step:nth-child(3) { animation-delay: 0.5s; }
        .step:nth-child(4) { animation-delay: 0.7s; }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .step-icon {
            font-size: 1.5rem;
            color: white;
        }
        
        .contact-info {
            position: relative;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ff7d00;
            overflow: hidden;
        }
        
        .contact-info:after {
            content: '';
            position: absolute;
            bottom: -30px;
            right: -20px;
            width: 100px;
            height: 100px;
            background-image: url('../image/contact-pattern.png');
            background-size: contain;
            background-repeat: no-repeat;
            opacity: 0.1;
            transform: rotate(10deg);
        }
        
        .highlight-text {
            position: relative;
            display: inline-block;
        }
        
        .highlight-text:after {
            content: '';
            position: absolute;
            width: 100%;
            height: 6px;
            bottom: 0;
            left: 0;
            background-color: rgba(255, 125, 0, 0.2);
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="container order-container">
        <div class="row g-4">
            <!-- Left Column - Order Form -->
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center py-3">
                        <h4 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Place an Order</h4>
                        <a href="index.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
                    </div>
                    <div class="card-body p-4">
                        <form action="place_order.php" method="POST" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label class="form-label">Customer Name:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($customer_name); ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Select Order Type:</label>
                                
                                <div class="type-card" onclick="selectOrderType('sublimation')">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="order_type" id="sublimation" value="sublimation" required>
                                        <label class="form-check-label fw-bold" for="sublimation">Sublimation</label>
                                    </div>
                                    <p class="mb-0 mt-2 small text-muted">Custom printed jerseys, sportswear and apparel with your designs.</p>
                                </div>
                                
                                <div class="type-card" onclick="selectOrderType('tailoring')">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="order_type" id="tailoring" value="tailoring" required>
                                        <label class="form-check-label fw-bold" for="tailoring">Tailoring</label>
                                    </div>
                                    <p class="mb-0 mt-2 small text-muted">Custom-made clothing tailored to your specific measurements.</p>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label" for="total_amount">Budget Amount (₱):</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-peso-sign"></i></span>
                                    <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" min="100" required>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i> Minimum order amount is ₱100.00
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-1"></i> Proceed to Order Details
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Instructions -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header py-3">
                        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Order Information & Instructions</h4>
                    </div>
                    <div class="card-body p-4">
                        <h5 class="mb-3">How It Works</h5>
                        
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <div class="step-title">Select Order Type & Budget</div>
                                <div class="step-desc">Choose between Sublimation or Tailoring services and set your budget.</div>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <div class="step-title">Complete Order Details</div>
                                <div class="step-desc">Fill in your specific requirements, measurements, and design preferences.</div>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <div class="step-title">Review & Confirmation</div>
                                <div class="step-desc">Our team will review your order and contact you for any clarification.</div>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <div class="step-title">Production & Delivery</div>
                                <div class="step-desc">Once approved, we'll start production and deliver within the estimated timeframe.</div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Order Types Explained</h5>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-tshirt me-2"></i>Sublimation Orders</h6>
                            <p class="small text-muted">Perfect for custom sports jerseys, team uniforms, and personalized apparel. We use high-quality sublimation printing that won't fade or crack even after multiple washes.</p>
                            <ul class="small text-muted mb-4">
                                <li>Vibrant, full-color designs</li>
                                <li>Customizable names and numbers</li>
                                <li>Available for individuals or teams</li>
                                <li>Standard production time: 5-7 business days</li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-cut me-2"></i>Tailoring Orders</h6>
                            <p class="small text-muted">For custom-made clothing tailored to your exact measurements. Our professional tailors ensure a perfect fit for any garment.</p>
                            <ul class="small text-muted mb-4">
                                <li>Custom-fitted clothing</li>
                                <li>Wide range of fabric options</li>
                                <li>Alterations and repairs available</li>
                                <li>Standard production time: 7-10 business days</li>
                            </ul>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="contact-info mb-2">
                            <h6 class="mb-3"><i class="fas fa-headset me-2"></i>Need Assistance?</h6>
                            <p class="small mb-2">Our customer support team is available to help with any questions.</p>
                            <p class="small mb-1"><i class="fas fa-phone me-2"></i>Phone: (123) 456-7890</p>
                            <p class="small mb-1"><i class="fas fa-envelope me-2"></i>Email: support@jxtailoring.com</p>
                            <p class="small mb-0"><i class="fas fa-clock me-2"></i>Hours: Mon-Fri, 9am-5pm</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap & JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
    
    // Select order type cards
    function selectOrderType(type) {
        // Update radio button
        document.getElementById(type).checked = true;
        
        // Update visual selection
        const cards = document.querySelectorAll('.type-card');
        cards.forEach(card => {
            card.classList.remove('selected');
        });
        
        // Add selected class to clicked card
        document.getElementById(type).closest('.type-card').classList.add('selected');
    }

    // Function to show the sublimation form and select template
function selectSublimationTemplate(templateId) {
    // First show the sublimation form section
    document.getElementById('order_type').value = 'sublimation';
    toggleOrderForms(); // Assuming you have this function to show/hide forms
    
    // Then select the template
    const templateSelect = document.getElementById('template_id');
    if (templateSelect) {
        templateSelect.value = templateId;
        
        // If you have a change event listener on the template select
        templateSelect.dispatchEvent(new Event('change'));
        
        // Scroll to the form
        document.getElementById('sublimation_form').scrollIntoView({ behavior: 'smooth' });
        
        // Maybe highlight the selected template
        highlightSelectedTemplate(templateId);
    }
}

// Optional: Add visual feedback to show which template is selected
function highlightSelectedTemplate(templateId) {
    // If you have a list of templates displayed in the form
    const templateElements = document.querySelectorAll('.template-option');
    templateElements.forEach(el => {
        if (el.dataset.templateId === templateId) {
            el.classList.add('selected');
        } else {
            el.classList.remove('selected');
        }
    });
}

    document.addEventListener('DOMContentLoaded', function() {
    // Check if template_id parameter exists in URL
    const urlParams = new URLSearchParams(window.location.search);
    const templateId = urlParams.get('template_id');
    
    if (templateId) {
        // Automatically select sublimation as the order type
        selectOrderType('sublimation');
        
        // Create a hidden input to store the template_id
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'template_id';
        hiddenInput.value = templateId;
        
        // Add it to the form
        document.querySelector('form').appendChild(hiddenInput);
    }
    
    // Modify form submission to include the template_id when redirecting
    document.querySelector('form').addEventListener('submit', function(e) {
        const orderType = document.querySelector('input[name="order_type"]:checked').value;
        
        // If sublimation is selected and we have a template_id, prevent default form submission
        if (orderType === 'sublimation' && templateId) {
            e.preventDefault();
            
            // Get the total amount
            const totalAmount = document.getElementById('total_amount').value;
            
            // Submit the form via AJAX with proper data
            fetch('place_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_type=sublimation&total_amount=${totalAmount}&template_id=${templateId}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                // Check if the response is a redirect
                const redirectUrl = response.url;
                if (redirectUrl && redirectUrl !== window.location.href) {
                    // If it's a redirect, extract the order_id from the URL
                    const urlParams = new URLSearchParams(redirectUrl.split('?')[1]);
                    const orderId = urlParams.get('order_id');
                    
                    if (orderId) {
                        // If we found an order_id, redirect to sublimation_order_request with both IDs
                        window.location.href = `sublimation_order_request.php?order_id=${orderId}&template_id=${templateId}`;
                        return null; // Stop processing
                    }
                }
                // If we couldn't extract from redirect, try parsing the response text
                return response.text();
            })
            .then(html => {
                if (!html) return; // Skip if we already redirected
                
                // Look for the order ID in the HTML response (just check for the pattern)
                const match = html.match(/order_id=([A-Z0-9]+)/);
                if (match && match[1]) {
                    const orderId = match[1];
                    // Redirect to sublimation_order_request.php with both order_id and template_id
                    window.location.href = `sublimation_order_request.php?order_id=${orderId}&template_id=${templateId}`;
                } else {
                    // Direct submission approach as fallback
                    console.log("Could not extract order_id from response, using direct form submission");
                    // Create a hidden form to submit instead of trying to parse the HTML
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'place_order.php';
                    
                    // Add the necessary fields
                    const fields = {
                        'order_type': 'sublimation',
                        'total_amount': totalAmount,
                        'template_id': templateId
                    };
                    
                    for (const key in fields) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = fields[key];
                        form.appendChild(input);
                    }
                    
                    // Add form to body and submit
                    document.body.appendChild(form);
                    form.submit();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // If AJAX fails, use direct form submission with a limited URL length
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'place_order.php';
                
                // Add only essential fields to prevent URL length issues
                const fields = {
                    'order_type': 'sublimation',
                    'total_amount': totalAmount,
                    'template_id': templateId
                };
                
                for (const key in fields) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = fields[key];
                    form.appendChild(input);
                }
                
                document.body.appendChild(form);
                form.submit();
            });
        }
    });
});
    </script>
</body>
</html>
