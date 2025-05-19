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

    <li class="nav-item <?= ($current_page == 'sales_report.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="sales_report.php">
            <i class="fas fa-fw fa-chart-area"></i>
            <span>Sales Report</span>
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
    }

    /* Fix Burger Menu Position */
    #sidebarToggleTop {
        transition: all 0.3s ease;
    }
</style>
