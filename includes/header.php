<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Mossé Luxe - Luxury Streetwear'; ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* Applying Inter as the default font */
        /* Custom scrollbar for a more modern feel */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #888;
        }

        /* Helper class for aspect ratio (Tailwind aspect-ratio plugin isn't default) */
        @media (min-width: 768px) {
            .aspect-md-h-1 {
                position: relative;
                padding-bottom: 100%; /* 1:1 Aspect Ratio */
            }
            .aspect-md-h-1 > * {
                position: absolute;
                height: 100%;
                width: 100%;
                top: 0;
                left: 0;
            }
        }

        /* Carousel Specific Styles */
        .carousel-slide {
            flex-shrink: 0;
            width: 100%;
            height: 100%; /* Ensure slide takes full height of parent */
            position: relative;
            text-align: center;
            color: white;
        }
        .carousel-slide img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .carousel-slide-content {
            position: absolute; /* Use absolute positioning */
            bottom: 0; /* Position at the bottom */
            left: 50%;
            transform: translateX(-50%); /* Center horizontally */
            z-index: 20;
            padding: 1.5rem;
            bottom: 1.5rem; /* Position at the bottom with padding */
            animation: fadeIn 1s ease-out;
        }
        
        /* Fade-in animation for slide content */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .carousel-slide-content.animate-in {
            animation: fadeIn 1s ease-out;
        }

        /* Custom carousel controls */
        .carousel-control {
            position: absolute;
            z-index: 30;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.8);
            color: black;
            border-radius: 9999px;
            width: 2.5rem; /* 40px */
            height: 2.5rem; /* 40px */
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }
        .carousel-control:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-50%) scale(1.1);
        }
        .carousel-control.prev {
            left: 1.5rem; /* p-6 */
        }
        .carousel-control.next {
            right: 1.5rem; /* p-6 */
        }

        /* Custom carousel dots */
        .carousel-dots {
            position: absolute;
            z-index: 30;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.75rem; /* space-x-3 */
        }
        .carousel-dot {
            width: 0.75rem; /* 12px */
            height: 0.75rem; /* 12px */
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .carousel-dot.active {
            background: rgba(255, 255, 255, 1);
            width: 2rem; /* 32px */
        }

    </style>
    <script>
        // Function to update cart count display
        function updateCartCountDisplay() {
            fetch('ajax_cart_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_count'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCountElement = document.getElementById('cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cart_count;
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching cart count:', error);
            });
        }

        // Call on page load
        document.addEventListener('DOMContentLoaded', updateCartCountDisplay);
    </script>
</head>
<body class="bg-white text-black antialiased">

    <!-- 
      MODAL / OVERLAY CONTAINERS 
      These are here for structure, functionality would be built out.
    -->
    
    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu" class="fixed inset-0 bg-white z-[100] p-6 transform -translate-x-full transition-transform duration-300 ease-in-out md:hidden">
        <div class="flex justify-between items-center mb-10">
            <!-- Logo in Menu -->
            <a href="index.php">
                <img src="assets/images/logo-dark.png" alt="Mossé Luxe" class="h-32 w-auto">
            </a>
            <!-- Close Button -->
            <button id="close-menu-btn" aria-label="Close menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <!-- Mobile Nav Links -->
        <nav class="flex flex-col space-y-5">
            <a href="index.php" class="text-2xl font-bold uppercase tracking-wider">Home</a>
            <a href="shop.php" class="text-2xl font-bold uppercase tracking-wider">Shop</a>
            <a href="about.php" class="text-2xl font-bold uppercase tracking-wider">About</a>
            <a href="contact.php" class="text-2xl font-bold uppercase tracking-wider">Contact</a>
            <a href="my_account.php" class="text-2xl font-bold uppercase tracking-wider">Account</a>
            <a href="wishlist.php" class="text-2xl font-bold uppercase tracking-wider">Wishlist</a>
        </nav>
    </div>

    <!-- Search Overlay (Example) -->
    <div id="search-overlay" class="fixed inset-0 bg-white/90 z-[90] p-6 hidden flex-col items-center backdrop-blur-sm">
        <button id="close-search-btn" class="absolute top-6 right-6 text-black" aria-label="Close search">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <div class="w-full max-w-2xl mt-24">
            <h2 class="text-4xl md:text-6xl font-black text-center uppercase mb-8">Search</h2>
            <input type="search" placeholder="Search for products..." class="w-full p-4 text-lg bg-white border border-black rounded-md text-black placeholder-black/50 focus:outline-none focus:ring-2 focus:ring-black">
        </div>
    </div>

    <!-- Cart Sidebar (Example) -->
    <div id="cart-sidebar" class="fixed top-0 right-0 h-full w-full max-w-md bg-white z-[100] border-l border-black/10 shadow-2xl p-6 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col">
        <div class="flex justify-between items-center pb-6 border-b border-black/10">
            <h3 class="text-2xl font-bold uppercase">My Cart</h3>
            <button id="close-cart-btn" aria-label="Close cart">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <!-- Cart Content -->
        <div class="flex-grow flex items-center justify-center text-black/50">
            <p>Your cart is empty.</p>
        </div>
        <!-- Cart Footer -->
        <div class="border-t border-black/10 pt-6 space-y-4">
            <div class="flex justify-between font-bold text-lg">
                <span>Subtotal</span>
                <span>R 0.00</span>
            </div>
            <a href="#" class="block w-full text-center bg-black text-white py-3 px-6 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">
                Checkout
            </a>
        </div>
    </div>
    
    <!-- Announcement Bar -->
    <a href="index.php#newsletter-signup" id="announcement-bar" class="fixed top-0 left-0 w-full bg-black text-white text-center p-2.5 text-sm font-semibold tracking-wider uppercase z-[60] block hover:bg-black/80 transition-colors">
        Join The List & Receive 10% Off Your First Order.
    </a>

    <!-- Page Wrapper -->
    <div id="page-wrapper" class="transition-transform duration-300">

                <!-- Header -->
                <header id="main-header" class="navbar-scroll fixed top-[var(--header-top-offset)] left-0 w-full z-[50] transition-all duration-300 ease-in-out bg-white text-black border-b border-black/10">
                    <nav class="container mx-auto px-4 md:px-6 py-10 flex justify-between items-center">
                        <!-- Mobile Menu Butt<section id="newsletter-signup" class="py-16 md:py-24 bg-neutral-100">
                            ...
                        </section>
                        <section id="newsletter-signup" class="py-16 md:py-24 bg-neutral-100">
                            ...
                        </section>
                        on -->
                        <button id="open-menu-btn" class="md:hidden" aria-label="Open menu">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                        </button>
        
                        <!-- Desktop Nav (Left) -->
                        <div class="hidden md:flex items-center gap-8">
                            <a href="index.php" class="text-sm font-semibold uppercase tracking-wider hover:text-gray-700 transition-colors">Home</a>
                            <a href="shop.php" class="text-sm font-semibold uppercase tracking-wider hover:text-gray-700 transition-colors">Shop</a>
                            <a href="about.php" class="text-sm font-semibold uppercase tracking-wider hover:text-gray-700 transition-colors">About</a>
                            <a href="contact.php" class="text-sm font-semibold uppercase tracking-wider hover:text-gray-700 transition-colors">Contact</a>
                        </div>
        
                        <!-- Logo (Center) -->
                        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                            <a href="index.php" aria-label="Homepage">
                                <img id="header-logo" src="assets/images/logo-dark.png" alt="Mossé Luxe" class="h-32 md:h-40 w-auto">
                            </a>
                        </div>
        
                        <!-- Icons (Right) -->
                        <div class="flex items-center gap-4 md:gap-6">
                            <button id="open-search-btn" aria-label="Search">
                                <svg class="w-5 h-5 md:w-6 md:h-6 hover:text-gray-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </button>
                            <a href="wishlist.php" class="hidden md:block" aria-label="Wishlist">
                                <svg class="w-6 h-6 hover:text-gray-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                            </a>
                            <a href="my_account.php" class="hidden md:block" aria-label="My Account">
                                <svg class="w-6 h-6 hover:text-gray-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </a>
                            <a href="cart.php" id="open-cart-btn" class="relative" aria-label="Open cart">
                                <svg class="w-6 h-6 hover:text-gray-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <!-- Cart count example -->
                                <span id="cart-count" class="absolute -top-2 -right-2 bg-black text-white text-xs font-bold w-4 h-4 rounded-full flex items-center justify-center">0</span>
                            </a>
                        </div>
                    </nav>
                </header>