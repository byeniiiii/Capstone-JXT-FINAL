<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get the current file name
?>

<!-- Customer-Style Animated Sidebar -->
<div class="sidebar-container">
    <!-- Mobile Toggle Button -->
    <button id="sidebarToggleBtn" class="sidebar-toggle">
        <svg class="toggle-icon" viewBox="0 0 100 100" width="24">
            <path class="line line1" d="M 20,29.000046 H 80.000231 C 80.000231,29.000046 94.498839,28.817352 94.532987,66.711331 94.543142,77.980673 90.966081,81.670246 85.259173,81.668997 79.552261,81.667751 75.000211,74.999942 75.000211,74.999942 L 25.000021,25.000058" />
            <path class="line line2" d="M 20,50 H 80" />
            <path class="line line3" d="M 20,70.999954 H 80.000231 C 80.000231,70.999954 94.498839,71.182648 94.532987,33.288669 94.543142,22.019327 90.966081,18.329754 85.259173,18.331003 79.552261,18.332249 75.000211,25.000058 75.000211,25.000058 L 25.000021,74.999942" />
        </svg>
    </button>

    <!-- Main Sidebar Panel -->
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
                <span class="menu-highlight"></span>
            </a>
            
            <!-- Section: Management -->
            <div class="menu-section">
                <span>Management</span>
            </div>

            <!-- Place Order -->
            <a href="new_orders.php" class="menu-item <?= ($current_page == 'new_orders.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <span class="menu-text">Place Order</span>
                <span class="menu-highlight"></span>
            </a>
            
            <!-- Approve Orders -->
            <a href="orders.php" class="menu-item <?= ($current_page == 'orders.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="menu-text">Approve Orders</span>
                <span class="menu-highlight"></span>
            </a>

            <!-- Made-to-Order -->
            <a href="custom_orders.php" class="menu-item <?= ($current_page == 'custom_orders.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-tshirt"></i>
                </div>
                <span class="menu-text">Add Made-To-Order</span>
                <span class="menu-highlight"></span>
            </a>

             <!-- Sublimation Orders -->
            <a href="sublimation_orders.php" class="menu-item <?= ($current_page == 'sublimation_orders.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-print"></i>
                </div>
                <span class="menu-text">Sublimation Orders</span>
                <span class="menu-highlight"></span>
            </a>


             <!-- Tailoring Orders -->
            <a href="tailoring_orders.php" class="menu-item <?= ($current_page == 'tailoring_orders.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-cut"></i>
                </div>
                <span class="menu-text">Tailoring Orders</span>
                <span class="menu-highlight"></span>
            </a>
            
            
            <!-- Section: Transaction -->
            <div class="menu-section">
                <span>Transaction</span>
            </div>
            
            <!-- Order Reports -->
            <a href="order_reports.php" class="menu-item <?= ($current_page == 'order_reports.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <span class="menu-text">Order Reports</span>
                <span class="menu-highlight"></span>
            </a>
            
            <!-- Sales Report -->
           

            <!--  Notifications 
            <a href="notification.php" class="menu-item <?= ($current_page == 'sales_report.php') ? 'active' : ''; ?>">
                <div class="menu-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="menu-text">Notification</span>
                <span class="menu-highlight"></span>
            </a>
        </div>

-->
        
        
        <!-- Sidebar Footer with Logout -->
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Log Out</span>
            </a>
            <div class="copyright">© 2025 JX Tailoring</div>
        </div>
    </div>
    
    <!-- Overlay for mobile -->
    <div class="sidebar-overlay"></div>
</div>

<!-- Customer-Style CSS -->
<style>
:root {
    --sidebar-bg: #ffffff;
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 70px;
    --accent-color: #ff7d00;
    --text-color: #343a40;
    --text-muted: #6c757d;
    --hover-bg: #f8f9fa;
    --active-bg: rgba(255,125,0,0.1);
    --border-color: #e9ecef;
    --transition-speed: 0.3s;
    --border-radius: 8px;
    --icon-size: 18px;
    --shadow-color: rgba(0, 0, 0, 0.05);
}

/* Base Sidebar Container */
.sidebar-container {
    position: relative;
    height: 100%;
}

/* Main Sidebar Panel */
.neo-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: var(--sidebar-width);
    background: var(--sidebar-bg);
    color: var(--text-color);
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1000;
    transition: all var(--transition-speed) ease;
    box-shadow: 0 0 15px var(--shadow-color);
    padding: 0;
    border-right: 1px solid var(--border-color);
}

/* Custom Scrollbar */
.neo-sidebar::-webkit-scrollbar {
    width: 4px;
}

.neo-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.neo-sidebar::-webkit-scrollbar-thumb {
    background-color: #ddd;
    border-radius: 20px;
}

.neo-sidebar::-webkit-scrollbar-thumb:hover {
    background-color: #ccc;
}

/* Logo Area */
.sidebar-header {
    padding: 20px 15px;
    margin-bottom: 5px;
    position: relative;
    border-bottom: 1px solid var(--border-color);
}

.logo-area {
    display: flex;
    align-items: center;
}

.logo-icon {
    min-width: 40px;
    height: 40px;
    background: var(--accent-color);
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    transition: transform 0.5s ease;
}

