<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/abandoned_cart_functions.php';

// Set headers only when called via HTTP request
if (isset($_SERVER['REQUEST_METHOD'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Error reporting is handled in bootstrap.php based on APP_ENV

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$conn = get_db_connection();

// Only process requests when called via HTTP, not when included in other scripts
if (!isset($_SERVER['REQUEST_METHOD'])) {
    // If no request method, just exit silently (for debugging scripts)
    return;
}

$response = [
    'success' => false,
    'message' => 'Invalid request.',
    'cart_count' => countCartItems($_SESSION['cart']),
    'new_subtotal' => 0,
    'new_total' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = trim($_POST['action']);
    $product_id = isset($_POST['product_id']) ? (int)filter_var($_POST['product_id'], FILTER_VALIDATE_INT) : 0;
    $quantity = isset($_POST['quantity']) ? (int)filter_var($_POST['quantity'], FILTER_VALIDATE_INT) : 0;
    $csrf_token = $_POST['csrf_token'] ?? '';

    if ($action !== 'get_count' && (!isset($csrf_token) || !verify_csrf_token($csrf_token))) {
        error_log("CSRF Token Check Failed - Action: $action, Token: " . substr($csrf_token, 0, 10) . "...");
        $response['message'] = 'Security token invalid. Please refresh the page and try again.';
        echo json_encode($response);
        exit;
    }

    try {
        switch ($action) {
            case 'add':
                $result = addToCart($conn, $product_id, $quantity);
                if ($result['success']) {
                    $response = array_merge($response, $result);
                } else {
                    $response = $result;
                }
                break;

            case 'update':
                $result = updateCartItem($conn, $product_id, $quantity);
                if ($result['success']) {
                    $response = array_merge($response, $result);
                } else {
                    $response = $result;
                }
                break;

            case 'remove':
                $result = removeFromCart($conn, $product_id);
                if ($result['success']) {
                    $response = array_merge($response, $result);
                } else {
                    $response = $result;
                }
                break;

            case 'clear':
                $result = clearCart($conn);
                if ($result['success']) {
                    $response = array_merge($response, $result);
                } else {
                    $response = $result;
                }
                break;

            case 'get_count':
                $response = [
                    'success' => true,
                    'cart_count' => countCartItems($_SESSION['cart']),
                    'message' => 'Cart count retrieved successfully.'
                ];
                break;

            case 'get_cart':
                $cart_data = getCartData($_SESSION['cart']);
                $totals = calculateCartTotals($_SESSION['cart']);
                $response = [
                    'success' => true,
                    'cart_data' => $cart_data,
                    'totals' => $totals,
                    'cart_count' => countCartItems($_SESSION['cart']),
                    'message' => 'Cart data retrieved successfully.'
                ];
                break;

            case 'apply_coupon':
                $coupon_code = trim($_POST['coupon_code'] ?? '');
                $result = applyCoupon($conn, $coupon_code);
                $response = $result;
                break;

            case 'remove_coupon':
                $result = removeCoupon();
                $response = $result;
                break;

            default:
                $response['message'] = 'Unknown action requested.';
                break;
        }
    } catch (Exception $e) {
        error_log('Cart AJAX Error: ' . $e->getMessage());
        $response = [
            'success' => false,
            'message' => 'An error occurred processing your request. Please try again.',
            'cart_count' => countCartItems($_SESSION['cart'])
        ];
    }
}

echo json_encode($response);

// Note: All cart helper functions (countCartItems, validateProduct, addToCart, etc.)
// are defined in includes/cart_functions.php which is loaded via bootstrap.php

// Sync user cart on login if needed
if (isset($_SESSION['user_id'])) {
    syncUserCartOnLogin($conn, $_SESSION['user_id']);
}

// Track abandoned cart after any cart operation
if (!empty($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    $email = $_SESSION['user_email'] ?? null;
    saveAbandonedCart($conn, $_SESSION['cart'], $email);
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    // Only close if this is the main script being executed
    $conn->close();
}
// No closing PHP tag - prevents accidental whitespace output
