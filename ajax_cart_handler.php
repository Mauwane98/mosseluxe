<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $conn = get_db_connection();

    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    switch ($action) {
        case 'add':
            $product_id = filter_var($_POST['product_id'] ?? 0, FILTER_VALIDATE_INT);
            $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);

            if ($product_id > 0 && $quantity > 0) {
                // Fetch product details from database
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
                        $response = ['success' => true, 'message' => 'Product added to cart.', 'cart_count' => count($_SESSION['cart'])];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Product not found or not available.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid product ID or quantity.'];
            }
            break;

        case 'remove':
            $product_id = filter_var($_POST['product_id'] ?? 0, FILTER_VALIDATE_INT);
            if ($product_id > 0) {
                if (isset($_SESSION['cart'][$product_id])) {
                    unset($_SESSION['cart'][$product_id]);
                    $response = ['success' => true, 'message' => 'Product removed from cart.', 'cart_count' => count($_SESSION['cart'])];
                } else {
                    $response = ['success' => false, 'message' => 'Product not in cart.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid product ID.'];
            }
            break;

        case 'update':
            $product_id = filter_var($_POST['product_id'] ?? 0, FILTER_VALIDATE_INT);
            $quantity = filter_var($_POST['quantity'] ?? 0, FILTER_VALIDATE_INT);

            if ($product_id > 0 && $quantity >= 0) {
                if (isset($_SESSION['cart'][$product_id])) {
                    if ($quantity == 0) {
                        unset($_SESSION['cart'][$product_id]);
                        $response = ['success' => true, 'message' => 'Product removed from cart.', 'cart_count' => count($_SESSION['cart'])];
                    } else {
                        // Check stock before updating
                        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? AND status = 1");
                        $stmt->bind_param("i", $product_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $product = $result->fetch_assoc();
                        $stmt->close();

                        if ($product && $quantity <= $product['stock']) {
                            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                            $response = ['success' => true, 'message' => 'Cart updated.', 'cart_count' => count($_SESSION['cart'])];
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
            $response = ['success' => true, 'cart_count' => count($_SESSION['cart'])];
            break;

        default:
            $response = ['success' => false, 'message' => 'Unknown action.'];
            break;
    }
    $conn->close();
}

echo json_encode($response);
?>