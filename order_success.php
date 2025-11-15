<?php
$pageTitle = "Order Success - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';
require_once 'includes/header.php';

$order_id = filter_var($_GET['order_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
$order_details = null;
$order_items = [];

if ($order_id) {
    $conn = get_db_connection();

    // Fetch order details
    $stmt = $conn->prepare("SELECT id, total_price, status, created_at FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc();
    }
    $stmt->close();

    // Fetch order items
    $stmt_items = $conn->prepare("SELECT oi.quantity, oi.price, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    while ($row = $result_items->fetch_assoc()) {
        $order_items[] = $row;
    }
    $stmt_items->close();

    $conn->close();
}
?>

<main>
    <div class="container mx-auto px-4 py-16 md:py-24 text-center">
        <?php if ($order_details): ?>
            <svg class="mx-auto h-16 w-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h1 class="mt-4 text-4xl md:text-6xl font-black uppercase tracking-tighter text-green-600">Order Successful!</h1>
            <p class="mt-4 text-lg text-black/70">Thank you for your purchase. Your order <?php echo htmlspecialchars(get_order_id_from_numeric_id($order_details['id'])); ?> has been placed successfully.</p>
            <p class="text-lg text-black/70">A confirmation email has been sent to your inbox.</p>

            <div class="mt-12 bg-white p-6 rounded-lg shadow-md max-w-2xl mx-auto">
                <h2 class="text-2xl font-bold mb-4">Order Summary</h2>
                <div class="border-b border-gray-200 pb-4 mb-4">
                    <?php foreach ($order_items as $item): ?>
                        <div class="flex justify-between text-base text-gray-900 mb-2">
                            <p><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</p>
                            <p>R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex justify-between text-base text-gray-900 mb-2">
                    <p>Total Paid</p>
                    <p>R <?php echo number_format($order_details['total_price'], 2); ?></p>
                </div>
                <div class="flex justify-between text-base text-gray-900">
                    <p>Order Status</p>
                    <p><?php echo htmlspecialchars($order_details['status']); ?></p>
                </div>
            </div>

            <div class="mt-12 flex justify-center space-x-4">
                <a href="my_account.php?view=orders" class="text-lg font-semibold text-black border-b-2 border-black hover:border-transparent transition-colors">
                    View Your Orders
                </a>
                <a href="shop.php" class="text-lg font-semibold text-black border-b-2 border-black hover:border-transparent transition-colors">
                    Continue Shopping
                </a>
            </div>

        <?php else: ?>
            <h1 class="mt-4 text-4xl md:text-6xl font-black uppercase tracking-tighter text-red-600">Order Not Found</h1>
            <p class="mt-4 text-lg text-black/70">We could not find details for your order. Please check your account or contact support.</p>
            <div class="mt-12">
                <a href="shop.php" class="text-lg font-semibold text-black border-b-2 border-black hover:border-transparent transition-colors">
                    Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
