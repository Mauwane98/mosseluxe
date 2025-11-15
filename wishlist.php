<?php
$pageTitle = "My Wishlist - Mossé Luxe";
require_once 'includes/bootstrap.php';

// If user is not logged in, redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'includes/header.php';
$conn = get_db_connection();

$user_id = $_SESSION['user_id'];
$wishlist_items = [];

$sql = "SELECT p.id, p.name, p.price, p.sale_price, p.image FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ? ORDER BY w.created_at DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $wishlist_items[] = $row;
    }
    $stmt->close();
}

?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <!-- Page Header -->
        <div class="text-center mb-12">
            <div class="flex items-center justify-center gap-3 mb-4">
                <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">My Wishlist</h1>
            </div>
            <p class="text-lg text-black/70 max-w-2xl mx-auto">Your saved items, ready for when you're ready to make them yours.</p>
        </div>

        <?php if (!empty($wishlist_items)): ?>
            <!-- Wishlist Items Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 md:gap-8">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="group bg-white shadow-md rounded-lg overflow-hidden hover:shadow-xl transition-all duration-300 border border-transparent hover:border-black/10">
                        <!-- Product Image -->
                        <div class="relative overflow-hidden">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.src='https://placehold.co/400x400/f1f1f1/000000?text=Mossé+Luxe'">
                            <!-- Remove Button Overlay -->
                            <button type="button" class="absolute top-3 right-3 w-8 h-8 bg-white/90 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 hover:bg-white remove-from-wishlist-btn" data-product-id="<?php echo $item['id']; ?>" title="Remove from wishlist">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Product Info -->
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-2 line-clamp-2"><?php echo htmlspecialchars($item['name']); ?></h3>

                            <!-- Pricing -->
                            <div class="mb-4">
                                <?php if ($item['sale_price']): ?>
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-red-600">R <?php echo number_format($item['sale_price'], 2); ?></span>
                                        <span class="text-sm text-black/50 line-through">R <?php echo number_format($item['price'], 2); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-lg font-bold text-black">R <?php echo number_format($item['price'], 2); ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-3">
                                <a href="<?php echo SITE_URL; ?>product/<?php echo $item['id']; ?>/<?php echo urlencode(str_replace(' ', '-', strtolower($item['name']))); ?>" class="w-full block text-center bg-black text-white py-3 px-4 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider text-sm">
                                    View Product
                                </a>
                                <button type="button" class="w-full bg-neutral-100 text-black py-3 px-4 font-bold uppercase rounded-md hover:bg-neutral-200 transition-colors tracking-wider text-sm add-to-cart-from-wishlist-btn" data-product-id="<?php echo $item['id']; ?>">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Continue Shopping CTA -->
            <div class="text-center mt-12">
                <a href="<?php echo SITE_URL; ?>shop" class="inline-flex items-center gap-2 bg-black text-white py-4 px-8 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <!-- Empty Wishlist State -->
            <div class="text-center py-16">
                <div class="w-24 h-24 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-black/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold mb-4">Your Wishlist is Empty</h2>
                <p class="text-black/70 mb-8 max-w-md mx-auto">Save items you love for later. Start browsing our collection and add your favorites to your wishlist.</p>
                <a href="<?php echo SITE_URL; ?>shop" class="inline-flex items-center gap-2 bg-black text-white py-4 px-8 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
include 'includes/footer.php';
?>
