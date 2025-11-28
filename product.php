<?php
$pageTitle = "Product Details - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';
$conn = get_db_connection();

if (!isset($_GET['id'])) {
    http_response_code(404);
    echo "<h1>Product not found</h1>";
    exit();
}

$product_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
require_once 'includes/header.php';
require_once 'includes/recently_viewed_functions.php';

$product = null;
if (isset($_GET['id'])) {
    $product_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
    $sql = "SELECT id, name, description, price, sale_price, image, stock, is_featured, is_coming_soon, is_bestseller, is_new FROM products WHERE id = ? AND status = 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
        }
        $stmt->close();
    }
} else {
    // Fallback: Try to parse product ID from REQUEST_URI for direct URL access
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#^/product/(\d+)/#', $request_uri, $matches)) {
        $product_id = (int)$matches[1];
        $_GET['id'] = $product_id; // Set for consistency
        $sql = "SELECT id, name, description, price, sale_price, image, stock, is_featured, is_coming_soon, is_bestseller, is_new FROM products WHERE id = ? AND status = 1";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
            }
            $stmt->close();
        }
    }
}

    // Fetch additional product images and media
    if ($product) {
        $images_sql = "SELECT id, image_path, media_type, variant_color, variant_size, is_primary, sort_order, is_360_view
                      FROM product_images WHERE product_id = ?
                      ORDER BY is_primary DESC, sort_order ASC, id ASC";
        if ($stmt_imgs = $conn->prepare($images_sql)) {
            $stmt_imgs->bind_param("i", $product_id);
            $stmt_imgs->execute();
            $result_imgs = $stmt_imgs->get_result();
            $product_images = [];
            while ($row = $result_imgs->fetch_assoc()) {
                $product_images[] = $row;
            }
            $stmt_imgs->close();
        }

        // Get product variants for selection UI
        $product_variants = [];
        try {
            if (function_exists('get_product_variants_by_type')) {
                $product_variants = get_product_variants_by_type($product_id);
            }
        } catch (Exception $e) {
            error_log('Error getting product variants: ' . $e->getMessage());
            $product_variants = [];
        }

        // Get unique colors and sizes for filters
        $available_colors = [];
        $available_sizes = [];
        if (!empty($product_variants)) {
            foreach ($product_variants as $type => $variants) {
                if ($type === 'Color') {
                    $available_colors = array_column($variants, 'variant_value');
                } elseif ($type === 'Size') {
                    $available_sizes = array_column($variants, 'variant_value');
                }
            }
        }

        // Add to recently viewed (session-based)
        if (!isset($_SESSION['recently_viewed'])) {
            $_SESSION['recently_viewed'] = [];
        }
        // Remove if already exists, then add to beginning
        $_SESSION['recently_viewed'] = array_filter($_SESSION['recently_viewed'], function($id) use ($product_id) {
            return $id != $product_id;
        });
        array_unshift($_SESSION['recently_viewed'], $product_id);
        // Keep only last 10
$_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 10);
    }

    // Fetch approved reviews for this product with enhanced fields (only if product exists)
    $reviews = [];
    if ($product) {
        $reviews_sql = "SELECT r.rating, r.review_text, r.review_photos, r.verified_purchase, r.created_at, u.name as customer_name
                       FROM product_reviews r
                       JOIN users u ON r.user_id = u.id
                       WHERE r.product_id = ? AND r.is_approved = 1
                       ORDER BY r.created_at DESC";
        if ($reviews_stmt = $conn->prepare($reviews_sql)) {
            $reviews_stmt->bind_param("i", $product_id);
            $reviews_stmt->execute();
            $reviews_result = $reviews_stmt->get_result();
            while ($row = $reviews_result->fetch_assoc()) {
                // Decode review photos JSON if present
                if (!empty($row['review_photos'])) {
                    $row['review_photos'] = json_decode($row['review_photos'], true) ?: [];
                } else {
                    $row['review_photos'] = [];
                }
                $reviews[] = $row;
            }
            $reviews_stmt->close();
        }
    }
?>

    <!-- Reviews Section -->
<!-- <div class="mt-16">
    <h2 class="text-2xl md:text-3xl font-black uppercase tracking-tighter mb-8">Customer Reviews</h2>

    <!-- Display Approved Reviews -->
    <?php if (!empty($reviews)): ?>
        <div class="space-y-6 mb-8">
            <?php foreach ($reviews as $review): ?>
                <div class="border border-black/10 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="text-sm font-semibold"><?php echo htmlspecialchars($review['customer_name']); ?></div>
                            <!-- Verified Purchase Badge -->
                            <?php if ($review['verified_purchase']): ?>
                                <span class="inline-flex items-center bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full border border-green-200">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Verified Purchase
                                </span>
                            <?php endif; ?>
                            <div class="flex items-center">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="text-sm text-black/50"><?php echo date('d M Y', strtotime($review['created_at'])); ?></div>
                    </div>
                    <!-- Review Photos -->
                    <?php if (!empty($review['review_photos'])): ?>
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <?php foreach (array_slice($review['review_photos'], 0, 6) as $photo_url): ?>
                                <div class="aspect-square rounded-lg overflow-hidden border border-gray-200 group cursor-pointer"
                                     onclick="openReviewPhoto('<?php echo htmlspecialchars($photo_url); ?>')">
                                    <img src="<?php echo htmlspecialchars($photo_url); ?>"
                                         alt="Review photo"
                                         class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                                         onerror="this.style.display='none'">
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($review['review_photos']) > 6): ?>
                                <div class="aspect-square rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-xs text-gray-500">
                                    +<?php echo count($review['review_photos']) - 6; ?> more
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <p class="text-black/70"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Write Review Form (for logged-in users) -->
    <div id="review-form-section">
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
            <div class="bg-white border border-black/10 rounded-lg p-6">
                <h3 class="text-lg font-bold uppercase tracking-wider mb-4">Write a Review</h3>

                <!-- Check if user already reviewed this product -->
                <?php
                $user_reviewed = false;
                if (isset($_SESSION['user_id'])) {
                    $check_review_sql = "SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?";
                    if ($check_stmt = $conn->prepare($check_review_sql)) {
                        $check_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
                        $check_stmt->execute();
                        $check_stmt->store_result();
                        $user_reviewed = $check_stmt->num_rows > 0;
                        $check_stmt->close();
                    }
                }
                ?>

                <?php if ($user_reviewed): ?>
                    <p class="text-black/70">You have already submitted a review for this product. It will be published after approval.</p>
                <?php else: ?>
                    <form id="review-form">
                        <?php echo generate_csrf_token_input(); ?>
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

                        <!-- Rating -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                            <div class="flex gap-1" id="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button type="button" class="star-rating text-2xl text-gray-300 hover:text-yellow-400" data-rating="<?php echo $i; ?>">
                                        <i class="fas fa-star"></i>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="rating-input" required>
                            <p class="text-sm text-black/50 mt-1" id="rating-text">Click to rate</p>
                        </div>

                        <!-- Review Text -->
                        <div class="mb-4">
                            <label for="review_text" class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
                            <textarea name="review_text" id="review_text" rows="4" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"
                                      placeholder="Share your thoughts about this product..."></textarea>
                        </div>

                        <button type="submit" class="bg-black text-white px-6 py-2 rounded-md hover:bg-black/80 transition-colors">
                            Submit Review
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg">
                <p class="text-black/70 mb-4">Please <a href="login.php?redirect=product?id=<?php echo $product_id; ?>" class="text-black font-semibold underline">log in</a> to write a review.</p>
            </div>
        <?php endif; ?>
    </div>
