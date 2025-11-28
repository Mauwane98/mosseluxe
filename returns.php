<?php
$pageTitle = "Returns - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';

$orders = [];
$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    $conn = get_db_connection();

    // Get user's orders that are eligible for returns (delivered within 30 days)
    $sql = "SELECT id, created_at, total_price, status
            FROM orders
            WHERE user_id = ?
            AND status IN ('Delivered', 'Completed')
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
    $conn->close();
}

require_once 'includes/header.php';
?>

<main>
    <div class="container mx-auto px-4 md:px-6 py-16 md:py-24">
        <div class="text-center mb-16">
            <div class="flex items-center justify-center gap-3 mb-4">
                <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V4a2 2 0 00-2-2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2z"/>
                    </svg>
                </div>
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Returns & Exchanges</h1>
            </div>
            <p class="text-lg text-black/70 max-w-2xl mx-auto">
                Easy returns within 30 days of delivery. We're here to make the process simple for you.
            </p>
        </div>

        <!-- Returns Policy -->
        <div class="bg-neutral-50 p-8 rounded-lg mb-12">
            <h2 class="text-2xl font-bold mb-6 text-center">Return Policy</h2>
            <div class="grid md:grid-cols-3 gap-8 text-center">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-black rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold mb-2">30 Days</h3>
                    <p class="text-sm text-gray-600">Return or exchange within 30 days of delivery</p>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-black rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    </div>
                    <h3 class="font-bold mb-2">Free Shipping</h3>
                    <p class="text-sm text-gray-600">We cover return shipping costs</p>
                </div>
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-black rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                                      />
                            </svg>
                        </div>
                    <h3 class="font-bold mb-2">Pre-Approved</h3>
                    <p class="text-sm text-gray-600">Quick processing for approved returns</p>
                </div>
            </div>
        </div>

        <!-- Return Steps -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold mb-8 text-center">How Returns Work</h2>
            <div class="grid md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">1</div>
                    <h3 class="font-bold mb-2">Choose Items</h3>
                    <p class="text-sm text-gray-600">Select the items you want to return from your eligible orders</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">2</div>
                    <h3 class="font-bold mb-2">Fill Form</h3>
                    <p class="text-sm text-gray-600">Complete the return request with reason and preferred resolution</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">3</div>
                    <h3 class="font-bold mb-2">Pack & Ship</h3>
                    <p class="text-sm text-gray-600">We'll provide a prepaid return label for easy shipping</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">4</div>
                    <h3 class="font-bold mb-2">Get Refund</h3>
                    <p class="text-sm text-gray-600">Receive your refund or exchange within 3-5 business days</p>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"]): ?>
            <!-- Return Request Form -->
            <div class="bg-neutral-50 p-8 rounded-lg">
                <h2 class="text-2xl font-bold mb-6">Start Your Return</h2>

                <?php if (!empty($orders)): ?>
                    <form id="return-form" action="ajax_return_handler.php" method="POST" class="space-y-6">
                        <?php echo generate_csrf_token_input(); ?>

                        <!-- Select Order -->
                        <div>
                            <label for="order_id" class="block text-sm font-medium text-black/80 mb-1">Select Order *</label>
                            <select name="order_id" id="order_id" required class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="">Choose an order...</option>
                                <?php foreach ($orders as $order): ?>
                                    <option value="<?php echo $order['id']; ?>">
                                        Order #<?php echo get_order_id_from_numeric_id($order['id']); ?> -
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?> -
                                        R <?php echo number_format($order['total_price'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Items Selection (populated via AJAX) -->
                        <div id="order-items-section" style="display: none;">
                            <label class="block text-sm font-medium text-black/80 mb-3">Select Items to Return *</label>
                            <div id="order-items-container" class="space-y-3">
                                <!-- Items will be loaded here via AJAX -->
                            </div>
                        </div>

                        <!-- Return Reason -->
                        <div>
                            <label for="return_reason" class="block text-sm font-medium text-black/80 mb-1">Return Reason *</label>
                            <select name="return_reason" id="return_reason" required class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="">Select a reason...</option>
                                <option value="wrong_size">Wrong Size</option>
                                <option value="wrong_item">Wrong Item Ordered</option>
                                <option value="defective">Item Defective/Damaged</option>
                                <option value="not_as_described">Not as Described</option>
                                <option value="changed_mind">Changed My Mind</option>
                                <option value="better_price">Found Better Price</option>
                                <option value="late_delivery">Late Delivery</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Additional Details -->
                        <div>
                            <label for="additional_details" class="block text-sm font-medium text-black/80 mb-1">Additional Details</label>
                            <textarea name="additional_details" id="additional_details" rows="4"
                                      class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black resize-none"
                                      placeholder="Please provide any additional information about your return..."></textarea>
                        </div>

                        <!-- Preferred Resolution -->
                        <div>
                            <label class="block text-sm font-medium text-black/80 mb-3">Preferred Resolution *</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="resolution" value="refund" required class="mr-3">
                                    <span>Refund to original payment method</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="resolution" value="store_credit" class="mr-3">
                                    <span>Store credit (faster processing)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="resolution" value="exchange" class="mr-3">
                                    <span>Exchange for different size/item</span>
                                </label>
                            </div>
                        </div>

                        <!-- Bank Details for Refund (shown conditionally) -->
                        <div id="bank-details-section" style="display: none;">
                            <h4 class="text-lg font-bold mb-3">Banking Details for Refund</h4>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label for="bank_name" class="block text-sm font-medium text-black/80 mb-1">Bank Name</label>
                                    <input type="text" name="bank_name" id="bank_name" class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                                <div>
                                    <label for="account_holder" class="block text-sm font-medium text-black/80 mb-1">Account Holder Name</label>
                                    <input type="text" name="account_holder" id="account_holder" class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                                <div>
                                    <label for="account_number" class="block text-sm font-medium text-black/80 mb-1">Account Number</label>
                                    <input type="text" name="account_number" id="account_number" class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                                <div>
                                    <label for="branch_code" class="block text-sm font-medium text-black/80 mb-1">Branch Code</label>
                                    <input type="text" name="branch_code" id="branch_code" class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                            </div>
                        </div>

                        <!-- Contact Preference -->
                        <div>
                            <label class="block text-sm font-medium text-black/80 mb-3">Preferred Contact Method</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="contact_method" value="email" checked class="mr-3">
                                    <span>Email (faster)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="contact_method" value="phone" class="mr-3">
                                    <span>Phone</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="contact_method" value="whatsapp" class="mr-3">
                                    <span>WhatsApp</span>
                                </label>
                            </div>
                        </div>

                        <div class="text-center pt-4">
                            <button type="submit" class="bg-black text-white py-3 px-12 font-bold uppercase rounded-md hover:bg-black/80 transition-colors">
                                Submit Return Request
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center py-8">
                        <p class="text-black/70 mb-4">You don't have any eligible orders for returns.</p>
                        <p class="text-sm text-black/50">Orders must be delivered within the last 30 days to be eligible for returns.</p>
                        <a href="my_account.php?view=orders" class="inline-block mt-4 bg-black text-white py-2 px-6 rounded-md hover:bg-black/80 transition-colors">
                            View My Orders
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Login Required -->
            <div class="text-center bg-neutral-50 p-12 rounded-lg">
                <div class="text-6xl mb-4">🔐</div>
                <h3 class="text-xl font-bold mb-4">Login Required</h3>
                <p class="text-black/70 mb-6">Please log in to your account to start a return request.</p>
                <a href="login.php?redirect=returns.php" class="bg-black text-white py-3 px-6 rounded-md hover:bg-black/80 transition-colors">
                    Log In
                </a>
            </div>
        <?php endif; ?>

        <!-- Contact Support -->
        <div class="mt-12 text-center">
            <h3 class="text-lg font-bold mb-4">Need Help?</h3>
            <p class="text-black/70 mb-4">Have questions about our returns policy or need assistance with your return?</p>
            <div class="flex justify-center gap-4">
                <a href="contact.php" class="bg-black text-white py-2 px-6 rounded-md hover:bg-black/80 transition-colors">
                    Contact Support
                </a>
                <a href="track_order.php" class="bg-neutral-100 text-black py-2 px-6 rounded-md hover:bg-neutral-200 transition-colors">
                    Track Order
                </a>
            </div>
        </div>
    </div>
</main>

<script>
// Dynamic form interactions
document.addEventListener('DOMContentLoaded', function() {
    const orderSelect = document.getElementById('order_id');
    const itemsSection = document.getElementById('order-items-section');
    const itemsContainer = document.getElementById('order-items-container');
    const resolutionRadios = document.querySelectorAll('input[name="resolution"]');
    const bankDetailsSection = document.getElementById('bank-details-section');

    // Load order items when order is selected
    orderSelect.addEventListener('change', function() {
        const orderId = this.value;

        if (!orderId) {
            itemsSection.style.display = 'none';
            return;
        }

        // Fetch order items via AJAX
        fetch('ajax_get_order_items.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'order_id': orderId,
                'csrf_token': document.querySelector('input[name="csrf_token"]').value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.items.length > 0) {
                // Build item checkboxes
                let html = '';
                data.items.forEach(item => {
                    html += `
                        <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <input type="checkbox" name="return_items[]" value="${item.product_id}" class="mr-3">
                            <img src="${item.image}" alt="${item.name}" class="w-12 h-12 object-cover rounded mr-3">
                            <div class="flex-1">
                                <div class="font-medium">${item.name}</div>
                                <div class="text-sm text-gray-600">Qty: ${item.quantity} | R ${item.price}</div>
                            </div>
                        </label>
                    `;
                });

                itemsContainer.innerHTML = html;
                itemsSection.style.display = 'block';
            } else {
                itemsContainer.innerHTML = '<p class="text-gray-500">No eligible items found for return.</p>';
                itemsSection.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading order items:', error);
            itemsContainer.innerHTML = '<p class="text-red-600">Error loading items. Please try again.</p>';
        });
    });

    // Show bank details for refund resolution
    resolutionRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'refund' && this.checked) {
                bankDetailsSection.style.display = 'block';
                document.getElementById('bank_name').required = true;
                document.getElementById('account_holder').required = true;
                document.getElementById('account_number').required = true;
                document.getElementById('branch_code').required = true;
            } else {
                bankDetailsSection.style.display = 'none';
                document.getElementById('bank_name').required = false;
                document.getElementById('account_holder').required = false;
                document.getElementById('account_number').required = false;
                document.getElementById('branch_code').required = false;
            }
        });
    });

    // Form submission
    const returnForm = document.getElementById('return-form');
    if (returnForm) {
        returnForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = returnForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            const formData = new FormData(returnForm);

            fetch('ajax_return_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message and reload
                    if (typeof window.showToast === 'function') {
                        window.showToast(data.message, 'success');
                    } else {
                        alert(data.message);
                    }
                    // Replace form with success message
                    returnForm.parentElement.innerHTML = `
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">✅</div>
                            <h3 class="text-xl font-bold mb-4">Return Request Submitted!</h3>
                            <p class="text-black/70 mb-6">${data.message}</p>
                            <p class="text-sm text-black/50 mb-6">Return ID: ${data.return_id || 'Processing'}</p>
                            <a href="my_account.php?view=orders" class="bg-black text-white py-2 px-6 rounded-md hover:bg-black/80 transition-colors">
                                View My Orders
                            </a>
                        </div>
                    `;
                } else {
                    if (typeof window.showToast === 'function') {
                        window.showToast(data.message, 'error');
                    } else {
                        alert(data.message);
                    }
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Return submission error:', error);
                if (typeof window.showToast === 'function') {
                    window.showToast('Network error. Please try again.', 'error');
                } else {
                    alert('Network error. Please try again.');
                }
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
