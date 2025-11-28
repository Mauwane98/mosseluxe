<?php
$pageTitle = "Track Order - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';
$conn = get_db_connection();
require_once 'includes/header.php'; // Now include header after all PHP logic

$csrf_token = generate_csrf_token();
$error = '';
$order = null;
$order_items = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $order_id_input = trim($_POST['order_id']);
        $email_input = trim($_POST['email']);

        // Extract numeric part of order ID (e.g., from "ML-123")
        $order_id = filter_var($order_id_input, FILTER_SANITIZE_NUMBER_INT);
        $email = filter_var($email_input, FILTER_SANITIZE_EMAIL);

        if (empty($order_id) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid Order ID and Email Address.';
        } else {
            // Query to find the order. This is a bit complex because the email can be in the users table (for registered users)
            // or in the shipping_address_json (for guests).
            $sql = "SELECT o.*, u.email as user_email 
                    FROM orders o 
                    LEFT JOIN users u ON o.user_id = u.id 
                    WHERE o.id = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $order_data = $result->fetch_assoc();
                $stmt->close();

                $email_match = false;
                if ($order_data) {
                    // Check if the email matches the registered user's email
                    if (isset($order_data['user_email']) && strtolower($order_data['user_email']) === strtolower($email)) {
                        $email_match = true;
                    }
                    // If not, check the shipping address JSON for a guest email
                    elseif (isset($order_data['shipping_address_json'])) {
                        $shipping_info = json_decode($order_data['shipping_address_json'], true);
                        if (isset($shipping_info['email']) && strtolower($shipping_info['email']) === strtolower($email)) {
                            $email_match = true;
                        }
                    }
                }

                if ($email_match) {
                    $order = $order_data;
                    // Fetch order items
                    $sql_items = "SELECT oi.quantity, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
                    if ($stmt_items = $conn->prepare($sql_items)) {
                        $stmt_items->bind_param("i", $order_id);
                        $stmt_items->execute();
                        $result_items = $stmt_items->get_result();
                        while ($row = $result_items->fetch_assoc()) {
                            $order_items[] = $row;
                        }
                        $stmt_items->close();
                    }
                } else {
                    $error = "No order found matching the provided details. Please check your information and try again.";
                }
            } else {
                $error = "An error occurred. Please try again later.";
            }
        }
    }
}