</div> -->

<!-- Main Content -->
    <div class="container mx-auto px-4 py-16 md:py-24">
        <?php if ($product): ?>
            <div class="grid md:grid-cols-2 gap-8 lg:gap-16">
                <!-- Enhanced Product Image Gallery -->
                <div class="space-y-4">
                    <!-- Media Controls & View Options -->
                    <div class="flex gap-2 mb-2">
                        <button id="image-view-btn" class="px-3 py-1 text-xs bg-black text-white rounded-full">Images</button>
                                    <?php
                                    $hasVideo = false;
                                    $has360 = false;
                                    if (!empty($product_images)) {
                                        foreach ($product_images as $img) {
                                            if ($img['media_type'] === 'video') $hasVideo = true;
                                            if ($img['is_360_view']) $has360 = true;
                                        }
                                    }
                                    ?>
                                    <?php if ($hasVideo): ?>
                                    <button id="video-view-btn" class="px-3 py-1 text-xs bg-gray-300 text-black rounded-full">Video</button>
                                    <?php endif; ?>
                                    <?php if ($has360): ?>
                                    <button id="360-view-btn" class="px-3 py-1 text-xs bg-gray-300 text-black rounded-full">360°</button>
                                    <?php endif; ?>
                    </div>

                    <!-- Main Media Display with Zoom -->
                    <div class="relative aspect-w-1 aspect-h-1 bg-neutral-100 rounded-md overflow-hidden group">
                        <!-- Zoom Lens -->
                        <div id="zoom-lens" class="absolute w-32 h-32 bg-black/20 border border-white hidden pointer-events-none z-10 rounded-full"></div>

                        <!-- Zoom Display -->
                        <div id="zoom-result" class="absolute top-0 right-0 w-64 h-64 bg-white border border-gray-300 rounded-md shadow-lg hidden overflow-hidden z-20">
                            <img id="zoom-image" class="w-full h-full object-cover" src="">
                        </div>

                        <!-- Main Image Container -->
                        <div id="main-media-container" class="w-full h-full relative">
                            <!-- Main Image (Static or Selected) -->
                            <img id="main-product-image"
                                src="<?php echo SITE_URL . htmlspecialchars($product['image']); ?>"
                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                class="w-full h-full object-contain p-4 cursor-zoom-in"
                                onerror="this.src='https://placehold.co/600x600/f1f1f1/000000?text=Mossé+Luxe'"
                            >
                        </div>

                        <!-- Video Player (Hidden by default) -->
                        <div id="video-container" class="hidden w-full h-full">
                            <video id="product-video" controls class="w-full h-full object-contain" poster="">
                                <source src="" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>

                                    <!-- 360° View Container (Hidden by default) -->
                        <div id="360-container" class="hidden w-full h-full relative">
                            <div id="360-viewer" class="w-full h-full relative overflow-hidden">
                                <img id="360-image" src="" alt="360° View" class="w-full h-full object-contain transition-none">
                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 bg-black/70 rounded-lg p-2">
                                    <button id="prev-360" class="px-3 py-1 bg-white text-black rounded text-sm hover:bg-gray-200 transition-colors">◀ Prev</button>
                                    <div class="flex items-center gap-1 text-white text-xs">
                                        <span>Drag to spin</span>
                                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                    </div>
                                    <button id="next-360" class="px-3 py-1 bg-white text-black rounded text-sm hover:bg-gray-200 transition-colors">Next ▶</button>
                                </div>
                            </div>
                        </div>
                    <div class="flex flex-wrap items-center gap-4 md:gap-6">
                        <!-- Payment Method Icons -->
                        <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors">
                            <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Cards Accepted</span>
                        </div>

                        <!-- PayFast -->
                        <?php if (defined('PAYFAST_ENABLED') && PAYFAST_ENABLED): ?>
                        <div class="h-8 w-auto">
                            <img src="https://www.payfast.co.za/images/payfast_logo.png" alt="PayFast" class="h-full object-contain opacity-75 hover:opacity-100 transition-opacity">
                        </div>
                        <?php endif; ?>

                        <!-- SSL Certificate -->
                        <div class="flex items-center gap-2 bg-green-50 px-4 py-2 rounded-lg border border-green-200">
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-medium text-green-700">SSL Secured</span>
                        </div>

                        <!-- Trust Icons -->
                        <div class="flex items-center gap-2 bg-blue-50 px-4 py-2 rounded-lg border border-blue-200">
                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium text-blue-700">Verified</span>
                        </div>

                        <!-- Money Back Guarantee -->
                        <div class="flex items-center gap-2 bg-orange-50 px-4 py-2 rounded-lg border border-orange-200">
                            <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-medium text-orange-700">30-Day Returns</span>
                        </div>
                    </div>
                </div>
            </div>
                            </div>
                        </div>

                        <!-- Play Button Overlay for Videos -->
                        <div id="play-overlay" class="absolute inset-0 flex items-center justify-center hidden cursor-pointer" onclick="playVideo()">
                            <div class="bg-black/70 text-white rounded-full p-4">
                                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Thumbnails with Media Types -->
                    <?php if (!empty($product_images)): ?>
                        <div id="thumbnail-carousel" class="flex gap-2 overflow-x-auto pb-2">
                            <?php
                            // Group images by color/size for better organization
                            $image_groups = [];
                            foreach ($product_images as $img) {
                                $key = ($img['variant_color'] ?: 'default') . '_' . ($img['variant_size'] ?: 'default');
                                if (!isset($image_groups[$key])) {
                                    $image_groups[$key] = [];
                                }
                                $image_groups[$key][] = $img;
                            }

                            $all_images = $product_images;
                            $has_variants = !empty($available_colors) || !empty($available_sizes);

                            foreach ($all_images as $index => $img):
                                $variant_info = '';
                                if ($img['variant_color']) $variant_info .= "Color: {$img['variant_color']} ";
                                if ($img['variant_size']) $variant_info .= "Size: {$img['variant_size']}";
                                $variant_info = trim($variant_info);
                            ?>
                                <button type="button"
                                    onclick="changeMedia(<?php echo $index; ?>, '<?php echo htmlspecialchars($img['media_type']); ?>')"
                                    class="flex-shrink-0 w-16 h-16 border-2 rounded-md overflow-hidden hover:border-black transition-colors relative <?php echo $index === 0 ? 'border-black' : 'border-gray-300'; ?>"
                                    data-index="<?php echo $index; ?>"
                                    data-media-type="<?php echo htmlspecialchars($img['media_type']); ?>"
                                    data-variant-color="<?php echo htmlspecialchars($img['variant_color'] ?: ''); ?>"
                                    data-variant-size="<?php echo htmlspecialchars($img['variant_size'] ?: ''); ?>"
                                    title="<?php echo htmlspecialchars($variant_info); ?>"
                                >
                                    <?php if ($img['media_type'] === 'video'): ?>
                                        <img src="<?php echo SITE_URL . htmlspecialchars($img['image_path']); ?>" alt="" class="w-full h-full object-cover">
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </div>
                                    <?php elseif ($img['is_360_view']): ?>
                                        <img src="<?php echo SITE_URL . htmlspecialchars($img['image_path']); ?>" alt="" class="w-full h-full object-cover">
                                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-xs text-center">360°</div>
                                    <?php else: ?>
                                        <img src="<?php echo SITE_URL . htmlspecialchars($img['image_path']); ?>" alt="" class="w-full h-full object-cover">
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Variant Selection Controls -->
                    <?php if (!empty($available_colors) || !empty($available_sizes)): ?>
                        <div class="border-t border-gray-200 pt-4 space-y-4">
                            <?php if (!empty($available_colors)): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($available_colors as $color): ?>
                                            <button type="button"
                                                onclick="selectVariant('color', '<?php echo htmlspecialchars($color); ?>')"
                                                class="px-3 py-1 text-xs border border-gray-300 rounded-full hover:border-black transition-colors select-color-btn"
                                                data-color="<?php echo htmlspecialchars($color); ?>">
                                                <?php echo htmlspecialchars($color); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($available_sizes)): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Size</label>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($available_sizes as $size): ?>
                                            <button type="button"
                                                onclick="selectVariant('size', '<?php echo htmlspecialchars($size); ?>')"
                                                class="px-3 py-1 text-xs border border-gray-300 rounded-full hover:border-black transition-colors select-size-btn"
                                                data-size="<?php echo htmlspecialchars($size); ?>">
                                                <?php echo htmlspecialchars($size); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Details -->
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <?php if (!empty($product['name'])): ?>
                            <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tighter"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <?php else: ?>
                            <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tighter">PRODUCT</h1>
                        <?php endif; ?>
                        <!-- Premium Badges -->
                        <div class="flex flex-wrap gap-2">
                            <?php if ($product['is_featured']): ?>
                                <span class="inline-flex items-center bg-gradient-to-r from-amber-400 to-orange-500 text-black text-xs font-black px-3 py-2 rounded-full shadow-lg border-2 border-white/30">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
