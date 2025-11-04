<?php

namespace App\Controllers;

use Twig\Environment;

class CartController
{
    private $conn;
    private $twig;

    public function __construct($conn, Environment $twig)
    {
        $this->conn = $conn;
        $this->twig = $twig;
    }

    public function index()
    {
        // Initialize cart in session if it doesn't exist
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $cart_items = $_SESSION['cart'];
        $total_cart_price = 0;
        $cart_products_details = []; // To store product details fetched from DB

        // --- Fetch product details for display in the cart ---
        if (!empty($_SESSION['cart'])) {
            // Get all product IDs from the cart
            $product_ids_in_cart = array_keys($_SESSION['cart']);

            // Build a query to fetch details for all products in the cart
            if (!empty($product_ids_in_cart)) {
                $placeholders = implode(',', array_fill(0, count($product_ids_in_cart), '?'));
                $sql_cart_items = "SELECT id, name, price, sale_price, image FROM products WHERE id IN ($placeholders)";
                
                if ($stmt_cart_items = $this->conn->prepare($sql_cart_items)) {
                    // Bind product IDs to the prepared statement
                    $types = str_repeat('i', count($product_ids_in_cart));
                    $stmt_cart_items->bind_param($types, ...$product_ids_in_cart);

                    if ($stmt_cart_items->execute()) {
                        $result_cart_items = $stmt_cart_items->get_result();
                        while ($row_cart_item = $result_cart_items->fetch_assoc()) {
                            // Store details keyed by product ID for easy lookup
                            $cart_products_details[$row_cart_item['id']] = $row_cart_item;
                        }
                    } else {
                        error_log("Error executing cart items query: " . $stmt_cart_items->error);
                    }
                    $stmt_cart_items->close();
                } else {
                    error_log("Error preparing cart items query: " . $this->conn->error);
                }
            }
        }

        // Calculate total cart price for display
        foreach ($cart_items as $product_id => $item) {
            if (isset($cart_products_details[$product_id])) {
                $product_detail = $cart_products_details[$product_id];
                $price_to_use = (isset($product_detail['sale_price']) && $product_detail['sale_price'] > 0) ? $product_detail['sale_price'] : $product_detail['price'];
                $item_total = $price_to_use * $item['quantity'];
                $total_cart_price += $item_total;
            }
        }

        echo $this->twig->render('cart/index.html', [
            'cart_items' => $cart_items,
            'cart_products_details' => $cart_products_details,
            'total_cart_price' => $total_cart_price,
            'shipping_cost' => SHIPPING_COST,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

    public function add()
    {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            // Handle error, e.g., return JSON response or redirect
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token!']);
            return;
        }

        $product_id = filter_var(trim($_POST['product_id']), FILTER_SANITIZE_NUMBER_INT);
        $quantity = filter_var(trim($_POST['quantity']), FILTER_SANITIZE_NUMBER_INT);

        if (!$product_id || !$quantity || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity.']);
            return;
        }

        // Fetch product details to validate and get price
        $sql_product = "SELECT id, name, price, sale_price FROM products WHERE id = ? AND status = 1";
        if ($stmt_product = $this->conn->prepare($sql_product)) {
            $stmt_product->bind_param("i", $product_id);
            $stmt_product->execute();
            $result_product = $stmt_product->get_result();
            if ($product_data = $result_product->fetch_assoc()) {
                $price_to_use = (isset($product_data['sale_price']) && $product_data['sale_price'] > 0) ? $product_data['sale_price'] : $product_data['price'];

                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }

                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'quantity' => $quantity,
                        'price' => $price_to_use,
                        'name' => $product_data['name']
                    ];
                }
                echo json_encode(['success' => true, 'message' => 'Product added to cart!', 'cart_count' => $this->getCartItemCount()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not found or unavailable.']);
            }
            $stmt_product->close();
        } else {
            error_log("Error preparing product validation query in cart: " . $this->conn->error);
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    }

    public function update()
    {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Invalid CSRF token!'); // Or handle error more gracefully
        }

        $product_id = filter_var(trim($_POST['product_id']), FILTER_SANITIZE_NUMBER_INT);
        $quantity = filter_var(trim($_POST['quantity']), FILTER_SANITIZE_NUMBER_INT);

        if (!$product_id || $quantity < 0) {
            // Redirect back to cart with an error message
            header("Location: /cart?error=invalid_quantity");
            exit();
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
        header("Location: /cart");
        exit();
    }

    public function remove()
    {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Invalid CSRF token!'); // Or handle error more gracefully
        }

        $product_id = filter_var(trim($_POST['product_id']), FILTER_SANITIZE_NUMBER_INT);

        if (!$product_id) {
            header("Location: /cart?error=invalid_product");
            exit();
        }

        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
        header("Location: /cart");
        exit();
    }

    private function getCartItemCount()
    {
        $count = 0;
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $count += $item['quantity'] ?? 0;
            }
        }
        return $count;
    }
}