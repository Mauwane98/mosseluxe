<?php
// Prevent caching of the header
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Luxury streetwear and fashion accessories crafted with precision and heritage. Discover our curated collection of high-end leather goods and apparel.">
    <meta name="keywords" content="luxury streetwear, leather goods, fashion accessories, streetwear fashion, luxury apparel, Mossé Luxe">
    <meta name="author" content="Mossé Luxe">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo rtrim(SITE_URL, '/') . ($_SERVER['REQUEST_URI'] ?? '/'); ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo rtrim(SITE_URL, '/') . ($_SERVER['REQUEST_URI'] ?? '/'); ?>">
    <meta property="og:title" content="<?php echo isset($pageTitle) ? $pageTitle : 'Mossé Luxe - Luxury Streetwear'; ?>">
    <meta property="og:description" content="Luxury streetwear and fashion accessories crafted with precision and heritage. Discover our curated collection.">
    <meta property="og:image" content="<?php echo SITE_URL; ?>assets/images/logo.png">
    <meta property="og:site_name" content="Mossé Luxe">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $current_full_url; ?>">
    <meta property="twitter:title" content="<?php echo isset($pageTitle) ? $pageTitle : 'Mossé Luxe - Luxury Streetwear'; ?>">
    <meta property="twitter:description" content="Luxury streetwear and fashion accessories crafted with precision and heritage.">
    <meta property="twitter:image" content="<?php echo SITE_URL; ?>assets/images/logo.png">

    <title><?php echo isset($pageTitle) ? $pageTitle : 'Mossé Luxe - Luxury Streetwear'; ?></title>
    

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/tailwind_output.css">
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/custom.css">
    <!-- Accessibility Stylesheet -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/accessibility.css">
    <!-- Interactive Features Stylesheet -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/interactive-features.css">
    <script>
        // Make SITE_URL available globally for AJAX requests
        window.SITE_URL = "<?php echo SITE_URL; ?>";
        // Make CSRF token available globally for AJAX requests
        // Token is already set in bootstrap.php session regeneration
        window.csrfToken = "<?php echo $_SESSION['csrf_token'] ?? ''; ?>";
        // WhatsApp Number
        window.whatsappNumber = "<?php echo (isset($whatsapp_number) && $whatsapp_number) ? ltrim($whatsapp_number, '+') : '27676162809'; ?>";
    </script>

    <!-- Scroll Position Retention -->
    <script src="<?php echo SITE_URL; ?>assets/js/scroll-retention.js" defer></script>


    <!-- Structured Data / JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Mossé Luxe",
      "url": "<?php echo SITE_URL; ?>",
      "logo": "<?php echo SITE_URL; ?>assets/images/logo.png",
      "description": "Luxury streetwear and fashion accessories crafted with precision and heritage",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Pretoria",
        "addressCountry": "ZA"
      },
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "<?php echo CONTACT_PHONE; ?>",
        "contactType": "customer service"
      },
      "sameAs": [
        "https://instagram.com/mosseluxe",
        "https://twitter.com/mosseluxe",
        "https://www.facebook.com/mosseluxe"
      ]
    }
    </script>