.logo-icon i {
    font-size: 20px;
    color: white;
}

/* Logo Text */
.logo-text {
    color: var(--text-color);
    font-size: 1.3rem;
    font-weight: 600;
    margin: 0;
}

.logo-text span {
    color: var(--accent-color);
    font-weight: 700;
}

/* Menu Section */
.sidebar-menu {
    padding: 15px 0;
}

/* Section Label */
.menu-section {
    padding: 10px 20px 5px;
    color: var(--text-muted);
    font-size: 0.8rem;
    font-weight: 500;
    margin-top: 10px;
}

/* Menu Item */
.menu-item {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    text-decoration: none;
    color: var(--text-color);
    margin: 2px 8px;
    border-radius: var(--border-radius);
    transition: all 0.2s ease;
    position: relative;
}

.menu-item:hover {
    background: var(--hover-bg);
    color: var(--accent-color);
}

.menu-item.active {
    background: var(--active-bg);
    color: var(--accent-color);
    font-weight: 500;
}

.menu-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 35px;
    margin-right: 10px;
}

.menu-icon i {
    font-size: var(--icon-size);
    color: var(--text-muted);
    transition: all 0.3s ease;
}

.menu-item:hover .menu-icon i {
    color: var(--accent-color);
}

.menu-item.active .menu-icon i {
    color: var(--accent-color);
}

.menu-text {
    font-weight: 400;
    font-size: 0.95rem;
}

/* Footer */
.sidebar-footer {
    padding: 15px;
    border-top: 1px solid var(--border-color);
    margin-top: auto;
}

.logout-btn {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    border-radius: var(--border-radius);
    background: var(--hover-bg);
    color: var(--text-color);
    text-decoration: none;
    transition: all 0.2s ease;
    margin-bottom: 10px;
}

.logout-btn:hover {
    background: var(--active-bg);
    color: var(--accent-color);
}

.logout-btn i {
    margin-right: 10px;
}

.copyright {
    color: var(--text-muted);
    font-size: 0.7rem;
    text-align: center;
}

/* Toggle Button */
.sidebar-toggle {
    position: fixed;
    top: 15px;
    left: 15px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: white;
    display: none;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    z-index: 1001;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

/* Close button for mobile */
.sidebar-close {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: var(--hover-bg);
    display: none;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    color: var(--text-muted);
    z-index: 1001;
    transition: all 0.3s ease;
}

.sidebar-close:hover {
    background: var(--active-bg);
    color: var(--accent-color);
}

/* Overlay */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.3);
    z-index: 999;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}

/* Dropdown styles */
.dropdown-toggle {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    text-decoration: none;
    color: var(--text-color);
}

.dropdown-toggle .fa-chevron-down {
    font-size: 12px;
    transition: transform 0.3s ease;
    margin-left: 10px;
}

.dropdown-toggle[aria-expanded="true"] .fa-chevron-down {
    transform: rotate(180deg);
}

.sidebar-dropdown {
    position: static !important;
    background: transparent;
    border: none;
    padding: 0;
    margin: 0;
    width: 100%;
    transform: none !important;
}

.sidebar-dropdown .dropdown-item {
    padding: 8px 15px 8px 50px;
    color: var(--text-color);
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.sidebar-dropdown .dropdown-item:hover {
    background: var(--hover-bg);
    color: var(--accent-color);
}

.sidebar-dropdown .dropdown-item.active {
    background: var(--active-bg);
    color: var(--accent-color);
}

.sidebar-dropdown .dropdown-item i {
    width: 20px;
    text-align: center;
}

/* Mobile Styles */
@media (max-width: 768px) {
    .neo-sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar-toggle {
        display: flex;
    }
    
    .mobile-active .neo-sidebar {
        transform: translateX(0);
    }
    
    .mobile-active .sidebar-overlay {
        display: block;
        opacity: 1;
    }

    .mobile-active .sidebar-close {
        display: flex;
    }
    
    .mobile-active .sidebar-toggle {
        display: none;
    }
    
    #content-wrapper {
        margin-left: 0 !important;
        padding-top: 60px !important;
    }
}

/* Desktop Content Adjustment */
@media (min-width: 769px) {
    #content-wrapper {
        margin-left: var(--sidebar-width);
        transition: margin var(--transition-speed) ease;
    }
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.sidebar-container');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const overlay = document.querySelector('.sidebar-overlay');
    
    // Toggle sidebar on mobile
    toggleBtn.addEventListener('click', function() {
        container.classList.toggle('mobile-active');
    });
    
    // Close sidebar on mobile
    closeBtn.addEventListener('click', function() {
        container.classList.remove('mobile-active');
    });
    
    // Close sidebar when clicking overlay
    overlay.addEventListener('click', function() {
        container.classList.remove('mobile-active');
    });
    
    // Handle menu items on mobile
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                container.classList.remove('mobile-active');
            }
        });
    });
});
</script>

<!-- Add this script at the end of the file -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all menu items
    const menuItems = document.querySelectorAll('.menu-item');
    
    // Add click event listener to each menu item
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Prevent default behavior
            e.preventDefault();
            
            // Get the href attribute
            const href = this.getAttribute('href');
            
            // Navigate to the page
            window.location.href = href;
        });
    });
});
</script>
