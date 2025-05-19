<?php
// Include database connection
include('../db.php');
session_start();

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit();
}

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

// Generate new order ID
$order_id = generateUniqueOrderId($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Tailoring Service | JX Tailoring</title>
    
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
        .container {
            max-width: 900px;
            margin: 40px auto;
        }
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #343a40;
            color: white;
            font-weight: 600;
            border-top-left-radius: 0.5rem !important;
            border-top-right-radius: 0.5rem !important;
        }
        .service-option {
            padding: 2rem;
            text-align: center;
            cursor: pointer;
        }
        .service-icon {
            font-size: 4rem;
            color: #ff7d00;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: #ff7d00;
            border-color: #ff7d00;
        }
        .btn-primary:hover {
            background-color: #e06c00;
            border-color: #e06c00;
        }
        .service-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .service-description {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        .order-id-banner {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h4 class="mb-0"><i class="fas fa-tshirt me-2"></i>Select Tailoring Service</h4>
                <a href="index.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
            </div>
            <div class="card-body p-4">
                <!-- Order ID Banner -->
                <div class="order-id-banner">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">
                                <i class="fas fa-tag me-2 text-primary"></i> 
                                Your Order ID: <strong><?php echo $order_id; ?></strong>
                            </h5>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-0 text-muted">This ID will be used to track your order</p>
                        </div>
                    </div>
                </div>
                
                <h5 class="mb-4">Hello <?php echo htmlspecialchars($customer_name); ?>, please select a service:</h5>
                
                <div class="row">
                    <!-- Alterations Service Option -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="service-option" onclick="location.href='alteration.php?order_id=<?php echo $order_id; ?>';">
                                <div class="service-icon">
                                    <i class="fas fa-cut"></i>
                                </div>
                                <h5 class="service-title">Alterations</h5>
                                <p class="service-description">
                                    Modify, resize, or repair your existing clothing items. Perfect for adjusting fit, 
                                    fixing damages, or updating styles.
                                </p>
                                <ul class="text-start mb-4">
                                    <li>Resize clothing to fit better</li>
                                    <li>Repair tears, holes, and damage</li>
                                    <li>Modify design elements</li>
                                    <li>Hem adjustment and length modification</li>
                                </ul>
                                <button class="btn btn-primary btn-lg">
                                    <i class="fas fa-cut me-2"></i> Choose Alterations
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Made Service Option -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="service-option" onclick="location.href='custom_made.php?order_id=<?php echo $order_id; ?>';">
                                <div class="service-icon">
                                    <i class="fas fa-tshirt"></i>
                                </div>
                                <h5 class="service-title">Custom Made Clothing</h5>
                                <p class="service-description">
                                    Create brand new clothing items from scratch according to your specifications.
                                    Perfect for unique designs and special occasions.
                                </p>
                                <ul class="text-start mb-4">
                                    <li>Create clothing to your exact measurements</li>
                                    <li>Choose fabrics, colors, and styles</li>
                                    <li>Design unique pieces</li>
                                    <li>Perfect fit guaranteed</li>
                                </ul>
                                <button class="btn btn-primary btn-lg">
                                    <i class="fas fa-tshirt me-2"></i> Choose Custom Made
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>