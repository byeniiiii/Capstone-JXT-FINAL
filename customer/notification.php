<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include '../db.php';

// Get customer ID
$customer_id = $_SESSION['customer_id'];

// Mark notification as read if notification_id is provided
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = $_GET['mark_read'];
    $mark_read_sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND customer_id = ?";
    $mark_stmt = $conn->prepare($mark_read_sql);
    $mark_stmt->bind_param("ii", $notification_id, $customer_id);
    $mark_stmt->execute();
    $mark_stmt->close();
    
    // Redirect to remove the query parameter
    header("Location: notification.php");
    exit();
}

// Mark all notifications as read
if (isset($_GET['mark_all_read'])) {
    $mark_all_sql = "UPDATE notifications SET is_read = 1 WHERE customer_id = ?";
    $mark_all_stmt = $conn->prepare($mark_all_sql);
    $mark_all_stmt->bind_param("i", $customer_id);
    $mark_all_stmt->execute();
    $mark_all_stmt->close();
    
    // Redirect to remove the query parameter
    header("Location: notification.php");
    exit();
}

// Get notifications for the customer - FIXED QUERY
$sql = "SELECT n.*, o.order_id 
        FROM notifications n
        LEFT JOIN orders o ON n.order_id = o.order_id
        WHERE n.customer_id = ?
        ORDER BY n.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

