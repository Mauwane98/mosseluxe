<?php
require_once __DIR__ . '/includes/bootstrap.php'; // This includes db_connect.php, config.php, and csrf.php
require_once __DIR__ . '/includes/notification_service.php'; // For email notifications
require_once __DIR__ . '/includes/order_service.php'; // For order ID generation

// Ensure SHIPPING_COST is defined
if (!defined('SHIPPING_COST')) {
    require_once __DIR__ . '/includes/config.php'; // Load config if not already loaded
}

$conn = get_db_connection();

try {
    // Get checkout data from AJAX POST request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid request data');
    }

    $user_id = $data['user_id'] ?? null;
    $cart_items = $data['cart_items'] ?? [];
    $subtotal = $data['subtotal'] ?? 0;
    $shipping_cost = $data['shipping_cost'] ?? SHIPPING_COST;
    $total = $data['total'] ?? 0;
    $final_total = $data['final_total'] ?? $total;
    $shipping_info = $data['shipping_info'] ?? [];
    $discount_data = $data['discount_data'] ?? null;

    // Basic validation
    if (empty($cart_items) || $final_total <= 0) {
        throw new Exception('Invalid cart data or total');
    }

    if (empty($shipping_info)) {
        throw new Exception('Shipping information is required');
    }

    // Start transaction
    $conn->begin_transaction();
    $transaction_successful = true;

    // Store shipping details in JSON format
    $shipping_address_json = json_encode($shipping_info);

    // Generate proper order ID
    $formatted_order_id = generate_order_id();

    // Validate and reserve stock before creating order
    $stock_validation_passed = true;
    foreach ($cart_items as $product_id => $item) {
        $stmt_stock = $conn->prepare("SELECT stock FROM products WHERE id = ? AND status = 1 FOR UPDATE");
        $stmt_stock->bind_param("i", $product_id);
        $stmt_stock->execute();
        $result = $stmt_stock->get_result();
        $product = $result->fetch_assoc();
        $stmt_stock->close();

        if (!$product || $product['stock'] < $item['quantity']) {
            $stock_validation_passed = false;
            break;
        }
    }

    if (!$stock_validation_passed) {
        throw new Exception("Insufficient stock for one or more products. Please update your cart.");
    }

    // Create a pending order in the database
    $order_status = 'Pending';
    $stmt = $conn->prepare("INSERT INTO orders (order_id, total_price, status, shipping_address_json) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $formatted_order_id, $user_id, $final_total, $order_status, $shipping_address_json);

    if (!$stmt->execute()) {
        throw new Exception("Failed to create order");
    }
    $order_id = $conn->insert_id;
    $stmt->close();

    // Insert order items and decrement stock
    foreach ($cart_items as $product_id => $item) {
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt_item->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
        if (!$stmt_item->execute()) {
            throw new Exception("Failed to create order items");
        }
        $stmt_item->close();

        // Decrement stock
        $stmt_update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt_update_stock->bind_param("ii", $item['quantity'], $product_id);
        $stmt_update_stock->execute();
        $stmt_update_stock->close();
    }

    // Update discount usage if applicable
    if ($discount_data) {
        $stmt_discount = $conn->prepare("UPDATE discount_codes SET usage_count = usage_count + 1 WHERE id = ?");
        $stmt_discount->bind_param("i", $discount_data['id']);
        $stmt_discount->execute();
        $stmt_discount->close();
    }

    $conn->commit();

    // Return order data for Yoco payment
    // Yoco expects amount in cents (multiply by 100 for ZAR)
    $amount_in_cents = intval($final_total * 100);

    echo json_encode([
        'success' => true,
        'numeric_order_id' => $order_id,
        'formatted_order_id' => $formatted_order_id,
        'amount' => $amount_in_cents,
        'currency' => 'ZAR',
        'description' => 'Mossé Luxe Order #' . $formatted_order_id,
        'customer_email' => $shipping_info['email'],
        'customer_name' => $shipping_info['firstName'] . ' ' . $shipping_info['lastName'],
        'metadata' => [
            'order_id' => $formatted_order_id,
            'numeric_order_id' => $order_id,
            'customer_email' => $shipping_info['email']
        ]
    ]);

} catch (Exception $e) {
    if ($conn->connect_errno === null) {
        $conn->rollback();
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