?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 md:px-6 py-16 md:py-24">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Track Your Order</h1>
                <?php if (!$order): ?>
                <p class="mt-4 text-lg text-black/70">Enter your order details below to see its current status.</p>
                <?php endif; ?>
            </div>

            <div class="bg-neutral-50 p-8 rounded-lg">
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($order): ?>
                    <!-- Display Order Status with Visual Progress -->
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-4">Order <?php echo htmlspecialchars(get_order_id_from_numeric_id($order['id'])); ?></h3>

                    <!-- Order Progress Timeline -->
                    <?php
                        $status_steps = [
                            'pending' => ['text' => 'Order Received', 'icon' => '📦', 'completed' => true],
                            'paid' => ['text' => 'Payment Confirmed', 'icon' => '✅', 'completed' => in_array($order['status'], ['Paid', 'Processing', 'Shipped', 'Delivered', 'Completed'])],
                            'processing' => ['text' => 'Order Processing', 'icon' => '⚙️', 'completed' => in_array($order['status'], ['Processing', 'Shipped', 'Delivered', 'Completed'])],
                            'shipped' => ['text' => 'Shipped', 'icon' => '🚚', 'completed' => in_array($order['status'], ['Shipped', 'Delivered', 'Completed'])],
                            'delivered' => ['text' => 'Delivered', 'icon' => '📬', 'completed' => in_array($order['status'], ['Delivered', 'Completed'])]
                        ];

                        $current_step = 0;
                        switch(strtolower($order['status'])) {
                            case 'pending': $current_step = 0; break;
                            case 'paid': $current_step = 1; break;
                            case 'processing': $current_step = 2; break;
                            case 'shipped': $current_step = 3; break;
                            case 'delivered': case 'completed': $current_step = 4; break;
                        }
                    ?>
                    <div class="mb-8">
                        <h4 class="font-semibold mb-4">Order Progress</h4>
                        <div class="flex justify-between items-center">
                            <?php foreach($status_steps as $key => $step): ?>
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2 text-lg <?php echo $step['completed'] ? 'bg-black text-white' : 'bg-gray-100 text-gray-400'; ?>">
                                        <?php echo $step['icon']; ?>
                                    </div>
                                    <span class="text-xs font-medium <?php echo $step['completed'] ? 'text-black' : 'text-gray-500'; ?>">
                                        <?php echo $step['text']; ?>
                                    </span>
                                </div>
                                <?php if($key !== 'delivered'): ?>
                                    <div class="flex-1 h-0.5 mx-2 mt-[-20px] <?php echo $step['completed'] ? 'bg-black' : 'bg-gray-200'; ?>"></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Current Status Box -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6 mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl">
                                <?php
                                    switch(strtolower($order['status'])) {
                                        case 'pending': echo '⏳'; break;
                                        case 'paid': case 'processing': echo '⚙️'; break;
                                        case 'shipped': echo '🚚'; break;
                                        case 'delivered': case 'completed': echo '✅'; break;
                                        case 'cancelled': echo '❌'; break;
                                        default: echo '📋';
                                    }
                                ?>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-blue-800">Current Status</h4>
                                <p class="text-blue-700 font-semibold"><?php echo htmlspecialchars($order['status']); ?></p>
                                <p class="text-sm text-blue-600 mt-1">
                                    <?php
                                        switch(strtolower($order['status'])) {
                                            case 'pending': echo 'We have received your order and are preparing it for processing.'; break;
                                            case 'paid': echo 'Your payment has been confirmed and we are preparing your order.'; break;
                                            case 'processing': echo 'Your order is being processed and packed carefully.'; break;
                                            case 'shipped': echo 'Your order has been shipped and is on its way to you.'; break;
                                            case 'delivered': case 'completed': echo 'Your order has been successfully delivered.'; break;
                                            case 'cancelled': echo 'This order has been cancelled.'; break;
                                            default: echo 'Order status is being updated.';
                                        }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Order DetHails -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Order Information -->
                        <div class="bg-neutral-50 p-6 rounded-lg">
                            <h4 class="font-bold mb-4">Order Information</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Order Date:</span>
                                    <span class="font-medium"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Amount:</span>
                                    <span class="font-bold">R <?php echo number_format($order['total_price'], 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Items:</span>
                                    <span><?php echo count($order_items); ?> item<?php echo count($order_items) > 1 ? 's' : ''; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="bg-neutral-50 p-6 rounded-lg">
                            <h4 class="font-bold mb-4">Items Ordered</h4>
                            <div class="space-y-3">
                                <?php foreach($order_items as $item): ?>
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="w-10 h-10 object-cover rounded border" onerror="this.style.display='none'">
                                        <div class="flex-1">
                                            <div class="font-medium text-sm"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="text-xs text-gray-600">Qty: <?php echo $item['quantity']; ?> | R <?php echo number_format($item['price'], 2); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Estimated Delivery Times (if applicable) -->
                    <?php if (in_array($order['status'], ['Shipped', 'Processing'])): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
                            <div class="flex items-start gap-3">
                                <div class="text-yellow-600 text-lg">📅</div>
                                <div>
                                    <h4 class="font-semibold text-yellow-800">Estimated Delivery</h4>
                                    <p class="text-sm text-yellow-700 mt-1">
                                        <?php if ($order['status'] === 'Shipped'): ?>
                                            Your order is on its way! Estimated delivery: 1-3 business days.
                                        <?php else: ?>
                                            We're preparing your order for shipment. You'll receive tracking information soon.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Support Actions -->
                    <div class="flex flex-wrap gap-4 mt-8 pt-6 border-t border-gray-200">
                        <?php if (in_array($order['status'], ['Pending', 'Paid', 'Processing'])): ?>
                            <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm">
                                Cancel Order
                            </button>
                        <?php endif; ?>

                        <?php if (in_array($order['status'], ['Delivered', 'Completed'])): ?>
                            <a href="returns.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm">
                                Return/Exchange
                            </a>
                        <?php endif; ?>

                        <a href="contact.php" class="bg-neutral-100 text-black px-4 py-2 rounded-md hover:bg-neutral-200 text-sm">
                            Contact Support
                        </a>

                        <button onclick="printOrder(<?php echo $order['id']; ?>)" class="bg-neutral-100 text-black px-4 py-2 rounded-md hover:bg-neutral-200 text-sm">
                            Print Receipt
                        </button>
                    </div>
                    
                    <div class="border-t border-black/10 pt-4 mt-6">
                        <h5 class="font-semibold mb-2">Items:</h5>
                        <ul class="space-y-1 text-black/80">
                            <?php foreach($order_items as $item): ?>
                                <li><?php echo htmlspecialchars($item['product_name']); ?> (Qty: <?php echo $item['quantity']; ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <hr class="my-6 border-black/10">
                    <a href="track_order.php" class="w-full block text-center bg-black text-white py-3 px-6 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">Track Another Order</a>

                <?php else: ?>
                    <!-- Display Tracking Form -->
                    <form action="track_order.php" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div>
                            <label for="order_id" class="block text-sm font-medium text-black/80 mb-1">Order ID</label>
                            <input type="text" id="order_id" name="order_id" placeholder="e.g., ML-123" required value="<?php echo isset($_POST['order_id']) ? htmlspecialchars($_POST['order_id']) : ''; ?>" class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-black/80 mb-1">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="The email used for the order" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <button type="submit" class="w-full bg-black text-white py-3 px-6 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">
                            Track Order
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>

