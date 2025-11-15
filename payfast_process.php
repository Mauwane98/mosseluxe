<?php
require_once __DIR__ . '/includes/bootstrap.php'; // This includes db_connect.php, config.php, and csrf.php
require_once __DIR__ . '/includes/notification_service.php'; // For email notifications

// Ensure SHIPPING_COST is defined
if (!defined('SHIPPING_COST')) {
    require_once __DIR__ . '/includes/config.php'; // Load config if not already loaded
}

// Validate CSRF token from checkout.php (token is in session from previous page)
// This script is reached via a GET redirect from checkout.php, so we check the session token.
// The token would have been generated on checkout.php and stored in $_SESSION['csrf_token'].
// We don't expect a POST token here, but we still need to verify the session's integrity.
// However, since checkout.php already verified the POST token and regenerated it,
// we just need to ensure $_SESSION['checkout_data'] exists and is valid.
// The actual CSRF check for the *form submission* happened on checkout.php.
// Here, we just need to ensure the session data is present.

if (!isset($_SESSION['checkout_data'])) {
    $_SESSION['toast_message'] = ['message' => 'Checkout data not found. Please start over.', 'type' => 'error'];
    header("Location: checkout.php");
    exit;
}

$conn = get_db_connection();

// Retrieve data from session
$checkout_data = $_SESSION['checkout_data'];

$user_id = $checkout_data['user_id'];
$cart_items = $checkout_data['cart_items'];
$subtotal = $checkout_data['subtotal'];
$shipping_cost = $checkout_data['shipping_cost'];
$total = $checkout_data['total'];
$shipping_info = $checkout_data['shipping_info'];

// Basic validation of retrieved data
if (empty($cart_items) || $total <= 0) {
    $_SESSION['toast_message'] = ['message' => "Your cart is empty or total is invalid. Please start over.", 'type' => 'error'];
    header("Location: checkout.php");
    exit();
}

// Start transaction
$conn->begin_transaction();
$transaction_successful = true;

try {
    // Store shipping details in a JSON format
    $shipping_address_json = json_encode($shipping_info);

    // Create a pending order in the database
    $order_status = 'Pending'; // Initial status
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address_json) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $user_id, $total, $order_status, $shipping_address_json);

    if (!$stmt->execute()) {
        throw new Exception("Failed to create order.");
    }
    $order_id = $conn->insert_id;
    $stmt->close();

    // Insert order items
    foreach ($cart_items as $product_id => $item) {
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt_item->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
        if (!$stmt_item->execute()) {
            throw new Exception("Failed to create order items.");
        }
        $stmt_item->close();
    }

    $conn->commit();

    // Send order confirmation and admin notification emails
    $orderDataForEmail = [
        'order_id' => $order_id,
        'items' => $cart_items,
        'subtotal' => $subtotal,
        'shipping_cost' => $shipping_cost,
        'total' => $checkout_data['final_total'] ?? $total, // Use discounted total if applicable
        'status' => $order_status,
        'user_id' => $user_id,
        'shipping_info' => $shipping_info
    ];

    $customerName = $shipping_info['firstName'] . ' ' . $shipping_info['lastName'];

    // Send confirmation email to customer
    $customerEmailSent = NotificationService::sendOrderConfirmationEmail($orderDataForEmail, $shipping_info['email'], $customerName);
    if ($customerEmailSent) {
        error_log("Order confirmation email sent successfully for order #$order_id");
    } else {
        error_log("Failed to send order confirmation email for order #$order_id");
        // Don't fail the order process for email issues
    }

    // Send notification email to admin
    $adminEmailSent = NotificationService::sendNewOrderNotification($orderDataForEmail);
    if ($adminEmailSent) {
        error_log("Admin notification email sent successfully for order #$order_id");
    } else {
        error_log("Failed to send admin notification email for order #$order_id");
        // Don't fail the order process for email issues
    }

    // Clear the cart and checkout data after creating the order
    unset($_SESSION['cart']);
    unset($_SESSION['checkout_data']);
    regenerate_csrf_token(); // Regenerate token after successful state change

    // Generating PayFast parameters
    $pf_data = array(
        // Merchant details
        'merchant_id' => PAYFAST_MERCHANT_ID,
        'merchant_key' => PAYFAST_MERCHANT_KEY,
        'return_url' => SITE_URL . 'order_success.php?order_id=' . $order_id, // Define SITE_URL in config.php
        'cancel_url' => SITE_URL . 'checkout.php?status=cancelled',
        'notify_url' => SITE_URL . 'payfast_notify.php',

        // Buyer details
        'name_first' => $shipping_info['firstName'],
        'name_last' => $shipping_info['lastName'],
        'email_address' => $shipping_info['email'],
        'cell_number' => $shipping_info['phone'],

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

    // Redirect to PayFast
    header('Location: ' . PAYFAST_URL . '?' . $pf_param_string);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['toast_message'] = ['message' => 'Order processing failed: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: checkout.php");
    exit();
}
?>
