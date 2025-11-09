<?php
$pageTitle = "Checkout - Mossé Luxe";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/header.php';
require_once 'includes/config.php'; // For SHIPPING_COST

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$cart_items = $_SESSION['cart'] ?? [];
$subtotal = 0;

// Calculate subtotal
foreach ($cart_items as $product_id => $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping_cost = SHIPPING_COST;
$total = $subtotal + $shipping_cost;

// User information (if logged in)
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['name'] ?? '';
$user_email = $_SESSION['email'] ?? '';

// Guest checkout support
$guest_checkout = !isset($_SESSION['user_id']);
$login_required_message = '';

// Shipping information (can be pre-filled if user is logged in and has previous addresses)
$shipping_name = '';
$shipping_address = '';
$shipping_city = '';
$shipping_zip = '';
$shipping_phone = '';

// If user is logged in, try to fetch their default address or last used address
if ($user_id) {
    // This part would typically involve fetching from a 'user_addresses' table
    // For now, we'll leave it blank or use dummy data
}

?>

<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter text-center mb-12">Checkout</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Shipping Information Form -->
            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Shipping Information</h2>
                    <?php if (!$user_id): ?>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Checking out as guest</span>
                            <a href="login.php?redirect=checkout" class="text-black hover:underline ml-2">Sign in</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Trust Badges -->
                <div class="flex items-center space-x-4 mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                        <span>SSL Secured</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-truck text-blue-600 mr-2"></i>
                        <span>Fast Shipping</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-undo text-purple-600 mr-2"></i>
                        <span>30-Day Returns</span>
                    </div>
                </div>

                <form action="payfast_process.php" method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="shipping_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="shipping_name" id="shipping_name" value="<?php echo htmlspecialchars($shipping_name); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
                        </div>
                        <div>
                            <label for="shipping_email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" name="shipping_email" id="shipping_email" value="<?php echo htmlspecialchars($user_email); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="shipping_address" class="block text-sm font-medium text-gray-700">Address</label>
                        <input type="text" name="shipping_address" id="shipping_address" value="<?php echo htmlspecialchars($shipping_address); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="shipping_city" class="block text-sm font-medium text-gray-700">City</label>
                            <input type="text" name="shipping_city" id="shipping_city" value="<?php echo htmlspecialchars($shipping_city); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
                        </div>
                        <div>
                            <label for="shipping_zip" class="block text-sm font-medium text-gray-700">Zip Code</label>
                            <input type="text" name="shipping_zip" id="shipping_zip" value="<?php echo htmlspecialchars($shipping_zip); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
                        </div>
                    </div>
                    <div class="mb-6">
                        <label for="shipping_phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="tel" name="shipping_phone" id="shipping_phone" value="<?php echo htmlspecialchars($shipping_phone); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
                    </div>

                    <!-- Hidden fields for order processing -->
                    <input type="hidden" name="subtotal" value="<?php echo $subtotal; ?>">
                    <input type="hidden" name="shipping_cost" value="<?php echo $shipping_cost; ?>">
                    <input type="hidden" name="total" value="<?php echo $total; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    
                    <button type="submit" class="w-full flex items-center justify-center rounded-md border border-transparent bg-black px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-black/80">
                        Proceed to Payment
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-fit">
                <h2 class="text-2xl font-bold mb-4">Order Summary</h2>
                <div class="border-b border-gray-200 pb-4 mb-4">
                    <?php foreach ($cart_items as $product_id => $item): ?>
                        <div class="flex justify-between text-sm text-gray-900 mb-2">
                            <p><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</p>
                            <p>R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex justify-between text-base text-gray-900 mb-2">
                    <p>Subtotal</p>
                    <p>R <?php echo number_format($subtotal, 2); ?></p>
                </div>
                <div class="flex justify-between text-base text-gray-900 mb-4">
                    <p>Shipping</p>
                    <p>R <?php echo number_format($shipping_cost, 2); ?></p>
                </div>
                <div class="flex justify-between text-xl font-bold text-gray-900 border-t border-gray-200 pt-4">
                    <p>Order Total</p>
                    <p>R <?php echo number_format($total, 2); ?></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
