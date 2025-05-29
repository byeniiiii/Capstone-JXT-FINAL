<?php
// filepath: c:\xampp\htdocs\jx_tailoring\manager\sidebar.php

// Determine which page is currently active
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" 
    style="background: linear-gradient(135deg, #2c3e50 0%, #1a2738 100%);">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <div class="sidebar-brand-icon">
            <i class="fas fa-scissors"></i>
        </div>
        <div class="sidebar-brand-text mx-3">JXT Tailoring</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Orders & Payments
    </div>

    <!-- Nav Item - Manage Orders -->
    <li class="nav-item <?php echo ($currentPage == 'manage_orders.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="manage_orders.php">
            <i class="fas fa-fw fa-tasks"></i>
            <span>Order Management</span>
        </a>
    </li>

    <!-- Nav Item - Payments -->
    <li class="nav-item <?php echo ($currentPage == 'manage_payments.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="manage_payments.php">
            <i class="fas fa-fw fa-money-bill-wave"></i>
            <span>Payment Records</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Business Analytics
    </div>

    <!-- Nav Item - Order Reports -->
    <li class="nav-item <?php echo ($currentPage == 'order_reports.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="order_reports.php">
            <i class="fas fa-clipboard-list"></i>
            <span>Order Reports</span>
        </a>
    </li>

    <!-- Nav Item - Sales Reports -->
    <li class="nav-item <?php echo ($currentPage == 'sales_report.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="sales_report.php">
            <i class="fas fa-chart-line"></i>
            <span>Sales Reports</span>
        </a>
    </li>

    <!-- Nav Item - Activity Logs -->
    <li class="nav-item <?php echo ($currentPage == 'activity_logs.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="activity_logs.php">
            <i class="fas fa-fw fa-history"></i>
            <span>Activity Logs</span>  
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Administration
    </div>

    <!-- Nav Item - Staff -->
    <li class="nav-item <?php echo ($currentPage == 'staff.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="logout.php">
            <i class="fas fa-fw fa-user-tie"></i>
            <span>Log Out</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle" 
                style="background-color: rgba(255,255,255,0.2);">
            <i class="fas fa-angle-left text-white"></i>
        </button>
    </div>

</ul>
<!-- End of Sidebar -->
