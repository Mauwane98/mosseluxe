<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db_connect.php';
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = get_db_connection();

    // 1. Receive data from checkout.php
    $shipping_name = filter_var($_POST['shipping_name'], FILTER_SANITIZE_STRING);
    $shipping_email = filter_var($_POST['shipping_email'], FILTER_SANITIZE_EMAIL);
    $shipping_address = filter_var($_POST['shipping_address'], FILTER_SANITIZE_STRING);
    $shipping_city = filter_var($_POST['shipping_city'], FILTER_SANITIZE_STRING);
    $shipping_zip = filter_var($_POST['shipping_zip'], FILTER_SANITIZE_STRING);
    $shipping_phone = filter_var($_POST['shipping_phone'], FILTER_SANITIZE_STRING);

    $subtotal = filter_var($_POST['subtotal'], FILTER_VALIDATE_FLOAT);
    $shipping_cost = filter_var($_POST['shipping_cost'], FILTER_VALIDATE_FLOAT);
    $total = filter_var($_POST['total'], FILTER_VALIDATE_FLOAT);
    $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT) ?: null;

    // Guest checkout support - no user_id required
    $is_guest_checkout = empty($user_id);

    // Basic validation
    if (empty($shipping_name) || empty($shipping_email) || empty($shipping_address) || empty($shipping_city) || empty($shipping_zip) || empty($shipping_phone) || $total <= 0) {
        $_SESSION['error_message'] = "Please fill in all required shipping details and ensure your cart is not empty.";
        header("Location: checkout.php");
        exit();
    }

    // Store shipping details in a JSON format
    $shipping_address_json = json_encode([
        'name' => $shipping_name,
        'email' => $shipping_email,
        'address' => $shipping_address,
        'city' => $shipping_city,
        'zip' => $shipping_zip,
        'phone' => $shipping_phone
    ]);

    // 2. Create a pending order in the database
    $order_status = 'Pending'; // Initial status
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address_json) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $user_id, $total, $order_status, $shipping_address_json);

    if ($stmt->execute()) {
        $order_id = $conn->insert_id;

        // Insert order items
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt_item->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
            $stmt_item->execute();
            $stmt_item->close();
        }

        // Clear the cart after creating the order
        unset($_SESSION['cart']);

        // 3. Generating PayFast parameters
        $pf_data = array(
            // Merchant details
            'merchant_id' => PAYFAST_MERCHANT_ID,
            'merchant_key' => PAYFAST_MERCHANT_KEY,
            'return_url' => SITE_URL . 'order_success.php?order_id=' . $order_id, // Define SITE_URL in config.php
            'cancel_url' => SITE_URL . 'checkout.php?status=cancelled',
            'notify_url' => SITE_URL . 'payfast_notify.php',

            // Buyer details
            'name_first' => explode(' ', $shipping_name)[0] ?? '',
            'name_last' => explode(' ', $shipping_name)[1] ?? '',
            'email_address' => $shipping_email,
            'cell_number' => $shipping_phone,

            // Transaction details
            'm_payment_id' => $order_id, // Unique order ID
            'amount' => number_format(sprintf('%.2f', $total), 2, '.', ''),
            'item_name' => 'Mossé Luxe Order #' . $order_id,
            'item_description' => 'Online purchase from Mossé Luxe',
            'custom_int1' => $user_id, // Custom integer field for user_id
            'custom_str1' => $shipping_address_json, // Custom string field for shipping details
        );

        // Construct the POST string for PayFast
        $pf_param_string = '';
        foreach ($pf_data as $key => $val) {
            $pf_param_string .= $key . '=' . urlencode($val) . '&';
        }
        // Remove the last '&'
        $pf_param_string = rtrim($pf_param_string, '&');

        // 4. Redirect to PayFast
        header('Location: ' . PAYFAST_URL . '?' . $pf_param_string);
        exit();

    } else {
        $_SESSION['error_message'] = "Failed to create order. Please try again.";
        header("Location: checkout.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: checkout.php");
    exit();
}
?>
