<?php
session_start();
include '../db.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['customer_id']);
$customer_id = $is_logged_in ? $_SESSION['customer_id'] : null;

if (isset($conn)) {
    $db_conn = $conn;
}

// Fetch logged-in customer's details if logged in
$customer_name = 'Guest';
if ($is_logged_in) {
    $query = "SELECT first_name, last_name FROM customers WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $customer_name = $customer ? $customer['first_name'] . ' ' . $customer['last_name'] : 'Guest';
}

// Fetch templates added by Sublimators
$query = "SELECT t.*, u.first_name, u.last_name,
          SUBSTRING_INDEX(t.image_path, '/', -1) as image_filename 
          FROM templates t
          JOIN users u ON t.added_by = u.user_id
          WHERE u.role = 'Sublimator'
          ORDER BY t.created_at DESC";
$result = mysqli_query($conn, $query);

// Get unique categories for filtering
$categoryQuery = "SELECT DISTINCT category FROM templates WHERE category IS NOT NULL";
$categoryResult = mysqli_query($conn, $categoryQuery);



//akoy ga add ani - cadiz
// Random order ID generation with recursion
function getOrderId($n = 10) {
    global $conn; 
    
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomStr = "";

   
    for ($i = 0; $i < $n; $i++) {
        $index = random_int(0, strlen($characters) - 1);
        $randomStr .= $characters[$index];
    }

    
    $qry1 = "SELECT order_id FROM orders WHERE order_id = ?";
    $stmt1 = $conn->prepare($qry1);
    $stmt1->bind_param("s", $randomStr);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    
    // If the order ID exists, regenerate
    if ($result1->num_rows > 0) {
        return getOrderId($n); 
    }

    return $randomStr;
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../image/logo.png">
    <title>Home | JX Tailoring</title>
    
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
            padding-top: 56px; /* Space for fixed navbar */
        }
        
        /* Navbar styling */
        .navbar {
            background-color: #343a40;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        
        .navbar-brand {
            font-weight: 600;
            color: white;
        }
        
        .navbar-toggler {
            border: none;
            color: white;
        }
        
        /* Offcanvas navbar for mobile */
        .offcanvas {
            background-color: #343a40;
        }
        
        .offcanvas-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .offcanvas-title {
            color: white;
            font-weight: 600;
        }
        
        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 0.75rem 1rem;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        
        .navbar-nav .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 8px;
        }
        
        /* Main content area */
        .main-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        /* Page header */
        .page-header {
            padding: 20px 0;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-weight: 600;
            color: #343a40;
            margin-bottom: 10px;
        }
        
        /* Filters section */
        .filters-section {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        
        /* Template cards */
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .template-card {
            background-color: white;
            border: none;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }
        
        .template-image {
            height: 200px;
            background-color: #f8f9fa;
            overflow: hidden;
            position: relative;
        }
        
        .template-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .template-card:hover .template-image img {
            transform: scale(1.05);
        }
        
        .template-body {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .template-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 5px;
            color: #343a40;
        }
        
        .template-details {
            flex-grow: 1;
            margin-bottom: 10px;
        }
        
        .template-category {
            display: inline-block;
            background-color: #f8f9fa;
            color: #6c757d;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 12px;
            margin-bottom: 10px;
        }
        
        .template-price {
            font-weight: 700;
            color: #343a40;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        
        .template-footer {
            margin-top: auto;
        }
        
        .btn-select {
            background-color: #343a40;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            font-weight: 500;
            width: 100%;
            transition: background-color 0.2s;
        }
        
        .btn-select:hover {
            background-color: #212529;
            color: white;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 15px;
        }
        
        /* User avatar in navbar */
        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }
        
        /* Category filter button */
        .filter-button {
            flex-shrink: 0; /* Prevent shrinking */
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
            border-radius: 30px;
            padding: 8px 16px;
            font-size: 0.9rem;
            transition: all 0.2s;
            white-space: nowrap; /* Prevent text wrapping */
            font-weight: 500;
        }
        
        .filter-button:hover,
        .filter-button.active {
            background-color: #343a40;
            border-color: #343a40;
            color: white;
        }
        
        /* Footer */
        .footer {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
            padding: 0 15px;
            margin-bottom: 20px;
        }
        
        .footer-title {
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 8px;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .social-icons a {
            color: rgba(255,255,255,0.75);
            font-size: 1.2rem;
            margin-right: 15px;
            transition: color 0.2s;
        }
        
        .social-icons a:hover {
            color: white;
        }
        
        .copyright {
            width: 100%;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 20px;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.75);
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .template-grid {
                grid-template-columns: repeat(3, 1fr); /* Show 3 templates per row on mobile */
                gap: 10px; /* Reduce gap for mobile to fit items better */
            }
            
            /* Remove this line:
            .filters-container {
                flex-direction: column;
            }
            */
            
            /* Other mobile styles can stay */
            /* ... */
            
            /* Make template cards more compact on mobile */
            .template-image {
                height: 120px;
            }
            
            .template-body {
                padding: 8px;
            }
            
            .template-title {
                font-size: 0.85rem;
                margin-bottom: 3px;
            }
            
            .template-category {
                font-size: 0.7rem;
                margin-bottom: 5px;
            }
            
            .template-price {
                font-size: 0.9rem;
                margin-bottom: 8px;
            }
            
            .btn-select {
                padding: 5px 8px;
                font-size: 0.8rem;
            }
            
            /* Hide some elements to save space on small screens */
            .template-details p {
                display: none;
            }
        }

        /* Add a media query for very small devices to show 2 templates per row */
        @media (max-width: 375px) {
            .template-grid {
                grid-template-columns: repeat(2, 1fr); /* Show 2 templates per row on very small devices */
            }
        }

        /* Add a tablet-specific media query for better layout control */
        @media (min-width: 577px) and (max-width: 991px) {
            .template-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
        }

        /* Horizontal scrolling filters */
        .filters-scroll-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
            scrollbar-width: none; /* Hide scrollbar Firefox */
            -ms-overflow-style: none; /* Hide scrollbar IE/Edge */
            margin-bottom: 10px;
        }

        .filters-scroll-container::-webkit-scrollbar {
            display: none; /* Hide scrollbar Chrome/Safari/Opera */
        }

        .filters-container {
            display: flex;
            flex-wrap: nowrap; /* Prevent wrapping */
            gap: 8px;
            padding-bottom: 5px;
        }

        /* Added styles for non-logged in state */
        .btn-login {
            background-color: transparent;
            border: 1px solid white;
            color: white;
            transition: all 0.2s;
        }
        
        .btn-login:hover {
            background-color: white;
            color: #343a40;
        }
        
        .btn-signup {
            background-color: white;
            color: #343a40;
            transition: all 0.2s;
        }
        
        .btn-signup:hover {
            background-color: #f8f9fa;
        }

        /* Carousel size adjustments */
        #mainCarousel {
            margin-bottom: 20px; /* Reduced from 30px */
            box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.15); /* Slightly smaller shadow */
        }

        .carousel-image-container {
            height: 350px; /* Reduced from 500px to a more normal size */
            overflow: hidden;
            position: relative;
        }

        .carousel-caption {
            bottom: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 10%;
            text-align: center;
        }

        .carousel-caption h2 {
            font-size: 2rem;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            margin-bottom: 0.75rem;
        }

        .carousel-caption p {
            font-size: 1rem;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
            margin-bottom: 1rem;
            display: block;
        }

        .carousel-caption .btn {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.2);
            align-self: center;
            font-size: 0.95rem;
        }

        /* Enhanced responsive adjustments for carousel */
        @media (max-width: 991px) {
            .carousel-image-container {
                height: 300px; /* Reduced from 400px */
            }
            
            .carousel-caption h2 {
                font-size: 1.75rem;
                margin-bottom: 0.5rem;
            }
            
            .carousel-caption p {
                font-size: 0.9rem;
                margin-bottom: 0.75rem;
            }
            
            .carousel-caption .btn {
                padding: 0.4rem 1.2rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 767px) {
            .carousel-image-container {
                height: 250px; /* Reduced from 300px */
            }
            
            .carousel-caption h2 {
                font-size: 1.5rem;
                margin-bottom: 0.4rem;
            }
            
            .carousel-caption p {
                font-size: 0.85rem;
                margin-bottom: 0.6rem;
            }
            
            .carousel-caption .btn {
                padding: 0.35rem 1rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 576px) {
            .carousel-image-container {
                height: 200px; /* Reduced from 250px */
            }
            
            .carousel-caption {
                padding: 0 5%;
            }
            
            .carousel-caption h2 {
                font-size: 1.25rem;
                margin-bottom: 0.3rem;
            }
            
            .carousel-caption p {
                font-size: 0.75rem;
                margin-bottom: 0.5rem;
            }
            
            .carousel-caption .btn {
                padding: 0.25rem 0.75rem;
                font-size: 0.75rem;
            }
        }

        /* For extremely small screens */
        @media (max-width: 375px) {
            .carousel-image-container {
                height: 180px;
            }
            
            .carousel-caption h2 {
                font-size: 1.1rem;
            }
            
            .carousel-caption p {
                margin-bottom: 0.4rem;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <div class="d-flex align-items-center">
                <a href="index.php" class="me-2">
                    <img src="../image/logo.png" height="50" alt="JX Tailoring Logo">
                </a>
                <span class="navbar-brand mb-0"></span>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarOffcanvas">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Desktop navigation menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $is_logged_in ? 'place_order.php' : 'login.php?redirect=place_order.php'; ?>">
                            <i class="fas fa-clipboard-list"></i> Place Order
                        </a>
                    </li>
                    
                    <?php if ($is_logged_in): ?>
                    <!-- Show these items only when logged in -->
                    <li class="nav-item">
                        <a class="nav-link" href="notification.php">
                            <i class="fas fa-bell"></i> Notifications
                            <?php 
                            // Add notification counter if there are unread notifications
                            $unread_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE customer_id = ? AND is_read = 0";
                            $unread_stmt = $conn->prepare($unread_sql);
                            $unread_stmt->bind_param("i", $customer_id);
                            $unread_stmt->execute();
                            $unread_result = $unread_stmt->get_result();
                            $unread_count = $unread_result->fetch_assoc()['unread_count'];
                            $unread_stmt->close();
                            
                            if ($unread_count > 0): 
                            ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($customer_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="track_order.php"><i class="fas fa-shopping-bag me-2"></i>Track Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <!-- Show these items when not logged in -->
                    <li class="nav-item">
                        <a class="btn btn-login me-2" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-signup" href="signup.php">Sign Up</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Offcanvas Menu -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="navbarOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">
                <i class="fas fa-tshirt me-2"></i>JX Tailoring
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <?php if ($is_logged_in): ?>
            <!-- Logged in profile info -->
            <div class="d-flex align-items-center mb-4 p-2 bg-dark bg-opacity-25 rounded">
                <div class="avatar me-3">
                    <?php echo substr($customer['first_name'], 0, 1); ?>
                </div>
                <div>
                    <strong class="d-block text-white"><?php echo htmlspecialchars($customer_name); ?></strong>
                    <small class="text-white-50">Customer</small>
                </div>
            </div>
            <?php endif; ?>
            
            <ul class="navbar-nav">
                <li class="nav-item mb-1">
                    <a class="nav-link active" href="index.php"><i class="fas fa-home"></i> Home</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="<?php echo $is_logged_in ? 'place_order.php' : 'login.php?redirect=place_order.php'; ?>">
                        <i class="fas fa-clipboard-list"></i> Place Order
                    </a>
                </li>
                
                <?php if ($is_logged_in): ?>
                <!-- Menu items for logged in users -->
                <li class="nav-item mb-1">
                    <a class="nav-link" href="track_order.php"><i class="fas fa-shopping-bag"></i> Track Orders</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="notification.php"><i class="fas fa-bell"></i> Notifications</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
                <?php else: ?>
                <!-- Menu items for guests -->
                <li class="nav-item mb-1">
                    <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="signup.php"><i class="fas fa-user-plus"></i> Sign Up</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <!-- Main Banner Carousel -->
    <div id="mainCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
        <!-- Carousel Indicators -->
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="4" aria-label="Slide 5"></button>
        </div>
        
        <!-- Carousel Slides -->
        <div class="carousel-inner">
            <!-- Slide 1 -->
            <div class="carousel-item active">
                <div class="carousel-image-container">
                    <img src="../image/b1.png" class="d-block w-100" alt="Quality Tailoring Services">
                    <div class="carousel-caption">
                        <h2>Premium Quality Tailoring</h2>
                        <p>Handcrafted garments made to fit your unique style and measurements</p>
                        <a href="<?php echo $is_logged_in ? 'place_order.php' : 'login.php?redirect=place_order.php'; ?>" class="btn btn-light">Order Now</a>
                    </div>
                </div>
            </div>
            
            <!-- Slide 2 -->
            <div class="carousel-item">
                <div class="carousel-image-container">
                    <img src="../image/b2.png" class="d-block w-100" alt="Custom Sublimation Printing">
                    <div class="carousel-caption">
                        <h2>Custom Sublimation Printing</h2>
                        <p>Vibrant, durable designs for sports jerseys and team uniforms</p>
                        <a href="<?php echo $is_logged_in ? 'place_order.php' : 'login.php?redirect=place_order.php'; ?>" class="btn btn-light">Customize Now</a>
                    </div>
                </div>
            </div>
            
            <!-- Slide 3 -->
            <div class="carousel-item">
                <div class="carousel-image-container">
                    <img src="../image/b3.png" class="d-block w-100" alt="Team Uniforms">
                    <div class="carousel-caption">
                        <h2>Team Uniforms & Jerseys</h2>
                        <p>Outfit your entire team with matching, custom-designed apparel</p>
                        <a href="<?php echo $is_logged_in ? 'place_order.php' : 'login.php?redirect=place_order.php'; ?>" class="btn btn-light">Explore Options</a>
                    </div>
                </div>
            </div>
            
            <!-- Slide 4 -->
            <div class="carousel-item">
                <div class="carousel-image-container">
                    <img src="../image/b4.png" class="d-block w-100" alt="Professional Alteration">
                    <div class="carousel-caption">
                        <h2>Professional Alterations</h2>
                        <p>Perfect fit guaranteed with our expert alteration services</p>
                        <a href="<?php echo $is_logged_in ? 'place_order.php' : 'login.php?redirect=place_order.php'; ?>" class="btn btn-light">Learn More</a>
                    </div>
                </div>
            </div>
            
            <!-- Slide 5 -->
            <div class="carousel-item">
                <div class="carousel-image-container">
                    <img src="../image/b5.png" class="d-block w-100" alt="Express Service">
                    <div class="carousel-caption">
                        <h2>Fast Turnaround Times</h2>
                        <p>Quality work delivered on schedule, every time</p>
                        <a href="<?php echo $is_logged_in ? 'track_order.php' : 'login.php?redirect=track_order.php'; ?>" class="btn btn-light">Track Orders</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Carousel Controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
    
    <!-- Main Container -->
    <div class="main-container">
        
        <!-- Filters Section -->
        <div class="filters-section mb-4">
            <div class="d-block d-md-flex flex-wrap justify-content-between align-items-center">
                <div class="filter-label mb-2 mb-md-0">
                </div>
                <div class="filters-scroll-container">
                    <div class="filters-container d-flex">
                        <button class="filter-button active" data-category="">All Categories</button>
                        <?php 
                        mysqli_data_seek($categoryResult, 0);
                        while ($category = mysqli_fetch_assoc($categoryResult)) { 
                            if (!empty($category['category'])) {
                        ?>
                            <button class="filter-button" data-category="<?php echo htmlspecialchars($category['category']); ?>">
                                <?php echo htmlspecialchars($category['category']); ?>
                            </button>
                        <?php 
                            }
                        } 
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Templates Grid -->
        <div class="template-grid">
            <?php if (mysqli_num_rows($result) > 0) { 
                while ($row = mysqli_fetch_assoc($result)) { 
                    $imagePath = "../sublimator/uploads/" . htmlspecialchars($row['image_filename']);
                    if (!file_exists($imagePath) || empty($row['image_filename'])) {
                        $imagePath = "default.jpg";
                    }
            ?>
                <div class="template-card" data-category="<?php echo htmlspecialchars($row['category']); ?>">
                    <div class="template-image">
                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                    </div>
                    <div class="template-body">
                        <h5 class="template-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                        <div class="template-details">
                            <?php if (!empty($row['category'])) { ?>
                                <span class="template-category"><?php echo htmlspecialchars($row['category']); ?></span>
                            <?php } ?>
                            <p class="text-muted small">
                                <i class="fas fa-user me-1"></i> Designer: 
                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                            </p>
                        </div>
                        <div class="template-price">₱<?php echo number_format($row['price'], 2); ?></div>
                        <div class="template-footer">
                            <?php if ($is_logged_in): ?>
                            <a href="javascript:void(0);" class="btn btn-select" 
                               onclick="showSublimationOrderModal(
                                                            '<?=  getOrderId(); ?>',
                                                            <?php echo $row['template_id']; ?>, 
                                                             '<?php echo addslashes($row['name']); ?>', 
                                                             <?php echo $row['price']; ?>, 
                                                             '<?php echo $imagePath; ?>')">
                                <i class="fas fa-shopping-cart me-1"></i> Select Template
                            </a>
                            <?php else: ?>
                            <a href="login.php?redirect=index.php" class="btn btn-select">
                                <i class="fas fa-sign-in-alt me-1"></i> Login to Order
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php } 
            } else { ?>
                <div class="empty-state col-12">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <h4>No Templates Found</h4>
                    <p class="text-muted">We don't have any templates available at the moment. Please check back later.</p>
                </div>
            <?php } ?>
        </div>
        
        <!-- No Results Message (Hidden by default) -->
        <div class="empty-state" id="no-results-message" style="display: none;">
            <i class="fas fa-filter fa-3x mb-3"></i>
            <h4>No Templates Found</h4>
            <p class="text-muted">No templates match the selected filter. Please try another category.</p>
            <button class="btn btn-outline-secondary mt-3" onclick="resetFilters()">
                <i class="fas fa-undo me-1"></i> Reset Filters
            </button>
        </div>

        <!-- Made-to-Order Services Section -->
        <div class="made-to-order-section mt-5">
            <h3 class="text-center mb-4">Made-to-Order Services</h3>
            <ul class="nav nav-tabs nav-fill mb-4" id="mtoTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="alterations-tab" data-bs-toggle="tab" data-bs-target="#alterations" type="button" role="tab" aria-controls="alterations" aria-selected="true">
                        <i class="fas fa-cut me-2"></i>Alterations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="repairs-tab" data-bs-toggle="tab" data-bs-target="#repairs" type="button" role="tab" aria-controls="repairs" aria-selected="false">
                        <i class="fas fa-tools me-2"></i>Repairs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="resize-tab" data-bs-toggle="tab" data-bs-target="#resize" type="button" role="tab" aria-controls="resize" aria-selected="false">
                        <i class="fas fa-compress-arrows-alt me-2"></i>Resize
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="custom-tab" data-bs-toggle="tab" data-bs-target="#custom" type="button" role="tab" aria-controls="custom" aria-selected="false">
                        <i class="fas fa-tshirt me-2"></i>Custom Made
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="mtoTabContent">
                <!-- Alterations Tab -->
                <div class="tab-pane fade show active" id="alterations" role="tabpanel" aria-labelledby="alterations-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="service-description">
                                <h4>Alteration Services</h4>
                                <p>Professional clothing alterations to achieve the perfect fit for your garments.</p>
                                <ul class="service-features">
                                    <li><i class="fas fa-check text-success me-2"></i>Hem adjustments</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Taking in or letting out seams</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Sleeve length modifications</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Waist adjustments</li>
                                </ul>
                                <div class="price-info mt-3">
                                    <p class="mb-1"><strong>Starting at ₱300</strong></p>
                                    <small class="text-muted">Final price depends on complexity and type of alteration</small>
                                </div>
                                <a href="<?php echo $is_logged_in ? 'tailoring_order_request.php' : 'login.php?redirect=tailoring_order_request.php'; ?>" class="btn btn-primary mt-3">
                                    <i class="fas fa-scissors me-2"></i>Request Alteration
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <img src="../image/alteration-service.jpg" alt="Alteration Services" class="img-fluid rounded shadow-sm">
                        </div>
                    </div>
                </div>
                
                <!-- Repairs Tab -->
                <div class="tab-pane fade" id="repairs" role="tabpanel" aria-labelledby="repairs-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="service-description">
                                <h4>Repair Services</h4>
                                <p>Expert clothing repairs to fix damages and extend the life of your garments.</p>
                                <ul class="service-features">
                                    <li><i class="fas fa-check text-success me-2"></i>Seam repairs</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Zipper replacement</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Button replacement</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Patch repairs</li>
                                </ul>
                                <div class="price-info mt-3">
                                    <p class="mb-1"><strong>Starting at ₱300</strong></p>
                                    <small class="text-muted">Final price depends on repair type and complexity</small>
                                </div>
                                <a href="<?php echo $is_logged_in ? 'tailoring_order_request.php' : 'login.php?redirect=tailoring_order_request.php'; ?>" class="btn btn-primary mt-3">
                                    <i class="fas fa-tools me-2"></i>Request Repair
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <img src="../image/repair-service.jpg" alt="Repair Services" class="img-fluid rounded shadow-sm">
                        </div>
                    </div>
                </div>

                <!-- Resize Tab -->
                <div class="tab-pane fade" id="resize" role="tabpanel" aria-labelledby="resize-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="service-description">
                                <h4>Resizing Services</h4>
                                <p>Professional garment resizing to make your clothing fit perfectly.</p>
                                <ul class="service-features">
                                    <li><i class="fas fa-check text-success me-2"></i>Size up or down</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Precise measurements</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Expert tailoring</li>
                                    <li><i class="fas fa-check text-success me-2"></i>All types of garments</li>
                                </ul>
                                <div class="price-info mt-3">
                                    <p class="mb-1"><strong>Starting at ₱500</strong></p>
                                    <small class="text-muted">Final price depends on garment type and size adjustment needed</small>
                                </div>
                                <a href="<?php echo $is_logged_in ? 'tailoring_order_request.php' : 'login.php?redirect=tailoring_order_request.php'; ?>" class="btn btn-primary mt-3">
                                    <i class="fas fa-compress-arrows-alt me-2"></i>Request Resizing
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <img src="../image/resize-service.jpg" alt="Resizing Services" class="img-fluid rounded shadow-sm">
                        </div>
                    </div>
                </div>

                <!-- Custom Made Tab -->
                <div class="tab-pane fade" id="custom" role="tabpanel" aria-labelledby="custom-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="service-description">
                                <h4>Custom Made Clothing</h4>
                                <p>Bespoke clothing tailored to your exact measurements and specifications.</p>
                                <ul class="service-features">
                                    <li><i class="fas fa-check text-success me-2"></i>Custom designs</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Premium fabrics available</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Perfect fit guarantee</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Professional consultation</li>
                                </ul>
                                <div class="price-info mt-3">
                                    <p class="mb-1"><strong>Starting at ₱1,500</strong></p>
                                    <small class="text-muted">Additional cost for shop-provided fabric (+₱500)</small>
                                </div>
                                <a href="<?php echo $is_logged_in ? 'tailoring_order_request.php' : 'login.php?redirect=tailoring_order_request.php'; ?>" class="btn btn-primary mt-3">
                                    <i class="fas fa-tshirt me-2"></i>Request Custom Made
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <img src="../image/custom-made-service.jpg" alt="Custom Made Services" class="img-fluid rounded shadow-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <!-- Include footer -->
    <?php include 'footer.php'; ?>

    <!-- Sublimation Order Modal -->
<div class="modal fade" id="sublimationOrderModal" tabindex="-1" aria-labelledby="sublimationOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="sublimationOrderModalLabel"><i class="fas fa-tshirt me-2"></i>Sublimation Order Form</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="sublimationOrderForm" method="POST" action="process_sublimation_order.php" enctype="multipart/form-data">
                    <!-- Hidden Fields -->

                    <input type="hidden" name="total_amount" id="modal_total_amount">
                    
                    <div class="row">
                        <!-- Left side - Template details and preview -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <h6 class="fw-bold mb-3">Selected Template</h6>
                                    <img id="modal_template_image" src="" alt="Template Preview" class="img-fluid rounded mb-3" style="max-height: 250px; object-fit: contain;">
                                    <h5 id="modal_template_name" class="mb-2"></h5>
                                    <p class="text-primary fw-bold" id="modal_template_price"></p>
                                    
                                    <!-- Cost breakdown section -->
                                    <div class="mt-4 text-start">
                                        <h6 class="fw-bold">Cost Breakdown:</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <td>Jerseys:</td>
                                                <td id="jersey_cost" class="text-end"></td>
                                            </tr>
                                            <tr id="shorts_cost_row" style="display:none;">
                                                <td>Shorts:</td>
                                                <td id="shorts_cost" class="text-end"></td>
                                            </tr>
                                            <tr class="fw-bold">
                                                <td>Total:</td>
                                                <td id="modal_calculated_amount" class="text-end"></td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <div class="alert alert-info mt-3 small">
                                        <i class="fas fa-info-circle me-2"></i> This template will be customized based on your specifications.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right side - Order form -->
                        <div class="col-md-8">
                            <div class="row g-3">
                                <!-- Order Type Information -->
                                <div class="col-md-12">
                                    <div class="card mb-3 bg-light border-0">
                                        <div class="card-body">
                                            <h6><i class="fas fa-info-circle me-2 text-primary"></i>Jersey Customization Details</h6>
                                            <p class="small text-muted mb-0">Fill out the details below to customize your jersey order.</p>
                                        </div>
                                    </div>
                                </div>


                                <div class="col-md-12">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="modal_order_id" name="order_id" placeholder="ORDER ID"  readonly>
                                        <label for="modal_order_id">Order ID</label>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="modal_template_id" name="template_id" placeholder="Template ID" readonly>
                                        <label for="modal_template_id">Template ID</label>
                                    </div>
                                </div>
                                
                                <!-- Customer Name - Only field kept from customer info -->
                                <div class="col-md-12">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="modal_full_name" name="full_name" placeholder="Full Name" required>
                                        <label for="modal_full_name">Your Name</label>
                                    </div>
                                </div>
                                
                                <!-- Completion Date -->
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="date" class="form-control" id="modal_completion_date" name="completion_date" required>
                                        <label for="modal_completion_date">Completion Date</label>
                                    </div>
                                </div>
                                
                                <!-- Design customization -->
                                <div class="col-md-12">
                                    <div class="form-floating mb-3">
                                        <textarea class="form-control" id="modal_customization" name="customization" placeholder="Design requirements" style="height: 100px;"></textarea>
                                        <label for="modal_customization">Design Requirements</label>
                                    </div>
                                    <div class="form-text small"><i class="fas fa-info-circle me-1"></i> Describe any specific design requirements, including colors and theme.</div>
                                </div>
                                
                                <!-- Jersey Players Section -->
                                <div class="col-md-12 mt-3">
                                    <h6 class="fw-bold mb-3">Player Jerseys</h6>
                                    <div id="jersey-players-container">
                                        <!-- Initial player form will be inserted here -->
                                    </div>
                                    
                                    <!-- Add more players button -->
                                    <div class="text-center mt-3 mb-2">
                                        <button type="button" class="btn btn-outline-primary" id="add-player-btn">
                                            <i class="fas fa-plus me-1"></i> Add Another Player
                                        </button>
                                    </div>
                                </div>
                                
                                
                                <!-- Additional notes -->
                                <div class="col-md-12">
                                    <div class="form-floating mb-3">
                                        <textarea class="form-control" id="modal_notes" name="notes" placeholder="Additional notes" style="height: 100px;"></textarea>
                                        <label for="modal_notes">Additional Notes</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="sublimationOrderForm" class="btn btn-primary">
                    <i class="fas fa-check me-1"></i> Submit Order
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap & JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Category filtering functionality
            const filterButtons = document.querySelectorAll('.filter-button');
            const templateCards = document.querySelectorAll('.template-card');
            const noResultsMessage = document.getElementById('no-results-message');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const category = this.getAttribute('data-category');
                    
                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter the templates
                    let visibleCount = 0;
                    
                    templateCards.forEach(card => {
                        const cardCategory = card.getAttribute('data-category');
                        
                        if (!category || category === cardCategory) {
                            card.style.display = 'block';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    // Show/hide no results message
                    noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
                });
            });
            
            // Hover effects for template cards
            templateCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 0.5rem 1rem rgba(0,0,0,0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '0 0.125rem 0.25rem rgba(0,0,0,0.075)';
                });
            });
        });
        
        // Reset filters function
        function resetFilters() {
            const allCategoriesButton = document.querySelector('.filter-button[data-category=""]');
            if (allCategoriesButton) {
                allCategoriesButton.click();
            }
        }

        function showSublimationOrderModal(orderId,templateId, templateName, templatePrice, templateImage) {

            console.log(orderId);
            // Update the modal with template information
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('modal_template_id').value = templateId;
            document.getElementById('modal_template_name').textContent = templateName;
            document.getElementById('modal_template_price').textContent = '₱' + templatePrice.toFixed(2);
            document.getElementById('modal_template_image').src = templateImage;
            document.getElementById('modal_total_amount').value = templatePrice.toFixed(2);
            
            // Set base price
            basePrice = templatePrice;
            
            // Pre-fill user information if available
            <?php if ($is_logged_in): ?>
            document.getElementById('modal_full_name').value = '<?php echo addslashes($customer_name); ?>';
            <?php endif; ?>
            
            // Set minimum date for completion date (7 days from today)
            const today = new Date();
            today.setDate(today.getDate() + 7); // Minimum 7 days from now
            const minDate = today.toISOString().split('T')[0];
            document.getElementById('modal_completion_date').setAttribute('min', minDate);
            
            // Set default value to 14 days from today
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 14);
            document.getElementById('modal_completion_date').value = defaultDate.toISOString().split('T')[0];
            
            // Clear existing player entries and add the first one
            const playersContainer = document.getElementById('jersey-players-container');
            playersContainer.innerHTML = '';
            addPlayerForm();
            
            // Initialize calculated amount display
            updateTotalAmount();
            
            // Show the modal
            const sublimationOrderModal = new bootstrap.Modal(document.getElementById('sublimationOrderModal'));
            sublimationOrderModal.show();
        }

        // Global variables for price calculation
        let basePrice = 0;
        const jerseyPrice = 0; // Base price is from template
        const shortsPrice = 100; // Changed from 150 to 100 pesos

        // Function to update total amount based on selections
        function updateTotalAmount() {
            // Count total jerseys and shorts
            const jerseyCount = document.querySelectorAll('.player-entry').length;
            const shortsCount = document.querySelectorAll('input[name="include_shorts[]"]:checked').length;
            
            // Calculate total
            const totalAmount = (basePrice * jerseyCount) + (shortsPrice * shortsCount);
            
            // Update hidden field and display
            document.getElementById('modal_total_amount').value = totalAmount.toFixed(2);
            document.getElementById('modal_calculated_amount').textContent = '₱' + totalAmount.toFixed(2);
            
            // Update breakdown
            document.getElementById('jersey_cost').textContent = `${jerseyCount} × ₱${basePrice.toFixed(2)} = ₱${(basePrice * jerseyCount).toFixed(2)}`;
            
            if (shortsCount > 0) {
                document.getElementById('shorts_cost').textContent = `${shortsCount} × ₱${shortsPrice.toFixed(2)} = ₱${(shortsPrice * shortsCount).toFixed(2)}`;
                document.getElementById('shorts_cost_row').style.display = 'table-row';
            } else {
                document.getElementById('shorts_cost_row').style.display = 'none';
            }
        }

        // Function to check if jersey number is unique
        function isJerseyNumberUnique(number, currentPlayerId) {
            let isUnique = true;
            const playerEntries = document.querySelectorAll('.player-entry');
            
            playerEntries.forEach(entry => {
                const entryId = entry.id;
                if (entryId !== currentPlayerId) {
                    const numberInput = entry.querySelector('input[name="player_number[]"]');
                    if (numberInput && numberInput.value === number) {
                        isUnique = false;
                    }
                }
            });
            
            return isJerseyNumberUnique;
        }

        // Function to add a new player jersey form
        function addPlayerForm() {
            const playerCount = document.querySelectorAll('.player-entry').length + 1;
            const playersContainer = document.getElementById('jersey-players-container');
            const playerId = 'player-' + Date.now(); // Use timestamp as unique ID
            
            const playerHtml = `
                <div class="player-entry card mb-3" id="${playerId}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Player #${playerCount}</h6>
                        ${playerCount > 1 ? `<button type="button" class="btn btn-sm btn-outline-danger remove-player-btn" 
                        onclick="removePlayer('${playerId}')">
                        <i class="fas fa-times"></i> Remove
                        </button>` : ''}

                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="player_name_${playerId}" name="player_name[]" placeholder="Player Name" required>
                                    <label for="player_name_${playerId}">Jersey Name</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control jersey-number" id="player_number_${playerId}" 
                                           name="player_number[]" min="0" max="99" placeholder="Number" required
                                           data-player-id="${playerId}">
                                    <label for="player_number_${playerId}">Jersey Number</label>
                                    <div class="invalid-feedback">This jersey number is already taken</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="player_size_${playerId}" name="player_size[]" required>
                                        <option value="" disabled selected>Select size</option>
                                        <option value="XS">XS</option>
                                        <option value="S">S</option>
                                        <option value="M">M</option>
                                        <option value="L">L</option>
                                        <option value="XL">XL</option>
                                        <option value="XXL">XXL</option>
                                    </select>
                                    <label for="player_size_${playerId}">Jersey Size</label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input shorts-checkbox" type="checkbox" id="include_shorts_${playerId}" name="include_shorts[]" value="${playerId}">
                                    <label class="form-check-label" for="include_shorts_${playerId}">
                                        Include shorts/lower (+₱${shortsPrice.toFixed(2)})
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            playersContainer.insertAdjacentHTML('beforeend', playerHtml);
            
            // Add event listener for shorts checkbox - simplified
            document.getElementById(`include_shorts_${playerId}`).addEventListener('change', function() {
                // Update total amount when shorts are added/removed
                updateTotalAmount();
            });
            
            // Add event listener for jersey number validation
            const jerseyNumberInput = document.getElementById(`player_number_${playerId}`);
            jerseyNumberInput.addEventListener('change', function() {
                const number = this.value;
                const currentPlayerId = this.getAttribute('data-player-id');
                
                if (!isJerseyNumberUnique(number, currentPlayerId)) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
            
            // Update calculated total
            updateTotalAmount();
        }

        // Function to remove a player entry
        function removePlayer(playerId) {
            const playerElement = document.getElementById(playerId);
            if (playerElement) {
                playerElement.remove();
                
                // Renumber the remaining players
                const playerEntries = document.querySelectorAll('.player-entry');
                playerEntries.forEach((entry, index) => {
                    entry.querySelector('.card-header h6').textContent = `Player #${index + 1}`;
                });
                
                // Update total amount
                updateTotalAmount();
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            // Add click event for adding more players
            document.getElementById('add-player-btn').addEventListener('click', function() {
                addPlayerForm();
            });
            
            // Form validation
            const sublimationOrderForm = document.getElementById('sublimationOrderForm');
            if (sublimationOrderForm) {
                sublimationOrderForm.addEventListener('submit', function(e) {
                    // Validate that at least one player is added
                    const playerEntries = document.querySelectorAll('.player-entry');
                    if (playerEntries.length === 0) {
                        e.preventDefault();
                        alert('Please add at least one player jersey.');
                        return;
                    }
                    
                    // Validate unique jersey numbers
                    let hasDuplicates = false;
                    const jerseyNumbers = [];
                    
                    document.querySelectorAll('.jersey-number').forEach(input => {
                        const number = input.value;
                        if (jerseyNumbers.includes(number)) {
                            input.classList.add('is-invalid');
                            hasDuplicates = true;
                        } else {
                            jerseyNumbers.push(number);
                        }
                    });
                    
                    if (hasDuplicates) {
                        e.preventDefault();
                        alert('Each player must have a unique jersey number.');
                        return;
                    }
                });
            }
            
            // Other existing event listeners...
        });
    </script>

    <?php if (isset($_SESSION['success'])): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Order Submitted Successfully!',
                html: '<?php echo addslashes($_SESSION['success']); ?>' + 
                      <?php if (isset($_SESSION['order_id'])): ?>
                      '<br><br>' +
                      '<div><strong>Order ID:</strong> <span class="fw-bold" style="font-size: 18px; letter-spacing: 1px; color: #0d47a1;">' +
                      '<?php echo $_SESSION['order_id']; ?>' +
                      '</span></div>' +
                      '<br>' +
                      '<div class="alert alert-primary py-3 mb-0" style="background: linear-gradient(145deg, #e3f2fd, #bbdefb);">' +
                      '<div class="d-flex align-items-center justify-content-center">' +
                      '<strong style="font-size: 16px;">Order ID:</strong>&nbsp;' +
                      '<span class="fw-bold" style="font-size: 18px; letter-spacing: 1px; color: #0d47a1;"><?php echo $_SESSION['order_id']; ?></span>&nbsp;' +
                      '<button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="copyOrderId()" title="Copy Order ID" style="box-shadow: 0 2px 5px rgba(0,0,0,0.1);">' +
                      '<i class="fas fa-copy"></i></button></div>' +
                      '</div>' +
                      '<script>' +
                      'function copyOrderId() {' +
                      '  const orderIdText = "<?php echo $_SESSION['order_id']; ?>";' +
                      '  navigator.clipboard.writeText(orderIdText);' +
                      '  const copyBtn = document.querySelector(".btn-outline-primary");' +
                      '  copyBtn.innerHTML = "<i class=\\"fas fa-check\\"></i>";' +
                      '  copyBtn.classList.add("btn-success");' +
                      '  copyBtn.classList.remove("btn-outline-primary");' +
                      '  setTimeout(() => {' +
                      '    copyBtn.innerHTML = "<i class=\\"fas fa-copy\\"></i>";' +
                      '    copyBtn.classList.remove("btn-success");' +
                      '    copyBtn.classList.add("btn-outline-primary");' +
                      '  }, 2000);' +
                      '}' +
                      '</script>'
                      <?php else: ?>
                      ''
                      <?php endif; ?>,
                confirmButtonColor: '#3085d6'
            });
        });
    </script>
<?php 
unset($_SESSION['success']); 
if (isset($_SESSION['order_id'])) unset($_SESSION['order_id']);
endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo addslashes($_SESSION['error']); ?>',
                confirmButtonColor: '#d33'
            });
        });
    </script>
<?php unset($_SESSION['error']); endif; ?>
</body>
</html>
