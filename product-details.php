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
            $product_variants = get_product_variants_by_type($product_id);
        } catch (Exception $e) {
            error_log('Error getting product variants: ' . $e->getMessage());
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
    }
?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <?php if ($product): ?>
            <div class="grid md:grid-cols-2 gap-8 lg:gap-16">
                <!-- Product Image Gallery -->
                <div class="space-y-4">
                    <!-- Main Image -->
                    <div class="relative aspect-square bg-gray-100 rounded-lg overflow-hidden">
                        <img id="main-product-image"
                            src="<?php echo SITE_URL . htmlspecialchars($product['image']); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="w-full h-full object-contain p-4"
                            onerror="this.src='https://placehold.co/600x600/f1f1f1/000000?text=Mossé+Luxe'"
                        >

                        <?php if (!empty($product_images)): ?>
                            <!-- Thumbnails -->
                            <div class="flex gap-2 overflow-x-auto mt-4 pb-2">
                                <?php foreach ($product_images as $index => $img): ?>
                                    <button type="button"
                                        onclick="changeMainImage('<?php echo SITE_URL . htmlspecialchars($img['image_path']); ?>')"
                                        class="flex-shrink-0 w-16 h-16 border-2 rounded-md overflow-hidden <?php echo $index === 0 ? 'border-black' : 'border-gray-300'; ?>">
                                        <img src="<?php echo SITE_URL . htmlspecialchars($img['image_path']); ?>"
                                             alt="thumbnail"
                                             class="w-full h-full object-cover">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="space-y-6">
                    <div class="flex items-center gap-3 mb-4">
                        <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tighter"><?php echo htmlspecialchars($product['name']); ?></h1>

                        <!-- Badges -->
                        <?php if ($product['is_featured'] || $product['is_bestseller'] || $product['is_new'] || $product['is_coming_soon']): ?>
                            <div class="flex flex-wrap gap-2">
                                <?php if ($product['is_featured']): ?>
                                    <span class="bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-bold">FEATURED</span>
                                <?php endif; ?>
                                <?php if ($product['is_bestseller']): ?>
                                    <span class="bg-blue-500 text-white px-2 py-1 rounded-full text-xs font-bold">BESTSELLER</span>
                                <?php endif; ?>
                                <?php if ($product['is_new']): ?>
                                    <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold">NEW</span>
                                <?php endif; ?>
                                <?php if ($product['is_coming_soon']): ?>
                                    <span class="bg-purple-500 text-white px-2 py-1 rounded-full text-xs font-bold">COMING SOON</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Price -->
                    <div class="text-3xl font-bold">
                        <?php if ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']): ?>
                            <span class="text-red-600">R <?php echo number_format($product['sale_price'], 2); ?></span>
                            <span class="line-through text-gray-500 text-xl ml-2">R <?php echo number_format($product['price'], 2); ?></span>
                        <?php else: ?>
                            <span>R <?php echo number_format($product['price'], 2); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="prose text-gray-700">
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>

                    <!-- Add to Cart Section -->
                    <div class="space-y-4 border-t pt-6">
                        <form id="add-to-cart-form" class="space-y-4">
                            <?php echo generate_csrf_token_input(); ?>
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="action" value="add">

                            <!-- Quantity Selector -->
                            <div class="flex items-center gap-2">
                                <label for="quantity" class="font-semibold">Quantity:</label>
                                <input type="number" id="quantity" name="quantity" value="1"
                                       min="1" max="<?php echo $product['stock']; ?>"
                                       class="w-20 p-2 border border-gray-300 rounded text-center">
                            </div>

                            <!-- Add to Cart Button -->
                            <?php if ($product['stock'] > 0): ?>
                                <button type="submit" class="w-full bg-black text-white font-bold py-3 px-6 rounded-md hover:bg-gray-800 transition-colors">
                                    Add to Cart
                                </button>
                            <?php else: ?>
                                <button type="button" disabled class="w-full bg-gray-400 text-white font-bold py-3 px-6 rounded-md cursor-not-allowed">
                                    Out of Stock
                                </button>
                            <?php endif; ?>
                        </form>

                        <!-- WhatsApp Inquiry -->
                        <button id="whatsapp-inquiry-btn"
                                onclick="openWhatsAppInquiry('<?php echo addslashes($product['name']); ?>')"
                                class="w-full bg-green-600 text-white font-bold py-3 px-6 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347
m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884
m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"
                                      />
                            </svg>
                            Inquire via WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-16">
                <h1 class="text-4xl font-black uppercase tracking-tighter mb-8">Product Not Found</h1>
                <p class="text-gray-600 mb-8">The product you're looking for doesn't exist or has been removed.</p>
                <a href="shop.php" class="bg-black text-white px-6 py-3 rounded-md hover:bg-gray-800 transition-colors">
                    Browse Products
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>

<script>
window.productImages = <?php
echo json_encode($product_images, JSON_PRETTY_PRINT);
?>;
</script>

<script>
// All cart functionality is now handled by assets/js/cart.js
// This script can be used for any other product-details-specific functionality.
document.addEventListener('DOMContentLoaded', function() {

});
</script>
