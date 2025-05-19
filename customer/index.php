<?php
session_start();
include '../db.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['customer_id']);
$customer_id = $is_logged_in ? $_SESSION['customer_id'] : null;

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
                            <a href="<?php echo $is_logged_in ? 'place_order.php?template_id=' . $row['template_id'] : 'login.php?redirect=place_order.php&template_id=' . $row['template_id']; ?>" class="btn btn-select">
                                <i class="fas fa-shopping-cart me-1"></i> Select Template
                            </a>
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
    </div>
    
    <!-- Include footer -->
    <?php include 'footer.php'; ?>

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
    </script>
</body>
</html>
