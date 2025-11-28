<?php
/**
 * Cart Recovery Page
 * Restore abandoned cart from email link
 */

$pageTitle = "Recover Your Cart - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/abandoned_cart_functions.php';

$conn = get_db_connection();

$error = null;
$cart_recovered = false;

// Get recovery token from URL
$token = isset($_GET['token']) ? $_GET['token'] : null;

if ($token) {
    // Get abandoned cart
    $abandoned_cart = getAbandonedCartByToken($conn, $token);
    
    if ($abandoned_cart) {
        // Restore cart to session
        $_SESSION['cart'] = $abandoned_cart['cart_data'];
        
        // Mark as recovered
        markCartAsRecovered($conn, $abandoned_cart['id']);
        
        $cart_recovered = true;
    } else {
        $error = "Invalid or expired recovery link.";
    }
} else {
    $error = "No recovery token provided.";
}

require_once 'includes/header.php';
?>

<main class="min-h-screen bg-gray-50 py-16">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto">
            <?php if ($cart_recovered): ?>
                <!-- Success Message -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="mb-6">
                        <svg class="w-20 h-20 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    
                    <h1 class="text-3xl font-bold mb-4">Welcome Back!</h1>
                    <p class="text-lg text-gray-600 mb-8">
                        Your cart has been restored with <?php echo count($_SESSION['cart']); ?> item<?php echo count($_SESSION['cart']) != 1 ? 's' : ''; ?>.
                    </p>
                    
                    <!-- Cart Summary -->
                    <div class="bg-gray-50 rounded-lg p-6 mb-8">
                        <h2 class="text-xl font-semibold mb-4">Your Items</h2>
                        <div class="space-y-4">
                            <?php 
                            $subtotal = 0;
                            foreach ($_SESSION['cart'] as $product_id => $item): 
                                $item_total = $item['price'] * $item['quantity'];
                                $subtotal += $item_total;
                            ?>
                                <div class="flex items-center gap-4 text-left">
                                    <img src="<?php echo SITE_URL . htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-16 h-16 object-cover rounded">
                                    <div class="flex-1">
                                        <h3 class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="text-sm text-gray-600">Qty: <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <p class="font-bold">R <?php echo number_format($item_total, 2); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="border-t mt-4 pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold">Total:</span>
                                <span class="text-2xl font-bold">R <?php echo number_format($subtotal, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="checkout.php" class="bg-black text-white px-8 py-3 rounded-md font-semibold hover:bg-black/80 transition-colors">
                            Proceed to Checkout
                        </a>
                        <a href="shop.php" class="bg-gray-200 text-gray-700 px-8 py-3 rounded-md font-semibold hover:bg-gray-300 transition-colors">
                            Continue Shopping
                        </a>
                    </div>
                    
                    <!-- Special Offer (Optional) -->
                    <div class="mt-8 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-800 font-semibold">
                            🎉 Complete your purchase now and get free shipping!
                        </p>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Error Message -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="mb-6">
                        <svg class="w-20 h-20 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    
                    <h1 class="text-3xl font-bold mb-4">Oops!</h1>
                    <p class="text-lg text-gray-600 mb-8">
                        <?php echo htmlspecialchars($error); ?>
                    </p>
                    
                    <p class="text-gray-600 mb-8">
                        This recovery link may have expired or already been used.
                    </p>
                    
                    <a href="shop.php" class="inline-block bg-black text-white px-8 py-3 rounded-md font-semibold hover:bg-black/80 transition-colors">
                        Browse Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
