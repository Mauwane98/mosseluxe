<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/notification_service.php';
require_once __DIR__ . '/includes/order_service.php';

if (!defined('SHIPPING_COST')) {
    require_once __DIR__ . '/includes/config.php';
}

function create_order_for_yoco($conn, $data) {
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

    if (empty($cart_items) || $final_total <= 0) {
        throw new Exception('Invalid cart data or total');
    }

    if (empty($shipping_info)) {
        throw new Exception('Shipping information is required');
    }

    $conn->begin_transaction();

    $shipping_address_json = json_encode($shipping_info);
    
    // Retry logic for order creation in case of duplicate order_id
    $max_attempts = 3;
    $attempt = 0;
    $order_created = false;
    $order_id = null;
    $formatted_order_id = null;

    while ($attempt < $max_attempts && !$order_created) {
        try {
            $formatted_order_id = generate_order_id();
            
            // Check stock for all items
            foreach ($cart_items as $product_id => $item) {
                $stmt_stock = $conn->prepare("SELECT stock FROM products WHERE id = ? AND status = 1 FOR UPDATE");
                $stmt_stock->bind_param("i", $product_id);
                $stmt_stock->execute();
                $result = $stmt_stock->get_result();
                $product = $result->fetch_assoc();
                $stmt_stock->close();

                if (!$product || $product['stock'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for one or more products.");
                }
            }

            // Insert order
            $order_status = 'Pending';
            $stmt = $conn->prepare("INSERT INTO orders (user_id, order_id, total_price, status, shipping_address_json) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdss", $user_id, $formatted_order_id, $final_total, $order_status, $shipping_address_json);

            if ($stmt->execute()) {
                $order_id = $conn->insert_id;
                $order_created = true;
            } else {
                // Check if it's a duplicate key error
                if ($stmt->errno == 1062) { // Duplicate entry error
                    $attempt++;
                    if ($attempt >= $max_attempts) {
                        throw new Exception("Unable to generate unique order ID after multiple attempts");
                    }
                    usleep(50000); // Wait 50ms before retry
                } else {
                    throw new Exception("Failed to create order: " . $stmt->error);
                }
            }
            $stmt->close();
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Insufficient stock') !== false) {
                throw $e; // Re-throw stock errors immediately
            }
            $attempt++;
            if ($attempt >= $max_attempts) {
                throw new Exception("Failed to create order after multiple attempts: " . $e->getMessage());
            }
        }
    }

    foreach ($cart_items as $product_id => $item) {
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt_item->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
        if (!$stmt_item->execute()) {
            throw new Exception("Failed to create order items");
        }
        $stmt_item->close();
    }

    if ($discount_data) {
        $stmt_discount = $conn->prepare("UPDATE discount_codes SET usage_count = usage_count + 1 WHERE id = ?");
        $stmt_discount->bind_param("i", $discount_data['id']);
        $stmt_discount->execute();
        $stmt_discount->close();
    }

    $conn->commit();

    $amount_in_cents = intval($final_total * 100);

    return [
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
    ];
}

// Handle direct AJAX request to this file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    header('Content-Type: application/json');
    $conn = get_db_connection();
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $result = create_order_for_yoco($conn, $data);
        echo json_encode($result);
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
}
?>
