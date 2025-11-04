<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once 'includes/db_connect.php';
require_once 'includes/notification_service.php';
require_once 'includes/config.php';
$conn = get_db_connection();

// --- 1. Log Raw ITN Data for Debugging ---
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$log_file = 'payfast_itn_log.txt';
file_put_contents($log_file, "--- ITN Received ---\n" . $raw_post_data . "\n\n", FILE_APPEND);

// --- 2. Sanitize and Prepare ITN Data ---
$pf_data = [];
foreach ($raw_post_array as $keyval) {
    $keyval = explode('=', $keyval);
    if (count($keyval) == 2) {
        $pf_data[urldecode($keyval[0])] = urldecode($keyval[1]);
    }
}

// --- 3. Verify the ITN Request ---
$pf_host = PAYFAST_VALIDATE_URL; // Use the validation URL from config
$passphrase = PAYFAST_PASSPHRASE; // Your PayFast passphrase from config

// Create the signature string
$signature_string = '';
foreach ($pf_data as $key => $val) {
    if ($key !== 'signature') {
        $signature_string .= $key . '=' . urlencode(trim($val)) . '&';
    }
}
// Remove last '&'
$signature_string = substr($signature_string, 0, -1);
$signature = md5($signature_string . '&passphrase=' . urlencode(trim($passphrase)));

// Check if signatures match
if ($pf_data['signature'] !== $signature) {
    file_put_contents($log_file, "Signature mismatch.\n\n", FILE_APPEND);
    http_response_code(400); // Bad Request
    exit();
}

// --- 3.5. Verify Payment Amount ---
$order_id = $pf_data['m_payment_id'];
$amount_gross = $pf_data['amount_gross'];

// Fetch the order total from your database
$sql_get_order = "SELECT total_price FROM orders WHERE id = ?";
if ($stmt_get_order = $conn->prepare($sql_get_order)) {
    $stmt_get_order->bind_param("i", $order_id);
    $stmt_get_order->execute();
    $result_order = $stmt_get_order->get_result();
    $order_data = $result_order->fetch_assoc();
    $stmt_get_order->close();

    if (!$order_data) {
        file_put_contents($log_file, "Order ID " . $order_id . " not found in database.\n\n", FILE_APPEND);
        http_response_code(400);
        exit();
    }

    if (abs((float)$amount_gross - (float)$order_data['total_price']) > 0.01) { // Use a small tolerance for float comparison
        file_put_contents($log_file, "Amount mismatch for order " . $order_id . ". PayFast amount: " . $amount_gross . ", DB amount: " . $order_data['total_price'] . "\n\n", FILE_APPEND);
        http_response_code(400);
        exit();
    }
}

// --- 4. Update Order Status ---
$order_id = $pf_data['m_payment_id'];
$payment_status = $pf_data['payment_status'];
$pf_payment_id = $pf_data['pf_payment_id'];

if ($payment_status === 'COMPLETE') {
    $new_status = 'Paid';
} else {
    $new_status = 'Failed';
}

// --- 5. Transactional Update: Order Status and Stock Levels ---
$conn->begin_transaction();
try {
    // Update the order status in the database
    $sql_update_order = "UPDATE orders SET status = ?, pf_payment_id = ? WHERE id = ? AND status = 'Pending'"; // Only update if still pending
    if ($stmt_update = $conn->prepare($sql_update_order)) {
        $stmt_update->bind_param("ssi", $new_status, $pf_payment_id, $order_id);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        throw new Exception("Failed to prepare order update statement.");
    }

    // If payment was successful, reduce stock levels
    if ($new_status === 'Paid') {
        // Get order items
        $sql_items = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        
        while ($item = $result_items->fetch_assoc()) {
            // Update stock for each item
            $sql_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
            if (!$stmt_stock->execute()) {
                throw new Exception("Failed to update stock for product ID: " . $item['product_id']);
            }
            $stmt_stock->close();

            // Check for low stock and send notification if needed
            $sql_check_stock = "SELECT name, stock FROM products WHERE id = ?";
            $stmt_check_stock = $conn->prepare($sql_check_stock);
            $stmt_check_stock->bind_param("i", $item['product_id']);
            $stmt_check_stock->execute();
            $product_stock_data = $stmt_check_stock->get_result()->fetch_assoc();
            $stmt_check_stock->close();

            if ($product_stock_data && $product_stock_data['stock'] <= NotificationService::LOW_STOCK_THRESHOLD) {
                NotificationService::sendLowStockAlert(['id' => $item['product_id'], 'name' => $product_stock_data['name'], 'stock' => $product_stock_data['stock']]);
            }
        }
        $stmt_items->close();
    }

    // If all good, commit the transaction
    $conn->commit();
    file_put_contents($log_file, "Order " . $order_id . " successfully processed. Status: " . $new_status . "\n\n", FILE_APPEND);

} catch (Exception $e) {
    $conn->rollback();
    file_put_contents($log_file, "Transaction failed for order " . $order_id . ": " . $e->getMessage() . "\n\n", FILE_APPEND);
    http_response_code(500); // Internal Server Error
}

$conn->close();

// Respond with a 200 OK to PayFast
http_response_code(200);
?>
