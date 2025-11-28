<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Verify Yoco webhook signature
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_YOCO_SIGNATURE'] ?? '';
$expected_signature = hash_hmac('sha256', $payload, YOCO_WEBHOOK_SECRET);

if (!hash_equals($expected_signature, $signature)) {
    http_response_code(401);
    echo 'Webhook verification failed';
    exit();
}

$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    echo 'Invalid JSON payload';
    exit();
}

// Log webhook data
$log_message = 'Yoco Webhook: ' . json_encode($data);
error_log($log_message);

$conn = get_db_connection();

try {
    $payment_id = $data['id'] ?? '';
    $status = $data['status'] ?? '';
    $amount = $data['amount'] ?? 0;
    $currency = $data['currency'] ?? '';
    $metadata = $data['metadata'] ?? [];

    if (isset($metadata['order_id'])) {
        $order_id = (int) $metadata['order_id'];
    } else {
        // If metadata doesn't have order_id, we can't process
        http_response_code(400);
        echo 'Order ID not found in metadata';
        exit();
    }

    // Fetch order details
    $stmt_order = $conn->prepare("SELECT total_price, status FROM orders WHERE id = ?");
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();
    $order = $result_order->fetch_assoc();
    $stmt_order->close();

    if (!$order) {
        http_response_code(404);
        echo 'Order not found';
        exit();
    }

    $conn->begin_transaction();

    // Update order status based on Yoco status
    $new_order_status = '';
    if ($status === 'successful') {
        $new_order_status = 'Completed';

        // Reduce stock for completed orders
        $stmt_items = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while ($item = $result_items->fetch_assoc()) {
            $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt_stock->execute();
            $stmt_stock->close();
        }
        $stmt_items->close();

        // Clear the user's cart
        $user_id_to_clear = $order_full['user_id'];
        if ($user_id_to_clear) {
            // Clear from database
            $stmt_clear_cart = $conn->prepare("DELETE FROM user_carts WHERE user_id = ?");
            $stmt_clear_cart->bind_param("i", $user_id_to_clear);
            $stmt_clear_cart->execute();
            $stmt_clear_cart->close();

            // If the session user matches, clear the session cart too
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id_to_clear) {
                unset($_SESSION['cart']);
            }
        }
        
        // Send confirmation emails for successful payments
        require_once __DIR__ . '/includes/notification_service.php';

        $stmt_order_full = $conn->prepare("
            SELECT o.*, GROUP_CONCAT(oi.product_id, ':', oi.quantity, ':', oi.price) as items_csv
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.id = ?
            GROUP BY o.id
        ");
        $stmt_order_full->bind_param("i", $order_id);
        $stmt_order_full->execute();
        $order_full = $stmt_order_full->get_result()->fetch_assoc();
        $stmt_order_full->close();

        // Parse items
        $items = [];
        if ($order_full['items_csv']) {
            $item_parts = explode(',', $order_full['items_csv']);
            foreach ($item_parts as $part) {
                list($product_id, $quantity, $price) = explode(':', $part);
                $items[$product_id] = [
                    'quantity' => $quantity,
                    'price' => $price,
                    'name' => 'Product',
                    'image' => ''
                ];
            }
        }

        $orderDataForEmail = [
            'order_id' => $order_id,
            'items' => $items,
            'subtotal' => $order_full['total_price'] - SHIPPING_COST,
            'shipping_cost' => SHIPPING_COST,
            'total' => $order_full['total_price'],
            'status' => $new_order_status,
            'user_id' => $order_full['user_id']
        ];

        if ($order_full['shipping_address_json']) {
            $shipping_info = json_decode($order_full['shipping_address_json'], true);
            $orderDataForEmail['shipping_info'] = $shipping_info;

            $customerEmailSent = NotificationService::sendOrderConfirmationEmail(
                $orderDataForEmail,
                $shipping_info['email'] ?? '',
                ($shipping_info['firstName'] ?? '') . ' ' . ($shipping_info['lastName'] ?? '')
            );

            if ($customerEmailSent) {
                error_log("Order confirmation email sent successfully for order #$order_id");
            }
        }

        // Send admin notification
        $adminEmailSent = NotificationService::sendNewOrderNotification($orderDataForEmail);
        if ($adminEmailSent) {
            error_log("Admin notification email sent successfully for order #$order_id");
        }

    } elseif ($status === 'failed' || $status === 'cancelled') {
        $new_order_status = 'Failed';
    } elseif ($status === 'pending') {
        $new_order_status = 'Pending';
    } else {
        $new_order_status = 'Unknown';
    }

    // Update order status and payment ID
    $stmt_update = $conn->prepare("UPDATE orders SET status = ?, pf_payment_id = ? WHERE id = ?");
    $stmt_update->bind_param("ssi", $new_order_status, $payment_id, $order_id);
    $stmt_update->execute();
    $stmt_update->close();

    $conn->commit();

    error_log("Order #$order_id updated to status: $new_order_status, Payment ID: $payment_id");

} catch (Exception $e) {
    if ($conn->connect_errno === null) {
        $conn->rollback();
    }

    error_log("Webhook processing error: " . $e->getMessage());
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

// Respond with success
http_response_code(200);
echo 'Webhook processed successfully';
?>
