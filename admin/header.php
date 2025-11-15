<?php
// This is the main bootstrap for the admin area
require_once 'bootstrap.php';
$current_page = basename($_SERVER['PHP_SELF']); // e.g., 'dashboard.php'
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-neutral-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Mossé Luxe Admin' : 'Mossé Luxe Admin'; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/tailwind_output.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Admin Theme Styles -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/admin_theme.css">
    <script>
        // Store dark mode preference
        if (localStorage.getItem('admin_dark_mode') === 'enabled') {
            document.documentElement.classList.add('admin-dark');
        }
    </script>
    <script>
        // Make CSRF token available globally for AJAX requests
        window.csrfToken = "<?php echo generate_csrf_token(); ?>";
        // Make SITE_URL available globally for AJAX requests
        window.SITE_URL = "<?php echo SITE_URL; ?>";

        // Basic toast notification function for admin panel
        function showAdminToast(message, type = 'info') {
            const toastContainer = document.getElementById('admin-toast-container') || document.createElement('div');
            if (toastContainer.id !== 'admin-toast-container') {
                toastContainer.id = 'admin-toast-container';
                Object.assign(toastContainer.style, {
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    zIndex: '9999',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '10px'
                });
                document.body.appendChild(toastContainer);
            }

            const toast = document.createElement('div');
            toast.className = `px-4 py-2 rounded-md shadow-md text-sm font-medium`;
            toast.style.cssText = `
                background-color: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#6B7280'};
                color: white;
                padding: 8px 16px;
                border-radius: 6px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                font-weight: 500;
                margin-bottom: 8px;
            `;
            toast.textContent = message;

            toastContainer.appendChild(toast);

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        }

        // Check for PHP session messages on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php
            if (isset($_SESSION['toast_message'])) {
                $message = addslashes($_SESSION['toast_message']['message']);
                $type = $_SESSION['toast_message']['type'];
                echo "showAdminToast('$message', '$type');\n";
                unset($_SESSION['toast_message']);
            }
            ?>

            // Restore sidebar scroll position
            const sidebarNav = document.querySelector('#sidebar nav');
            const savedScroll = sessionStorage.getItem('admin_sidebar_scroll');
            if (sidebarNav && savedScroll !== null) {
                sidebarNav.scrollTop = parseInt(savedScroll);
            }
        });

        // Save sidebar scroll position before navigating
        window.addEventListener('beforeunload', function() {
            const sidebarNav = document.querySelector('#sidebar nav');
            if (sidebarNav) {
                sessionStorage.setItem('admin_sidebar_scroll', sidebarNav.scrollTop);
            }
        });

        // Admin Dark Mode Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('dark-mode-toggle');

            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const html = document.documentElement;
                    const icon = this.querySelector('i');

                    if (html.classList.contains('admin-dark')) {
                        // Switch to light mode
                        html.classList.remove('admin-dark');
                        localStorage.setItem('admin_dark_mode', 'disabled');
                        if (icon) {
                            icon.className = 'fas fa-moon text-xl';
                        }
                    } else {
                        // Switch to dark mode
                        html.classList.add('admin-dark');
                        localStorage.setItem('admin_dark_mode', 'enabled');
                        if (icon) {
                            icon.className = 'fas fa-sun text-xl';
                        }
                    }
                });

                // Set initial button state based on current theme
                const html = document.documentElement;
                const icon = toggleButton.querySelector('i');

                if (html.classList.contains('admin-dark')) {
                    if (icon) {
                        icon.className = 'fas fa-sun text-xl';
                    }
                } else {
                    if (icon) {
                        icon.className = 'fas fa-moon text-xl';
                    }
                }
            }

            // Mobile Sidebar Toggle Functionality
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');

            if (sidebarToggle && sidebar) {
                // Toggle sidebar on mobile
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();

                    if (window.innerWidth < 1024) { // Only on mobile
                        sidebar.classList.toggle('sidebar-open-mobile');

                        // Prevent body scroll when sidebar is open on mobile
                        if (sidebar.classList.contains('sidebar-open-mobile')) {
                            document.body.style.overflow = 'hidden';
                            if (mainContent) {
                                mainContent.style.filter = 'blur(2px)';
                            }
                            // Force sidebar to be visible with inline styles (highest priority)
                            sidebar.style.transform = 'translateX(0)';
                        } else {
                            document.body.style.overflow = '';
                            if (mainContent) {
                                mainContent.style.filter = '';
                            }
                            // Reset sidebar transform with inline styles
                            sidebar.style.transform = 'translateX(-100%)';
                        }
                    }
                });

                // Close sidebar when clicking outside on mobile
                if (mainContent) {
                    mainContent.addEventListener('click', function() {
                        if (window.innerWidth < 1024 && sidebar.classList.contains('sidebar-open-mobile')) {
                            sidebar.classList.remove('sidebar-open-mobile');
                            document.body.style.overflow = '';
                            mainContent.style.filter = '';
                        }
                    });
                }

                // Close sidebar on window resize
                let resizeTimer;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(function() {
                        if (window.innerWidth >= 1024) {
                            // Reset mobile styles on desktop
                            sidebar.classList.remove('sidebar-open-mobile');
                            document.body.style.overflow = '';
                            if (mainContent) {
                                mainContent.style.filter = '';
                            }
                        }
                    }, 250);
                });
            }
        });
    </script>
