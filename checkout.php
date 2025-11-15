<?php
$pageTitle = "Checkout - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';

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

$checkout_error = '';
$csrf_token = generate_csrf_token(); // Generate token for the form

// If user is logged in, try to fetch their default address or last used address
if ($user_id) {
    // This part would typically involve fetching from a 'user_addresses' table
    // For now, we'll leave it blank or use dummy data
    // For demonstration, let's assume a logged-in user might have their name/email pre-filled
    $shipping_name = $user_name;
    $shipping_email = $user_email;
} else {
    $shipping_email = ''; // For guest checkout, email needs to be entered
}

// Handle AJAX form submission for shipping details and payment processing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please try again.']);
        exit();
    }

    // Sanitize and validate inputs
    $shipping_name = trim($_POST['shipping_name'] ?? '');
    $shipping_email = filter_var(trim($_POST['shipping_email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $shipping_city = trim($_POST['shipping_city'] ?? '');
    $shipping_zip = trim($_POST['shipping_zip'] ?? '');
    $shipping_phone = trim($_POST['shipping_phone'] ?? '');
    $discount_code = trim(strtoupper($_POST['discount_code'] ?? ''));

    // Server-side validation
    if (empty($shipping_name) || empty($shipping_email) || empty($shipping_address) || empty($shipping_city) || empty($shipping_zip) || empty($shipping_phone)) {
        echo json_encode(['success' => false, 'message' => 'All shipping fields are required.']);
        exit();
    } elseif (!filter_var($shipping_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit();
    }

    // Validate discount code if provided
    $discount_data = null;
    if (!empty($discount_code)) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT * FROM discount_codes WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) AND usage_limit > usage_count");
        $stmt->bind_param("s", $discount_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired discount code.']);
            exit();
        } else {
            $discount_data = $result->fetch_assoc();
        }
        $stmt->close();
    }

    // Calculate discount if valid
    $discount_amount = 0;
    $final_total = $total;
    if ($discount_data) {
        if ($discount_data['type'] === 'percentage') {
            $discount_amount = $subtotal * ($discount_data['value'] / 100);
        } else {
            $discount_amount = min($discount_data['value'], $subtotal); // Don't exceed subtotal
        }
        $final_total = $total - $discount_amount;
    }

    // Prepare checkout data for Yoco processing
    $checkout_data = [
        'user_id' => $user_id,
        'cart_items' => $cart_items,
        'subtotal' => $subtotal,
        'shipping_cost' => $shipping_cost,
        'total' => $total,
        'final_total' => $final_total,
        'discount_data' => $discount_data,
        'discount_code' => $discount_code,
        'discount_amount' => $discount_amount,
        'shipping_info' => [
            'firstName' => explode(' ', $shipping_name, 2)[0],
            'lastName' => explode(' ', $shipping_name, 2)[1] ?? '',
            'address' => $shipping_address,
            'address2' => '', // Not collected in form, provide empty string
            'city' => $shipping_city,
            'province' => '', // Not collected in form, provide empty string
            'zip' => $shipping_zip,
            'email' => $shipping_email,
            'phone' => $shipping_phone
        ]
    ];

    // Make request to yoco_process.php to create order
    $yoco_data = $checkout_data;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SITE_URL . 'yoco_process.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yoco_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($http_code === 200 && $result['success']) {
        // Return the Yoco payment data
        echo json_encode([
            'success' => true,
            'paymentData' => $result
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['error'] ?? 'Failed to create order. Please try again.'
        ]);
    }
    exit();
}

require_once 'includes/header.php';
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

                <form id="checkout-form" action="#" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="ajax" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="shipping_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="shipping_name" id="shipping_name" value="<?php echo htmlspecialchars($shipping_name); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
                        </div>
                        <div>
                            <label for="shipping_email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" name="shipping_email" id="shipping_email" value="<?php echo htmlspecialchars($shipping_email); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
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

                    <!-- Discount Code Section -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <label for="discount_code" class="block text-sm font-medium text-gray-700 mb-2">Discount Code (Optional)</label>
                        <div class="flex gap-2">
                            <input type="text" name="discount_code" id="discount_code" placeholder="Enter discount code" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm uppercase">
                            <button type="button" id="apply-discount-btn" class="px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-black/80">Apply</button>
                        </div>
                        <p id="discount-message" class="text-sm mt-2 hidden"></p>
                    </div>

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
                        <div class="w-16 h-16 flex-shrink-0 overflow-hidden rounded-md border border-gray-200 mr-4">
                            <img src="<?php echo SITE_URL . htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="h-full w-full object-cover object-center">
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between text-sm text-gray-900">
                                <p><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</p>
                                <p>R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                            </div>
                            <p class="text-xs text-gray-500">R <?php echo number_format($item['price'], 2); ?> each</p>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const applyDiscountBtn = document.getElementById('apply-discount-btn');
    const discountCodeInput = document.getElementById('discount_code');
    const discountMessage = document.getElementById('discount-message');
    const csrfToken = '<?php echo $csrf_token; ?>';
    const checkoutForm = document.getElementById('checkout-form');

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

        const submitBtn = checkoutForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = 'Processing...';
        submitBtn.disabled = true;

        // Collect form data
        const formData = new FormData(checkoutForm);

        // Send to yoco_process.php to create order
        fetch(checkoutForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Order created, now initiate Yoco payment
                initiateYocoPayment(data.paymentData);
            } else {
                alert(data.message || 'Failed to process your order. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your order. Please try again.');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
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
                alert('Payment failed: ' + result.error.message);
            } else {
                console.log('Payment successful:', result);
                // Redirect to success page
                window.location.href = '<?php echo SITE_URL; ?>order_success.php?order_id=' + paymentData.numeric_order_id;
            }
        }
    });
}
</script>

<?php
require_once 'includes/footer.php';
?>
