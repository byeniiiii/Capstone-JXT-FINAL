<?php
// Database connection
include_once '../db.php';

// Include sidebar navigation
include 'sidebar.php';

// Function to create a staff notification - fixed parameter order
function createStaffNotification($staff_id, $conn, $message, $order_id = null) {
    $stmt = $conn->prepare("INSERT INTO staff_notifications (staff_id, order_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $staff_id, $order_id, $message);
    return $stmt->execute();
}

// Get unread notifications count for the current staff member
$staff_id = $_SESSION['user_id'] ?? 0;

// Query staff_notifications table
$unread_query = "SELECT COUNT(*) as count FROM staff_notifications 
                WHERE staff_id = '$staff_id' AND is_read = 0";
$unread_result = mysqli_query($conn, $unread_query);
$unread_count = 0;
if ($unread_result) {
    $unread_count = mysqli_fetch_assoc($unread_result)['count'];
}

// Get recent notifications (limit to 5)
$notifications_query = "SELECT n.*, o.order_status, o.order_type,
                        DATE_FORMAT(n.created_at, '%M %d, %Y at %h:%i %p') as formatted_date
                      FROM staff_notifications n
                      LEFT JOIN orders o ON n.order_id = o.order_id
                      WHERE n.staff_id = '$staff_id'
                      ORDER BY n.created_at DESC 
                      LIMIT 5";
$notifications_result = mysqli_query($conn, $notifications_query);
?>

<!-- Enhanced styles for notifications -->
<style>
    .dropdown-list {
        width: 330px;
        padding: 0;
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .dropdown-header {
        background-color: #263e79;
        border: 1px solid #263e79;
        color: white;
        font-weight: 700;
        padding: 1rem;
        text-align: center;
    }
    
    .dropdown-list .dropdown-item {
        white-space: normal;
        padding: 0.8rem 1rem;
        border-bottom: 1px solid #e3e6f0;
        line-height: 1.3;
    }
    
    .dropdown-list .dropdown-item:hover {
        background-color: #f8f9fc;
    }
    
    .dropdown-list .dropdown-item:last-child {
        border-bottom: none;
    }
    
    .icon-circle {
        height: 2.5rem;
        width: 2.5rem;
        border-radius: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .notification-timestamp {
        font-size: 0.7rem;
        color: #858796;
    }
    
    .notification-message {
        font-size: 0.85rem;
        line-height: 1.4;
    }
    
    .notification-order-type {
        display: inline-block;
        padding: 0.2rem 0.4rem;
        font-size: 0.65rem;
        border-radius: 3px;
        color: white;
        margin-right: 0.5rem;
    }
    
    .type-tailoring {
        background-color: #36b9cc;
    }
    
    .type-sublimation {
        background-color: #4e73df;
    }
    
    .dropdown-list .unread {
        background-color: #f0f8ff;
    }
    
    .view-all-link {
        background-color: #f8f9fc;
        font-weight: 600;
        padding: 0.75rem;
        text-align: center;
    }
</style>

<!-- Nav Item - Notifications -->
<li class="nav-item dropdown no-arrow mx-1">
    <a class="nav-link dropdown-toggle position-relative" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-bell fa-fw"></i>
        <!-- Counter - Notifications -->
        <?php if ($unread_count > 0): ?>
        <span class="badge badge-danger badge-counter position-absolute" style="top: 5px; right: 5px;">
            <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
        </span>
        <?php endif; ?>
    </a>
    <!-- Dropdown - Notifications -->
    <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
        <h6 class="dropdown-header">
            <i class="fas fa-bell mr-2"></i> NOTIFICATIONS CENTER
        </h6>
        
        <?php if (mysqli_num_rows($notifications_result) > 0): ?>
            <?php while ($notification = mysqli_fetch_assoc($notifications_result)): ?>
            <a class="dropdown-item d-flex align-items-center <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
               href="<?php echo $notification['order_id'] ? 'view_order.php?id='.$notification['order_id'] : '#'; ?>"
               onclick="<?php echo !$notification['is_read'] ? 'markAsRead('.$notification['notification_id'].')' : ''; ?>">
                <div class="mr-3">
                    <div class="icon-circle <?php echo getIconClass($notification['order_status'] ?? null); ?>">
                        <i class="fas <?php echo getIconType($notification['order_status'] ?? null); ?> text-white"></i>
                    </div>
                </div>
                <div class="w-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <?php if($notification['order_id']): ?>
                            <span class="font-weight-bold">Order #<?php echo htmlspecialchars($notification['order_id']); ?></span>
                        <?php endif; ?>
                        <?php if(!$notification['is_read']): ?>
                            <span class="badge badge-primary badge-pill" style="font-size: 0.6rem;">NEW</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($notification['order_type']): ?>
                    <div>
                        <span class="notification-order-type type-<?php echo strtolower($notification['order_type']); ?>">
                            <?php echo ucfirst($notification['order_type']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="notification-message mb-1 <?php echo $notification['is_read'] ? 'text-gray-800' : 'font-weight-bold text-gray-900'; ?>">
                        <?php echo htmlspecialchars($notification['message']); ?>
                    </div>
                    <div class="notification-timestamp">
                        <i class="far fa-clock mr-1"></i> <?php echo $notification['formatted_date']; ?>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        <?php else: ?>
            <a class="dropdown-item d-flex align-items-center" href="#">
                <div class="mr-3">
                    <div class="icon-circle bg-light">
                        <i class="fas fa-info-circle text-info"></i>
                    </div>
                </div>
                <div>
                    <span class="font-weight-medium">No notifications</span>
                    <div class="small text-gray-500">You're all caught up!</div>
                </div>
            </a>
        <?php endif; ?>
        
        <a class="dropdown-item text-center small text-gray-800 view-all-link" href="all_notifications.php">
            <i class="fas fa-angle-right mr-1"></i> Show All Notifications
        </a>
    </div>
</li>

<!-- Add JavaScript to mark notifications as read -->
<script>
function markAsRead(notificationId) {
    // Use fetch API to mark notification as read
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    }).then(response => {
        // Notification will be marked as read
        console.log('Notification marked as read');
    }).catch(error => {
        console.error('Error marking notification as read:', error);
    });
}
</script>

<?php
// Helper functions for display
function getIconClass($status) {
    if (!$status) return 'bg-info';
    
    switch ($status) {
        case 'pending_approval': return 'bg-warning';
        case 'declined': return 'bg-danger';
        case 'approved': return 'bg-success';
        case 'in_process': return 'bg-primary';
        case 'ready_for_pickup': return 'bg-info';
        case 'completed': return 'bg-success';
        default: return 'bg-primary';
    }
}

function getIconType($status) {
    if (!$status) return 'fa-bell';
    
    switch ($status) {
        case 'pending_approval': return 'fa-clock';
        case 'declined': return 'fa-times-circle';
        case 'approved': return 'fa-check-circle';
        case 'in_process': return 'fa-spinner';
        case 'ready_for_pickup': return 'fa-box';
        case 'completed': return 'fa-check-double';
        default: return 'fa-clipboard-list';
    }
}
?>