></path></svg>
                                    FEATURED
                                </span>
                            <?php endif; ?>
                            <?php if ($product['is_bestseller']): ?>
                                <span class="inline-flex items-center bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-xs font-black px-3 py-2 rounded-full shadow-lg border-2 border-white/20">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                                          clip-rule="evenodd"></path></svg>
                                    BESTSELLER
                                </span>
                            <?php endif; ?>
                            <?php if ($product['is_new']): ?>
                                <span class="inline-flex items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white text-xs font-black px-3 py-2 rounded-full shadow-lg border-2 border-white/20 backdrop-blur-sm">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                          clip-rule="evenodd"></path></svg>
                                    NEW
                                </span>
                            <?php endif; ?>
                            <?php if ($product['is_coming_soon']): ?>
                                <span class="inline-flex items-center bg-gradient-to-r from-purple-500 to-pink-600 text-white text-xs font-black px-3 py-2 rounded-full shadow-lg border-2 border-white/20">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                          clip-rule="evenodd"></path></svg>
                                    COMING SOON
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4">
                        <?php if ($product['sale_price'] > 0): ?>
                            <p class="text-3xl font-semibold"><span class="text-red-600">R <?php echo number_format($product['sale_price'], 2); ?></span> <span class="line-through text-black/50 text-xl">R <?php echo number_format($product['price'], 2); ?></span></p>
                        <?php else: ?>
                            <p class="text-3xl font-semibold">R <?php echo number_format($product['price'], 2); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mt-6">
                        <p class="text-black/70"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>

                            <div class="mt-8">
                                <form id="add-to-cart-form">
                                    <?php echo generate_csrf_token_input(); ?>
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <div class="flex items-center">
                                        <label for="quantity" class="mr-4 font-semibold">Quantity:</label>
                                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="w-20 p-2 border border-black/20 rounded-md text-center">
                                    </div>
                                </form>
                            <div class="mt-6 space-y-3">
                                <?php if ($product['stock'] > 0): ?>
                                    <button type="submit" form="add-to-cart-form" class="w-full bg-black text-white font-bold uppercase py-3 px-4 rounded-md hover:bg-black/80 transition-colors duration-300 flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-4 4m0 0h18m-4 4H7m0 0l-2-2"></path>
                                        </svg>
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <div class="space-y-2">
                                        <button type="button" class="w-full bg-neutral-400 text-white font-bold uppercase py-3 px-4 rounded-md cursor-not-allowed flex items-center justify-center gap-2" disabled>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728m0 0L5.636 5.636m12.728 12.728L5.636 18.364"></path>
                                            </svg>
                                            Out of Stock
                                        </button>
                                        <!-- Notify Me Button -->
                                        <button type="button"
                                                id="notify-me-btn"
                                                class="w-full bg-blue-600 text-white font-bold uppercase py-3 px-4 rounded-md hover:bg-blue-700 transition-colors duration-300 flex items-center justify-center gap-2"
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                            </svg>
                                            Notify Me When Available
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <!-- Wishlist & Secondary Actions -->
                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button"
                                            id="wishlist-toggle-btn"
                                            class="w-full bg-neutral-100 text-black font-bold uppercase py-3 px-4 rounded-md hover:bg-neutral-200 transition-colors duration-300 flex items-center justify-center gap-2 text-sm"
                                            data-product-id="<?php echo $product['id']; ?>">
                                        <svg class="w-4 h-4 wishlist-heart-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
                                        <span class="wishlist-btn-text">Add to Wishlist</span>
                                    </button>

                                    <!-- WhatsApp Inquiry Button -->
                                    <button type="button"
                                            id="whatsapp-inquiry-btn"
                                            class="w-full bg-green-600 text-white font-bold uppercase py-3 px-4 rounded-md hover:bg-green-700 transition-colors duration-300 flex items-center justify-center gap-2 text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                        <span class="hidden sm:inline">WhatsApp</span>
                                        <span class="sm:hidden">Chat</span>
                                    </button>
                                </div>

                                <!-- Price Alert -->
                                <div class="border-t border-gray-200 pt-4">
                                    <div class="flex gap-3">
                                        <div class="flex-1">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Set Price Alert</label>
                                            <div class="flex gap-2">
                                                <input type="number"
                                                       id="price-alert-input"
                                                       class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded-md"
                                                       placeholder="R<?php echo number_format($product['sale_price'] ?: $product['price'], 2); ?>"
                                                       min="0.01"
                                                       step="0.01">
                                                <button type="button"
                                                        id="set-price-alert-btn"
                                                        class="px-3 py-1 bg-orange-600 text-white text-xs font-bold uppercase rounded-md hover:bg-orange-700 transition-colors"
                                                        data-product-id="<?php echo $product['id']; ?>">
                                                    Set Alert
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Get notified when price drops</p>
                                        </div>

                                    </div>
                                </div>
                            </div>
                    </div>

                    <!-- Social Share Buttons -->
                    <div class="mt-8">
                        <h3 class="text-lg font-bold uppercase tracking-wider mb-4">Share This Product</h3>
                        <div class="flex gap-3">
                            <button onclick="shareOnWhatsApp()" class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347
