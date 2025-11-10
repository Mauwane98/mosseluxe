<?php
// Start session and check if admin is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-neutral-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Mossé Luxe Admin' : 'Mossé Luxe Admin'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'gold': {
                            400: '#C5A572',
                            500: '#B8955D',
                            600: '#A67C52'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Enhanced Admin Panel Styles */
        .sidebar-link {
            position: relative;
            overflow: hidden;
        }

        .sidebar-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(197, 165, 114, 0.2), transparent);
            transition: left 0.5s;
        }

        .sidebar-link:hover::before {
            left: 100%;
        }

        .sidebar-link:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .nav-tooltip {
            position: relative;
        }

        .nav-tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            margin-left: 10px;
            opacity: 0;
            animation: fadeInTooltip 0.3s ease forwards;
        }

        @keyframes fadeInTooltip {
            to {
                opacity: 1;
            }
        }

        .pulse-notification {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .sidebar-collapsed .sidebar-link span {
            opacity: 0;
            width: 0;
        }

        .sidebar-toggle-btn {
            transition: all 0.3s ease;
        }

        .sidebar-toggle-btn:hover {
            transform: rotate(180deg);
        }

        /* Enhanced form interactions */
        .form-group {
            position: relative;
        }

        .form-control:focus + .form-label,
        .form-control:not(:placeholder-shown) + .form-label {
            transform: translateY(-25px);
            font-size: 12px;
            color: #C5A572;
        }

        .form-label {
            position: absolute;
            top: 12px;
            left: 12px;
            transition: all 0.3s ease;
            pointer-events: none;
            color: #6c757d;
        }

        /* Loading states */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #C5A572;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Success/Error animations */
        .success-message {
            animation: slideInFromTop 0.5s ease;
        }

        .error-message {
            animation: shake 0.5s ease;
        }

        @keyframes slideInFromTop {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Keyboard navigation */
        .sidebar-link:focus {
            outline: 2px solid #C5A572;
            outline-offset: 2px;
        }

        /* Dark mode toggle */
        .dark-mode-toggle {
            position: relative;
            width: 50px;
            height: 24px;
            background: #ccc;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .dark-mode-toggle.active {
            background: #C5A572;
        }

        .dark-mode-toggle::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
        }

        .dark-mode-toggle.active::before {
            transform: translateX(26px);
        }

        /* Prevent unwanted scrolling on navigation links */
        html, body {
            scroll-behavior: auto; /* Override any smooth scrolling */
        }

        /* Ensure independent scrolling works properly */
        .admin-layout {
            height: 100vh;
            overflow: hidden;
        }

        .admin-sidebar {
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .admin-main {
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Prevent page jumps on link clicks */
        a[href^="#"] {
            scroll-behavior: auto;
        }

        /* Ensure pagination and navigation links don't cause scrolling */
        .pagination a,
        .nav-link,
        .sidebar-link {
            scroll-behavior: auto;
        }
    </style>
</head>
<body class="h-full">
    <div class="flex min-h-full">
        <!-- Modern Sidebar -->
        <div class="w-72 bg-gradient-to-b from-gray-900 via-black to-gray-900 text-white flex flex-col shadow-2xl overflow-y-auto">
            <!-- Logo Section -->
            <div class="p-8 text-center border-b border-white/10 bg-gradient-to-r from-black to-gray-800">
                <a href="../index.php" target="_blank" class="block group">
                    <img src="../assets/images/logo-light.png" alt="Mossé Luxe" class="h-20 w-auto mx-auto mb-3 transition-transform group-hover:scale-105">
                    <h2 class="text-lg font-black uppercase tracking-widest text-white/90">Admin Panel</h2>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-grow px-4 py-6 space-y-8">
                <!-- Dashboard -->
                <div>
                    <a href="dashboard.php" class="sidebar-link nav-tooltip flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 <?php echo ($current_page === 'dashboard.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-lg' : 'hover:bg-white/10'; ?>" data-tooltip="View dashboard overview and statistics">
                        <i class="fas fa-tachometer-alt w-5 h-5 mr-3"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <!-- Catalog Management -->
                <div>
                    <h3 class="text-xs font-bold text-gold-400 uppercase tracking-wider mb-3 px-4 flex items-center">
                        <i class="fas fa-boxes w-4 h-4 mr-2"></i>
                        Catalog
                    </h3>
                    <div class="space-y-1">
                        <a href="products.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'products.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Manage product inventory and details">
                            <i class="fas fa-cube w-4 h-4 mr-3"></i>
                            <span>Products</span>
                        </a>
                        <a href="categories.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'categories.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Organize products into categories">
                            <i class="fas fa-tags w-4 h-4 mr-3"></i>
                            <span>Categories</span>
                        </a>
                    </div>
                </div>

                <!-- Order Management -->
                <div>
                    <h3 class="text-xs font-bold text-gold-400 uppercase tracking-wider mb-3 px-4 flex items-center">
                        <i class="fas fa-shopping-cart w-4 h-4 mr-2"></i>
                        Orders
                    </h3>
                    <div class="space-y-1">
                        <a href="orders.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'orders.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="View and manage customer orders">
                            <i class="fas fa-list w-4 h-4 mr-3"></i>
                            <span>All Orders</span>
                        </a>
                    </div>
                </div>

                <!-- User Management -->
                <div>
                    <h3 class="text-xs font-bold text-gold-400 uppercase tracking-wider mb-3 px-4 flex items-center">
                        <i class="fas fa-users w-4 h-4 mr-2"></i>
                        Users
                    </h3>
                    <div class="space-y-1">
                        <a href="users.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'users.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Manage customer accounts and profiles">
                            <i class="fas fa-user-friends w-4 h-4 mr-3"></i>
                            <span>Customers</span>
                        </a>
                        <a href="manage_admins.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'manage_admins.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Manage administrator accounts">
                            <i class="fas fa-user-shield w-4 h-4 mr-3"></i>
                            <span>Admins</span>
                        </a>
                        <a href="manage_reviews.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'manage_reviews.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Moderate and manage product reviews">
                            <i class="fas fa-star w-4 h-4 mr-3"></i>
                            <span>Reviews</span>
                        </a>
                        <a href="manage_subscriptions.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'manage_subscriptions.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Manage newsletter subscriptions">
                            <i class="fas fa-envelope w-4 h-4 mr-3"></i>
                            <span>Subscriptions</span>
                        </a>
                    </div>
                </div>

                <!-- Marketing -->
                <div>
                    <h3 class="text-xs font-bold text-gold-400 uppercase tracking-wider mb-3 px-4 flex items-center">
                        <i class="fas fa-bullhorn w-4 h-4 mr-2"></i>
                        Marketing
                    </h3>
                    <div class="space-y-1">
                        <a href="manage_discounts.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'manage_discounts.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Create and manage discount codes">
                            <i class="fas fa-percent w-4 h-4 mr-3"></i>
                            <span>Discounts</span>
                        </a>
                        <a href="launching_soon.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'launching_soon.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Manage upcoming product launches">
                            <i class="fas fa-rocket w-4 h-4 mr-3"></i>
                            <span>Launching Soon</span>
                        </a>
                        <a href="new_arrivals.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'new_arrivals.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Manage homepage new arrivals section">
                            <i class="fas fa-star w-4 h-4 mr-3"></i>
                            <span>New Arrivals</span>
                        </a>
                    </div>
                </div>

                <!-- Content Management -->
                <div>
                    <h3 class="text-xs font-bold text-gold-400 uppercase tracking-wider mb-3 px-4 flex items-center">
                        <i class="fas fa-edit w-4 h-4 mr-2"></i>
                        Content
                    </h3>
                    <div class="space-y-1">
                        <a href="manage_homepage.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'manage_homepage.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Customize homepage content and layout">
                            <i class="fas fa-home w-4 h-4 mr-3"></i>
                            <span>Homepage</span>
                        </a>
                        <a href="pages.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'pages.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Create and edit static pages">
                            <i class="fas fa-file-alt w-4 h-4 mr-3"></i>
                            <span>Pages</span>
                        </a>
                        <a href="messages.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'messages.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="View and respond to customer messages">
                            <i class="fas fa-inbox w-4 h-4 mr-3"></i>
                            <span>Messages</span>
                        </a>
                    </div>
                </div>

                <!-- Reports -->
                <div>
                    <h3 class="text-xs font-bold text-gold-400 uppercase tracking-wider mb-3 px-4 flex items-center">
                        <i class="fas fa-chart-bar w-4 h-4 mr-2"></i>
                        Reports
                    </h3>
                    <div class="space-y-1">
                        <a href="sales_report.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'sales_report.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="View detailed sales analytics and reports">
                            <i class="fas fa-chart-line w-4 h-4 mr-3"></i>
                            <span>Sales Report</span>
                        </a>
                    </div>
                </div>

                <!-- Settings -->
                <div>
                    <h3 class="text-xs font-bold text-gold-400 uppercase tracking-wider mb-3 px-4 flex items-center">
                        <i class="fas fa-cog w-4 h-4 mr-2"></i>
                        Settings
                    </h3>
                    <div class="space-y-1">
                        <a href="settings.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'settings.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Configure system settings and preferences">
                            <i class="fas fa-sliders-h w-4 h-4 mr-3"></i>
                            <span>General Settings</span>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- User Info & Logout -->
            <div class="p-4 border-t border-white/10 bg-gradient-to-r from-gray-800 to-black">
                <div class="flex items-center mb-3 px-2">
                    <div class="w-8 h-8 bg-gold-500 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-black text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
                        <p class="text-xs text-gray-400">Administrator</p>
                    </div>
                </div>
                <a href="logout.php" class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:bg-red-500 hover:text-white transition-all duration-200">
                    <i class="fas fa-sign-out-alt w-4 h-4 mr-3"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-y-auto">
            <!-- Top bar -->
            <header class="bg-white shadow-sm p-4 flex justify-between items-center flex-shrink-0">
                <h1 class="text-xl font-bold"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h1>
                <div>
                    <span class="text-sm">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</span>
                </div>
            </header>
            <main class="flex-1 p-6 md:p-8 overflow-y-auto">

    <script>
        // Enhanced Admin Panel JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + D for Dashboard
                if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                    e.preventDefault();
                    window.location.href = 'dashboard.php';
                }
                // Ctrl/Cmd + P for Products
                if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                    e.preventDefault();
                    window.location.href = 'products.php';
                }
                // Ctrl/Cmd + O for Orders
                if ((e.ctrlKey || e.metaKey) && e.key === 'o') {
                    e.preventDefault();
                    window.location.href = 'orders.php';
                }
                // Ctrl/Cmd + U for Users
                if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
                    e.preventDefault();
                    window.location.href = 'users.php';
                }
                // Ctrl/Cmd + S for Settings
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    window.location.href = 'settings.php';
                }
                // Escape to close modals (if any)
                if (e.key === 'Escape') {
                    const modals = document.querySelectorAll('.modal');
                    modals.forEach(modal => modal.style.display = 'none');
                }
            });

            // Loading states for forms
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

                        // Show loading overlay
                        const overlay = document.createElement('div');
                        overlay.className = 'loading-overlay';
                        overlay.innerHTML = '<div class="loading-spinner"></div>';
                        document.body.appendChild(overlay);
                    }
                });
            });

            // Enhanced form interactions
            const formControls = document.querySelectorAll('.form-control');
            formControls.forEach(control => {
                control.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                control.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });

            // Success/Error message animations
            const successMessages = document.querySelectorAll('.success-message');
            const errorMessages = document.querySelectorAll('.error-message');

            successMessages.forEach(msg => {
                setTimeout(() => msg.style.opacity = '0', 3000);
            });

            errorMessages.forEach(msg => {
                setTimeout(() => msg.style.opacity = '0', 5000);
            });

            // Table row hover effects
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                    this.style.transform = 'scale(1.01)';
                    this.style.transition = 'all 0.2s ease';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                    this.style.transform = 'scale(1)';
                });
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert, .bg-green-100, .bg-red-100');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });

            // Confirm delete actions
            const deleteButtons = document.querySelectorAll('button[onclick*="delete"], a[href*="delete"]');
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                        e.preventDefault();
                        return false;
                    }
                });
            });

            // Enhanced search functionality
            const searchInputs = document.querySelectorAll('input[type="search"], input[name="q"]');
            searchInputs.forEach(input => {
                let searchTimeout;
                input.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        // Add loading state to search
                        const searchBtn = input.form.querySelector('button[type="submit"]');
                        if (searchBtn) {
                            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Searching...';
                            searchBtn.disabled = true;

                            setTimeout(() => {
                                searchBtn.innerHTML = 'Search';
                                searchBtn.disabled = false;
                            }, 1000);
                        }
                    }, 300);
                });
            });

            // Responsive sidebar toggle
            const sidebar = document.querySelector('.w-72');
            const mainContent = document.querySelector('.flex-1');

            function toggleSidebar() {
                sidebar.classList.toggle('hidden');
                mainContent.classList.toggle('ml-0');
            }

            // Add toggle button for mobile (if needed)
            if (window.innerWidth < 768) {
                const toggleBtn = document.createElement('button');
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                toggleBtn.className = 'sidebar-toggle-btn fixed top-4 left-4 z-50 bg-black text-white p-2 rounded-md md:hidden';
                toggleBtn.addEventListener('click', toggleSidebar);
                document.body.appendChild(toggleBtn);
            }

            // Dark mode toggle (placeholder for future implementation)
            function toggleDarkMode() {
                document.body.classList.toggle('dark');
                const toggle = document.querySelector('.dark-mode-toggle');
                if (toggle) {
                    toggle.classList.toggle('active');
                }
                localStorage.setItem('darkMode', document.body.classList.contains('dark'));
            }

            // Load dark mode preference
            if (localStorage.getItem('darkMode') === 'true') {
                document.body.classList.add('dark');
                const toggle = document.querySelector('.dark-mode-toggle');
                if (toggle) toggle.classList.add('active');
            }

            // Add notification pulse to unread messages
            const messageLinks = document.querySelectorAll('a[href*="messages.php"]');
            messageLinks.forEach(link => {
                // Add pulse animation if there are unread messages (you can implement this logic)
                if (Math.random() > 0.7) { // Placeholder - replace with actual unread check
                    link.classList.add('pulse-notification');
                }
            });

            // Prevent unwanted page scrolling on navigation
            const allLinks = document.querySelectorAll('a[href]');
            allLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Only prevent default for pagination and internal navigation links
                    const href = this.getAttribute('href');
                    if (href && (href.includes('page=') || href.includes('?') && !href.includes('http') && !href.includes('.php'))) {
                        // For pagination and filter links, don't prevent default behavior
                        // but ensure smooth experience
                        return;
                    }

                    // For other links, ensure no unwanted scrolling
                    if (href && href.startsWith('#')) {
                        e.preventDefault();
                        const target = document.querySelector(href);
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                });
            });

            // Ensure pagination links don't cause unwanted scrolling
            const paginationLinks = document.querySelectorAll('.pagination a, a[href*="page="]');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Allow normal navigation for pagination
                    // Add loading state
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>' + originalText;
                    this.style.pointerEvents = 'none';

                    // Re-enable after navigation (this won't execute if page changes)
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = 'auto';
                    }, 2000);
                });
            });

            // Prevent form submissions from causing page jumps
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    // Add loading state to submit button
                    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

                        // Re-enable after timeout (in case of error)
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }, 5000);
                    }
                });
            });

            // Enhanced button interactions
            const buttons = document.querySelectorAll('button, .btn, input[type="submit"]');
            buttons.forEach(btn => {
                btn.addEventListener('mousedown', function() {
                    this.style.transform = 'scale(0.98)';
                });
                btn.addEventListener('mouseup', function() {
                    this.style.transform = 'scale(1)';
                });
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Auto-resize textareas
            const textareas = document.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = this.scrollHeight + 'px';
                });
            });

            // Print functionality for reports
            const printButtons = document.querySelectorAll('.print-btn, button[onclick*="print"]');
            printButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    window.print();
                });
            });

            // Export functionality enhancement
            const exportButtons = document.querySelectorAll('.export-btn, a[href*="export"]');
            exportButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Show loading state
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exporting...';
                    this.disabled = true;

                    // Reset after 3 seconds (placeholder - actual export would handle this)
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 3000);
                });
            });
        });

        // Utility functions
        function showLoading() {
            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = '<div class="loading-spinner"></div>';
            overlay.id = 'global-loading';
            document.body.appendChild(overlay);
        }

        function hideLoading() {
            const overlay = document.getElementById('global-loading');
            if (overlay) overlay.remove();
        }

        function showSuccess(message) {
            const alert = document.createElement('div');
            alert.className = 'success-message fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            alert.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + message;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        }

        function showError(message) {
            const alert = document.createElement('div');
            alert.className = 'error-message fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            alert.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + message;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        }
    </script>
