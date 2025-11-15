<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

// Ensure SHIPPING_COST is defined
if (!defined('SHIPPING_COST')) {
    require_once __DIR__ . '/includes/config.php'; // Load config if not already loaded
}

// Helper function to calculate cart totals
function get_cart_totals() {
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $shipping_cost = SHIPPING_COST; // Assuming SHIPPING_COST is defined in config.php
    $total = $subtotal + $shipping_cost;
    return ['subtotal' => $subtotal, 'total' => $total];
}

// Helper function to save user cart to database
function save_user_cart($conn, $user_id, $cart_items) {
    if (empty($cart_items)) return;

    // Clear existing cart items for this user
    $stmt_clear = $conn->prepare("DELETE FROM user_carts WHERE user_id = ?");
    $stmt_clear->bind_param("i", $user_id);
    $stmt_clear->execute();
    $stmt_clear->close();

    // Insert current cart items
    $stmt_insert = $conn->prepare("INSERT INTO user_carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
    foreach ($cart_items as $product_id => $item) {
        $stmt_insert->bind_param("iii", $user_id, $product_id, $item['quantity']);
        $stmt_insert->execute();
    }
    $stmt_insert->close();
}

// Helper function to load user cart from database
function load_user_cart($conn, $user_id) {
    $cart_items = [];
    $stmt = $conn->prepare("
        SELECT uc.product_id, uc.quantity, p.name, p.price, p.sale_price, p.image
        FROM user_carts uc
        JOIN products p ON uc.product_id = p.id
        WHERE uc.user_id = ? AND p.status = 1
        ORDER BY uc.updated_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cart_items[$row['product_id']] = [
            'name' => $row['name'],
            'price' => $row['sale_price'] > 0 ? $row['sale_price'] : $row['price'],
            'image' => $row['image'],
            'quantity' => $row['quantity']
        ];
    }

    $stmt->close();
    return $cart_items;
}

// Helper function to merge guest cart with user cart
function merge_guest_cart_with_user_cart($guest_cart, $user_cart) {
    $merged_cart = $user_cart;

    foreach ($guest_cart as $product_id => $guest_item) {
        if (isset($merged_cart[$product_id])) {
            // Add quantities if product already in user cart
            $merged_cart[$product_id]['quantity'] += $guest_item['quantity'];
        } else {
            // Add new item from guest cart
            $merged_cart[$product_id] = $guest_item;
        }
    }

    return $merged_cart;
}

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $product_id = filter_var($_POST['product_id'] ?? 0, FILTER_VALIDATE_INT);
    $quantity = filter_var($_POST['quantity'] ?? 0, FILTER_VALIDATE_INT);
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Initialize session cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Ensure SHIPPING_COST is defined
    if (!defined('SHIPPING_COST')) {
        define('SHIPPING_COST', 100.00); // Default shipping cost
    }

    // CSRF validation for all state-changing actions
    if ($action !== 'get_count' && !verify_csrf_token($csrf_token)) {
        $response = ['success' => false, 'message' => 'Invalid security token.'];
        echo json_encode($response);
        exit;
    }

    switch ($action) {
        case 'add':
            if ($product_id > 0 && $quantity > 0) {
                $conn = get_db_connection();
                $stmt = $conn->prepare("SELECT name, price, sale_price, image, stock FROM products WHERE id = ? AND status = 1");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();
                $stmt->close();

                if ($product) {
                    $current_quantity_in_cart = $_SESSION['cart'][$product_id]['quantity'] ?? 0;
                    if (($current_quantity_in_cart + $quantity) > $product['stock']) {
                        $response = ['success' => false, 'message' => 'Not enough stock available.'];
                    } else {
                        if (isset($_SESSION['cart'][$product_id])) {
                            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                        } else {
                            $_SESSION['cart'][$product_id] = [
                                'name' => $product['name'],
                                'price' => $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'],
                                'image' => $product['image'],
                                'quantity' => $quantity
                            ];
                        }

                        // Save cart to database if user is logged in
                        if (isset($_SESSION['user_id'])) {
                            save_user_cart($conn, $_SESSION['user_id'], $_SESSION['cart']);
                        }

                        $totals = get_cart_totals();
                        $response = [
                            'success' => true,
                            'message' => 'Product added to cart.',
                            'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
                            'new_subtotal' => $totals['subtotal'],
                            'new_total' => $totals['total']
                        ];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Product not found or not available.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid product ID or quantity.'];
            }
            break;

        case 'remove':
            if ($product_id > 0) {
                if (isset($_SESSION['cart'][$product_id])) {
                    unset($_SESSION['cart'][$product_id]);

                    // Save cart to database if user is logged in
                    $conn = get_db_connection();
                    if (isset($_SESSION['user_id'])) {
                        save_user_cart($conn, $_SESSION['user_id'], $_SESSION['cart']);
                    }

                    $totals = get_cart_totals();
                    $response = [
                        'success' => true,
                        'message' => 'Product removed from cart.',
                        'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
                        'new_subtotal' => $totals['subtotal'],
                        'new_total' => $totals['total'],
                        'removed_product_id' => $product_id // Indicate which product was removed
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Product not in cart.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid product ID.'];
            }
            break;

        case 'update':
            if ($product_id > 0 && $quantity >= 0) {
                if (isset($_SESSION['cart'][$product_id])) {
                    if ($quantity == 0) {
                        unset($_SESSION['cart'][$product_id]);
                        $totals = get_cart_totals();
                        $response = [
                            'success' => true,
                            'message' => 'Product removed from cart.',
                            'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
                            'new_subtotal' => $totals['subtotal'],
                            'new_total' => $totals['total'],
                            'removed_product_id' => $product_id // Indicate which product was removed
                        ];
                    } else {
                        $conn = get_db_connection();
                        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? AND status = 1");
                        $stmt->bind_param("i", $product_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $product = $result->fetch_assoc();
                        $stmt->close();

                        if ($product && $quantity <= $product['stock']) {
                            $_SESSION['cart'][$product_id]['quantity'] = $quantity;

                            // Save cart to database if user is logged in
                            if (isset($_SESSION['user_id'])) {
                                save_user_cart($conn, $_SESSION['user_id'], $_SESSION['cart']);
                            }

                            $totals = get_cart_totals();
                            $response = [
                                'success' => true,
                                'message' => 'Cart updated.',
                                'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
                                'new_subtotal' => $totals['subtotal'],
                                'new_total' => $totals['total']
                            ];
                        } else {
                            $response = ['success' => false, 'message' => 'Not enough stock available for this quantity.'];
                        }
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Product not in cart.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid product ID or quantity.'];
            }
            break;

        case 'get_count':
            $total_quantity = 0;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $total_quantity += $item['quantity'];
                }
            }
            $response = ['success' => true, 'cart_count' => $total_quantity];
            break;

        default:
            $response = ['success' => false, 'message' => 'Unknown action.'];
            break;
    }
}

echo json_encode($response);
?>