m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884
m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                                </svg>
                                WhatsApp
                            </button>
                            <button onclick="shareOnInstagram()" class="flex items-center gap-2 bg-pink-600 text-white px-4 py-2 rounded-md hover:bg-pink-700 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12.017 0C8.396 0 7.609.043 6.353.096 5.099.149 4.25.31 3.587.637c-.634.302-1.174.707-1.619 1.152-.445.445-.85.985-.985 1.619C.878 5.645.98 6.35.98 9.97s-.149 4.27-.31 5.523c-.149 1.255-.19 2.042-.19 5.663s.041 4.408.095 5.663c.053 1.256.209 2.105.552 2.768.302.634.707 1.174 1.152 1.619.445.445.985.85 1.619.985.638.301 1.405.415 2.656.415 3.62 0 4.407-.043 5.663-.096 1.256-.053 2.105-.209 2.768-.552.634-.302 1.174-.707 1.619-1.152.445-.445.85-.985.985-1.619.315-.638.415-1.405.415-2.656 0-3.62-.043-4.407-.096-5.663-.053-1.256-.209-2.105-.552-2.768-.302-.634-.707-1.174-1.152-1.619-.445-.445-.985-.85-1.619-.985-.638-.301-1.405-.415-2.656-.415-3.62 0-4.407.043-5.663.096-1.256.053-2.105.209-2.768.552-.302.302-.443.636-.552.985-.107.349-.096.854-.096 2.424 0 3.674.043 4.161.096 5.417.053 1.256.149 2.105.31 2.768.302.634.707 1.174 1.152 1.619.445.445.985.85 1.619.985.638.301 1.405.415 2.656.415 3.62 0 4.407-.043 5.663-.096 1.256-.053 2.105-.209 2.768-.552.302-.302.443-.636.552-.985.107-.349.096-.854.096-2.424 0-3.674-.043-4.161-.096-5.417-.053-1.256-.149-2.105-.31-2.768-.302-.634-.707-1.174-1.152-1.619-.445-.445-.985-.85-1.619-.985-.638-.301-1.405-.415-2.656-.415zM12.017 5.838c3.72 0 6.766 3.046 6.766 6.766s-3.046 6.766-6.766 6.766c-3.72 0-6.766-3.046-6.766-6.766s3.046-6.766 6.766-6.766zm0 11.181c2.545 0 4.609-2.064 4.609-4.609s-2.064-4.609-4.609-4.609c-2.545 0-4.609 2.064-4.609 4.609S9.472 17.019 12.017 17.019zM18.406 7.027c.867 0 1.571-.704 1.571-1.571s-.704-1.571-1.571-1.571-1.571.704-1.571 1.571.704 1.571 1.571 1.571z"/>
                                </svg>
                                Instagram
                            </button>
                            <button onclick="shareOnFacebook()" class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                Facebook
                            </button>
                            <button onclick="shareOnTikTok()" class="flex items-center gap-2 bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-1.13 5.15.21 1.46 1.45 2.25 3.48 2.45 5.59.26 2.75-.69 5.51-2.64 7.14-.86.72-2.02.98-3.15.97-.66-.01-1.32-.1-1.96-.28-.15-.07-.29-.18-.42-.32-.74-.74-.89-1.89-.75-2.92.31-1.78 1.41-3.31 2.99-4.02l.12-2.88c-.62-.04-1.25-.07-1.88-.05-.59.02-1.18.09-1.76.19-.06-.84-.09-1.68-.06-2.52.41-.25.9-.43 1.4-.51.93-.15 1.89-.21 2.84-.22.82-.01 1.64.07 2.45.22.02-.02.04-.02.05-.02.03-.03.05-.07.06-.11v-.35c-.04-.02-.07-.04-.08-.05-.02-.01-.03-.02-.05-.02-.08-.04-.17-.08-.27-.11-1.23-.27-2.48-.21-3.68.32-.3.06-.58.18-.84.36-.03.02-.06.03-.09.05-.17.16-.3.36-.39.57-.06.21-.09.43-.09.64 0 .22.08.44.24.59.16.15.36.2.56.16.24-.04.49-.04.68 0 .16.04.32.07.48.11.63.19 1.35.38 2.13.48.4.05.8.09 1.21.09z"/>
                                </svg>
                                TikTok
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Track this product view -->
            <?php
            trackProductView($conn, $product_id);
            require_once 'includes/related_products_functions.php';
            ?>

            <!-- Related Products Section -->
            <?php
            $related_products = getRelatedProducts($conn, $product_id, 'related', 4);
            if (!empty($related_products)):
            ?>
            <div class="container mx-auto px-4 py-16">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-3xl font-bold mb-2">You May Also Like</h2>
                    <p class="text-gray-600 mb-8">Customers who viewed this item also viewed</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <?php foreach ($related_products as $related): ?>
                        <div class="group bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition-all">
                            <a href="product/<?php echo $related['id']; ?>/<?php echo urlencode(str_replace(' ', '-', strtolower($related['name']))); ?>">
                                <div class="relative aspect-square bg-gray-100">
                                    <img src="<?php echo SITE_URL . htmlspecialchars($related['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['name']); ?>"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    <?php if ($related['sale_price'] && $related['sale_price'] < $related['price']): ?>
                                        <span class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 text-xs font-bold rounded">
                                            SALE
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="p-4">
                                    <h3 class="font-semibold text-sm mb-2 line-clamp-2"><?php echo htmlspecialchars($related['name']); ?></h3>
                                    <div class="flex items-center gap-2">
                                        <?php if ($related['sale_price'] && $related['sale_price'] < $related['price']): ?>
                                            <span class="text-lg font-bold text-red-600">R <?php echo number_format($related['sale_price'], 2); ?></span>
                                            <span class="text-sm text-gray-500 line-through">R <?php echo number_format($related['price'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-lg font-bold">R <?php echo number_format($related['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Frequently Bought Together -->
            <?php
            $frequently_bought = getFrequentlyBoughtTogether($conn, $product_id, 3);
            if (!empty($frequently_bought)):
            ?>
            <div class="container mx-auto px-4 py-16 bg-gray-50">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-3xl font-bold mb-2">Frequently Bought Together</h2>
                    <p class="text-gray-600 mb-8">Save when you buy these items together</p>
                    
                    <div class="bg-white rounded-lg p-6 shadow-md">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                            <!-- Current Product -->
                            <div class="md:col-span-3">
                                <div class="text-center">
                                    <img src="<?php echo SITE_URL . htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="w-full rounded-lg mb-2">
                                    <p class="font-semibold text-sm"><?php echo htmlspecialchars($product['name']); ?></p>
                                    <p class="text-lg font-bold">R <?php echo number_format($product['price'], 2); ?></p>
                                </div>
                            </div>
                            
                            <div class="md:col-span-1 text-center text-2xl font-bold">+</div>
                            
                            <!-- Frequently Bought Products -->
                            <?php 
                            $bundle_total = $product['price'];
                            foreach ($frequently_bought as $index => $bundle_product): 
                                $bundle_total += $bundle_product['price'];
                            ?>
                            <div class="md:col-span-3">
                                <div class="text-center">
                                    <img src="<?php echo SITE_URL . htmlspecialchars($bundle_product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($bundle_product['name']); ?>"
                                         class="w-full rounded-lg mb-2">
                                    <p class="font-semibold text-sm"><?php echo htmlspecialchars($bundle_product['name']); ?></p>
                                    <p class="text-lg font-bold">R <?php echo number_format($bundle_product['price'], 2); ?></p>
                                </div>
                            </div>
                            <?php if ($index < count($frequently_bought) - 1): ?>
                            <div class="md:col-span-1 text-center text-2xl font-bold">+</div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="text-sm text-gray-600">Bundle Price:</p>
                                    <p class="text-3xl font-bold text-green-600">R <?php echo number_format($bundle_total, 2); ?></p>
                                </div>
                                <button onclick="addBundleToCart()" class="bg-black text-white px-8 py-3 rounded-md font-semibold hover:bg-black/80 transition-colors">
                                    Add All to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recently Viewed Products -->
            <?php
            $recently_viewed = getRecentlyViewedProducts($conn, 8, $product_id);
            if (!empty($recently_viewed)):
            ?>
            <div class="container mx-auto px-4 py-16 bg-gray-50">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-3xl font-bold mb-8">Recently Viewed</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-6">
                        <?php foreach ($recently_viewed as $viewed): ?>
                        <div class="group bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <a href="product/<?php echo $viewed['id']; ?>/<?php echo urlencode(str_replace(' ', '-', strtolower($viewed['name']))); ?>">
                                <div class="relative aspect-square bg-gray-100">
                                    <img src="<?php echo SITE_URL . htmlspecialchars($viewed['image']); ?>" alt="<?php echo htmlspecialchars($viewed['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                </div>
                                <div class="p-4">
                                    <h3 class="font-semibold mb-2 text-sm group-hover:underline line-clamp-2"><?php echo htmlspecialchars($viewed['name']); ?></h3>
                                    <p class="text-lg font-bold">R <?php echo number_format($viewed['sale_price'] > 0 ? $viewed['sale_price'] : $viewed['price'], 2); ?></p>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reviews Section -->
            <?php
            require_once 'includes/review_functions.php';
            $reviews = getProductReviews($conn, $product_id, 10, 0);
            $review_stats = getReviewStats($conn, $product_id);
            ?>
            <div class="container mx-auto px-4 py-16 border-t border-gray-200">
                <div class="max-w-6xl mx-auto">
                    <!-- Reviews Header -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                        <div>
                            <h2 class="text-3xl font-bold mb-2">Customer Reviews</h2>
                            <?php if ($review_stats['total_reviews'] > 0): ?>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center">
                                        <?php
                                        $avg_rating = round($review_stats['average_rating'], 1);
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= floor($avg_rating)): ?>
                                                <svg class="w-5 h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                            <?php elseif ($i - 0.5 <= $avg_rating): ?>
                                                <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 20 20"><defs><linearGradient id="half"><stop offset="50%" stop-color="#FBBF24"/><stop offset="50%" stop-color="#E5E7EB"/></linearGradient></defs><path fill="url(#half)" d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                            <?php else: ?>
                                                <svg class="w-5 h-5 text-gray-300 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                            <?php endif;
                                        endfor; ?>
                                    </div>
                                    <span class="text-lg font-semibold"><?php echo $avg_rating; ?> out of 5</span>
                                    <span class="text-gray-500">(<?php echo $review_stats['total_reviews']; ?> reviews)</span>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500">No reviews yet. Be the first to review!</p>
                            <?php endif; ?>
                        </div>
                        <button id="write-review-btn" class="bg-black text-white px-6 py-3 rounded-md font-semibold hover:bg-black/80 transition-colors whitespace-nowrap">
                            Write a Review
                        </button>
                    </div>

                    <!-- Rating Distribution -->
                    <?php if ($review_stats['total_reviews'] > 0): ?>
                    <div class="bg-gray-50 rounded-lg p-6 mb-8">
                        <h3 class="font-semibold mb-4">Rating Distribution</h3>
                        <div class="space-y-2">
                            <?php
                            for ($star = 5; $star >= 1; $star--):
                                $count = $review_stats[$star == 5 ? 'five_star' : ($star == 4 ? 'four_star' : ($star == 3 ? 'three_star' : ($star == 2 ? 'two_star' : 'one_star')))];
                                $percentage = $review_stats['total_reviews'] > 0 ? ($count / $review_stats['total_reviews']) * 100 : 0;
                            ?>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium w-12"><?php echo $star; ?> star</span>
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="bg-yellow-400 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="text-sm text-gray-600 w-12 text-right"><?php echo $count; ?></span>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Reviews List -->
                    <div id="reviews-list" class="space-y-6">
                        <?php foreach ($reviews as $review): ?>
                        <div class="border-b border-gray-200 pb-6">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold"><?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                                        <?php if ($review['verified_purchase']): ?>
                                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">✓ Verified Purchase</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <svg class="w-4 h-4 <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?> fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-sm text-gray-500"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <h4 class="font-semibold mb-2"><?php echo htmlspecialchars($review['title']); ?></h4>
                            <p class="text-gray-700 mb-3"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                            <?php if (!empty($review['photos'])): ?>
                            <div class="flex gap-2 mb-3">
                                <?php foreach ($review['photos'] as $photo): ?>
                                    <img src="<?php echo SITE_URL . htmlspecialchars($photo); ?>" alt="Review photo" class="w-20 h-20 object-cover rounded-md cursor-pointer hover:opacity-75 transition-opacity" onclick="openPhotoModal(this.src)">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <button class="text-sm text-gray-600 hover:text-black transition-colors" onclick="markHelpful(<?php echo $review['id']; ?>)">
                                Helpful (<?php echo $review['helpful_count']; ?>)
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($reviews)): ?>
                    <div class="text-center py-12 text-gray-500">
                        <p class="text-lg mb-2">No reviews yet</p>
                        <p>Be the first to share your thoughts about this product!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Review Submission Modal -->
            <div id="review-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
                <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-2xl font-bold">Write a Review</h3>
                            <button id="close-review-modal" class="text-gray-500 hover:text-black">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <form id="review-form" enctype="multipart/form-data">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <!-- Rating -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">Rating <span class="text-red-500">*</span></label>
                                <div class="flex gap-2" id="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-8 h-8 text-gray-300 cursor-pointer hover:text-yellow-400 transition-colors star-icon" data-rating="<?php echo $i; ?>" viewBox="0 0 20 20"><path fill="currentColor" d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="rating-input" required>
                            </div>

                            <!-- Title -->
                            <div class="mb-4">
                                <label for="review-title" class="block text-sm font-medium mb-2">Review Title <span class="text-red-500">*</span></label>
                                <input type="text" id="review-title" name="title" required placeholder="Sum up your experience" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-black focus:border-transparent">
                            </div>

                            <!-- Review Text -->
                            <div class="mb-4">
                                <label for="review-text" class="block text-sm font-medium mb-2">Your Review <span class="text-red-500">*</span></label>
                                <textarea id="review-text" name="review_text" required rows="5" placeholder="Share your thoughts about this product..." class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-black focus:border-transparent"></textarea>
                            </div>

                            <!-- Guest Info (if not logged in) -->
                            <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="guest-name" class="block text-sm font-medium mb-2">Your Name <span class="text-red-500">*</span></label>
                                    <input type="text" id="guest-name" name="guest_name" required placeholder="John Doe" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-black focus:border-transparent">
                                </div>
                                <div>
                                    <label for="guest-email" class="block text-sm font-medium mb-2">Your Email <span class="text-red-500">*</span></label>
                                    <input type="email" id="guest-email" name="guest_email" required placeholder="john@example.com" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-black focus:border-transparent">
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Photo Upload -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium mb-2">Add Photos (Optional)</label>
                                <input type="file" name="photos[]" id="review-photos" multiple accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-md">
                                <p class="text-xs text-gray-500 mt-1">You can upload up to 5 photos</p>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" id="submit-review-btn" class="w-full bg-black text-white py-3 rounded-md font-semibold hover:bg-black/80 transition-colors">
                                Submit Review
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="container mx-auto px-4 py-16 md:py-24 text-center">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-8">Product Not Found</h1>
                <p class="text-black/70 mb-8">The product you're looking for doesn't exist or has been removed.</p>
                <a href="shop.php" class="bg-black text-white px-6 py-3 rounded-md hover:bg-black/80 transition-colors">
                    Browse Products
                </a>
            </div>
        <?php endif; ?>
        
