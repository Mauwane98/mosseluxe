<?php
$pageTitle = "Order Details - Mossé Luxe";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If user is not logged in, redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'includes/db_connect.php';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$order_id = filter_var($_GET['id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
$order_details = null;
$order_items = [];
$shipping_address = null;

if ($order_id) {
    $conn = get_db_connection();

    // Fetch order details, ensuring it belongs to the logged-in user
    $stmt = $conn->prepare("SELECT id, total_price, status, created_at, shipping_address_json FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc();
        $shipping_address = json_decode($order_details['shipping_address_json'], true);
    }
    $stmt->close();

    // Fetch order items
    if ($order_details) {
        $stmt_items = $conn->prepare("SELECT oi.quantity, oi.price, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while ($row = $result_items->fetch_assoc()) {
            $order_items[] = $row;
        }
        $stmt_items->close();
    }

    $conn->close();
}
?>

<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Order Details</h1>
            <p class="mt-4 text-lg text-black/70">Order #<?php echo htmlspecialchars($order_id); ?></p>
        </div>

        <?php if ($order_details): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Order Information -->
                <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold mb-4">Order Information</h2>
                    <div class="mb-4">
                        <p class="text-black/70"><strong>Order ID:</strong> #ML-<?php echo htmlspecialchars($order_details['id']); ?></p>
                        <p class="text-black/70"><strong>Order Date:</strong> <?php echo date('d M Y, H:i', strtotime($order_details['created_at'])); ?></p>
                        <p class="text-black/70"><strong>Total Amount:</strong> R <?php echo number_format($order_details['total_price'], 2); ?></p>
                        <p class="text-black/70"><strong>Status:</strong> 
                            <?php 
                                $status_class = '';
                                switch (strtolower($order_details['status'])) {
                                    case 'pending': $status_class = 'bg-yellow-100 text-yellow-800'; break;
                                    case 'paid': $status_class = 'bg-green-100 text-green-800'; break;
                                    case 'processing': $status_class = 'bg-blue-100 text-blue-800'; break;
                                    case 'shipped': $status_class = 'bg-cyan-100 text-cyan-800'; break;
                                    case 'delivered': $status_class = 'bg-gray-200 text-gray-800'; break;
                                    case 'cancelled': case 'failed': $status_class = 'bg-red-100 text-red-800'; break;
                                    default: $status_class = 'bg-gray-200 text-gray-800';
                                }
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>"><?php echo htmlspecialchars($order_details['status']); ?></span>
                        </p>
                    </div>

                    <h3 class="text-xl font-bold mt-6 mb-3">Shipping Address</h3>
                    <?php if ($shipping_address): ?>
                        <p class="text-black/70"><?php echo htmlspecialchars($shipping_address['name']); ?></p>
                        <p class="text-black/70"><?php echo htmlspecialchars($shipping_address['address']); ?></p>
                        <p class="text-black/70"><?php echo htmlspecialchars($shipping_address['city']); ?>, <?php echo htmlspecialchars($shipping_address['zip']); ?></p>
                        <p class="text-black/70">Phone: <?php echo htmlspecialchars($shipping_address['phone']); ?></p>
                        <p class="text-black/70">Email: <?php echo htmlspecialchars($shipping_address['email']); ?></p>
                    <?php else: ?>
                        <p class="text-black/70">Shipping address not available.</p>
                    <?php endif; ?>

                    <h3 class="text-xl font-bold mt-6 mb-3">Items Ordered</h3>
                    <div class="bg-neutral-50 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-black/5 text-xs uppercase">
                                    <tr>
                                        <th class="px-6 py-3">Product</th>
                                        <th class="px-6 py-3">Image</th>
                                        <th class="px-6 py-3">Quantity</th>
                                        <th class="px-6 py-3">Price</th>
                                        <th class="px-6 py-3">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($order_items)): ?>
                                        <?php foreach ($order_items as $item): ?>
                                            <tr class="border-b border-black/5">
                                                <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td class="px-6 py-4">
                                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="h-10 w-10 object-cover rounded">
                                                </td>
                                                <td class="px-6 py-4"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                                <td class="px-6 py-4">R <?php echo number_format($item['price'], 2); ?></td>
                                                <td class="px-6 py-4">R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-black/60 py-8">No items found for this order.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sidebar / Back to Account -->
                <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-fit">
                    <h2 class="text-2xl font-bold mb-4">Account Navigation</h2>
                    <nav class="flex flex-col space-y-1">
                        <a href="my_account.php?view=orders" class="px-4 py-2 rounded-md text-sm font-semibold hover:bg-black/5">Back to Order History</a>
                        <a href="my_account.php?view=profile" class="px-4 py-2 rounded-md text-sm font-semibold hover:bg-black/5">Profile Details</a>
                        <a href="wishlist.php" class="px-4 py-2 rounded-md text-sm font-semibold hover:bg-black/5">My Wishlist</a>
                        <a href="logout.php" class="px-4 py-2 rounded-md text-sm font-semibold hover:bg-black/5">Logout</a>
                    </nav>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center">
                <h2 class="text-2xl font-bold text-red-600 mb-4">Order Not Found or Access Denied</h2>
                <p class="text-lg text-black/70">The order you are looking for does not exist or you do not have permission to view it.</p>
                <div class="mt-8">
                    <a href="my_account.php?view=orders" class="text-lg font-semibold text-black border-b-2 border-black hover:border-transparent transition-colors">
                        Back to Order History
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>