<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get the current file name
?>

<!-- Mobile Toggle Button - Moved outside container for visibility -->
<button id="sidebarToggleBtn" class="sidebar-toggle">
    <svg class="toggle-icon" viewBox="0 0 100 100" width="24">
        <path class="line line1" d="M 20,29.000046 H 80.000231 C 80.000231,29.000046 94.498839,28.817352 94.532987,66.711331 94.543142,77.980673 90.966081,81.670246 85.259173,81.668997 79.552261,81.667751 75.000211,74.999942 75.000211,74.999942 L 25.000021,25.000058" />
        <path class="line line2" d="M 20,50 H 80" />
        <path class="line line3" d="M 20,70.999954 H 80.000231 C 80.000231,70.999954 94.498839,71.182648 94.532987,33.288669 94.543142,22.019327 90.966081,18.329754 85.259173,18.331003 79.552261,18.332249 75.000211,25.000058 75.000211,25.000058 L 25.000021,74.999942" />
    </svg>
</button>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar-container">
    <!-- Main Sidebar -->
    <div class="neo-sidebar">
        <!-- Logo Area -->
        <div class="sidebar-header">
            <div class="logo-area">
                <div class="logo-icon">
                    <i class="fas fa-cut"></i>
                </div>
                <h2 class="logo-text">JX<span>Tailoring</span></h2>
            </div>
        </div>
        
        <!-- Close button for mobile -->
        <button id="sidebarCloseBtn" class="sidebar-close">
            <i class="fas fa-times"></i>
        </button>
        
        <!-- Menu Items -->
        <div class="sidebar-menu">
            <!-- Dashboard -->
            <a href="dashboard.php" class="menu-item <?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <span class="menu-text">Dashboard</span>
            </a>
            
            <!-- Section: Management -->
            <div class="menu-section">
                <span>Management</span>
            </div>
            
            <!-- Manage Orders -->
            <a href="orders.php" class="menu-item <?= ($current_page == 'orders.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <span class="menu-text">Manage Orders</span>
            </a>
            
            <!-- Manage Templates -->
            <a href="templates.php" class="menu-item <?= ($current_page == 'templates.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <span class="menu-text">Manage Templates</span>
            </a>
            
            <!-- Section: Transaction -->
            <div class="menu-section">
                <span>Transaction</span>
            </div>
            
            <!-- Order Reports -->
            <a href="order_reports.php" class="menu-item <?= ($current_page == 'order_reports.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <span class="menu-text">Order Report</span>
            </a>
            
            <!-- Transaction History
            <a href="transaction_history.php" class="menu-item <?= ($current_page == 'transaction_history.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="menu-text">Transaction History</span>
            </a>

-->
            
             <a href="Activity_logs.php" class="menu-item <?= ($current_page == 'Activity_logs.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="menu-text">Activity Logs</span>
            </a>
           
             <a href="Sales_report.php" class="menu-item <?= ($current_page == 'Sales_report.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="menu-text">Sales Reports</span>
            </a>
            <!-- Log Out -->
            <a href="logout.php" class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <span class="menu-text">Log Out</span>
            </a>
        </div>
        
        <!-- Copyright -->
        <div class="copyright">
            © 2025 JX Tailoring
        </div>
    </div>
</div>

