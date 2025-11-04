<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once 'includes/db_connect.php';
require_once 'includes/config.php';
$conn = get_db_connection();

// --- 1. Initialize PayFast Variables from Config ---
$payfast_url = PAYFAST_URL;
$merchant_id = PAYFAST_MERCHANT_ID;
$merchant_key = PAYFAST_MERCHANT_KEY;
$passphrase = PAYFAST_PASSPHRASE;

// --- 2. Get Order Details ---
$order_id = isset($_GET['order_id']) ? filter_var(trim($_GET['order_id']), FILTER_SANITIZE_NUMBER_INT) : null;

if (!$order_id) {
    die("No order ID specified.");
}

// Fetch order details from the database
$sql_order = "SELECT * FROM orders WHERE id = ?";
if ($stmt_order = $conn->prepare($sql_order)) {
    $stmt_order->bind_param("i", $param_order_id);
    $param_order_id = $order_id;
    if ($stmt_order->execute()) {
        $result_order = $stmt_order->get_result();
        if ($order = $result_order->fetch_assoc()) {
            // Order found
        } else {
            die("Order not found.");
        }
    }
    $stmt_order->close();
} else {
    die("Database error.");
}

// --- 3. Prepare Data for PayFast ---
$data = [
    'merchant_id' => $merchant_id,
    'merchant_key' => $merchant_key,
    'return_url' => SITE_URL . 'order_success.php?order_id=' . $order_id,
    'cancel_url' => SITE_URL . 'checkout.php',
    'notify_url' => SITE_URL . 'payfast_notify.php',

    'm_payment_id' => $order_id,
    'amount' => number_format($order['total_price'], 2, '.', ''),
    'item_name' => 'Order #' . $order_id,
    'item_description' => 'Mossé Luxe Online Store Purchase',
];

// --- 4. Generate Signature ---
$signature = '';
foreach ($data as $key => $val) {
    if ($val !== '') {
        $signature .= $key . '=' . urlencode(trim($val)) . '&';
    }
}
// Remove last '&'
$signature = substr($signature, 0, -1);
$signature = md5($signature . '&passphrase=' . urlencode(trim($passphrase)));
$data['signature'] = $signature;

// --- 5. Redirect to PayFast ---
// Build the query string
$pf_param_string = http_build_query($data);
header('Location: ' . $payfast_url . '?' . $pf_param_string);
exit();
?>