</head>
<body class="bg-white text-black antialiased">

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu" class="fixed inset-0 bg-white z-[100] p-6 transform -translate-x-full transition-transform duration-300 ease-in-out md:hidden">
        <div class="flex justify-between items-center mb-10">
            <!-- Logo in Menu -->
            <a href="<?php echo SITE_URL; ?>" aria-label="Homepage">
                <img src="<?php echo SITE_URL; ?>assets/images/logo-dark.png" alt="Mossé Luxe" class="h-32 w-auto" width="200" height="200">
            </a>
            <!-- Close Button -->
            <button id="close-menu-btn" aria-label="Close menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <!-- Mobile Nav Links -->
        <nav class="flex flex-col space-y-5">
            <a href="<?php echo SITE_URL; ?>" class="nav-link text-2xl font-bold uppercase tracking-wider">Home</a>
            <a href="<?php echo SITE_URL; ?>shop" class="nav-link text-2xl font-bold uppercase tracking-wider">Shop</a>
            <a href="<?php echo SITE_URL; ?>about" class="nav-link text-2xl font-bold uppercase tracking-wider">About</a>
            <a href="<?php echo SITE_URL; ?>contact" class="nav-link text-2xl font-bold uppercase tracking-wider">Contact</a>
            <a href="<?php echo SITE_URL; ?>my_account" class="nav-link text-2xl font-bold uppercase tracking-wider">Account</a>
            <a href="<?php echo SITE_URL; ?>wishlist" class="nav-link text-2xl font-bold uppercase tracking-wider">Wishlist</a>
        </nav>
    </div>

    <!-- Search Overlay -->
    <div id="search-overlay" class="fixed inset-0 bg-white/90 z-[90] p-6 hidden flex-col items-center backdrop-blur-sm">
        <button id="close-search-btn" class="absolute top-6 right-6 text-black" aria-label="Close search">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <div class="w-full max-w-2xl mt-24">
            <form action="<?php echo SITE_URL; ?>search" method="GET" class="w-full">
                <input type="search" name="q" placeholder="Search for products..." aria-label="Search for products" class="w-full p-4 text-lg bg-white border border-black rounded-md text-black placeholder-black/50 focus:outline-none focus:ring-2 focus:ring-black">
                <button type="submit" aria-label="Submit search" class="absolute right-4 top-1/2 -translate-y-1/2 text-black hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </button>
            </form>
        </div>
    </div>

    <?php
    // Load WhatsApp settings dynamically
    $whatsapp_settings = [];
    $settings_query = "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'whatsapp_%'";
    $settings_stmt = get_db_connection()->prepare($settings_query);
    if ($settings_stmt) {
        $settings_stmt->execute();
        $result = $settings_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $whatsapp_settings[$row['setting_key']] = $row['setting_value'];
        }
        $settings_stmt->close();
    }

    $whatsapp_enabled = isset($whatsapp_settings['whatsapp_enabled']) && $whatsapp_settings['whatsapp_enabled'] == '1';
    $whatsapp_number = $whatsapp_settings['whatsapp_number'] ?? '+27676162809';
    $general_message = $whatsapp_settings['whatsapp_general_message'] ?? 'Hi! I\'m interested in your luxury streetwear collection. Can you tell me more about your latest arrivals?';
    $order_message = $whatsapp_settings['whatsapp_order_message'] ?? 'Hi! I need help with my order. Can you assist me with shipping details or order tracking?';
    $size_message = $whatsapp_settings['whatsapp_size_message'] ?? 'Hi! I\'m not sure about the sizing for your leather goods. Can you help me find the perfect fit and share your size guide?';

    ?>

    <!-- Combined Cart & WhatsApp Sidebar -->
    <div id="cart-sidebar" class="fixed top-0 right-0 h-full w-full max-w-md bg-white z-[100] border-l border-black/10 shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300 ease-in-out">

        <!-- Tab Header -->
        <div class="flex border-b border-black/10 bg-gray-50">
            <button id="cart-tab" class="flex-1 py-4 px-6 text-center font-semibold uppercase tracking-wider border-b-2 border-black text-black transition-colors active-tab">
                <i class="fas fa-shopping-cart mr-2"></i>My Cart
            </button>
            <?php if ($whatsapp_enabled): ?>
            <button id="whatsapp-tab" class="flex-1 py-4 px-6 text-center font-semibold uppercase tracking-wider text-black/60 hover:text-black transition-colors">
                <i class="fab fa-whatsapp mr-2"></i>Chat
            </button>
            <?php endif; ?>
            <button id="close-cart-btn" class="px-4 py-4 text-black/60 hover:text-black transition-colors" aria-label="Close sidebar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Tab Content Container -->
        <div class="flex-1 overflow-hidden">

            <!-- Cart Tab Content -->
            <div id="cart-content" class="h-full p-6 flex flex-col tab-content active">
                <!-- Cart Content -->
                <div id="cart-items-container" class="flex-grow flex items-center justify-center text-black/50">
                    <!-- Cart items will be dynamically injected here. The 'p' tag below is a placeholder. -->
                    <p id="empty-cart-message">Your cart is empty.</p>
                </div>
                <!-- Cart Footer -->
                <div class="border-t border-black/10 pt-6 space-y-4">
                    <div class="flex justify-between font-bold text-lg">
                        <span>Subtotal</span>
                        <span id="cart-subtotal">R 0.00</span>
                    </div>
                    <a href="<?php echo SITE_URL; ?>checkout" class="block w-full text-center bg-black text-white py-3 px-6 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">
                        Checkout
                    </a>
                </div>
            </div>

            <!-- WhatsApp Tab Content -->
            <div id="whatsapp-content" class="h-full p-6 flex flex-col tab-content">
                <!-- WhatsApp Header -->
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fab fa-whatsapp text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-lg font-bold">Chat with Mossé Luxe</h3>
                    <p class="text-sm text-black/60">We're here to help!</p>
                </div>

                <!-- Quick Actions -->
                <div class="space-y-4 mb-6">
                    <button onclick="openWhatsAppGeneral()"
                            class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-3">
                        <i class="fab fa-whatsapp"></i>
                        General Inquiry
                    </button>
                    <button onclick="openWhatsAppOrder()"
                            class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-3">
                        <i class="fas fa-shopping-cart"></i>
                        Order Support
                    </button>
                    <button onclick="openWhatsAppSize()"
                            class="w-full py-3 px-4 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-3">
                        <i class="fas fa-ruler"></i>
                        Size Guide
                    </button>
                </div>

                <!-- Current WhatsApp Status -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm font-medium text-green-800">Online</span>
                    </div>
                    <p class="text-xs text-green-700">Usually replies instantly</p>
                </div>

                <!-- Phone Contact -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium">Need immediate help?</p>
                            <p class="text-xs text-gray-600">Call our support team</p>
                        </div>
                        <a href="tel:<?php echo htmlspecialchars($whatsapp_number); ?>"
                           class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                            <i class="fas fa-phone"></i>
                            Call Now
                        </a>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500 phone-number-display">WhatsApp: <strong><?php echo htmlspecialchars($whatsapp_number); ?></strong></p>
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Sidebar Styles -->
    <style>
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .active-tab { background: white; border-bottom-color: #25D366 !important; }

    /* Enhanced sidebar animations */
    #cart-sidebar {
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Tab animations */
    .tab-content {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>

    <!-- Tab Switching JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const cartTab = document.getElementById('cart-tab');
        const whatsappTab = document.getElementById('whatsapp-tab');
        const cartContent = document.getElementById('cart-content');
        const whatsappContent = document.getElementById('whatsapp-content');

        // Tab switching functionality
        cartTab.addEventListener('click', function() {
            cartTab.classList.add('active-tab');
            if (whatsappTab) whatsappTab.classList.remove('active-tab');
            cartContent.classList.add('active');
            if (whatsappContent) whatsappContent.classList.remove('active');
        });

        if (whatsappTab) {
            whatsappTab.addEventListener('click', function() {
                whatsappTab.classList.add('active-tab');
                cartTab.classList.remove('active-tab');
                whatsappContent.classList.add('active');
                cartContent.classList.remove('active');
            });
        }

        // Enhanced sidebar control functions
        window.toggleCart = function() {
            const sidebar = document.getElementById('cart-sidebar');
            const overlay = document.getElementById('cart-sidebar-overlay');

            if (sidebar.classList.contains('translate-x-full')) {
                // Open sidebar
                sidebar.classList.remove('translate-x-full');
                if (overlay) overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                // Close sidebar
                sidebar.classList.add('translate-x-full');
                if (overlay) overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        };

        // Enhanced close button functionality
        document.getElementById('close-cart-btn').addEventListener('click', function() {
            toggleCart();
        });

        // Update cart tab to show "Open Cart" by default
        const openCartBtn = document.getElementById('open-cart-btn');
        if (openCartBtn) {
            openCartBtn.addEventListener('click', function() {
                // Switch to cart tab when opening
                cartTab.click();
                toggleCart();
            });
        }

        // Enhanced WhatsApp functions for sidebar
        window.openWhatsAppGeneral = function() {
            const message = "<?php echo addslashes($general_message); ?>";
            const whatsappUrl = `https://wa.me/<?php echo ltrim($whatsapp_number, '+'); ?>?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
            toggleCart(); // Close sidebar
        };

        window.openWhatsAppOrder = function() {
            const message = "<?php echo addslashes($order_message); ?>";
            const whatsappUrl = `https://wa.me/<?php echo ltrim($whatsapp_number, '+'); ?>?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
            toggleCart(); // Close sidebar
        };

        window.openWhatsAppSize = function() {
            const message = "<?php echo addslashes($size_message); ?>";
            const whatsappUrl = `https://wa.me/<?php echo ltrim($whatsapp_number, '+'); ?>?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
            toggleCart(); // Close sidebar
        };

        window.openWhatsAppFromSidebar = function(action) {
            window.toggleCart(); // Close sidebar
            // The specific functions will be called by the buttons
        };

        // Update the bottom phone number display with dynamic number
        window.addEventListener('load', function() {
            const phoneDisplays = document.querySelectorAll('.phone-number-display');
            phoneDisplays.forEach(el => {
                el.textContent = '<?php echo htmlspecialchars($whatsapp_number); ?>';
            });
        });
    });

    // Make toggleCart globally available for the cart button
    window.toggleCart = function() {
        const sidebar = document.getElementById('cart-sidebar');
        if (sidebar) {
            if (sidebar.classList.contains('translate-x-full')) {
                // Open sidebar
                sidebar.classList.remove('translate-x-full');
                // Switch to cart tab when opening
                const cartTab = document.getElementById('cart-tab');
                if (cartTab) cartTab.click();
            } else {
                // Close sidebar
                sidebar.classList.add('translate-x-full');
            }
        }
    };
    </script>

    <!-- Fixed Header Container -->
    <div id="fixed-header-container" class="fixed top-0 left-0 w-full z-[60] flex flex-col">
        <!-- Announcement Bar (Dynamically loaded from settings) -->
        <?php
        // Load announcement settings
        $announcement_settings = [];
        $settings_query = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('announcement_text', 'announcement_url', 'announcement_enabled')";
        $settings_stmt = get_db_connection()->prepare($settings_query);
        if ($settings_stmt) {
            $settings_stmt->execute();
            $result = $settings_stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $announcement_settings[$row['setting_key']] = $row['setting_value'];
            }
            $settings_stmt->close();
        }

        if (isset($announcement_settings['announcement_enabled']) && $announcement_settings['announcement_enabled'] == '1'):
            $announcement_text = $announcement_settings['announcement_text'] ?? 'Join The List & Receive 10% Off Your First Order.';
            $announcement_url = $announcement_settings['announcement_url'] ?? '#newsletter-signup';
        ?>
        <a href="<?php echo htmlspecialchars($announcement_url); ?>" id="announcement-bar" class="w-full bg-black text-white text-center p-2.5 text-sm font-semibold tracking-wider uppercase block hover:bg-black/80 transition-colors">
            <?php echo htmlspecialchars($announcement_text); ?>
        </a>
        <?php endif; ?>

        <!-- Header -->
        <header id="main-header" class="left-0 w-full transition-all duration-300 ease-in-out bg-white text-black border-b border-black/10">
            <nav class="navbar-scroll container mx-auto px-4 md:px-6 py-10 flex justify-between items-center">
                        <!-- Mobile Menu Button -->
                        <button id="open-menu-btn" class="md:hidden" aria-label="Open menu">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                        </button>
        
                        <!-- Desktop Nav (Left) -->
                        <div class="hidden md:flex items-center gap-8">
                                        <a href="<?php echo SITE_URL; ?>" class="nav-link text-sm font-semibold uppercase tracking-wider hover:text-gray-700 transition-colors">Home</a>
                                        <a href="<?php echo SITE_URL; ?>shop" class="nav-link text-sm font-semibold uppercase tracking-wider hover:text-gray-700 transition-colors">Shop</a>
                                        <a href="<?php echo SITE_URL; ?>about" class="nav-link text-sm font-semibold uppercase tracking-wider hover:text-gray-700 transition-colors">About</a>
                                        <a href="<?php echo SITE_URL; ?>contact" class="nav-link text-sm font-semibold uppercase tracking-wider hover:text-gray-700 transition-colors">Contact</a>
                                                                </div>
        
                        <!-- Logo (Center) -->
                        <div class="absolute left-1/2 -translate-x-1/2">
                            <a href="<?php echo SITE_URL; ?>" aria-label="Homepage">
                                <img id="header-logo" src="<?php echo SITE_URL; ?>assets/images/logo-dark.png" alt="Mossé Luxe" class="h-32 md:h-40 w-auto" width="1000" height="1000">
                            </a>
                        </div>
        
                        <!-- Icons (Right) -->
                        <div class="flex items-center gap-4 md:gap-6">
                            <button id="open-search-btn" aria-label="Search">
                                <svg class="w-5 h-5 md:w-6 md:h-6 hover:text-gray-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </button>
                            <a href="<?php echo SITE_URL; ?>wishlist.php" class="hidden md:block" aria-label="Wishlist">
                                <svg class="w-6 h-6 hover:text-gray-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                            </a>
                            <a href="<?php echo SITE_URL; ?>my_account.php" class="hidden md:block" aria-label="My Account">
                                <svg class="w-6 h-6 hover:text-gray-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </a>
                            <button id="open-cart-btn" class="relative" aria-label="Open cart">
                                <svg class="w-6 h-6 hover:text-gray-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <span id="cart-count" class="absolute -top-2 -right-2 bg-black text-white text-xs font-bold w-4 h-4 rounded-full flex items-center justify-center hidden">0</span>
                            </button>
                        </div>
                    </nav>
                </header>
    </div> <!-- Close fixed-header-container -->

    <!-- Page Wrapper -->
    <div id="page-wrapper" class="transition-transform duration-300">
        <main>
            <!-- Main content starts here and is closed in footer.php -->
