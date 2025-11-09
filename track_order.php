<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db_connect.php';
require_once 'includes/csrf.php';
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
                    <!-- Display Order Status -->
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-2">Order #ML-<?php echo htmlspecialchars($order['id']); ?></h3>
                    <p class="text-black/60 mb-6">Here is the current status of your order.</p>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Status:</span>
                            <?php 
                                $status_class = '';
                                switch (strtolower($order['status'])) {
                                    case 'pending': $status_class = 'bg-yellow-100 text-yellow-800'; break;
                                    case 'paid': $status_class = 'bg-green-100 text-green-800'; break;
                                    case 'processing': $status_class = 'bg-blue-100 text-blue-800'; break;
                                    case 'shipped': $status_class = 'bg-cyan-100 text-cyan-800'; break;
                                    case 'delivered': $status_class = 'bg-gray-200 text-gray-800'; break;
                                    case 'cancelled': case 'failed': $status_class = 'bg-red-100 text-red-800'; break;
                                    default: $status_class = 'bg-gray-200 text-gray-800';
                                }
                            ?>
                            <span class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                        </div>
                        <div class="flex justify-between items-center border-t border-black/10 pt-4">
                            <span class="font-semibold">Order Date:</span>
                            <span><?php echo date('d M Y', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="flex justify-between items-center border-t border-black/10 pt-4">
                            <span class="font-semibold">Total:</span>
                            <span class="font-bold">R <?php echo number_format($order['total_price'], 2); ?></span>
                        </div>
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
</main>

<?php require_once 'includes/footer.php'; ?>