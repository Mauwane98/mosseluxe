and <?php
// Start session and include admin authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
require_once '../includes/config.php'; // For SHIPPING_COST
$conn = get_db_connection();

$order_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : 0;
$error = '';

if (!$order_id) {
    header("Location: orders.php");
    exit();
}

// Fetch order details
$sql = "SELECT o.*, u.name as user_name, u.email as user_email, o.shipping_address_json 
        FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: orders.php?error=not_found");
    exit();
}

// Fetch order items
$sql = "SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result();
$stmt->close();

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $status = htmlspecialchars($_POST['status']); // Sanitize status
        
        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $order_id);
        if ($stmt->execute()) {
            header("Location: view_order.php?id=" . $order_id . "&success=status_updated");
            exit();
        } else {
            $error = "Error updating record: " . $conn->error;
        }
        $stmt->close();
    }
}

$csrf_token = generate_csrf_token();
$active_page = 'orders';
$page_title = 'Order Details #ML-' . $order_id;
?>
<?php include '../includes/admin_page_header.php'; ?>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php
    include '../includes/admin_header.php'; 
    ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card p-4 mb-4">
                <h5 class="gold-text">Order Items</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $subtotal = 0; ?>
                            <?php while($item = $order_items->fetch_assoc()): ?>
                                <?php $subtotal += $item['price'] * $item['quantity']; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>R <?php echo number_format($item['price'], 2); ?></td>
                                    <td>R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">Subtotal:</td>
                                <td>R <?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                            <?php if (isset($order['discount_code']) && !empty($order['discount_code'])): ?>
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">Shipping:</td>
                                <td>R <?php echo number_format(SHIPPING_COST, 2); ?></td>
                            </tr>
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">Discount (<?php echo htmlspecialchars($order['discount_code']); ?>):</td>
                                <td>- R <?php echo number_format($order['discount_amount'], 2); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="fw-bold table-light">
                                <td colspan="3" class="text-end gold-text">Total:</td>
                                <td class="gold-text">R <?php echo number_format($order['total_price'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table> 
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 mb-4">
                <h5 class="gold-text">Shipping Address</h5>
                <?php $shipping_address = json_decode($order['shipping_address_json'], true); ?>
                <?php if ($shipping_address): ?>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($shipping_address['firstName'] . ' ' . $shipping_address['lastName']); ?></strong></p>
                    <p class="mb-1"><?php echo htmlspecialchars($shipping_address['address']); ?></p>
                    <?php if(!empty($shipping_address['address2'])): ?><p class="mb-1"><?php echo htmlspecialchars($shipping_address['address2']); ?></p><?php endif; ?>
                    <p class="mb-1"><?php echo htmlspecialchars($shipping_address['city'] . ', ' . $shipping_address['province'] . ', ' . $shipping_address['zip']); ?></p>
                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($shipping_address['email']); ?></p>
                <?php else: ?>
                    <p class="text-muted">No shipping address provided.</p>
                <?php endif; ?>
            </div>
            <div class="card p-4 mb-4">
                <h5 class="gold-text">Customer Details</h5>
                <p class="mb-1"><strong>Customer:</strong> <?php echo htmlspecialchars($order['user_name'] ?? 'Guest'); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['user_email'] ?? 'N/A'); ?></p>
                <p class="mb-1"><strong>Date:</strong> <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
            </div>
            <div class="card p-4">
                <h5 class="gold-text">Update Status</h5>
                <?php if(!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <form action="view_order.php?id=<?php echo $order_id; ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Pending" <?php if($order['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                            <option value="Paid" <?php if($order['status'] == 'Paid') echo 'selected'; ?>>Paid</option>
                            <option value="Processing" <?php if($order['status'] == 'Processing') echo 'selected'; ?>>Processing</option>
                            <option value="Shipped" <?php if($order['status'] == 'Shipped') echo 'selected'; ?>>Shipped</option>
                            <option value="Delivered" <?php if($order['status'] == 'Delivered') echo 'selected'; ?>>Delivered</option>
                            <option value="Cancelled" <?php if($order['status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                            <option value="Failed" <?php if($order['status'] == 'Failed') echo 'selected'; ?>>Failed</option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary-dark w-100">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
