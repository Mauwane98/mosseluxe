<?php
/**
 * Shop Page
 * 
 * Displays product catalog with filtering, sorting, and pagination.
 * Uses ShopController for business logic separation.
 */

require_once 'includes/bootstrap.php';
require_once __DIR__ . '/app/Services/InputSanitizer.php';
require_once __DIR__ . '/app/Repositories/ProductRepository.php';
require_once __DIR__ . '/app/Controllers/ShopController.php';

// Initialize controller
$conn = get_db_connection();
$shopController = new \App\Controllers\ShopController($conn);

// Get shop data from controller
$shopData = $shopController->index($_GET);

// Extract data for view
$products = $shopData['products'];
$categories = $shopData['categories'];
$pagination = $shopData['pagination'];
$filters = $shopData['filters'];
$priceRange = $shopData['price_range'];

// Legacy variable names for template compatibility
$selected_category = $filters['category'];
$sort_by = $filters['sort_by'];
$search_query = $filters['search'];
$min_price = $filters['min_price'];
$max_price = $filters['max_price'];
$absolute_min = $priceRange['min'];
$absolute_max = $priceRange['max'];
$total_products = $pagination['total'];
$total_pages = $pagination['pages'];
$page = $pagination['current'];

$pageTitle = get_setting('shop_title', 'Shop') . " - Mossé Luxe";
require_once 'includes/header.php';
?>

