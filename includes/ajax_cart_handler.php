<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once 'includes/db_connect.php';
require_once 'includes/csrf.php';
$conn = get_db_connection();

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $response['message'] = 'Invalid security token.';
        echo json_encode($response);
        exit;
    }

    $product_id = isset($_POST['product_id']) ? filter_var(trim($_POST['product_id']), FILTER_SANITIZE_NUMBER_INT) : null;
    $quantity = isset($_POST['quantity']) ? filter_var(trim($_POST['quantity']), FILTER_SANITIZE_NUMBER_INT) : 1;

    if ($product_id && $quantity > 0) {
        // Fetch product details to validate and get price
        $sql_product = "SELECT id, name, price, sale_price FROM products WHERE id = ? AND status = 1 AND stock >= ?";
        if ($stmt_product = $conn->prepare($sql_product)) {
            $stmt_product->bind_param("ii", $product_id, $quantity);

            if ($stmt_product->execute()) {
                $result_product = $stmt_product->get_result();
                if ($product_data = $result_product->fetch_assoc()) {
                    // Product is valid and in stock
                    $price_to_use = (isset($product_data['sale_price']) && $product_data['sale_price'] > 0) ? $product_data['sale_price'] : $product_data['price'];

                    // Add or update item in cart
                    if (isset($_SESSION['cart'][$product_id])) {
                        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                    } else {
                        $_SESSION['cart'][$product_id] = [
                            'quantity' => $quantity,
                            'price' => $price_to_use,
                            'name' => $product_data['name']
                        ];
                    }

                    // Calculate total items in cart
                    $total_items = 0;
                    foreach ($_SESSION['cart'] as $item) {
                        $total_items += $item['quantity'];
                    }

                    $response['success'] = true;
                    $response['message'] = htmlspecialchars($product_data['name']) . ' has been added to your cart.';
                    $response['cart_item_count'] = $total_items;

                } else {
                    $response['message'] = 'Product not found or out of stock.';
                }
            }
            $stmt_product->close();
        }
    } else {
        $response['message'] = 'Invalid product or quantity.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();
echo json_encode($response);