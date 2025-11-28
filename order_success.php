<?php
$pageTitle = "Order Success - Mossé Luxe";
require_once 'includes/bootstrap.php';
require_once 'includes/loyalty_functions.php';

$conn = get_db_connection();
$order_id = $_GET['order_id'] ?? null;
$points_earned = 0;

// Award loyalty points for this order
if ($order_id && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get order total
    $stmt = $conn->prepare("SELECT total FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        $order_total = $order['total'];
        
        // Check if points already awarded
        $stmt2 = $conn->prepare("SELECT id FROM loyalty_transactions WHERE reference_type = 'order' AND reference_id = ?");
        $stmt2->bind_param("i", $order_id);
        $stmt2->execute();
        $check_result = $stmt2->get_result();
        
        if ($check_result->num_rows == 0) {
            // Award points
            awardPurchasePoints($conn, $user_id, $order_id, $order_total);
            $points_earned = floor($order_total * POINTS_PER_RAND);
        }
        $stmt2->close();
    }
    $stmt->close();
}

// Clear the cart after a successful order
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}

// If the user is logged in, also clear their cart from the database
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM user_carts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

require_once 'includes/header.php';

?>

<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <div class="max-w-2xl mx-auto text-center">
            <div class="mb-8">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter mb-4">Order Confirmed!</h1>
                <p class="text-lg text-black/70 mb-4">
                    Thank you for your order. We've received your payment and are processing your items.
                </p>
                
                <?php if ($points_earned > 0): ?>
                    <div class="inline-block bg-gradient-to-r from-purple-100 to-blue-100 border-2 border-purple-300 rounded-lg px-6 py-3 mb-4">
                        <p class="text-sm text-purple-800 mb-1">🎁 Loyalty Rewards</p>
                        <p class="text-2xl font-bold text-purple-900">+<?php echo number_format($points_earned); ?> Points Earned!</p>
                        <p class="text-sm text-purple-700">Check your <a href="loyalty.php" class="underline font-semibold">rewards dashboard</a></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-2xl font-bold mb-4">Order Summary</h2>
                <div class="space-y-2 text-left">
                    <div class="flex justify-between">
                        <span>Order Number:</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($order_id); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Status:</span>
                        <span class="font-semibold text-green-600">Payment Confirmed</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <p class="text-black/70">
                    We'll send you an email confirmation with tracking information once your order ships.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="shop.php" class="bg-black text-white px-8 py-3 rounded-md font-semibold hover:bg-black/80 transition-colors">
                        Continue Shopping
                    </a>
                    <a href="my_account.php" class="border border-black text-black px-8 py-3 rounded-md font-semibold hover:bg-gray-50 transition-colors">
                        View My Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
