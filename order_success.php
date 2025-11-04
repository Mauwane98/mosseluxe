<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include header and footer
require_once 'includes/header.php';
require_once 'includes/db_connect.php'; // Needed to fetch order details if not passed via GET
$conn = get_db_connection();

$order_id = null;
$order_details = null;

// Check if order ID is passed via GET parameter
if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $order_id = filter_var(trim($_GET['order_id']), FILTER_SANITIZE_NUMBER_INT);

    // Fetch order details from the database using the order_id
    // This assumes an 'orders' table with columns like id, user_id, order_date, total_amount, shipping_address, status
    // And an 'order_items' table to list the products in the order.
    
    // For simplicity, we'll just display a generic success message and link to homepage/shop.
    // A more complete implementation would fetch and display order details here.
    // Example: Fetching order details
    
    $sql_order = "SELECT * FROM orders WHERE id = ?";
    if ($stmt_order = $conn->prepare($sql_order)) {
        $stmt_order->bind_param("i", $param_order_id);
        $param_order_id = $order_id;
        if ($stmt_order->execute()) {
            $result_order = $stmt_order->get_result();
            if ($row_order = $result_order->fetch_assoc()) {
                $order_details = $row_order;
            }
        }
        $stmt_order->close();
    }
    
} else {
    // If no order ID is provided, redirect to shop or show an error
    // For now, we'll just show a generic success message.
    // In a real app, you'd want to ensure the order ID is present.
}

$conn->close();
?>

<div class="container my-5 bg-white-section rounded shadow-sm">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 text-center">
            <div class="card bg-dark border-secondary text-white p-4 rounded-3">
                <div class="card-body">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h1 class="my-3 gold-text" style="font-family: 'Playfair Display', serif;">Thank You!</h1>
                    <h4 class="mb-4">Your order has been placed successfully.</h4>
                    <?php if ($order_id): ?>
                        <p class="text-muted">An email confirmation has been sent to your email address with the order details. Your order ID is <strong class="text-white">ML-<?php echo htmlspecialchars($order_id); ?></strong>.</p>
                    <?php else: ?>
                        <p class="text-muted">An email confirmation has been sent to your email address with the order details.</p>
                    <?php endif; ?>
                    
                    <hr class="my-4">

                    <h5 class="mb-3">What's Next?</h5>
                    <p class="text-muted">We will process your order within 1-2 business days. You will receive another email once your order has been shipped.</p>

                    <div class="d-grid gap-2 mt-4">
                        <a href="shop.php" class="btn btn-outline-dark-alt">Continue Shopping</a>
                        <a href="index.php" class="btn btn-link text-white">Back to Homepage</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