<!-- Main Content -->
    <div class="container mx-auto px-4 py-16 md:py-24">
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter"><?php echo htmlspecialchars(get_setting('shop_h1_title', 'Premium Collection')); ?></h1>
            <p class="mt-4 text-lg text-black/70 max-w-2xl mx-auto"><?php echo htmlspecialchars(get_setting('shop_sub_title', 'Discover our curated collection of luxury streetwear.')); ?></p>
        </div>

        <!-- Filters Section -->
        <div class="mb-8">
            <form action="shop.php" method="GET" id="filter-form" class="bg-gray-50 rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Search Bar -->
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search products..." class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block p-2.5 pl-10">
                        <svg class="absolute left-3 bottom-3 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    
                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" id="category" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block p-2.5">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $category_item): ?>
                                <option value="<?php echo $category_item['id']; ?>" <?php echo ($selected_category == $category_item['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category_item['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sort By -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select name="sort_by" id="sort_by" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block p-2.5">
                            <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest Arrivals</option>
                            <option value="price_asc" <?php echo ($sort_by == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_desc" <?php echo ($sort_by == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name_asc" <?php echo ($sort_by == 'name_asc') ? 'selected' : ''; ?>>Name: A-Z</option>
                            <option value="name_desc" <?php echo ($sort_by == 'name_desc') ? 'selected' : ''; ?>>Name: Z-A</option>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Price Range: R<span id="price-min-display"><?php echo number_format($min_price); ?></span> - R<span id="price-max-display"><?php echo number_format($max_price); ?></span>
                        </label>
                        <div class="flex gap-2 items-center">
                            <input type="range" name="min_price" id="min-price" min="<?php echo $absolute_min; ?>" max="<?php echo $absolute_max; ?>" value="<?php echo $min_price; ?>" class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            <input type="range" name="max_price" id="max-price" min="<?php echo $absolute_min; ?>" max="<?php echo $absolute_max; ?>" value="<?php echo $max_price; ?>" class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 md:flex-none bg-black text-white py-2.5 px-8 rounded-md text-sm font-semibold hover:bg-black/80 transition-colors">
                        Apply Filters
                    </button>
                    <button type="button" onclick="resetFilters()" class="flex-1 md:flex-none bg-gray-200 text-gray-700 py-2.5 px-8 rounded-md text-sm font-semibold hover:bg-gray-300 transition-colors">
                        Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Result Count -->
        <div class="flex justify-between items-center mb-6">
            <p data-result-count class="text-gray-600"><?php echo $total_products; ?> product<?php echo $total_products !== 1 ? 's' : ''; ?> found</p>
        </div>

        <?php if (!empty($products)): ?>
            <!-- Product Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 md:gap-8 product-grid" data-product-grid>
                <?php foreach ($products as $product): ?>
                    <div class="group relative product-card">
                        <div class="relative aspect-w-1 aspect-h-1 bg-neutral-50 rounded-2xl overflow-hidden border border-transparent group-hover:border-black/20 transition-colors duration-300 hover:shadow-lg">
                            <a href="product/<?php echo $product['id']; ?>/<?php echo urlencode(str_replace(' ', '-', strtolower($product['name'] ?? 'product'))); ?>" class="absolute inset-0 z-0"></a>
                                <!-- Premium Badges - Fixed position for better visibility -->
                                <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                                    <?php if ($product['sale_price'] > 0): ?>
                                        <span class="inline-flex items-center bg-gradient-to-r from-red-500 to-red-600 text-white text-[10px] font-black px-2.5 py-1.5 rounded-full shadow-xl border-2 border-white/20 backdrop-blur-sm">
                                            SALE
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($product['is_featured']): ?>
                                        <span class="inline-flex items-center bg-gradient-to-r from-amber-400 to-orange-500 text-black text-[10px] font-black px-2.5 py-1.5 rounded-full shadow-xl border-2 border-white/30 backdrop-blur-sm">
                                            FEATURED
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($product['is_new']): ?>
                                        <span class="inline-flex items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white text-[10px] font-black px-2.5 py-1.5 rounded-full shadow-xl border-2 border-white/20 backdrop-blur-sm">
                                            NEW
                                        </span>
                                    <?php endif; ?>
                                    <!-- Additional badges can be added here -->
                                </div>

                                    <img
                                        src="<?php echo htmlspecialchars(ImageService::getImagePath($product['image'], 'product')); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                        loading="eager"
                                        onerror="this.src='<?php echo SITE_URL; ?>assets/images/product-placeholder.png'"
                                    >

                                <!-- Top-right Buttons (Always visible, enhanced interactions) -->
                                <div class="absolute top-4 right-4 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-20">
                                    <!-- Quick View Button -->
                                    <button class="quick-view-btn bg-white/90 backdrop-blur-sm text-black p-2 rounded-full border border-white/50 shadow-xl hover:bg-blue-50 hover:text-blue-600 transition-all duration-300 relative z-30"
                                            data-product-id="<?php echo $product['id']; ?>"
                                            type="button"
                                            title="Quick View"
                                            aria-label="Quick View">
                                        <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <!-- Wishlist Button -->
                                    <button class="bg-white/90 backdrop-blur-sm text-black p-2 rounded-full border border-white/50 shadow-xl hover:bg-red-50 hover:text-red-600 transition-all duration-300 relative z-30"
                                            type="button"
                                            onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(<?php echo $product['id']; ?>, this)"
                                            title="Add to Wishlist">
                                        <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Quick Actions Overlay -->
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all duration-500 flex items-center justify-center opacity-0 group-hover:opacity-100 z-10">
                                    <div class="flex gap-3 transform translate-y-4 group-hover:translate-y-0 transition-all duration-500">
                                        <!-- Quick View Button -->
                                        <button class="quick-view-btn bg-white/95 backdrop-blur-sm text-black p-3 rounded-full border-2 border-white/50 shadow-2xl hover:bg-white hover:scale-110 transition-all duration-300 relative z-40"
                                                data-product-id="<?php echo $product['id']; ?>"
                                                type="button"
                                                title="Quick View">
                                            <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>

                                        <!-- Quick Add Button -->
                                        <button class="bg-black text-white p-3 rounded-full border-2 border-white/50 shadow-2xl hover:bg-white hover:text-black hover:scale-110 transition-all duration-300 relative z-40"
                                                type="button"
                                                onclick="event.preventDefault(); event.stopPropagation(); window.AppCart?.addItem(<?php echo $product['id']; ?>, 1)"
                                                title="Add to Cart">
                                            <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-4 4m0 0h18m-4 4H7m0 0l-2-2"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Hidden Quick Add Form -->
                                <form id="quick-add-form-shop-<?php echo $product['id']; ?>" class="quick-add-form hidden" action="<?php echo SITE_URL; ?>ajax_cart_handler.php" method="POST">
                                    <?php echo generate_csrf_token_input(); ?>
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" id="quick-add-quantity-<?php echo $product['id']; ?>" name="quantity" value="1">
                                </form>

                                <!-- Subtle border indicator -->
                                <div class="absolute inset-0 rounded-2xl border-2 border-black/0 group-hover:border-black/10 transition-colors duration-500 pointer-events-none"></div>
                            </div>
                        <!-- Enhanced Product Information -->

                        <div class="mt-6 text-left space-y-1">
                            <!-- Brand -->
                            <h4 class="text-xs font-semibold uppercase tracking-widest text-black/60">Mossé Luxe</h4>

                            <!-- Product Name -->
                        <h3 class="text-lg font-black leading-tight text-black group-hover:text-gray-800 transition-colors">
                                <a href="product/<?php echo $product['id']; ?>/<?php echo urlencode(str_replace(' ', '-', strtolower($product['name'] ?? 'product'))); ?>" class="hover:text-gray-700"><?php echo htmlspecialchars($product['name'] ?? 'PRODUCT'); ?></a>
                            </h3>

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

                                <div class="mt-4 space-y-3">
                                    <!-- Qty input and Add to Cart Button -->
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center border border-gray-300 rounded-lg">
                                            <button onclick="decreaseShopQuantity(<?php echo $product['id']; ?>)" class="px-2 py-2 text-gray-600 hover:text-black text-sm font-bold">-</button>
                                            <input id="shop-quantity-<?php echo $product['id']; ?>" type="number" value="1" min="1" max="<?php echo 99; ?>" readonly class="w-12 text-center border-0 bg-transparent py-2 text-sm">
                                            <button onclick="increaseShopQuantity(<?php echo $product['id']; ?>)" class="px-2 py-2 text-gray-600 hover:text-black text-sm font-bold">+</button>
                                        </div>
                                        <button onclick="window.AppCart.addItem(<?php echo $product['id']; ?>, parseInt(document.getElementById('shop-quantity-<?php echo $product['id']; ?>').value))"
                                            class="flex-1 bg-black text-white py-2.5 px-3 rounded-lg font-bold text-sm hover:bg-black/80 transition-colors">
                                            Add to Cart
                                        </button>
                                    </div>

                                    <!-- WhatsApp Inquiry Button -->
                                    <button onclick="openWhatsAppInquiry('<?php echo addslashes($product['name']); ?>')"
                                        class="w-full bg-green-600 text-white py-2.5 px-3 rounded-lg font-bold text-sm hover:bg-green-700 transition-colors">
                                        <span class="flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                            Inquire on WhatsApp
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-12">
                    <nav aria-label="Page navigation">
                        <ul class="inline-flex items-center -space-x-px">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li>
                                    <a href="?page=<?php echo $i; ?>&category=<?php echo $selected_category; ?>&sort_by=<?php echo $sort_by; ?>&search=<?php echo urlencode($search_query); ?>" 
                                       class="px-3 py-2 leading-tight <?php echo $page == $i ? 'text-black bg-gray-200' : 'text-gray-500 bg-white'; ?> border border-gray-300 hover:bg-gray-100 hover:text-gray-700">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-center text-lg text-black/60">No products are available at the moment. Please check back soon.</p>
        <?php endif; ?>
    </div>

<!-- Quick View Modal -->
<div id="quick-view-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[9999] hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col md:flex-row modal-container transform scale-95 opacity-0 transition-all duration-300">
        <!-- Close Button -->
        <button onclick="closeQuickView()" class="absolute top-4 right-4 z-10 bg-black/10 hover:bg-black/20 rounded-full p-2 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- Modal Content -->
        <div id="quick-view-content" class="flex-1 p-6 md:p-8 overflow-y-auto">
            <!-- Loading state will be replaced by JavaScript -->
            <div class="flex items-center justify-center py-12">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-black mx-auto mb-4"></div>
                    <p class="text-black/70">Loading product details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AJAX Shop Filter Module -->
<script src="<?php echo SITE_URL; ?>assets/js/shop-filter.js"></script>

<script>
// Reset filters function - clears all filters and refreshes
function resetFilters() {
    // Clear form inputs
    const form = document.getElementById('filter-form');
    if (form) {
        form.reset();
        // Reset price displays
        const minDisplay = document.getElementById('price-min-display');
        const maxDisplay = document.getElementById('price-max-display');
        const minInput = document.getElementById('min-price');
        const maxInput = document.getElementById('max-price');
        if (minDisplay && minInput) minDisplay.textContent = parseInt(minInput.min).toLocaleString();
        if (maxDisplay && maxInput) maxDisplay.textContent = parseInt(maxInput.max).toLocaleString();
    }
    // Trigger AJAX refresh or fallback to page reload
    if (window.ShopFilter) {
        window.ShopFilter.refresh();
    } else {
        window.location.href = 'shop.php';
    }
}
</script>

<?php
require_once 'includes/footer.php';
?>
