<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get the current file name
?>

<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
    <!-- Sidebar Toggle Button (Always Visible on Mobile) -->
<button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" onclick="toggleSidebar()"> 
    <i class="fa fa-bars"></i>
</button>

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Tailoring<sup>mgt</sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>
    
    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Transactions</div>

    <!-- Nav Item - Approve Orders -->
    <li class="nav-item <?= ($current_page == 'approve_orders.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="approve_orders.php">
            <i class="fas fa-check-circle"></i>
            <span>Approve Orders</span>
            <?php
            // Count pending orders
            if(isset($conn)) {
                $pending_count_query = "SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending_approval'";
                $pending_result = $conn->query($pending_count_query);
                if ($pending_result && $row = $pending_result->fetch_assoc()) {
                    $pending_count = $row['count'];
                    if ($pending_count > 0) {
                        echo '<span class="badge badge-danger badge-counter">' . $pending_count . '</span>';
                    }
                }
            }
            ?>
        </a>
    </li>

    <li class="nav-item <?= ($current_page == 'approve_payments.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="approve_payments.php">
            <i class="fas fa-history"></i>
            <span>Approve Payments</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">User Management</div>

    <li class="nav-item <?= ($current_page == 'users.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="users.php">
            <i class="fas fa-user-alt"></i>
            <span>Manage Users</span>
        </a>
    </li>

    <li class="nav-item <?= ($current_page == 'sublimator.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="sublimator.php">
            <i class="fas fa-user-friends"></i>
            <span>Manage Sublimators</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Reports</div>

    <li class="nav-item <?= ($current_page == 'order_reports.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="order_reports.php">
            <i class="fas fa-shopping-cart"></i>
            <span>Order Reports</span>
        </a>
    </li>

    <li class="nav-item <?= ($current_page == 'activity_logs.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="activity_logs.php">
            <i class="fas fa-history"></i>
            <span>Activity Logs</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Nav Item - Log Out -->
    <li class="nav-item">
        <a class="nav-link" href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Log Out</span>
        </a>
    </li>

</ul>

<!-- JavaScript -->
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('accordionSidebar');
        sidebar.classList.toggle('toggled');

        const burgerMenu = document.getElementById('sidebarToggleTop');

        if (sidebar.classList.contains('toggled')) {
            // Sidebar is closed, show burger menu at the top
            burgerMenu.style.position = 'fixed';
            burgerMenu.style.top = '10px';
            burgerMenu.style.left = '10px';
            burgerMenu.style.zIndex = '1050'; // Ensure it's above everything
        } else {
            // Sidebar is open, reset burger menu position
            burgerMenu.style.position = 'fixed';
            burgerMenu.style.top = '10px';
            burgerMenu.style.left = '120px';
            burgerMenu.style.zIndex = '1050'; // Ensure it's above everything
        }
    }
</script>

<!-- CSS -->
<style>
    /* Sidebar Toggle Behavior */
    .sidebar.toggled {
        width: 0;
        overflow: hidden;
    }

    .sidebar {
        transition: all 0.3s ease;
        background-color: #451717 !important; /* Changed to deep burgundy color */
    }

    /* Make sure all sidebar elements use the new background color */
    .sidebar-dark .nav-item .nav-link,
    .sidebar-dark .sidebar-brand,
    .sidebar-dark .sidebar-heading {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .sidebar-dark .sidebar-divider {
        border-top: 1px solid rgba(255, 255, 255, 0.15);
    }

    /* Active state for the new color */
    .sidebar-dark .nav-item.active .nav-link {
        background-color: rgba(255, 255, 255, 0.2);
    }

    /* Fix Burger Menu Position */
    #sidebarToggleTop {
        transition: all 0.3s ease;
    }
</style>
