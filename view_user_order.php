<?php
// Start session and check if user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If user is not logged in, redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'includes/header.php';
require_once 'includes/db_connect.php';
$conn = get_db_connection();

$order_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : 0;
$user_id = $_SESSION['user_id'];
$order = null;
$order_items = [];

if ($order_id > 0) {
    // Fetch order details, ensuring it belongs to the logged-in user
    $sql_order = "SELECT * FROM orders WHERE id = ? AND user_id = ?"; // Already fetches all columns, including shipping_address_json
    if ($stmt = $conn->prepare($sql_order)) {
        $stmt->bind_param("ii", $order_id, $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
        }
        $stmt->close();
    }

    // If order was found and belongs to the user, fetch its items
    if ($order) {
        $sql_items = "SELECT oi.*, p.name as product_name, p.image as product_image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
        if ($stmt_items = $conn->prepare($sql_items)) {
            $stmt_items->bind_param("i", $order_id);
            if ($stmt_items->execute()) {
                $result_items = $stmt_items->get_result();
                while ($row = $result_items->fetch_assoc()) {
                    $order_items[] = $row;
                }
            }
            $stmt_items->close();
        }
    }
}

$conn->close();
?>

<div class="container my-5 bg-white-section rounded shadow-sm">
    <?php if ($order): ?>
        <header class="d-flex justify-content-between align-items-center mb-4">
            <h1>Order Details <span class="gold-text">#ML-<?php echo $order['id']; ?></span></h1>
            <a href="my_account.php" class="btn btn-outline-dark-alt">&leftarrow; Back to My Orders</a>
        </header>

        <div class="row">
            <div class="col-lg-8">
                <div class="card bg-dark border-secondary p-4 mb-4">
                    <h3 class="gold-text mb-3">Items in this Order</h3>
                    <?php foreach ($order_items as $item): ?>
                        <div class="row border-bottom border-secondary py-3 align-items-center">
                            <div class="col-md-2">
                                <img src="<?php echo htmlspecialchars($item['product_image']); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            </div>
                            <div class="col-md-6">
                                <h5><?php echo htmlspecialchars($item['product_name']); ?></h5>
                            </div>
                            <div class="col-md-2 text-md-center">
                                <p class="mb-0">Qty: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="col-md-2 text-md-end">
                                <p class="mb-0">R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="row fw-bold mt-3">
                        <?php if (isset($order['discount_code']) && !empty($order['discount_code'])): ?>
                            <div class="col-md-10 text-end">Discount (<?php echo htmlspecialchars($order['discount_code']); ?>):</div>
                            <div class="col-md-2 text-md-end">- R <?php echo number_format($order['discount_amount'], 2); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="row fw-bold mt-2">
                        <div class="col-md-10 text-end gold-text">Total:</div>
                        <div class="col-md-2 text-md-end gold-text">R <?php echo number_format($order['total_price'], 2); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card bg-dark border-secondary p-4 mb-4">
                    <h5 class="gold-text">Order Summary</h5>
                    <p class="mb-1"><strong>Order Date:</strong> <?php echo date('d M Y', strtotime($order['created_at'])); ?></p>
                    <p class="mb-1"><strong>Order Total:</strong> R <?php echo number_format($order['total_price'], 2); ?></p>
                    <p class="mb-1"><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                </div>
                <div class="card bg-dark border-secondary p-4 mb-4">
                    <h5 class="gold-text">Shipping Address</h5>
                    <?php $shipping_address = json_decode($order['shipping_address_json'], true); ?>
                    <?php if ($shipping_address): ?>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($shipping_address['firstName'] . ' ' . $shipping_address['lastName']); ?></strong></p>
                        <p class="mb-1"><?php echo htmlspecialchars($shipping_address['address']); ?></p>
                        <?php if(!empty($shipping_address['address2'])): ?><p class="mb-1"><?php echo htmlspecialchars($shipping_address['address2']); ?></p><?php endif; ?>
                        <p class="mb-1"><?php echo htmlspecialchars($shipping_address['city'] . ', ' . $shipping_address['province'] . ', ' . $shipping_address['zip']); ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($shipping_address['email']); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No shipping address provided for this order.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="text-center py-5">
            <h1 class="gold-text">Order Not Found</h1>
            <p class="text-muted">The order you are looking for could not be found or does not belong to your account.</p>
            <a href="my_account.php" class="btn btn-outline-light mt-3">Return to My Account</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>