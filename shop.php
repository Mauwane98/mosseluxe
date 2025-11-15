<?php
require_once 'includes/bootstrap.php';
$pageTitle = get_setting('shop_title', 'Shop') . " - Mossé Luxe";
require_once 'includes/header.php'; // Now include header after all PHP logic

$conn = get_db_connection();
$products = [];
$categories = [];

// Fetch categories for the filter dropdown
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($result_categories = $conn->query($sql_categories)) {
    while ($row_category = $result_categories->fetch_assoc()) {
        $categories[] = $row_category;
    }
    $result_categories->free();
}

// Get filter and sort parameters
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest'; // Default sort

// Pagination
$limit = 8; // Number of products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build SQL query for counting total products
$count_sql = "SELECT COUNT(id) as total FROM products WHERE status = 1";
$sql = "SELECT id, name, price, sale_price, image, is_featured, is_coming_soon, is_bestseller, is_new FROM products WHERE status = 1";
$params = [];
$types = '';

if ($selected_category > 0) {
    $count_sql .= " AND category = ?";
    $sql .= " AND category = ?";
    $params[] = $selected_category;
    $types .= 'i';
}

// Get total number of products
$total_products = 0;
if ($stmt_count = $conn->prepare($count_sql)) {
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_products = $result_count->fetch_assoc()['total'];
    $stmt_count->close();
}

$total_pages = ceil($total_products / $limit);

switch ($sort_by) {
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY name DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY created_at DESC";
        break;
}

$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';


if ($stmt = $conn->prepare($sql)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
}

?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter"><?php echo htmlspecialchars(get_setting('shop_h1_title', 'All Products')); ?></h1>
            <p class="mt-4 text-lg text-black/70 max-w-2xl mx-auto"><?php echo htmlspecialchars(get_setting('shop_sub_title', 'Discover our curated collection of luxury streetwear, crafted with precision and passion.')); ?></p>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <form action="shop.php" method="GET" class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                <select name="category" id="category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block p-2.5">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $category_item): ?>
                        <option value="<?php echo $category_item['id']; ?>" <?php echo ($selected_category == $category_item['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category_item['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="sort_by" id="sort_by" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block p-2.5">
                    <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest Arrivals</option>
                    <option value="price_asc" <?php echo ($sort_by == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo ($sort_by == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="name_asc" <?php echo ($sort_by == 'name_asc') ? 'selected' : ''; ?>>Name: A-Z</option>
                    <option value="name_desc" <?php echo ($sort_by == 'name_desc') ? 'selected' : ''; ?>>Name: Z-A</option>
                </select>

                <button type="submit" class="bg-black text-white py-2.5 px-4 rounded-md text-sm font-semibold hover:bg-black/80 transition-colors">Apply Filters</button>
            </form>
        </div>

        <?php if (!empty($products)): ?>
            <!-- Product Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 md:gap-8">
                <?php foreach ($products as $product): ?>
                    <div class="group relative">
                        <a href="product/<?php echo $product['id']; ?>/<?php echo urlencode(str_replace(' ', '-', strtolower($product['name'] ?? 'product'))); ?>">
            <div class="relative aspect-w-1 aspect-h-1 bg-neutral-50 rounded-2xl overflow-hidden border border-transparent group-hover:border-black/20 transition-colors duration-300 hover:shadow-lg">
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
                                        src="<?php echo SITE_URL . htmlspecialchars($product['image']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        class="w-full h-full object-contain p-6 group-hover:scale-105 transition-transform duration-300 ease-out"
                                        loading="eager"
                                        onerror="this.src='<?php echo SITE_URL; ?>assets/images/product-placeholder.png'"
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
                                                onclick="event.preventDefault(); handleQuickAdd(<?php echo $product['id']; ?>, 'quick-add-form-shop-<?php echo $product['id']; ?>')"
                                                title="Add to Cart">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-4 4m0 0h18m-4 4H7m0 0l-2-2"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Hidden Quick Add Form -->
                                <form id="quick-add-form-shop-<?php echo $product['id']; ?>" class="hidden" action="ajax_cart_handler.php" method="POST">
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
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-12">
                    <nav aria-label="Page navigation">
                        <ul class="inline-flex items-center -space-x-px">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li>
                                    <a href="?page=<?php echo $i; ?>&category=<?php echo $selected_category; ?>&sort_by=<?php echo $sort_by; ?>" 
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
</main>



<?php
require_once 'includes/footer.php';
?>