// Count unread notifications
$unread_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE customer_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param("i", $customer_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'];
$unread_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | JXT Tailoring</title>
    <link rel="icon" type="image/png" href="../image/logo.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 70px; /* Space for mobile nav */
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
        }
        
        .page-header {
            background-color: white;
            padding: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,.04);
            margin-bottom: 20px;
        }
        
        .notification-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.2s;
            margin-bottom: 16px;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,.05);
        }
        
        .notification-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,.1);
        }
        
        .notification-unread {
            border-left: 4px solid #ff7d00;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,.05);
        }
        
        .notification-body {
            padding: 16px;
        }
        
        .notification-time {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .notification-title {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .notification-title i {
            margin-right: 8px;
            color: #ff7d00;
        }
        
        .notification-message {
            color: #495057;
            margin-bottom: 12px;
        }
        
        .notification-footer {
            display: flex;
            justify-content: space-between;
        }
        
        .btn-order {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,.05);
        }
        
        .empty-state i {
            color: #dee2e6;
            margin-bottom: 16px;
        }
        
        /* Mobile Bottom Navigation */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: white;
            display: flex;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            height: 60px;
        }

        .mobile-bottom-nav__item {
            flex-grow: 1;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .mobile-bottom-nav__item-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #6c757d;
            padding: 8px 0;
            text-decoration: none;
        }

        .mobile-bottom-nav__item-link i {
            font-size: 1.3rem;
            margin-bottom: 2px;
        }

        .mobile-bottom-nav__item-link span {
            font-size: 0.6rem;
        }

        .mobile-bottom-nav__item.active .mobile-bottom-nav__item-link {
            color: #ff7d00;
        }
        
        .badge-notification {
            position: absolute;
            top: -5px;
            right: 25px;
            font-size: 0.6rem;
            padding: 0.25em 0.4em;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <div class="d-flex align-items-center">
                <a href="index.php" class="me-2">
                    <img src="../image/logo.png" height="40" alt="JXT Tailoring Logo">
                </a>
                <span class="navbar-brand mb-0">Notifications</span>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarOffcanvas">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>
    
    <!-- Offcanvas Menu -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="navbarOffcanvas">
        <div class="offcanvas-header bg-dark text-white">
            <h5 class="offcanvas-title">
                <img src="../image/logo.png" height="30" alt="JXT Tailoring Logo" class="me-2">
                JXT Tailoring
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav">
                <li class="nav-item mb-1">
                    <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="place_order.php"><i class="fas fa-clipboard-list"></i> Place Order</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="track_order.php"><i class="fas fa-shopping-bag"></i> Track Orders</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link active" href="notification.php"><i class="fas fa-bell"></i> Notifications</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container" style="margin-top: 80px; margin-bottom: 20px;">
        <!-- Header with notification count and mark all read button -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h4 mb-0">Notifications</h1>
                <?php if ($unread_count > 0): ?>
                <p class="text-muted mb-0">You have <?php echo $unread_count; ?> unread notification<?php echo $unread_count != 1 ? 's' : ''; ?></p>
                <?php endif; ?>
            </div>
            <div>
                <!-- Back to Home button -->
                <a href="index.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-home me-1"></i> Back to Home
                </a>
                
                <?php if ($unread_count > 0): ?>
                <a href="notification.php?mark_all_read=1" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-check-double me-1"></i> Mark all as read
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Notifications list -->
        <?php if ($result->num_rows > 0): ?>
            <?php while ($notification = $result->fetch_assoc()): ?>
                <div class="card notification-card <?php echo $notification['is_read'] ? '' : 'notification-unread'; ?>">
                    <div class="notification-header">
                        <span class="badge <?php echo $notification['is_read'] ? 'bg-secondary' : 'bg-primary'; ?>">
                            <?php echo $notification['is_read'] ? 'Read' : 'New'; ?>
                        </span>
                        <span class="notification-time">
                            <?php echo date('M d, Y - h:i A', strtotime($notification['created_at'])); ?>
                        </span>
                    </div>
                    <div class="notification-body">
                        <div class="notification-title">
                            <?php
                            // Choose icon based on notification title
                            $icon = 'fa-bell'; // Default icon
                            $title = strtolower($notification['title']); // Convert to lowercase for easier matching

                            if (strpos($title, 'order') !== false) {
                                if (strpos($title, 'declined') !== false) {
                                    $icon = 'fa-times-circle';
                                } elseif (strpos($title, 'approved') !== false) {
                                    $icon = 'fa-check-circle';
                                } elseif (strpos($title, 'ready') !== false) {
                                    $icon = 'fa-box';
                                } elseif (strpos($title, 'completed') !== false) {
                                    $icon = 'fa-clipboard-check';
                                } else {
                                    $icon = 'fa-clipboard-list'; 
                                }
                            } elseif (strpos($title, 'payment') !== false) {
                                $icon = 'fa-credit-card';
                            } elseif (strpos($title, 'delivery') !== false || strpos($title, 'pickup') !== false) {
                                $icon = 'fa-truck';
                            } elseif (strpos($title, 'measurement') !== false) {
                                $icon = 'fa-ruler';
                            } elseif (strpos($title, 'reminder') !== false) {
                                $icon = 'fa-calendar-check';
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                            <h5 class="mb-0"><?php echo htmlspecialchars($notification['title']); ?></h5>
                        </div>
                        <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                        <div class="notification-footer">
                            <?php if ($notification['order_id']): ?>
                            <a href="view_order.php?order_id=<?php echo $notification['order_id']; ?>" 
                               class="btn btn-sm btn-outline-primary btn-order"
                               onclick="event.preventDefault(); window.location.href='view_order.php?order_id=<?php echo $notification['order_id']; ?>';">
                                <i class="fas fa-eye me-1"></i> View Order #<?php echo $notification['order_id']; ?>
                            </a>
                            <?php else: ?>
                            <div></div>
                            <?php endif; ?>
                            
                            <?php if (!$notification['is_read']): ?>
                            <a href="notification.php?mark_read=<?php echo $notification['notification_id']; ?>" class="btn btn-sm btn-link">
                                Mark as read
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash fa-3x"></i>
                <h4>No Notifications Yet</h4>
                <p class="text-muted">You don't have any notifications at the moment.</p>
                <a href="index.php" class="btn btn-primary mt-3">
                    <i class="fas fa-home me-1"></i> Back to Home
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav d-block d-lg-none">
        <div class="mobile-bottom-nav__item">
            <a href="index.php" class="mobile-bottom-nav__item-link">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
        </div>
        <div class="mobile-bottom-nav__item">
            <a href="place_order.php" class="mobile-bottom-nav__item-link">
                <i class="fas fa-clipboard-list"></i>
                <span>Order</span>
            </a>
        </div>
        <div class="mobile-bottom-nav__item">
            <a href="track_order.php" class="mobile-bottom-nav__item-link">
                <i class="fas fa-shopping-bag"></i>
                <span>Track</span>
            </a>
        </div>
        <div class="mobile-bottom-nav__item active">
            <a href="notification.php" class="mobile-bottom-nav__item-link position-relative">
                <?php if ($unread_count > 0): ?>
                <span class="badge bg-danger badge-notification"><?php echo $unread_count; ?></span>
                <?php endif; ?>
                <i class="fas fa-bell"></i>
                <span>Alerts</span>
            </a>
        </div>
        <div class="mobile-bottom-nav__item">
            <a href="profile.php" class="mobile-bottom-nav__item-link">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </div>
    </nav>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>