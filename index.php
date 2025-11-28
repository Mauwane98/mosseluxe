<?php
require_once 'includes/bootstrap.php';

// Process referral link if present
if (isset($_GET['ref'])) {
    require_once 'includes/referral_service.php';
    $referralService = new ReferralService();
    $referral_processed = $referralService->processReferral($_GET['ref']);

    if ($referral_processed) {
        // Set a cookie to remember this referral for the session
        setcookie('referral_link', '1', time() + 86400, '/'); // 24 hours
        error_log("Referral link processed for code: " . $_GET['ref']);
    } else {
        error_log("Invalid referral code attempted: " . $_GET['ref']);
    }
}

$pageTitle = "Mossé Luxe - Redefining Urban Luxury";

$conn = get_db_connection();

// Fetch Hero Slides
$hero_slides_sql = "SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY sort_order ASC";
$hero_slides_result = $conn->query($hero_slides_sql);
$hero_slides = [];
if ($hero_slides_result) {
    while ($row = $hero_slides_result->fetch_assoc()) {
        $hero_slides[] = $row;
    }
}

// Fetch Homepage Sections
$sections_sql = "SELECT * FROM homepage_sections WHERE is_active = 1 ORDER BY sort_order ASC";
$sections_result = $conn->query($sections_sql);
$sections = [];
if ($sections_result) {
    while ($row = $sections_result->fetch_assoc()) {
        $sections[$row['section_key']] = $row;
    }
}

// Extract specific sections into their own variables for easier use in the template
$brand_statement = $sections['brand_statement'] ?? null;
$newsletter = $sections['newsletter'] ?? null;
$new_arrivals_section = $sections['new_arrivals'] ?? null;

// Check if hero buttons are globally enabled
$hero_buttons_enabled = get_setting('hero_buttons_enabled', '0') === '1';

// Fetch New Arrivals Products
$new_arrivals_sql = "SELECT p.* FROM products p JOIN new_arrivals na ON p.id = na.product_id WHERE p.status = 1 ORDER BY na.display_order ASC LIMIT 8";
$new_arrivals_result = $conn->query($new_arrivals_sql);
$new_arrivals = [];
if ($new_arrivals_result) {
    while ($row = $new_arrivals_result->fetch_assoc()) {
        $new_arrivals[] = $row;
    }
}
$new_arrivals_message = "No new arrivals to display at the moment.";

include 'includes/header.php';
?>

