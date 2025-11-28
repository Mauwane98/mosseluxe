<?php
$pageTitle = "Your Cart - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';
$conn = get_db_connection();
require_once 'includes/header.php';

// Ensure SHIPPING_COST is defined
if (!defined('SHIPPING_COST')) {
    require_once __DIR__ . '/includes/config.php';
}

// The cart is primarily driven by the session.
// The ajax_cart_handler.php is responsible for merging the DB cart into the session on login.
$cart_items = $_SESSION['cart'] ?? [];



$subtotal = 0;
// Calculate subtotal
if (!empty($cart_items)) {
    foreach ($cart_items as $item) {
        if (isset($item['price']) && isset($item['quantity'])) {
            $subtotal += $item['price'] * $item['quantity'];
        }
    }
}

$shipping_cost = SHIPPING_COST ?? 100.00;
$total = $subtotal + $shipping_cost;
?>

    <div class="container mx-auto px-4 py-16 md:py-24">
        <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter text-center mb-12">Your Shopping Cart</h1>
        <input type="hidden" id="csrf_token" value="<?php echo generate_csrf_token(); ?>">

        <?php if (!empty($cart_items)): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div id="cart-items-container" class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                    <?php foreach ($cart_items as $product_id => $item): ?>
                        <div class="flex items-center border-b border-gray-200 py-4">
                            <div class="w-24 h-24 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">
                                <img src="<?php echo SITE_URL . htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="h-full w-full object-cover object-center">
                            </div>
                            <div class="ml-4 flex flex-1 flex-col">
                                <div>
                                    <div class="flex justify-between text-base font-medium text-gray-900">
                                        <h3><a href="product/<?php echo $product_id; ?>/<?php echo urlencode(str_replace(' ', '-', strtolower($item['name']))); ?>"><?php echo htmlspecialchars($item['name']); ?></a></h3>
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
                <div class="lg:col-span-1 bg-white p-6 rounded-lg h-fit sticky top-4">
                    <h2 class="text-2xl font-bold mb-4">Order Summary</h2>
                    <div class="flex justify-between text-base text-gray-900 mb-2">
                        <p>Subtotal</p>
                        <p id="cart-subtotal">R <?php echo number_format($subtotal, 2); ?></p>
                    </div>
                    <div class="flex justify-between text-base text-gray-900 mb-4">
                        <p>Shipping</p>
                        <p>R <?php echo number_format($shipping_cost, 2); ?></p>
                    </div>
                    <div class="flex justify-between text-xl font-bold text-gray-900 border-t border-gray-200 pt-4">
                        <p>Order Total</p>
                        <p id="cart-total">R <?php echo number_format($total, 2); ?></p>
                    </div>
                    <div class="mt-6 space-y-3">
                        <a href="checkout.php" role="button" class="w-full flex items-center justify-center rounded-md border border-transparent bg-black px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-black/80">Proceed to Checkout</a>
                        <button onclick="openCartWhatsAppInquiry()" class="w-full flex items-center justify-center rounded-md border border-transparent bg-green-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-green-700 gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <span>Inquire via WhatsApp</span>
                        </button>
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

<?php
// Store shipping cost for JavaScript before footer closes PHP context
$js_shipping_cost = $shipping_cost;
?>

<script>
// Cart page specific functionality
// Store PHP values in data attributes to avoid PHP context issues
window.cartPageConfig = {
    shippingCost: <?php echo $js_shipping_cost; ?>
};

document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.getElementById('csrf_token')?.value || window.csrfToken;
    const shippingCost = window.cartPageConfig.shippingCost;
    
    // Remove button handling is done by main.js via event delegation
    
    // Update cart totals in the UI when quantity changes
    document.querySelectorAll('.cart-quantity-input').forEach(input => {
        let debounceTimer;
        
        input.addEventListener('input', function() {
            updateCartTotals();
        });
        
        // Send update to server after user stops typing
        input.addEventListener('change', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                updateQuantityOnServer(this.dataset.productId, parseInt(this.value) || 1);
            }, 500);
        });
    });
    
    async function updateQuantityOnServer(productId, quantity) {
        if (quantity < 1) {
            quantity = 1;
        }
        
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        formData.append('csrf_token', csrfToken);
        
        try {
            const response = await fetch(window.SITE_URL + 'ajax_cart_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update cart count if available
                if (typeof window.updateCartCountDisplay === 'function') {
                    window.updateCartCountDisplay();
                }
            } else {
                alert(data.message || 'Error updating quantity');
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
        }
    }

    function updateCartTotals() {
        let subtotal = 0;
        document.querySelectorAll('.cart-quantity-input').forEach(input => {
            const quantity = parseInt(input.value) || 0;
            const itemContainer = input.closest('.flex.items-center');
            const priceElement = itemContainer.querySelector('.text-base.font-medium.text-gray-900 p:last-child');
            if (priceElement) {
                const priceText = priceElement.textContent.replace('R ', '').replace(/,/g, '');
                const itemPrice = parseFloat(priceText);
                if (!isNaN(itemPrice)) {
                    subtotal += itemPrice;
                }
            }
        });

        // Update subtotal and total displays
        const subtotalDisplay = document.getElementById('cart-subtotal');
        const totalDisplay = document.getElementById('cart-total');

        if (subtotalDisplay) {
            subtotalDisplay.textContent = 'R ' + subtotal.toFixed(2);
        }
        if (totalDisplay) {
            totalDisplay.textContent = 'R ' + (subtotal + shippingCost).toFixed(2);
        }
    }
});
</script>

<?php
require_once 'includes/footer.php';
?>
