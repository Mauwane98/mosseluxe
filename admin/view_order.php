<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';

// Ensure admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once '../includes/csrf.php';
require_once '../includes/config.php'; // For SHIPPING_COST
$conn = get_db_connection();

$order_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) : 0;
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
        $status = trim($_POST['status']); // Trim whitespace
        $allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
        if (!in_array($status, $allowed_statuses)) {
            $error = 'Invalid order status provided.';
        } else {
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
}

$csrf_token = generate_csrf_token();
$pageTitle = 'Order Details #ML-' . $order_id;
include 'header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Order Items -->
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Order Items</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php $subtotal = 0; ?>
                    <?php while($item = $order_items->fetch_assoc()): ?>
                        <?php $subtotal += $item['price'] * $item['quantity']; ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $item['quantity']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">R<?php echo number_format($item['price'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-500">Subtotal:</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">R<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <?php if (isset($order['discount_code']) && !empty($order['discount_code'])): ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-500">Shipping:</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">R<?php echo number_format(SHIPPING_COST, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-500">Discount (<?php echo htmlspecialchars($order['discount_code']); ?>):</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600">-R<?php echo number_format($order['discount_amount'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="border-t-2 border-gray-300">
                        <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-900">Total:</td>
                        <td class="px-6 py-4 whitespace-nowrap text-lg font-bold text-gray-900">R<?php echo number_format($order['total_price'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Order Details Sidebar -->
    <div class="space-y-6">
        <!-- Shipping Address -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Shipping Address</h3>
            <?php $shipping_address = json_decode($order['shipping_address_json'], true); ?>
            <?php if ($shipping_address): ?>
                <div class="space-y-2">
                    <p class="text-sm font-semibold"><?php echo htmlspecialchars($shipping_address['firstName'] . ' ' . $shipping_address['lastName']); ?></p>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($shipping_address['address']); ?></p>
                    <?php if(!empty($shipping_address['address2'])): ?>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($shipping_address['address2']); ?></p>
                    <?php endif; ?>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($shipping_address['city'] . ', ' . $shipping_address['province'] . ', ' . $shipping_address['zip']); ?></p>
                    <p class="text-sm text-gray-600"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($shipping_address['email']); ?></p>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-500">No shipping address provided.</p>
            <?php endif; ?>
        </div>

        <!-- Customer Details -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Customer Details</h3>
            <div class="space-y-2">
                <p class="text-sm"><span class="font-medium">Customer:</span> <?php echo htmlspecialchars($order['user_name'] ?? 'Guest'); ?></p>
                <p class="text-sm"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($order['user_email'] ?? 'N/A'); ?></p>
                <p class="text-sm"><span class="font-medium">Date:</span> <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
            </div>
        </div>

        <!-- Update Status -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Update Status</h3>
            <?php if(!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form action="view_order.php?id=<?php echo $order_id; ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                        <option value="pending" <?php if($order['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="processing" <?php if($order['status'] == 'processing') echo 'selected'; ?>>Processing</option>
                        <option value="shipped" <?php if($order['status'] == 'shipped') echo 'selected'; ?>>Shipped</option>
                        <option value="completed" <?php if($order['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                        <option value="cancelled" <?php if($order['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" name="update_status" class="w-full bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Update Status</button>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
