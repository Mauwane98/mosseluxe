<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Custom logging function
function log_itn_message($message) {
    file_put_contents('payfast_itn.log', date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

log_itn_message("ITN received. Processing...");

// Variables for ITN data
$pfData = $_POST;
$pfParamString = '';

// 1. Receiving ITN data
if (empty($pfData)) {
    log_itn_message("No POST data received.");
    header("HTTP/1.0 400 Bad Request");
    exit();
}

// 2. Validating the ITN
// Remove 'signature' from the array
if (isset($pfData['signature'])) {
    unset($pfData['signature']);
}

// Sort the array by key
ksort($pfData);

// Reconstruct the parameter string
foreach ($pfData as $key => $val) {
    $pfParamString .= $key . '=' . urlencode($val) . '&';
}
$pfParamString = rtrim($pfParamString, '&');

// Generate security hash
$security_hash = md5($pfParamString);

// Validate the ITN against PayFast
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, PAYFAST_VALIDATE_URL);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $pfParamString);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    log_itn_message("cURL Error: " . $error);
    header("HTTP/1.0 500 Internal Server Error");
    exit();
}

if (strpos($response, 'VALID') === false) {
    log_itn_message("ITN Validation Failed: " . $response);
    header("HTTP/1.0 400 Bad Request");
    exit();
}

// Check if the security hash matches
if ($security_hash !== $_POST['signature']) {
    log_itn_message("Security Hash Mismatch. Received: " . $_POST['signature'] . ", Generated: " . $security_hash);
    header("HTTP/1.0 400 Bad Request");
    exit();
}

// All checks passed, ITN is valid
log_itn_message("ITN Validated Successfully. Transaction Status: " . $_POST['payment_status']);

$conn = get_db_connection();

$order_id = filter_var($_POST['m_payment_id'], FILTER_VALIDATE_INT);
$pf_payment_id = htmlspecialchars(trim($_POST['pf_payment_id']));
$payment_status = htmlspecialchars(trim($_POST['payment_status']));
$amount_paid = filter_var($_POST['amount_gross'], FILTER_VALIDATE_FLOAT);

// Fetch order details to compare total amount
$stmt_order = $conn->prepare("SELECT total_price, status FROM orders WHERE id = ?");
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();
$order = $result_order->fetch_assoc();
$stmt_order->close();

if (!$order) {
    log_itn_message("Order ID " . $order_id . " not found in database.");
    header("HTTP/1.0 404 Not Found");
    exit();
}

// Check if the amount paid matches the order total
if ($amount_paid != $order['total_price']) {
    log_itn_message("Amount mismatch for Order ID " . $order_id . ". Expected: " . $order['total_price'] . ", Received: " . $amount_paid);
    // This could be a fraud attempt or an error, handle accordingly
    header("HTTP/10 400 Bad Request");
    exit();
}

// 3. Updating the order status
$new_order_status = '';
if ($payment_status === 'COMPLETE') {
    $new_order_status = 'Completed';
} elseif ($payment_status === 'FAILED') {
    $new_order_status = 'Failed';
} elseif ($payment_status === 'PENDING') {
    $new_order_status = 'Pending'; // Still pending, but ITN received
} else {
    $new_order_status = 'Unknown';
}

// Only update if the status has changed or if it's a new ITN for a pending order
if ($order['status'] !== $new_order_status) {
    $stmt_update = $conn->prepare("UPDATE orders SET status = ?, pf_payment_id = ? WHERE id = ?");
    $stmt_update->bind_param("ssi", $new_order_status, $pf_payment_id, $order_id);
    $stmt_update->execute();
    $stmt_update->close();

    log_itn_message("Order ID " . $order_id . " status updated to " . $new_order_status);

    // 4. Handling stock and sending confirmation emails for successful payments
    if ($new_order_status === 'Completed') {
        $conn->begin_transaction();
        $stock_update_successful = true;

        // Reduce stock
        $stmt_items = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while ($item = $result_items->fetch_assoc()) {
            $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
            if (!$stmt_stock->execute()) {
                $stock_update_successful = false;
                break;
            }
            $stmt_stock->close();
        }
        $stmt_items->close();

        if ($stock_update_successful) {
            $conn->commit();
            log_itn_message("Stock reduced for Order ID " . $order_id);
        } else {
            $conn->rollback();
            log_itn_message("Failed to reduce stock for Order ID " . $order_id . ". Transaction rolled back.");
        }
    }
}

$conn->close();

// Respond with 200 OK to PayFast
header("HTTP/1.0 200 OK");
exit();

?>
