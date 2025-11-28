<?php
$pageTitle = "Checkout - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/paxi_service.php'; // Include Paxi service
require_once __DIR__ . '/yoco_process.php'; // Include the yoco processing logic

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

// Default shipping method and cost
$shipping_method = $_POST['shipping_method'] ?? 'paxi_standard';

// Calculate shipping cost based on method and cart total
if ($shipping_method === 'paxi_standard') {
    $shipping_cost = PaxiService::calculateCost($subtotal, 'standard');
} elseif ($shipping_method === 'paxi_express') {
    $shipping_cost = PaxiService::calculateCost($subtotal, 'express');
} else {
    // Fallback to Paxi Standard
    $shipping_cost = PaxiService::calculateCost($subtotal, 'standard');
}

$total = $subtotal + $shipping_cost;

// User information (if logged in)
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['name'] ?? '';
$user_email = $_SESSION['email'] ?? '';

// Initialize shipping fields
$shipping_name = $user_name;
$shipping_email = $user_email;
$shipping_address = '';
$shipping_city = '';
$shipping_zip = '';
$shipping_phone = '';

$csrf_token = generate_csrf_token(); // Generate token for the form

// Handle AJAX form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $conn = get_db_connection();

    try {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            throw new Exception('Invalid security token. Please try again.');
        }

        // Sanitize and validate inputs
        $shipping_name = trim($_POST['shipping_name'] ?? '');
        $shipping_email = filter_var(trim($_POST['shipping_email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $shipping_address = trim($_POST['shipping_address'] ?? '');
        $shipping_city = trim($_POST['shipping_city'] ?? '');
        $shipping_zip = trim($_POST['shipping_zip'] ?? '');
        $shipping_phone = trim($_POST['shipping_phone'] ?? '');
        $discount_code = trim(strtoupper($_POST['discount_code'] ?? ''));

        if (empty($shipping_name) || empty($shipping_email) || empty($shipping_address) || empty($shipping_city) || empty($shipping_zip) || empty($shipping_phone)) {
            throw new Exception('All shipping fields are required.');
        }
        if (!filter_var($shipping_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format.');
        }

        // Validate discount code
        $discount_data = null;
        if (!empty($discount_code)) {
            $stmt = $conn->prepare("SELECT * FROM discount_codes WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) AND usage_limit > usage_count");
            $stmt->bind_param("s", $discount_code);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $discount_data = $result->fetch_assoc();
            }
            $stmt->close();
        }

        $discount_amount = 0;
        $final_total = $total;
        if ($discount_data) {
            if ($discount_data['type'] === 'percentage') {
                $discount_amount = $subtotal * ($discount_data['value'] / 100);
            } else {
                $discount_amount = min($discount_data['value'], $subtotal);
            }
            $final_total = $total - $discount_amount;
        }

        $checkout_data = [
            'user_id' => $user_id,
            'cart_items' => $cart_items,
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping_cost,
            'total' => $total,
            'final_total' => $final_total,
            'discount_data' => $discount_data,
            'shipping_info' => [
                'firstName' => explode(' ', $shipping_name, 2)[0],
                'lastName' => explode(' ', $shipping_name, 2)[1] ?? '',
                'address' => $shipping_address,
                'city' => $shipping_city,
                'zip' => $shipping_zip,
                'email' => $shipping_email,
                'phone' => $shipping_phone
            ]
        ];

        // Call the function directly
        $payment_data = create_order_for_yoco($conn, $checkout_data);

        echo json_encode([
            'success' => true,
            'paymentData' => $payment_data
        ]);

    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Checkout Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

require_once 'includes/header.php';
?>

    <div class="container mx-auto px-4 py-16 md:py-24">
        <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter text-center mb-12">Checkout</h1>

        <!-- Progress Indicator -->
        <div class="flex items-center justify-center mb-12">
            <div class="flex items-center space-x-4">
                <!-- Shipping Step -->
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center font-bold text-sm">
                        1
                    </div>
                    <span class="text-xs font-medium text-black mt-2">Shipping</span>
                </div>
                <!-- Arrow -->
                <div class="w-8 h-0.5 bg-black"></div>
                <!-- Payment Step -->
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold text-sm">
                        2
                    </div>
                    <span class="text-xs font-medium text-gray-600 mt-2">Payment</span>
                </div>
                <!-- Arrow -->
                <div class="w-8 h-0.5 bg-gray-300"></div>
                <!-- Confirmation Step -->
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold text-sm">
                        3
                    </div>
                    <span class="text-xs font-medium text-gray-600 mt-2">Confirmation</span>
                </div>
            </div>
        </div>

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

                <form id="checkout-form" action="<?php echo SITE_URL; ?>checkout.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="ajax" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="shipping_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_name" id="shipping_name" value="<?php echo htmlspecialchars($shipping_name); ?>" placeholder="John Doe" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-black focus:ring-1 focus:ring-black sm:text-sm transition-colors">
                        </div>
                        <div>
                            <label for="shipping_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="shipping_email" id="shipping_email" value="<?php echo htmlspecialchars($shipping_email); ?>" placeholder="john@example.com" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-black focus:ring-1 focus:ring-black sm:text-sm transition-colors">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="shipping_address" class="block text-sm font-medium text-gray-700 mb-1">Address <span class="text-red-500">*</span></label>
                        <input type="text" name="shipping_address" id="shipping_address" value="<?php echo htmlspecialchars($shipping_address); ?>" placeholder="123 Main Street, Apt 4B" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-black focus:ring-1 focus:ring-black sm:text-sm transition-colors">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="shipping_city" class="block text-sm font-medium text-gray-700 mb-1">City <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_city" id="shipping_city" value="<?php echo htmlspecialchars($shipping_city); ?>" placeholder="Johannesburg" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-black focus:ring-1 focus:ring-black sm:text-sm transition-colors">
                        </div>
                        <div>
                            <label for="shipping_zip" class="block text-sm font-medium text-gray-700 mb-1">Zip Code <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_zip" id="shipping_zip" value="<?php echo htmlspecialchars($shipping_zip); ?>" placeholder="2000" pattern="[0-9]{4}" maxlength="4" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-black focus:ring-1 focus:ring-black sm:text-sm transition-colors">
                        </div>
                    </div>
                    <div class="mb-6">
                        <label for="shipping_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                        <input type="tel" name="shipping_phone" id="shipping_phone" value="<?php echo htmlspecialchars($shipping_phone); ?>" placeholder="0821234567" pattern="[0-9]{10}" maxlength="10" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-black focus:ring-1 focus:ring-black sm:text-sm transition-colors">
                        <p class="text-xs text-gray-500 mt-1">10 digits, no spaces or dashes</p>
                    </div>

                    <!-- Shipping Method Selection -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Delivery Method <span class="text-red-500">*</span></label>
                        
                        <div class="space-y-3">
                            <!-- Paxi Standard Delivery -->
                            <label class="flex items-start p-4 border-2 border-black bg-black/5 rounded-lg cursor-pointer hover:border-black transition-colors" id="paxi-standard-option">
                                <input type="radio" name="shipping_method" value="paxi_standard" class="mt-1 mr-3" checked onchange="updateShippingMethod('paxi_standard')">
                                <div class="flex-grow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-semibold text-gray-900 flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                                </svg>
                                                Paxi Standard
                                            </p>
                                            <p class="text-sm text-gray-600 mt-1">Collect from a Paxi Point near you</p>
                                            <p class="text-xs text-gray-500 mt-1">📦 7-9 business days • 📍 2800+ locations • Up to 5kg</p>
                                        </div>
                                        <div class="text-right">
                                            <?php $paxi_standard_cost = PaxiService::calculateCost($subtotal, 'standard'); ?>
                                            <p class="font-bold text-gray-900" id="paxi-standard-cost">R<?php echo number_format($paxi_standard_cost, 2); ?></p>
                                            <?php if ($paxi_standard_cost == 0): ?>
                                            <p class="text-xs text-green-600 mt-1">FREE!</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <!-- Paxi Express Delivery -->
                            <label class="flex items-start p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-black transition-colors" id="paxi-express-option">
                                <input type="radio" name="shipping_method" value="paxi_express" class="mt-1 mr-3" onchange="updateShippingMethod('paxi_express')">
                                <div class="flex-grow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-semibold text-gray-900 flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
                                                </svg>
                                                Paxi Express
                                            </p>
                                            <p class="text-sm text-gray-600 mt-1">Faster collection from Paxi Point</p>
                                            <p class="text-xs text-gray-500 mt-1">⚡ 3-5 business days • 📍 2800+ locations • Up to 5kg</p>
                                        </div>
                                        <div class="text-right">
                                            <?php $paxi_express_cost = PaxiService::calculateCost($subtotal, 'express'); ?>
                                            <p class="font-bold text-gray-900" id="paxi-express-cost">R<?php echo number_format($paxi_express_cost, 2); ?></p>
                                            <?php if ($paxi_express_cost == 0): ?>
                                            <p class="text-xs text-green-600 mt-1">FREE!</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Paxi Point Details (Manual Entry) -->
                    <div id="paxi-point-selection" class="mb-6 hidden">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="text-sm text-blue-800">
                                    <p class="font-semibold mb-1">Find your nearest Paxi Point</p>
                                    <p>Visit <a href="https://pargo.co.za/where-to-find-us/" target="_blank" class="underline font-medium">pargo.co.za/where-to-find-us</a> to locate your preferred pickup point, then enter the details below.</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label for="paxi_point_name" class="block text-sm font-medium text-gray-700 mb-1">Paxi Point Name <span class="text-red-500">*</span></label>
                                <input type="text" name="paxi_point_name" id="paxi_point_name" placeholder="e.g., PEP Menlyn Park" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black" required>
                                <p class="text-xs text-gray-500 mt-1">Enter the exact name of the Paxi Point</p>
                            </div>

                            <div>
                                <label for="paxi_point_address" class="block text-sm font-medium text-gray-700 mb-1">Paxi Point Address <span class="text-red-500">*</span></label>
                                <textarea name="paxi_point_address" id="paxi_point_address" rows="2" placeholder="e.g., Menlyn Park Shopping Centre, Atterbury Road, Pretoria" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black" required></textarea>
                                <p class="text-xs text-gray-500 mt-1">Full address of the Paxi Point</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="paxi_point_city" class="block text-sm font-medium text-gray-700 mb-1">City <span class="text-red-500">*</span></label>
                                    <input type="text" name="paxi_point_city" id="paxi_point_city" placeholder="e.g., Pretoria" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black" required>
                                </div>

                                <div>
                                    <label for="paxi_point_code" class="block text-sm font-medium text-gray-700 mb-1">Paxi Point Code (Optional)</label>
                                    <input type="text" name="paxi_point_code" id="paxi_point_code" placeholder="e.g., PAXI_PTA_001" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-black focus:border-black">
                                    <p class="text-xs text-gray-500 mt-1">If available on the Paxi website</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Discount Code Section -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <label for="discount_code" class="block text-sm font-medium text-gray-700 mb-2">Discount Code (Optional)</label>
                        <div class="flex gap-2">
                            <input type="text" name="discount_code" id="discount_code" placeholder="Enter discount code" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm uppercase">
                            <button type="button" id="apply-discount-btn" class="px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-black/80">Apply</button>
                        </div>
                        <p id="discount-message" class="text-sm mt-2 hidden"></p>
                    </div>

                    <button type="submit" id="submit-checkout-btn" class="w-full flex items-center justify-center rounded-md border border-transparent bg-black px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-black/80 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="hidden animate-spin -ml-1 mr-3 h-5 w-5 text-white" id="submit-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="submit-text">Proceed to Payment</span>
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-md h-fit sticky top-4">
                    <h2 class="text-2xl font-bold mb-4">Order Summary</h2>
                    <div class="space-y-3 border-b border-gray-200 pb-4 mb-4">
                        <?php foreach ($cart_items as $product_id => $item): ?>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">
                                    <img src="<?php echo SITE_URL . htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="h-full w-full object-cover object-center">
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex justify-between text-sm text-gray-900">
                                        <p class="truncate"><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</p>
                                    </div>
                                    <div class="flex justify-between">
                                        <p class="text-xs text-gray-500">R <?php echo number_format($item['price'], 2); ?> each</p>
                                        <p class="text-sm font-medium">R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-base text-gray-900">
                            <p>Subtotal</p>
                            <p>R <?php echo number_format($subtotal, 2); ?></p>
                        </div>
                        <div class="flex justify-between text-base text-gray-900">
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
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const applyDiscountBtn = document.getElementById('apply-discount-btn');
    const discountCodeInput = document.getElementById('discount_code');
    const discountMessage = document.getElementById('discount-message');
    const csrfToken = '<?php echo $csrf_token; ?>';
    const checkoutForm = document.getElementById('checkout-form');
    
    // Phone number formatting
    const phoneInput = document.getElementById('shipping_phone');
    phoneInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '').slice(0, 10);
    });
    
    // Zip code formatting
    const zipInput = document.getElementById('shipping_zip');
    zipInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '').slice(0, 4);
    });
    
    // Remove error styling when user starts typing
    const allInputs = ['shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_zip', 'shipping_phone'];
    allInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        input.addEventListener('input', function() {
            this.classList.remove('border-red-500');
        });
    });

    applyDiscountBtn.addEventListener('click', function() {
        const discountCode = discountCodeInput.value.trim().toUpperCase();

        if (!discountCode) {
            discountMessage.textContent = 'Please enter a discount code.';
            discountMessage.className = 'text-sm mt-2 text-red-600';
            discountMessage.classList.remove('hidden');
            return;
        }

        applyDiscountBtn.disabled = true;
        applyDiscountBtn.textContent = 'Applying...';

        // Send AJAX request to validate discount
        fetch('<?php echo SITE_URL; ?>ajax_validate_discount.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `discount_code=${encodeURIComponent(discountCode)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                discountMessage.textContent = data.message;
                discountMessage.className = 'text-sm mt-2 text-green-600';
                discountCodeInput.disabled = true;
                applyDiscountBtn.textContent = 'Applied';
                applyDiscountBtn.disabled = true;
            } else {
                discountMessage.textContent = data.message || 'Invalid discount code.';
                discountMessage.className = 'text-sm mt-2 text-red-600';
            }
            discountMessage.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
            discountMessage.textContent = 'An error occurred. Please try again.';
            discountMessage.className = 'text-sm mt-2 text-red-600';
            discountMessage.classList.remove('hidden');
        })
        .finally(() => {
            if (!discountCodeInput.disabled) {
                applyDiscountBtn.disabled = false;
                applyDiscountBtn.textContent = 'Apply';
            }
        });
    });

    // Clear discount message when user starts typing
    discountCodeInput.addEventListener('input', function() {
        if (discountCodeInput.disabled) return; // Don't clear if already applied
        discountMessage.classList.add('hidden');
    });

    // Handle checkout form submission
    checkoutForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate all required fields
        const requiredFields = [
            { id: 'shipping_name', label: 'Full Name' },
            { id: 'shipping_email', label: 'Email Address' },
            { id: 'shipping_address', label: 'Address' },
            { id: 'shipping_city', label: 'City' },
            { id: 'shipping_zip', label: 'Zip Code' },
            { id: 'shipping_phone', label: 'Phone Number' }
        ];

        // Check if Paxi is selected and add Paxi fields to validation
        const shippingMethod = document.querySelector('input[name="shipping_method"]:checked');
        if (shippingMethod && (shippingMethod.value === 'paxi_standard' || shippingMethod.value === 'paxi_express')) {
            requiredFields.push(
                { id: 'paxi_point_name', label: 'Paxi Point Name' },
                { id: 'paxi_point_address', label: 'Paxi Point Address' },
                { id: 'paxi_point_city', label: 'Paxi Point City' }
            );
        }

        let isValid = true;
        let firstInvalidField = null;

        requiredFields.forEach(field => {
            const input = document.getElementById(field.id);
            if (!input) return; // Skip if field doesn't exist
            
            const value = input.value.trim();
            
            if (!value) {
                isValid = false;
                input.classList.add('border-red-500');
                if (!firstInvalidField) firstInvalidField = input;
            } else {
                input.classList.remove('border-red-500');
            }
        });

        // Validate email format
        const emailInput = document.getElementById('shipping_email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailInput.value && !emailRegex.test(emailInput.value)) {
            isValid = false;
            emailInput.classList.add('border-red-500');
            if (!firstInvalidField) firstInvalidField = emailInput;
        }

        // Validate phone (10 digits)
        const phoneInput = document.getElementById('shipping_phone');
        if (phoneInput.value && phoneInput.value.length !== 10) {
            isValid = false;
            phoneInput.classList.add('border-red-500');
            if (!firstInvalidField) firstInvalidField = phoneInput;
        }

        // Validate zip (4 digits)
        const zipInput = document.getElementById('shipping_zip');
        if (zipInput.value && zipInput.value.length !== 4) {
            isValid = false;
            zipInput.classList.add('border-red-500');
            if (!firstInvalidField) firstInvalidField = zipInput;
        }

        if (!isValid) {
            if (window.Modal) {
                Modal.alert('Please fill in all required fields correctly.', 'Validation Error', 'warning').then(() => {
                    if (firstInvalidField) {
                        firstInvalidField.focus();
                    }
                });
            } else {
                alert('Please fill in all required fields correctly.');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
            }
            return;
        }

        const submitBtn = document.getElementById('submit-checkout-btn');
        const submitText = document.getElementById('submit-text');
        const submitSpinner = document.getElementById('submit-spinner');
        
        // Show loading state
        submitBtn.disabled = true;
        submitText.textContent = 'Processing...';
        submitSpinner.classList.remove('hidden');

        // Collect form data
        const formData = new FormData(checkoutForm);

        // Send to yoco_process.php to create order
        fetch(checkoutForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if response is ok
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Server error occurred');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                submitText.textContent = 'Opening Payment...';
                // Order created, now initiate Yoco payment
                initiateYocoPayment(data.paymentData);
            } else {
                if (window.Modal) {
                    Modal.alert(data.message || 'Failed to process your order. Please try again.', 'Order Error', 'error');
                } else {
                    alert(data.message || 'Failed to process your order. Please try again.');
                }
                // Reset button state
                submitBtn.disabled = false;
                submitText.textContent = 'Proceed to Payment';
                submitSpinner.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Checkout Error:', error);
            // Show more specific error message
            let errorMessage = 'An error occurred while processing your order.';
            if (error.message) {
                errorMessage += '<br><br><strong>Details:</strong> ' + error.message;
            }
            errorMessage += '<br><br>Please check your information and try again.';
            if (window.Modal) {
                Modal.alert(errorMessage, 'Checkout Error', 'error');
            } else {
                alert(errorMessage.replace(/<br>/g, '\n').replace(/<[^>]*>/g, ''));
            }
            
            // Reset button state
            submitBtn.disabled = false;
            submitText.textContent = 'Proceed to Payment';
            submitSpinner.classList.add('hidden');
        });
    });
});