<?php if ($product): ?>
<script>
window.productImages = <?php echo json_encode(isset($product_images) ? $product_images : [], JSON_PRETTY_PRINT); ?>;
</script>

<script>
// Product Gallery and Interactive Features
document.addEventListener('DOMContentLoaded', function() {
    // Product image gallery variables
    const productImages = window.productImages;
    const mainContainer = document.getElementById('main-media-container');
    const mainImage = document.getElementById('main-product-image');
    const videoContainer = document.getElementById('video-container');
    const videoPlayer = document.getElementById('product-video');
    const view360Container = document.getElementById('360-container');
    const view360Image = document.getElementById('360-image');

    // Media control buttons
    const imageViewBtn = document.getElementById('image-view-btn');
    const videoViewBtn = document.getElementById('video-view-btn');
    const view360Btn = document.getElementById('360-view-btn');

    /**
     * Change media (image/video/360) in main display
     */
    window.changeMedia = function(imageIndex, mediaType) {
        const imageData = productImages[imageIndex];
        if (!imageData) return;

        // Reset all containers
        mainContainer.classList.add('hidden');
        videoContainer.classList.add('hidden');
        view360Container.classList.add('hidden');

        if (mediaType === 'video') {
            videoContainer.classList.remove('hidden');
            videoPlayer.src = window.SITE_URL + imageData.image_path;
            videoPlayer.poster = window.SITE_URL + imageData.image_path.replace('.mp4', '.jpg') || mainImage.src;
            videoPlayer.load();
        } else if (imageData.is_360_view) {
            view360Container.classList.remove('hidden');
            view360Image.src = window.SITE_URL + imageData.image_path;
            if (view360Btn) {
                view360Btn.classList.remove('bg-gray-300', 'text-black');
                view360Btn.classList.add('bg-black', 'text-white');
            }
        } else {
            mainContainer.classList.remove('hidden');
            mainImage.src = window.SITE_URL + imageData.image_path;
            if (imageViewBtn) {
                imageViewBtn.classList.remove('bg-gray-300', 'text-black');
                imageViewBtn.classList.add('bg-black', 'text-white');
            }
        }

        // Update thumbnail selection
        document.querySelectorAll('#thumbnail-carousel button').forEach(btn => {
            btn.classList.remove('border-black');
            btn.classList.add('border-gray-300');
        });
        const selectedThumbnail = document.querySelector(`button[data-index="${imageIndex}"]`);
        if (selectedThumbnail) {
            selectedThumbnail.classList.remove('border-gray-300');
            selectedThumbnail.classList.add('border-black');
        }
    };

    /**
     * Switch view mode (images/videos/360)
     */
    function switchView(viewType) {
        // Update button styles
        [imageViewBtn, videoViewBtn, view360Btn].forEach(btn => {
            if (btn) {
                btn.classList.remove('bg-black', 'text-white');
                btn.classList.add('bg-gray-300', 'text-black');
            }
        });

        const activeBtn = viewType === 'image' ? imageViewBtn :
                         viewType === 'video' ? videoViewBtn :
                         view360Btn;
        if (activeBtn) {
            activeBtn.classList.remove('bg-gray-300', 'text-black');
            activeBtn.classList.add('bg-black', 'text-white');
        }

        // Hide all containers
        mainContainer.classList.add('hidden');
        videoContainer.classList.add('hidden');
        view360Container.classList.add('hidden');

        // Show active container
        if (viewType === 'image') {
            mainContainer.classList.remove('hidden');
        } else if (viewType === 'video') {
            videoContainer.classList.remove('hidden');
        } else if (viewType === '360') {
            view360Container.classList.remove('hidden');
        }
    }

    // View button event listeners
    if (imageViewBtn) imageViewBtn.addEventListener('click', () => switchView('image'));
    if (videoViewBtn) videoViewBtn.addEventListener('click', () => switchView('video'));
    if (view360Btn) view360Btn.addEventListener('click', () => switchView('360'));

    /**
     * Select product variant (color/size)
     */
    window.selectVariant = function(type, value) {
        const buttons = document.querySelectorAll(type === 'color' ? '.select-color-btn' : '.select-size-btn');
        buttons.forEach(btn => {
            btn.classList.remove('border-black');
            btn.classList.add('border-gray-300');
        });
        const selectedBtn = document.querySelector(`[data-${type}="${value}"]`);
        if (selectedBtn) {
            selectedBtn.classList.remove('border-gray-300');
            selectedBtn.classList.add('border-black');
        }

        // Filter thumbnails based on selected variants
        updateThumbnailVisibility();
    };

    /**
     * Update thumbnail visibility based on selected variants
     */
    function updateThumbnailVisibility() {
        const selectedColor = document.querySelector('.select-color-btn.border-black')?.dataset.color;
        const selectedSize = document.querySelector('.select-size-btn.border-black')?.dataset.size;

        document.querySelectorAll('#thumbnail-carousel button').forEach(btn => {
            const imgColor = btn.dataset.variantColor;
            const imgSize = btn.dataset.variantSize;

            // Show all if no variant selected, or show matching ones
            const colorMatch = !selectedColor || imgColor === selectedColor || !imgColor;
            const sizeMatch = !selectedSize || imgSize === selectedSize || !imgSize;

            btn.style.display = (colorMatch && sizeMatch) ? 'block' : 'none';
        });
    }

    /**
     * Play video
     */
    window.playVideo = function() {
        if (videoPlayer) {
            videoPlayer.play();
            const playOverlay = document.getElementById('play-overlay');
            if (playOverlay) playOverlay.classList.add('hidden');
        }
    };

    // Video overlay click to play
    const playOverlay = document.getElementById('play-overlay');
    if (playOverlay) {
        playOverlay.addEventListener('click', playVideo);
    }

    // WhatsApp inquiry
    const whatsappBtn = document.getElementById('whatsapp-inquiry-btn');
    if (whatsappBtn) {
        whatsappBtn.addEventListener('click', function() {
            const productName = "<?php echo addslashes($product['name']); ?>";
            if (window.openWhatsAppInquiry) {
                // Use the global function from main.js
                window.openWhatsAppInquiry(productName);
            } else {
                // Fallback if main.js not loaded
                const message = `Hi, I'm interested in the ${productName}. Can you provide more details?`;
                const whatsappNumber = '<?php echo WHATSAPP_NUMBER; ?>';
                const cleanNum = whatsappNumber.replace(/\+/g, '');
                const url = `https://wa.me/${cleanNum}?text=${encodeURIComponent(message)}`;
                window.open(url, '_blank', 'noopener,noreferrer');
            }
        });
    }

    // Social share functions
    window.shareOnWhatsApp = function() {
        const productName = "<?php echo addslashes($product['name']); ?>";
        const url = window.location.href;
        const message = "Check out this product: " + productName + " - " + url;
        const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');
    };

    window.shareOnInstagram = function() {
        // Instagram sharing typically requires their API or just copy link
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Product link copied to clipboard! You can now share it on Instagram.');
        });
    };

    window.shareOnFacebook = function() {
        const url = window.location.href;
        const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
        window.open(facebookUrl, '_blank');
    };

    window.shareOnTikTok = function() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Product link copied to clipboard! You can now share it on TikTok.');
        });
    };

    // Price alerts
    const priceAlertBtn = document.getElementById('set-price-alert-btn');
    const priceAlertInput = document.getElementById('price-alert-input');
    if (priceAlertBtn && priceAlertInput) {
        priceAlertBtn.addEventListener('click', function() {
            const alertPrice = parseFloat(priceAlertInput.value);
            if (isNaN(alertPrice) || alertPrice <= 0) {
                alert('Please enter a valid price.');
                return;
            }
            // In a real app, this would send to backend
            alert('Price alert set! We will notify you when the price drops to R' + alertPrice.toFixed(2));
        });
    }

    // Notify Me Modal functionality
    const notifyMeBtn = document.getElementById('notify-me-btn');
    const notifyModal = document.getElementById('notify-me-modal');
    const notifyOverlay = document.getElementById('notify-modal-overlay');
    const notifyCancelBtn = document.getElementById('notify-cancel-btn');
    const notifyForm = document.getElementById('notify-me-form');
    const notifyProductId = document.getElementById('notify-product-id');
    const notifySubmitBtn = document.getElementById('notify-submit-btn');
    const notifySpinner = document.getElementById('notify-spinner');
    const notifyBtnText = document.getElementById('notify-btn-text');
    const notifyMessage = document.getElementById('notify-message');
    const notifySuccess = document.getElementById('notify-success');
    const notifyError = document.getElementById('notify-error');
    const notifySuccessText = document.getElementById('notify-success-text');
    const notifyErrorText = document.getElementById('notify-error-text');

    // Open modal
    if (notifyMeBtn) {
        notifyMeBtn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            
            notifyProductId.value = productId;
            document.getElementById('notify-modal-product-name').textContent = 
                `We'll send you an email when "${productName}" is back in stock.`;
            
            // Reset form state
            notifyMessage.classList.add('hidden');
            notifySuccess.classList.add('hidden');
            notifyError.classList.add('hidden');
            notifySubmitBtn.disabled = false;
            notifySpinner.classList.add('hidden');
            notifyBtnText.textContent = 'Notify Me';
            
            notifyModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
    }

    // Close modal functions
    function closeNotifyModal() {
        notifyModal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    if (notifyCancelBtn) {
        notifyCancelBtn.addEventListener('click', closeNotifyModal);
    }

    if (notifyOverlay) {
        notifyOverlay.addEventListener('click', closeNotifyModal);
    }

    // Handle form submission
    if (notifyForm) {
        notifyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            notifySubmitBtn.disabled = true;
            notifySpinner.classList.remove('hidden');
            notifyBtnText.textContent = 'Submitting...';
            notifyMessage.classList.add('hidden');
            notifySuccess.classList.add('hidden');
            notifyError.classList.add('hidden');

            const formData = new FormData(notifyForm);
            
            fetch(window.SITE_URL + 'ajax_notify_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                notifySpinner.classList.add('hidden');
                notifyMessage.classList.remove('hidden');
                
                if (data.success) {
                    notifySuccess.classList.remove('hidden');
                    notifySuccessText.textContent = data.message || "You'll be notified when this product is back in stock!";
                    notifyBtnText.textContent = 'Done!';
                    
                    // Update the main button to show subscribed state
                    if (notifyMeBtn) {
                        notifyMeBtn.innerHTML = `
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Notification Set!
                        `;
                        notifyMeBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                        notifyMeBtn.classList.add('bg-green-600', 'cursor-default');
                        notifyMeBtn.disabled = true;
                    }
                    
                    // Close modal after 2 seconds
                    setTimeout(closeNotifyModal, 2000);
                } else {
                    notifyError.classList.remove('hidden');
                    notifyErrorText.textContent = data.message || 'Something went wrong. Please try again.';
                    notifySubmitBtn.disabled = false;
                    notifyBtnText.textContent = 'Try Again';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                notifySpinner.classList.add('hidden');
                notifyMessage.classList.remove('hidden');
                notifyError.classList.remove('hidden');
                notifyErrorText.textContent = 'Network error. Please check your connection and try again.';
                notifySubmitBtn.disabled = false;
                notifyBtnText.textContent = 'Try Again';
            });
        });
    }

    // Wishlist toggle
    const wishlistBtn = document.getElementById('wishlist-toggle-btn');
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            // Toggle wishlist (simplified, in real app would use AJAX)
            const icon = this.querySelector('svg');
            const text = this.querySelector('.wishlist-btn-text');

            if (icon.classList.contains('text-red-600')) {
                // Remove from wishlist
                icon.setAttribute('fill', 'none');
                icon.classList.remove('text-red-600');
                this.classList.remove('text-red-600', 'bg-red-50');
                text.textContent = 'Add to Wishlist';
            } else {
                // Add to wishlist
                icon.setAttribute('fill', 'currentColor');
                icon.classList.add('text-red-600');
                this.classList.add('text-red-600', 'bg-red-50');
                text.textContent = 'Remove from Wishlist';
            }
        });
    }

    // Initialize: show first image and set active button
    if (productImages.length > 0) {
        const firstImage = productImages[0];
        if (firstImage.media_type === 'video') {
            videoContainer.classList.remove('hidden');
            videoPlayer.src = window.SITE_URL + firstImage.image_path;
            videoPlayer.poster = window.SITE_URL + firstImage.image_path.replace('.mp4', '.jpg') || mainImage.src;
            if (videoViewBtn) switchView('video');
        } else if (firstImage.is_360_view) {
            view360Container.classList.remove('hidden');
            view360Image.src = window.SITE_URL + firstImage.image_path;
            if (view360Btn) switchView('360');
        } else {
            mainContainer.classList.remove('hidden');
            mainImage.src = window.SITE_URL + firstImage.image_path;
            if (imageViewBtn) switchView('image');
        }
    }


});

