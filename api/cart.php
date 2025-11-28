<?php
// Prevent any output before JSON
ob_start();

require_once __DIR__ . '/../includes/bootstrap.php';

// Clear any accidental output
ob_end_clean();

header('Content-Type: application/json');

// CORS Configuration - Restrict in production
if (defined('APP_ENV') && APP_ENV === 'production') {
    // Production: Only allow requests from your domain
    $allowed_origin = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://mosseluxe.co.za';
    header('Access-Control-Allow-Origin: ' . $allowed_origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // Development: Allow all origins for testing
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$conn = get_db_connection();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Parse the request path to get the action
$script_name = $_SERVER['SCRIPT_NAME'];
$path_info = str_replace(dirname($script_name), '', $request_uri);
$path_parts = explode('/', trim($path_info, '/'));
array_shift($path_parts); // remove 'api'

$action = isset($path_parts[1]) ? $path_parts[1] : null;

try {
    switch ($method) {
        case 'GET':
            if ($action === 'count') {
                get_cart_count($conn);
            } else {
                get_cart($conn);
            }
            break;
        case 'POST':
            if ($action === 'add') {
                add_to_cart($conn);
            } elseif ($action === 'update') {
                update_cart_item($conn);
            } elseif ($action === 'remove') {
                remove_from_cart($conn);
            } elseif ($action === 'clear') {
                clear_cart($conn);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('API Cart Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

$conn->close();

function ensure_cart_session() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function validate_csrf() {
    $csrf_token = $_POST['csrf_token'] ?? '';
    return verify_csrf_token($csrf_token);
}

function count_cart_items($cart) {
    $count = 0;
    foreach ($cart as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

function calculate_cart_totals($cart) {
    $subtotal = 0;
    $tax_rate = 0.15; // Example tax rate
    $shipping_cost = defined('SHIPPING_COST') ? SHIPPING_COST : 100.00;

    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    $tax = $subtotal * $tax_rate;
    $total = $subtotal + $tax + $shipping_cost;

    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'shipping' => $shipping_cost,
        'total' => $total
    ];
}

function get_cart_count($conn) {
    ensure_cart_session();
    $cart = $_SESSION['cart'];

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'cart_count' => count_cart_items($cart),
        'message' => 'Cart count retrieved successfully'
    ]);
}

function get_cart($conn) {
    ensure_cart_session();
    $cart = $_SESSION['cart'];

    $cart_data = [];
    foreach ($cart as $product_id => $item) {
        $cart_data[] = [
            'product_id' => (int)$product_id,
            'name' => $item['name'],
            'price' => (float)$item['price'],
            'quantity' => (int)$item['quantity'],
            'image' => $item['image'],
            'total' => (float)($item['price'] * $item['quantity'])
        ];
    }

    $totals = calculate_cart_totals($cart);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'cart_data' => $cart_data,
        'totals' => array_map(function($val) { return (float)$val; }, $totals),
        'cart_count' => count_cart_items($cart),
        'formatted_totals' => [
            'subtotal' => number_format($totals['subtotal'], 2),
            'tax' => number_format($totals['tax'], 2),
            'shipping' => number_format($totals['shipping'], 2),
            'total' => number_format($totals['total'], 2)
        ],
        'message' => 'Cart retrieved successfully'
    ]);
}

function add_to_cart($conn) {
    if (!validate_csrf()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        return;
    }

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id <= 0 || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
        return;
    }

    // Validate product exists and is available
    $stmt = $conn->prepare("SELECT id, name, price, sale_price, stock, status, image FROM products WHERE id = ? AND status = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        return;
    }

    $product = $result->fetch_assoc();
    $stmt->close();

    if ($product['stock'] <= 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Product is out of stock']);
        return;
    }

    ensure_cart_session();
    $current_quantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
    $new_total_quantity = $current_quantity + $quantity;

    // Check stock limits
    if ($new_total_quantity > $product['stock']) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "Only {$product['stock']} items available in stock"]);
        return;
    }

    // Add/update cart item
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] = $new_total_quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'price' => $product['sale_price'] > 0 ? (float)$product['sale_price'] : (float)$product['price'],
            'image' => SITE_URL . $product['image'],
            'quantity' => $quantity,
            'added_at' => time()
        ];
    }

    // Persist to user cart if logged in
    if (isset($_SESSION['user_id'])) {
        save_user_cart($conn, $_SESSION['cart']);
    }

    $totals = calculate_cart_totals($_SESSION['cart']);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "{$product['name']} added to cart successfully",
        'cart_count' => count_cart_items($_SESSION['cart']),
        'new_subtotal' => number_format($totals['subtotal'], 2),
        'new_total' => number_format($totals['total'], 2),
        'product_name' => $product['name']
    ]);
}

function update_cart_item($conn) {
    if (!validate_csrf()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        return;
    }

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        return;
    }

    ensure_cart_session();

    if (!isset($_SESSION['cart'][$product_id])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found in cart']);
        return;
    }

    if ($quantity <= 0) {
        // Remove item if quantity is 0
        return remove_from_cart($conn);
    }

    // Validate stock
    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if ($quantity > $product['stock']) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "Only {$product['stock']} items available in stock"]);
        return;
    }

    $_SESSION['cart'][$product_id]['quantity'] = $quantity;

    // Persist changes
    if (isset($_SESSION['user_id'])) {
        save_user_cart($conn, $_SESSION['cart']);
    }

    $totals = calculate_cart_totals($_SESSION['cart']);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully',
        'cart_count' => count_cart_items($_SESSION['cart']),
        'new_subtotal' => number_format($totals['subtotal'], 2),
        'new_total' => number_format($totals['total'], 2)
    ]);
}

function remove_from_cart($conn) {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        return;
    }

    ensure_cart_session();

    if (!isset($_SESSION['cart'][$product_id])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found in cart']);
        return;
    }

    $product_name = $_SESSION['cart'][$product_id]['name'];
    unset($_SESSION['cart'][$product_id]);

    // Clear from user cart if logged in
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("DELETE FROM user_carts WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
        $stmt->execute();
        $stmt->close();
    }

    $totals = calculate_cart_totals($_SESSION['cart']);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "{$product_name} removed from cart successfully",
        'cart_count' => count_cart_items($_SESSION['cart']),
        'new_subtotal' => number_format($totals['subtotal'], 2),
        'new_total' => number_format($totals['total'], 2)
    ]);
}

function clear_cart($conn) {
    if (!validate_csrf()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        return;
    }

    $_SESSION['cart'] = [];

    // Clear user cart from database if logged in
    if (isset($_SESSION['user_id'])) {
        $conn->query("DELETE FROM user_carts WHERE user_id = " . (int)$_SESSION['user_id']);
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Cart cleared successfully',
        'cart_count' => 0,
        'new_subtotal' => '0.00',
        'new_total' => '0.00'
    ]);
}

function save_user_cart($conn, $cart_data) {
    if (!isset($_SESSION['user_id']) || empty($cart_data)) {
        return;
    }

    $user_id = $_SESSION['user_id'];

    // Clear existing cart items
    $conn->query("DELETE FROM user_carts WHERE user_id = $user_id");

    // Insert current cart items
    $stmt = $conn->prepare("INSERT INTO user_carts (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)");
    foreach ($cart_data as $product_id => $item) {
        $stmt->bind_param("iii", $user_id, $product_id, $item['quantity']);
        $stmt->execute();
    }
    $stmt->close();
}
?>