<style>
    /* Base styling */
    :root {
        --primary-color: #FF7D00;
        --secondary-color: #1F1F1F;
        --background-color: #121212;
        --text-color: rgba(255, 255, 255, 0.85);
        --highlight-color: #FF7D00;
        --divider-color: rgba(255, 255, 255, 0.08);
        --heading-color: rgba(255, 255, 255, 0.4);
        --menu-item-hover-bg: rgba(255, 255, 255, 0.05);
        --menu-item-active-bg: rgba(255, 125, 0, 0.15);
        --menu-item-active-color: #FF7D00;
        --menu-item-hover-color: white;
        --menu-icon-hover-color: #FF7D00;
        --logout-item-bg: rgba(255, 125, 0, 0.08);
        --logout-item-hover-bg: rgba(255, 125, 0, 0.15);
        --logout-item-color: #FF7D00;
        --logout-item-hover-color: #FF7D00;
        --sidebar-footer-color: rgba(255, 255, 255, 0.3);
        --sidebar-overlay-bg: rgba(0, 0, 0, 0.5);
        --sidebar-overlay-blur: blur(2px);
    }

    /* Main Sidebar Styling */
    .sidebar-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;
        height: 100vh;
        background: var(--background-color);
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        z-index: 1040;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        overflow-y: auto;
        scrollbar-width: thin;
    }

    .neo-sidebar {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding-top: 10px;
    }

    /* Custom scrollbar */
    .sidebar-container::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar-container::-webkit-scrollbar-thumb {
        background-color: rgba(255, 125, 0, 0.3);
        border-radius: 4px;
    }

    /* Sidebar Toggle Animation */
    .sidebar-container.toggled {
        transform: translateX(-100%);
    }

    /* Mobile Close Button */
    .sidebar-close {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: rgba(255, 255, 255, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s;
        z-index: 10;
    }

    .sidebar-close:hover {
        background: rgba(255, 125, 0, 0.2);
        color: #fff;
        transform: rotate(90deg);
    }

    /* Sidebar Header & Logo */
    .sidebar-header {
        height: 70px;
        padding: 0;
        background: rgba(0, 0, 0, 0.2);
        overflow: hidden;
        position: relative;
        margin-top: 15px;
        margin-bottom: 20px;
    }

    .logo-area {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .logo-icon {
        width: 45px;
        height: 45px;
        background: var(--highlight-color);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        transition: all 0.4s ease;
        transform: rotate(0deg);
        box-shadow: 0 3px 10px rgba(255, 125, 0, 0.3);
    }

    .sidebar-header:hover .logo-icon {
        transform: rotate(360deg);
        box-shadow: 0 0 15px rgba(255, 125, 0, 0.7);
    }

    .logo-icon i {
        color: white;
        font-size: 1.5rem;
    }

    /* Animated shine effect on logo */
    .logo-icon:after {
        content: "";
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: rgba(255, 255, 255, 0.2);
        transform: rotate(45deg);
        transition: all 0.6s ease;
        opacity: 0;
    }

    .sidebar-header:hover .logo-icon:after {
        opacity: 1;
        top: 100%;
        left: 100%;
    }

    .logo-text {
        font-weight: 700;
        letter-spacing: 0.5px;
        font-size: 1.3rem;
    }

    .logo-text span {
        color: var(--highlight-color);
    }

    /* Dividers */
    .sidebar-divider {
        border-top: 1px solid var(--divider-color);
        margin: 0.8rem 1rem;
    }

    /* Headings */
    .menu-section {
        color: var(--heading-color);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 0.8rem 1rem 0.4rem;
        position: relative;
    }

    .menu-section span {
        position: relative;
        z-index: 1;
    }

    .menu-section:after {
        content: "";
        position: absolute;
        left: 1rem;
        bottom: 0.4rem;
        height: 6px;
        width: 30px;
        background: rgba(255, 125, 0, 0.1);
        z-index: 0;
        border-radius: 4px;
    }

    /* Menu Items */
    .menu-item {
        margin: 0.15rem 0.7rem;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        padding: 0.7rem 1rem;
        color: var(--text-color);
        text-decoration: none;
    }

    .menu-item:hover {
        background: var(--menu-item-hover-bg);
        color: var(--menu-item-hover-color);
    }

    .menu-item.active {
        background: var(--menu-item-active-bg);
        color: var(--menu-item-active-color);
        font-weight: 600;
    }

    .menu-icon {
        min-width: 25px;
        margin-right: 0.5rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .menu-item:hover .menu-icon {
        transform: translateX(3px);
        color: var(--menu-icon-hover-color);
    }

    /* Logout Item */
    .logout-item {
        margin: 1rem 0.7rem;
        border-radius: 8px;
        background: var(--logout-item-bg);
    }

    .logout-item .menu-item {
        color: var(--logout-item-color);
        opacity: 0.9;
    }

    .logout-item .menu-item:hover {
        opacity: 1;
        background: var(--logout-item-hover-bg);
    }

    .logout-item .menu-icon {
        transition: transform 0.3s ease;
    }

    .logout-item .menu-item:hover .menu-icon {
        transform: translateX(3px) rotate(-20deg);
    }

    /* Sidebar Footer */
    .copyright {
        padding: 0.75rem;
        font-size: 0.75rem;
        color: var(--sidebar-footer-color);
        text-align: center;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
    }

    .copyright p {
        margin: 0;
    }

    /* Burger Menu Button */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        width: 40px;
        height: 40px;
        background-color: #fff;
        border-radius: 50%;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        border: none;
        padding: 0;
        z-index: 1050;
        cursor: pointer;
        transition: all 0.3s;
    }

    .sidebar-toggle:hover {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        background-color: var(--highlight-color);
    }

    .sidebar-toggle:hover .toggle-icon .line {
        stroke: #fff;
    }

    /* Toggle Icon */
    .toggle-icon {
        width: 24px;
        height: 24px;
    }

    .line {
        fill: none;
        stroke: #333;
        stroke-width: 6;
        stroke-linecap: round;
        transition: stroke-dasharray 600ms cubic-bezier(0.4, 0, 0.2, 1),
            stroke-dashoffset 600ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .line1 {
        stroke-dasharray: 60 207;
    }

    .line2 {
        stroke-dasharray: 60 60;
    }

    .line3 {
        stroke-dasharray: 60 207;
    }

    .mobile-active .line1 {
        stroke-dasharray: 90 207;
        stroke-dashoffset: -134;
        stroke: var(--highlight-color);
    }

    .mobile-active .line2 {
        stroke-dasharray: 1 60;
        stroke-dashoffset: -30;
        stroke: var(--highlight-color);
    }

    .mobile-active .line3 {
        stroke-dasharray: 90 207;
        stroke-dashoffset: -134;
        stroke: var(--highlight-color);
    }

    /* Sidebar Overlay */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--sidebar-overlay-bg);
        backdrop-filter: var(--sidebar-overlay-blur);
        z-index: 1039;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }

    /* Content adjustment */
    #content-wrapper {
        margin-left: 250px;
        transition: margin 0.3s ease;
    }

    .sidebar-container.toggled ~ #content-wrapper {
        margin-left: 0;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-container {
            transform: translateX(-100%);
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.3);
        }

        .sidebar-container.active {
            transform: translateX(0);
        }

        /* Mobile specific adjustments */
        .mobile-active .sidebar-close {
            display: flex;
        }

        .mobile-active .sidebar-toggle {
            display: none;
        }

        /* Content wrapper adjustment for mobile */
        #content-wrapper {
            margin-left: 0 !important;
        }
    }