// Review System JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const reviewModal = document.getElementById('review-modal');
    const writeReviewBtn = document.getElementById('write-review-btn');
    const closeModalBtn = document.getElementById('close-review-modal');
    const reviewForm = document.getElementById('review-form');
    const starIcons = document.querySelectorAll('.star-icon');
    const ratingInput = document.getElementById('rating-input');
    let selectedRating = 0;

    // Open review modal
    writeReviewBtn?.addEventListener('click', function() {
        reviewModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    });

    // Close review modal
    closeModalBtn?.addEventListener('click', function() {
        reviewModal.classList.add('hidden');
        document.body.style.overflow = '';
    });

    // Close modal on outside click
    reviewModal?.addEventListener('click', function(e) {
        if (e.target === reviewModal) {
            reviewModal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    });

    // Star rating interaction
    starIcons.forEach(star => {
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.dataset.rating);
            ratingInput.value = selectedRating;
            updateStars();
        });

        star.addEventListener('mouseenter', function() {
            const hoverRating = parseInt(this.dataset.rating);
            starIcons.forEach((s, index) => {
                if (index < hoverRating) {
                    s.classList.remove('text-gray-300');
                    s.classList.add('text-yellow-400');
                } else {
                    s.classList.remove('text-yellow-400');
                    s.classList.add('text-gray-300');
                }
            });
        });
    });

    document.getElementById('star-rating')?.addEventListener('mouseleave', function() {
        updateStars();
    });

    function updateStars() {
        starIcons.forEach((star, index) => {
            if (index < selectedRating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
    }

    // Handle review form submission
    reviewForm?.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!selectedRating) {
            alert('Please select a rating');
            return;
        }

        const submitBtn = document.getElementById('submit-review-btn');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Submitting...';
        submitBtn.disabled = true;

        const formData = new FormData(reviewForm);

        try {
            const response = await fetch('<?php echo SITE_URL; ?>api/reviews.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message);
                reviewModal.classList.add('hidden');
                document.body.style.overflow = '';
                reviewForm.reset();
                selectedRating = 0;
                updateStars();
                // Optionally reload page to show new review
                setTimeout(() => location.reload(), 1500);
            } else {
                alert(data.message || 'Failed to submit review');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        } finally {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    });
});