</head>
<body class="h-full">
    <div id="admin-toast-container"></div>
    <div class="flex min-h-full">
        <!-- Responsive Sidebar -->
        <div id="sidebar" class="w-72 bg-white text-black flex flex-col shadow-2xl fixed inset-y-0 left-0 z-30 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
            <!-- Logo Section -->
            <div class="p-6 text-center border-b border-black/10 bg-white">
                <a href="../index.php" target="_blank" class="block group">
                    <img src="../assets/images/logo-dark.png" alt="Mossé Luxe" class="h-20 w-auto mx-auto mb-3 transition-transform group-hover:scale-105">
                    <h2 class="text-lg font-black uppercase tracking-widest text-black/90">Admin Panel</h2>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-grow px-4 py-6 space-y-8 overflow-y-auto">
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
                        <a href="announcement.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'announcement.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Manage website announcement bar">
                            <i class="fas fa-bullhorn w-4 h-4 mr-3"></i>
                            <span>Announcement Bar</span>
                        </a>
                        <a href="whatsapp.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'whatsapp.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Manage WhatsApp customer communication and support">
                            <i class="fab fa-whatsapp w-4 h-4 mr-3"></i>
                            <span>WhatsApp</span>
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
                        <a href="manage_homepage.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'manage_homepage.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Manage hero carousel slides">
                            <i class="fas fa-images w-4 h-4 mr-3"></i>
                            <span>Hero Carousel</span>
                        </a>
                        <a href="pages.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'pages.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Create and edit static pages">
                            <i class="fas fa-file-alt w-4 h-4 mr-3"></i>
                            <span>Pages</span>
                        </a>
                        <a href="shop_settings.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'shop_settings.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Manage shop page content and titles">
                            <i class="fas fa-shopping-bag w-4 h-4 mr-3"></i>
                            <span>Shop Settings</span>
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
                        Admin Settings
                    </h3>
                    <div class="space-y-1">
                        <a href="profile.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'profile.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Manage your admin profile">
                            <i class="fas fa-user-cog w-4 h-4 mr-3"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="manage_admins.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'manage_admins.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Create and manage admin logins and roles">
                            <i class="fas fa-user-shield w-4 h-4 mr-3"></i>
                            <span>Admins</span>
                        </a>
                        <a href="settings.php" class="sidebar-link nav-tooltip flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo ($current_page === 'settings.php') ? 'bg-gradient-to-r from-gold-500 to-gold-600 text-black shadow-md' : 'hover:bg-white/10'; ?>" data-tooltip="Configure system settings and preferences">
                            <i class="fas fa-sliders-h w-4 h-4 mr-3"></i>
                            <span>General Settings</span>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- User Info & Logout -->
            <div class="p-4 border-t border-black/10 bg-white">
                <div class="flex items-center mb-3 px-2">
                    <div class="w-8 h-8 bg-gold-500 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-black text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-black truncate"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></p>
                        <p class="text-xs text-gray-600">Administrator</p>
                    </div>
                </div>
                <div class="space-y-1">
                    <a href="profile.php" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 transition-all duration-200">
                        <i class="fas fa-user-cog w-4 h-4 mr-3"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="../logout.php" class="flex items-center px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-100 hover:text-red-800 transition-all duration-200">
                        <i class="fas fa-sign-out-alt w-4 h-4 mr-3"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div id="main-content" class="flex-1 flex flex-col lg:ml-72 transition-all duration-300 ease-in-out">
            <!-- Top bar -->
            <header class="bg-white shadow-sm p-4 flex justify-between items-center sticky top-0 z-20">
                <div class="flex items-center">
                    <!-- Mobile Menu Toggle -->
                    <button id="sidebar-toggle" class="lg:hidden text-gray-600 mr-4">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Dark Mode Toggle -->
                    <button id="dark-mode-toggle" class="dark-mode-toggle text-gray-600 hover:text-gray-800 transition-colors" aria-label="Toggle dark mode">
                        <i class="fas fa-moon text-xl"></i>
                    </button>
                    <span class="text-sm">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>!</span>
                </div>
            </header>
            <main class="flex-1 p-6 md:p-8 bg-neutral-100">
