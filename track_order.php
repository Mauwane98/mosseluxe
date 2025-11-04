<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/csrf.php';
$conn = get_db_connection();

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

<div class="container my-5 bg-white-section rounded shadow-sm">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <h1 class="display-4 mb-4">Track Your Order</h1>
            <p class="lead text-muted mb-5">Enter your order details below to see its current status.</p>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border p-4">
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($order): ?>
                        <!-- Display Order Status -->
                        <h3 class="text-dark">Order #ML-<?php echo htmlspecialchars($order['id']); ?></h3>
                        <p><strong>Status:</strong> 
                            <span class="badge 
                                <?php 
                                    $status_class = '';
                                    switch (strtolower($order['status'])) {
                                        case 'pending': $status_class = 'bg-warning text-dark'; break;
                                        case 'paid': $status_class = 'bg-success'; break;
                                        case 'processing': $status_class = 'bg-primary'; break;
                                        case 'shipped': $status_class = 'bg-info text-dark'; break;
                                        case 'delivered': $status_class = 'bg-secondary'; break;
                                        case 'cancelled': case 'failed': $status_class = 'bg-danger'; break;
                                        default: $status_class = 'bg-light text-dark';
                                    }
                                    echo $status_class;
                                ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </p>
                        <p><strong>Order Date:</strong> <?php echo date('d M Y', strtotime($order['created_at'])); ?></p>
                        <p><strong>Total:</strong> R <?php echo number_format($order['total_price'], 2); ?></p>
                        
                        <h5 class="mt-4">Items:</h5>
                        <ul class="list-unstyled text-dark">
                            <?php foreach($order_items as $item): ?>
                                <li><?php echo htmlspecialchars($item['product_name']); ?> (Qty: <?php echo $item['quantity']; ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                        <hr>
                        <a href="track_order.php" class="btn btn-outline-dark">Track another order</a>

                    <?php else: ?>
                        <!-- Display Tracking Form -->
                        <form action="track_order.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <div class="mb-3">
                                <label for="order_id" class="form-label text-dark">Order ID</label>
                                <input type="text" class="form-control" id="order_id" name="order_id" placeholder="e.g., ML-123" required value="<?php echo isset($_POST['order_id']) ? htmlspecialchars($_POST['order_id']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label text-dark">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="The email used for the order" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-outline-dark-alt">Track Order</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>