// Mark review as helpful
function markHelpful(reviewId) {
    fetch(`<?php echo SITE_URL; ?>api/reviews.php?review_id=${reviewId}&action=helpful`, {
        method: 'PUT'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

// Open photo modal
function openPhotoModal(src) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/90 z-[100] flex items-center justify-center p-4';
    modal.innerHTML = `
        <button onclick="this.parentElement.remove()" class="absolute top-4 right-4 text-white hover:text-gray-300">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <img src="${src}" alt="Review photo" class="max-w-full max-h-full object-contain">
    `;
    document.body.appendChild(modal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Add bundle to cart function
<?php if (isset($product_id) && !empty($frequently_bought)): ?>
function addBundleToCart() {
    const button = event.target;
    button.disabled = true;
    button.textContent = 'Adding...';
    
    // Get all product IDs from the bundle
    const productIds = [<?php 
        echo (int)$product_id;
        foreach ($frequently_bought as $bp) {
            echo ', ' . (int)$bp['id'];
        }
    ?>];
    
    // Add each product to cart
    let addedCount = 0;
    productIds.forEach((productId, index) => {
        fetch('<?php echo SITE_URL; ?>ajax_cart_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add&product_id=${productId}&quantity=1&csrf_token=<?php echo generate_csrf_token(); ?>`
        })
        .then(response => response.json())
        .then(data => {
            addedCount++;
            if (addedCount === productIds.length) {
                button.textContent = 'Added to Cart!';
                button.classList.add('bg-green-600');
                setTimeout(() => {
                    button.disabled = false;
                    button.textContent = 'Add All to Cart';
                    button.classList.remove('bg-green-600');
                    // Update cart count if function exists
                    if (typeof window.Cart !== 'undefined' && window.Cart.updateCartCount) {
                        window.Cart.updateCartCount();
                    }
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            button.disabled = false;
            button.textContent = 'Error - Try Again';
        });
    });
}
<?php endif; ?>
</script>
<?php endif; ?>

<!-- Notify Me Modal -->
<div id="notify-me-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="notify-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" id="notify-modal-overlay"></div>
        
        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 class="text-lg leading-6 font-bold text-gray-900" id="notify-modal-title">
                            Notify Me When Available
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="notify-modal-product-name">
                                We'll send you an email when this product is back in stock.
                            </p>
                        </div>
                        
                        <form id="notify-me-form" class="mt-4 space-y-4">
                            <?php echo generate_csrf_token_input(); ?>
                            <input type="hidden" name="product_id" id="notify-product-id" value="">
                            <input type="hidden" name="action" value="set_back_in_stock_alert">
                            
                            <?php if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
                            <!-- Email field for guests -->
                            <div>
                                <label for="notify-email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <input type="email" 
                                       name="email" 
                                       id="notify-email" 
                                       required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="your@email.com">
                            </div>
                            <?php else: ?>
                            <div class="bg-gray-50 rounded-md p-3">
                                <p class="text-sm text-gray-600">
                                    <svg class="w-4 h-4 inline mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    We'll notify you at: <strong><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></strong>
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Optional: Variant selection if product has variants -->
                            <?php if (!empty($available_sizes) || !empty($available_colors)): ?>
                            <div class="border-t border-gray-200 pt-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">Notify me for specific variant (optional):</p>
                                
                                <?php if (!empty($available_sizes)): ?>
                                <div class="mb-3">
                                    <label for="notify-size" class="block text-xs text-gray-500 mb-1">Size</label>
                                    <select name="size_variant" id="notify-size" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Any size</option>
                                        <?php foreach ($available_sizes as $size): ?>
                                        <option value="<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($available_colors)): ?>
                                <div>
                                    <label for="notify-color" class="block text-xs text-gray-500 mb-1">Color</label>
                                    <select name="color_variant" id="notify-color" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Any color</option>
                                        <?php foreach ($available_colors as $color): ?>
                                        <option value="<?php echo htmlspecialchars($color); ?>"><?php echo htmlspecialchars($color); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </form>
                        
                        <!-- Success/Error messages -->
                        <div id="notify-message" class="mt-3 hidden">
                            <div id="notify-success" class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md text-sm">
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span id="notify-success-text">You'll be notified when this product is back in stock!</span>
                            </div>
                            <div id="notify-error" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <span id="notify-error-text">Something went wrong. Please try again.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" 
                        form="notify-me-form"
                        id="notify-submit-btn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    <svg class="w-4 h-4 mr-2 hidden animate-spin" id="notify-spinner" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span id="notify-btn-text">Notify Me</span>
                </button>
                <button type="button" 
                        id="notify-cancel-btn"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
