<?php
$pageTitle = "Your Cart - Mossé Luxe";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/header.php';
require_once 'includes/config.php'; // For SHIPPING_COST

$cart_items = $_SESSION['cart'] ?? [];
$subtotal = 0;

// Calculate subtotal
foreach ($cart_items as $product_id => $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping_cost = SHIPPING_COST;
$total = $subtotal + $shipping_cost;

?>

<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter text-center mb-12">Your Shopping Cart</h1>

        <?php if (!empty($cart_items)): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                    <?php foreach ($cart_items as $product_id => $item): ?>
                        <div class="flex items-center border-b border-gray-200 py-4">
                            <div class="w-24 h-24 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="h-full w-full object-cover object-center">
                            </div>
                            <div class="ml-4 flex flex-1 flex-col">
                                <div>
                                    <div class="flex justify-between text-base font-medium text-gray-900">
                                        <h3><a href="product.php?id=<?php echo $product_id; ?>"><?php echo htmlspecialchars($item['name']); ?></a></h3>
                                        <p class="ml-4">R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">R <?php echo number_format($item['price'], 2); ?> each</p>
                                </div>
                                <div class="flex flex-1 items-end justify-between text-sm">
                                    <div class="flex items-center">
                                        <label for="quantity-<?php echo $product_id; ?>" class="mr-2">Qty</label>
                                        <input type="number" id="quantity-<?php echo $product_id; ?>" value="<?php echo $item['quantity']; ?>" min="1" class="w-16 p-1 border border-gray-300 rounded-md text-center cart-quantity-input" data-product-id="<?php echo $product_id; ?>">
                                    </div>
                                    <div class="flex">
                                        <button type="button" class="font-medium text-red-600 hover:text-red-500 remove-from-cart-btn" data-product-id="<?php echo $product_id; ?>">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-fit">
                    <h2 class="text-2xl font-bold mb-4">Order Summary</h2>
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
                    <div class="mt-6">
                        <a href="checkout.php" class="w-full flex items-center justify-center rounded-md border border-transparent bg-black px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-black/80">Proceed to Checkout</a>
                    </div>
                    <div class="mt-6 flex justify-center text-center text-sm text-gray-500">
                        <p>
                            or <a href="shop.php" class="font-medium text-black hover:text-black/80">Continue Shopping<span aria-hidden="true"> &rarr;</span></a>
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center">
                <p class="text-lg text-black/70">Your cart is empty.</p>
                <div class="mt-8">
                    <a href="shop.php" class="text-lg font-semibold text-black border-b-2 border-black hover:border-transparent transition-colors">
                        Continue Shopping
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateCartUI(data) {
        if (data.success) {
            // Reload the page to reflect cart changes
            window.location.reload();
        } else {
            alert(data.message || 'An error occurred.');
        }
    }

    // Quantity update
    document.querySelectorAll('.cart-quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.dataset.productId;
            const newQuantity = parseInt(this.value);

            if (isNaN(newQuantity) || newQuantity < 0) {
                alert('Quantity must be a positive number.');
                this.value = this.defaultValue; // Revert to previous valid quantity
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('product_id', productId);
            formData.append('quantity', newQuantity);

            fetch('ajax_cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                updateCartUI(data);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating quantity.');
            });
        });
    });

    // Remove from cart
    document.querySelectorAll('.remove-from-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }

            const productId = this.dataset.productId;

            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);

            fetch('ajax_cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                updateCartUI(data);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while removing item.');
            });
        });
    });
});
</script>