<main>
    <!-- Hero Section -->
    <section class="relative h-[50vh] md:h-[90vh] bg-black text-white overflow-hidden">
        <!-- Carousel Container -->
        <div id="hero-carousel" class="w-full h-full flex transition-transform duration-700 ease-in-out">
            <?php foreach ($hero_slides as $slide): ?>
                <div class="carousel-slide">
                    <img src="<?php echo htmlspecialchars($slide['image_url']); ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>" class="w-full h-full object-cover">
                    <div class="hero-overlay"></div>
                    <div class="carousel-slide-content"> 
                        <div class="absolute inset-0 flex flex-col justify-end md:justify-center items-center text-center text-white px-4 pb-32 md:pb-0 hero-content gap-y-4">

                            <?php if (!empty($slide['title'])): ?>
                                <h1 class="text-4xl md:text-6xl lg:text-7xl font-black uppercase tracking-tighter drop-shadow-lg">
                                    <?php echo htmlspecialchars($slide['title']); ?>
                                </h1>
                            <?php endif; ?>

                            <?php if (!empty($slide['subtitle'])): ?>
                                <p class="text-lg md:text-xl lg:text-2xl font-light max-w-2xl drop-shadow-md">
                                    <?php echo htmlspecialchars($slide['subtitle']); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($hero_buttons_enabled && isset($slide['button_visibility']) && $slide['button_visibility'] && !empty($slide['button_text']) && !empty($slide['button_url'])): ?>
                                <div class="w-full flex justify-center">
                                    <?php
                                    $button_style = $slide['button_style'] ?? 'wide';
                                    // New modern button styling with different styles - longer nice buttons
                                    $button_classes = 'inline-block text-center font-black uppercase border-2 border-white shadow-2xl transition-all duration-500 tracking-wider hover:scale-105 transform hover:shadow-3xl py-3 px-8';
                                    switch ($button_style) {
                                        case 'wide': // Modern Long Horizontal
                                            $button_classes .= ' max-w-xs mx-auto bg-white text-black rounded-full hover:bg-black hover:text-white';
                                            break;
                                        case 'wider': // Bold Pill Shape
                                            $button_classes .= ' max-w-sm mx-auto bg-black text-white rounded-full hover:bg-white hover:text-black';
                                            break;
                                        case 'widest': // Rounded Rectangular
                                            $button_classes .= ' max-w-md mx-auto bg-white text-black rounded-lg hover:bg-black hover:text-white';
                                            break;
                                        case 'largest': // Maximal Prominent
                                            $button_classes .= ' max-w-lg mx-auto bg-black text-white rounded-lg hover:bg-white hover:text-black';
                                            break;
                                        default:
                                            $button_classes .= ' max-w-xs mx-auto bg-white text-black rounded-full hover:bg-black hover:text-white';
                                    }
                                    ?>
                                    <a href="<?php echo htmlspecialchars($slide['button_url']); ?>" class="<?php echo $button_classes; ?>">
                                        <?php echo htmlspecialchars($slide['button_text']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Hidden slide data for JavaScript -->
        <script>
            window.heroSlideData = <?php echo json_encode(array_map(function($slide) {
                return [
                    'button_text' => $slide['button_text'] ?? '',
                    'button_url' => $slide['button_url'] ?? ''
                ];
            }, $hero_slides)); ?>;
            window.heroButtonsEnabled = <?php echo $hero_buttons_enabled ? 'true' : 'false'; ?>;
        </script>

        <!-- Carousel Controls -->
        <button id="prev-slide" class="carousel-control prev" aria-label="Previous slide">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        <button id="next-slide" class="carousel-control next" aria-label="Next slide">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>

        <!-- Carousel Dots -->
        <div id="carousel-dots" class="carousel-dots">
            <!-- Dots will be generated by JS -->
        </div>
    </section>



            <!-- New Arrivals Section -->
            <section id="new-arrivals" class="py-24 md:py-32 bg-white">
                <div class="container mx-auto px-4 md:px-6">
                    <h2 class="text-4xl md:text-5xl font-black text-center uppercase tracking-tighter mb-12 md:mb-16">
                        <?php echo htmlspecialchars($new_arrivals_section['title'] ?? 'New Arrivals'); ?>
                    </h2>
                    
                    <!-- Product Grid -->
                    <?php if (!empty($new_arrivals)): ?>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
                        <?php foreach ($new_arrivals as $product): ?>
                            <div class="group relative">
                                <a href="product/<?php echo $product['id']; ?>/<?php echo urlencode(str_replace(' ', '-', strtolower($product['name']))); ?>">
                                    <div class="relative aspect-w-1 aspect-h-1 bg-neutral-50 rounded-2xl overflow-hidden border border-transparent group-hover:border-black/20 transition-colors duration-300 hover:shadow-lg">
                                        <!-- Premium Badges - Fixed position for better visibility -->
                                        <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                                            <?php if ($product['sale_price'] > 0): ?>
                                                <span class="inline-flex items-center bg-gradient-to-r from-red-500 to-red-600 text-white text-[10px] font-black px-2.5 py-1.5 rounded-full shadow-xl border-2 border-white/20 backdrop-blur-sm">
                                                    SALE
                                                </span>
                                            <?php endif; ?>
                                            <?php if (isset($product['is_featured']) && $product['is_featured']): ?>
                                                <span class="inline-flex items-center bg-gradient-to-r from-amber-400 to-orange-500 text-black text-[10px] font-black px-2.5 py-1.5 rounded-full shadow-xl border-2 border-white/30 backdrop-blur-sm">
                                                    FEATURED
                                                </span>
                                            <?php endif; ?>
                                            <?php if (isset($product['is_new']) && $product['is_new']): ?>
                                                <span class="inline-flex items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white text-[10px] font-black px-2.5 py-1.5 rounded-full shadow-xl border-2 border-white/20 backdrop-blur-sm">
                                                    NEW
                                                </span>
                                            <?php endif; ?>
                                            <!-- Additional badges can be added here -->
                                        </div>

                                        <img
                                            src="<?php echo htmlspecialchars($product['image']); ?>"
                                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                                            class="w-full h-full object-contain p-6 group-hover:scale-105 transition-transform duration-300 ease-out"
                                            onerror="this.src='https://placehold.co/600x600/f1f1f1/000000?text=Mossé+Luxe'"
                                            loading="eager"
                                        >

                                        <!-- Top-right Buttons (Always visible, enhanced interactions) -->
                                        <div class="absolute top-4 right-4 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                            <!-- Wishlist Button -->
                                            <button class="bg-white/90 backdrop-blur-sm text-black p-2 rounded-full border border-white/50 shadow-xl hover:bg-red-50 hover:text-red-600 transition-all duration-300"
                                                    onclick="event.preventDefault(); toggleWishlist(<?php echo $product['id']; ?>, this)"
                                                    title="Add to Wishlist">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Quick Actions Overlay -->
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all duration-500 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                            <div class="flex gap-3 transform translate-y-4 group-hover:translate-y-0 transition-all duration-500">
                                                <!-- Quick View Button -->
                                                <button class="bg-white/95 backdrop-blur-sm text-black p-3 rounded-full border-2 border-white/50 shadow-2xl hover:bg-white hover:scale-110 transition-all duration-300"
                                                        onclick="event.preventDefault(); openQuickView(<?php echo $product['id']; ?>)"
                                                        title="Quick View">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Quick Add Button -->
                                                <button class="bg-black text-white p-3 rounded-full border-2 border-white/50 shadow-2xl hover:bg-white hover:text-black hover:scale-110 transition-all duration-300"
                                                        onclick="event.preventDefault(); handleQuickAdd(<?php echo $product['id']; ?>, 'quick-add-form-<?php echo $product['id']; ?>')"
                                                        title="Add to Cart">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-4 4m0 0h18m-4 4H7m0 0l-2-2"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Hidden Quick Add Form -->
                                        <form id="quick-add-form-<?php echo $product['id']; ?>" class="hidden" action="ajax_cart_handler.php" method="POST">
                                            <?php echo generate_csrf_token_input(); ?>
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                        </form>

                                        <!-- Subtle border indicator -->
                                        <div class="absolute inset-0 rounded-2xl border-2 border-black/0 group-hover:border-black/10 transition-colors duration-500"></div>
                                    </div>

                                    <!-- Enhanced Product Information -->
                                    <div class="mt-6 text-left space-y-1">
                                        <!-- Brand -->
                                        <h4 class="text-xs font-semibold uppercase tracking-widest text-black/60">Mossé Luxe</h4>

                                        <!-- Product Name -->
                                        <h3 class="text-lg font-black leading-tight text-black group-hover:text-gray-800 transition-colors"><?php echo htmlspecialchars($product['name'] ?? 'PRODUCT'); ?></h3>

                                        <!-- Price -->
                                        <div class="mt-2">
                                            <?php if ($product['sale_price'] > 0): ?>
                                                <p class="flex items-baseline gap-2">
                                                    <span class="text-lg font-black text-black">R <?php echo number_format($product['sale_price'], 2); ?></span>
                                                    <span class="text-sm text-black/50 line-through">R <?php echo number_format($product['price'], 2); ?></span>
                                                </p>
                                            <?php else: ?>
                                                <p class="text-lg font-black text-black">R <?php echo number_format($product['price'], 2); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <p class="text-center text-lg text-black/60"><?php echo htmlspecialchars($new_arrivals_message); ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Recently Viewed Section -->
            <?php
            $recently_viewed = $_SESSION['recently_viewed'] ?? [];
            if (!empty($recently_viewed)) {
                $placeholders = str_repeat('?,', count($recently_viewed) - 1) . '?';
                $recent_sql = "SELECT id, name, price, sale_price, image FROM products WHERE id IN ($placeholders) AND status = 1 ORDER BY FIELD(id, " . implode(',', $recently_viewed) . ")";
                $recent_stmt = $conn->prepare($recent_sql);
                $recent_stmt->bind_param(str_repeat('i', count($recently_viewed)), ...$recently_viewed);
                $recent_stmt->execute();
                $recent_result = $recent_stmt->get_result();
                $recent_products = [];
                while ($row = $recent_result->fetch_assoc()) {
                    $recent_products[] = $row;
                }
                $recent_stmt->close();

                if (!empty($recent_products)) {
            ?>
            <section class="py-24 md:py-32 bg-white">
                <div class="container mx-auto px-4 md:px-6">
                    <h2 class="text-4xl md:text-5xl font-black text-center uppercase tracking-tighter mb-12 md:mb-16">
                        <?php echo htmlspecialchars($sections['recently_viewed']['title'] ?? 'Recently Viewed'); ?>
                    </h2>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
                        <?php foreach ($recent_products as $product): ?>
                            <div class="group relative">
                                <a href="product/<?php echo $product['id']; ?>/<?php echo urlencode(str_replace(' ', '-', strtolower($product['name']))); ?>">
                                    <div class="relative aspect-w-1 aspect-h-1 bg-neutral-50 rounded-2xl overflow-hidden border border-transparent group-hover:border-black/20 transition-colors duration-300 hover:shadow-lg">
                                        <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                                            <?php if ($product['sale_price'] > 0): ?>
                                                <span class="inline-flex items-center bg-gradient-to-r from-red-500 to-red-600 text-white text-[10px] font-black px-2.5 py-1.5 rounded-full shadow-xl border-2 border-white/20 backdrop-blur-sm">
                                                    SALE
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <img
                                            src="<?php echo htmlspecialchars($product['image']); ?>"
                                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                                            class="w-full h-full object-contain p-6 group-hover:scale-105 transition-transform duration-300 ease-out"
                                            onerror="this.src='https://placehold.co/600x600/f1f1f1/000000?text=Mossé+Luxe'"
                                            loading="eager"
                                        >

                                        <!-- Enhanced Quick Add Button -->
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all duration-300 flex items-center justify-center">
                                            <button class="opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition-all duration-300 bg-white/95 backdrop-blur-sm text-black font-black uppercase text-sm py-3 px-6 rounded-full border-2 border-white/50 shadow-2xl hover:bg-white hover:shadow-white/50 quick-add-btn"
                                                    onclick="event.preventDefault(); handleQuickAdd(<?php echo $product['id']; ?>, 'quick-add-form-recently-viewed-<?php echo $product['id']; ?>')">
                                                Quick Add
                                            </button>
                                        </div>

                                        <!-- Hidden Quick Add Form -->
                                        <form id="quick-add-form-recently-viewed-<?php echo $product['id']; ?>" class="hidden" action="ajax_cart_handler.php" method="POST">
                                            <?php echo generate_csrf_token_input(); ?>
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                        </form>

                                        <!-- Subtle border indicator -->
                                        <div class="absolute inset-0 rounded-2xl border-2 border-black/0 group-hover:border-black/10 transition-colors duration-500"></div>
                                    </div>

                                    <!-- Enhanced Product Information -->
                                    <div class="mt-6 text-left space-y-1">
                                        <!-- Brand -->
                                        <h4 class="text-xs font-semibold uppercase tracking-widest text-black/60">Mossé Luxe</h4>

                                        <!-- Product Name -->
                                        <h3 class="text-lg font-black leading-tight text-black group-hover:text-gray-800 transition-colors"><?php echo htmlspecialchars($product['name'] ?? 'PRODUCT'); ?></h3>

                                        <!-- Price -->
                                        <div class="mt-2">
                                            <?php if ($product['sale_price'] > 0): ?>
                                                <p class="flex items-baseline gap-2">
                                                    <span class="text-lg font-black text-black">R <?php echo number_format($product['sale_price'], 2); ?></span>
                                                    <span class="text-sm text-black/50 line-through">R <?php echo number_format($product['price'], 2); ?></span>
                                                </p>
                                            <?php else: ?>
                                                <p class="text-lg font-black text-black">R <?php echo number_format($product['price'], 2); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
                }
            }
            ?>

            <!-- Brand Statement Section -->
            <?php if ($brand_statement): ?>
            <section class="py-24 md:py-32 bg-white">
                <div class="container mx-auto px-4 text-center max-w-3xl">
                    <?php if (!empty($brand_statement['subtitle'])): ?>
                        <h2 class="text-sm font-bold uppercase tracking-widest text-black/50 mb-3"><?php echo htmlspecialchars($brand_statement['subtitle']); ?></h2>
                    <?php endif; ?>
                    <h3 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">
                        <?php echo htmlspecialchars($brand_statement['title']); ?>
                    </h3>
                    <p class="mt-6 text-lg md:text-xl text-black/80 leading-relaxed">
                        <?php echo htmlspecialchars($brand_statement['content']); ?>
                    </p>
                    <a href="<?php echo htmlspecialchars($brand_statement['button_url']); ?>" class="mt-8 inline-block font-bold uppercase tracking-wider text-black border-b-2 border-black pb-1 hover:border-black/60 hover:text-black/60 transition-colors">
                        <?php echo htmlspecialchars($brand_statement['button_text']); ?>
                    </a>
                </div>
            </section>
            <?php endif; ?>

            <!-- Newsletter Signup -->
            <?php if ($newsletter): ?>
            <section id="newsletter-signup" class="py-16 md:py-24 bg-neutral-100">
                <div class="container mx-auto px-4 text-center max-w-xl">
                    <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-4">
                        <?php echo htmlspecialchars($newsletter['title']); ?>
                    </h2>
                    <p class="text-lg text-black/60 mb-8">
                        <?php echo htmlspecialchars($newsletter['content']); ?>
                    </p>
                    <form action="subscribe.php" method="POST" id="newsletter-form" class="flex flex-col md:flex-row gap-4 max-w-md mx-auto">
                        <?php generate_csrf_token_input(); ?>
                        <input 
                            type="email" 
                            name="email"
                            placeholder="Enter your email" 
                            required 
                            aria-label="Enter your email for the newsletter"
                            class="flex-grow p-3 bg-white border border-black/50 rounded-md text-black placeholder-black/50 focus:outline-none focus:ring-2 focus:ring-black"
                        >
                        <input 
                            type="submit" 
                            value="<?php echo htmlspecialchars($newsletter['button_text']); ?>"
                            class="bg-black text-white py-3 px-8 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider cursor-pointer"
                        >
                    </form>
                </div>
            </section>
            <?php endif; ?>

            <!-- Dynamic Custom Sections -->
            <?php foreach ($sections as $section_key => $section): ?>
                <?php
                // Skip sections already explicitly handled
                if (in_array($section_key, ['hero_carousel', 'new_arrivals', 'brand_statement', 'newsletter', 'recently_viewed'])) {
                    continue;
                }

                // Render custom section
                $section_style = '';
                if (!empty($section['background_color'])) {
                    $section_style .= 'background-color: ' . htmlspecialchars($section['background_color']) . ';';
                }
                if (!empty($section['text_color'])) {
                    $section_style .= 'color: ' . htmlspecialchars($section['text_color']) . ';';
                }
                $section_class = 'py-16 md:py-24'; // Default padding
                if (!empty($section['image_url'])) {
                    $section_style .= 'background-image: url(' . htmlspecialchars($section['image_url']) . '); background-size: cover; background-position: center;';
                    $section_class .= ' relative'; // Add relative for overlay
                }
                ?>
                <?php if ($section['is_active']): ?>
                    <section class="<?php echo $section_class; ?>" style="<?php echo $section_style; ?>">
                        <?php if (!empty($section['image_url'])): ?>
                            <div class="absolute inset-0 bg-black opacity-50"></div> <!-- Image overlay -->
                        <?php endif; ?>
                        <div class="container mx-auto px-4 text-center max-w-3xl relative z-10">
                            <?php if (!empty($section['subtitle'])): ?>
                                <h2 class="text-sm font-bold uppercase tracking-widest mb-3"><?php echo htmlspecialchars($section['subtitle']); ?></h2>
                            <?php endif; ?>
                            <?php if (!empty($section['title'])): ?>
                                <h3 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">
                                    <?php echo htmlspecialchars($section['title']); ?>
                                </h3>
                            <?php endif; ?>
                            <?php if (!empty($section['content'])): ?>
                                <p class="mt-6 text-lg md:text-xl leading-relaxed">
                                    <?php echo htmlspecialchars($section['content']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($section['button_text']) && !empty($section['button_url'])): ?>
                                <a href="<?php echo htmlspecialchars($section['button_url']); ?>" class="mt-8 inline-block font-bold uppercase tracking-wider border-b-2 pb-1 hover:opacity-75 transition-opacity">
                                    <?php echo htmlspecialchars($section['button_text']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>
        </main>

        <!-- Quick View Modal -->
        <div id="quick-view-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
            <div class="min-h-screen flex items-center justify-center p-4">
                <!-- Modal Container -->
                <div class="bg-white rounded-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden relative shadow-2xl transform transition-all duration-300 scale-95 opacity-0">
                    <!-- Close Button -->
                    <button onclick="closeQuickView()" class="absolute top-6 right-6 z-20 w-10 h-10 bg-black text-white rounded-full flex items-center justify-center hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <!-- Modal Content -->
                    <div id="quick-view-content" class="flex flex-col lg:flex-row h-full max-h-[90vh]">
                        <!-- Loading State -->
                        <div class="flex-1 flex items-center justify-center p-12">
                            <div class="text-center">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-black mx-auto mb-4"></div>
                                <p class="text-gray-600">Loading product details...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart Sidebar Overlay -->
        <div id="cart-sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40 transition-opacity duration-300" onclick="toggleCart()"></div>

<script>
/**
 * Handles the quick add to cart functionality by calling the new REST API.
 * This function replaces the old form submission logic.
 *
 * @param {number} productId The ID of the product to add.
 */
async function handleQuickAdd(productId) {
    // You can add a loading indicator to the button here
    
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    try {
        const baseUrl = window.SITE_URL || '/';
        const apiUrl = baseUrl.endsWith('/') ? `${baseUrl}api/cart/items` : `${baseUrl}/api/cart/items`;
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken // Sending CSRF token in a header is a good practice
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        });

        const result = await response.json();

        if (response.ok && result.success) {
            // Use your existing functions to show success and update the UI
            showToast(result.message, 'success');
            updateCartCount(result.cart_count);
        } else {
            showToast(result.message || 'Could not add item to cart.', 'error');
        }
    } catch (error) {
        console.error('Quick Add Error:', error);
        showToast('An unexpected error occurred.', 'error');
    }
}
</script>

<?php
require_once 'includes/footer.php';
?>