// Yoco Payment Integration
function initiateYocoPayment(paymentData) {
    const yoco = new window.YocoSDK({
        publicKey: '<?php echo YOCO_PUBLIC_KEY; ?>',
    });

    // Show inline popup
    yoco.showPopup({
        amountInCents: paymentData.amount,
        currency: paymentData.currency,
        name: paymentData.description,
        description: paymentData.description,
        reference: paymentData.formatted_order_id,
                callback: function (result) {
            if (result.error) {
                console.error('Yoco payment error:', result.error);
                if (window.Modal) {
                    Modal.alert('Payment failed: ' + result.error.message, 'Payment Error', 'error');
                } else {
                    alert('Payment failed: ' + result.error.message);
                }
            } else {
                // Redirect to success page
                window.location.href = '<?php echo SITE_URL; ?>order_success.php?order_id=' + paymentData.numeric_order_id;
            }
        }
    });
}

// ===== PAXI INTEGRATION =====
// Update shipping method
window.updateShippingMethod = function(method) {
    const paxiSelection = document.getElementById('paxi-point-selection');
    const paxiStandardOption = document.getElementById('paxi-standard-option');
    const paxiExpressOption = document.getElementById('paxi-express-option');
    
    // Remove all highlights
    paxiStandardOption.classList.remove('border-black', 'bg-black/5');
    paxiExpressOption.classList.remove('border-black', 'bg-black/5');
    
    // Paxi is always shown since it's the only option
    paxiSelection.classList.remove('hidden');
    
    // Highlight selected option
    if (method === 'paxi_standard') {
        paxiStandardOption.classList.add('border-black', 'bg-black/5');
    } else if (method === 'paxi_express') {
        paxiExpressOption.classList.add('border-black', 'bg-black/5');
    }
    
    // Paxi fields are always required
    document.getElementById('paxi_point_name').setAttribute('required', 'required');
    document.getElementById('paxi_point_address').setAttribute('required', 'required');
    document.getElementById('paxi_point_city').setAttribute('required', 'required');
};

// Show Paxi selection by default on page load
document.addEventListener('DOMContentLoaded', function() {
    const paxiSelection = document.getElementById('paxi-point-selection');
    if (paxiSelection) {
        paxiSelection.classList.remove('hidden');
        // Make fields required
        document.getElementById('paxi_point_name').setAttribute('required', 'required');
        document.getElementById('paxi_point_address').setAttribute('required', 'required');
        document.getElementById('paxi_point_city').setAttribute('required', 'required');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

<!-- Yoco SDK - loaded after footer so Modal is available -->
<script src="https://js.yoco.com/sdk/v1/yoco-sdk-web.js"></script>