</style>

<!-- JavaScript for Responsive Sidebar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar-container');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const overlay = document.getElementById('sidebarOverlay');
    const container = document.body; // Using body as the container
    
    // Function to toggle sidebar
    function toggleSidebar() {
        container.classList.toggle('mobile-active');
        if (container.classList.contains('mobile-active')) {
            sidebar.classList.add('active');
            overlay.classList.add('active');
        } else {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    }
    
    // Function to close sidebar
    function closeSidebar() {
        container.classList.remove('mobile-active');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    }
    
    // Toggle sidebar when burger icon is clicked
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            toggleSidebar();
        });
    }
    
    // Close sidebar when close button is clicked
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            closeSidebar();
        });
    }
    
    // Close sidebar when clicking on overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            closeSidebar();
        });
    }
    
    // Check window width on load and resize
    function checkWindowSize() {
        if (window.innerWidth <= 768) {
            // Mobile view - start with sidebar closed
            sidebar.classList.remove('active');
            container.classList.remove('mobile-active');
            overlay.classList.remove('active');
        } else {
            // Desktop view - show sidebar
            sidebar.classList.add('active');
        }
    }
    
    // Check window size on page load
    checkWindowSize();
    
    // Listen for window resize
    window.addEventListener('resize', checkWindowSize);
    
    // Close sidebar when a menu item is clicked (on mobile)
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
});
</script>
