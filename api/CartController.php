<?php

// Load cart action functions (addToCart, updateCartItem, etc.)
// This file contains all the cart functions from ajax_cart_handler.php
require_once __DIR__ . '/../includes/cart_functions.php';

class CartController {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Handles GET /api/cart
     * Retrieves the full cart data.
     */
    public function getCart() {
        // Logic from ajax_cart_handler.php case 'get_cart' will go here.
        $cart_data = getCartData($_SESSION['cart'] ?? []);
        $totals = calculateCartTotals($_SESSION['cart'] ?? []);
        $response = [
            'success' => true,
            'cart' => $cart_data,
            'totals' => $totals,
            'item_count' => countCartItems($_SESSION['cart'] ?? []),
        ];
        send_json_response(200, $response);
    }

    /**
     * Handles POST /api/cart/items
     * Adds an item to the cart.
     */
    public function addItem() {
        // Logic from ajax_cart_handler.php case 'add'
        $input = json_decode(file_get_contents('php://input'), true);
        $product_id = (int)($input['product_id'] ?? 0);
        $quantity = (int)($input['quantity'] ?? 1);

        // Call the addToCart helper function
        $result = addToCart($this->conn, $product_id, $quantity);
        
        if ($result['success']) {
            send_json_response(201, $result);
        } else {
            send_json_response(400, $result);
        }
    }

    /**
     * Handles PUT /api/cart/items/{id}
     * Updates an item's quantity in the cart.
     */
    public function updateItem($productId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $quantity = (int)($input['quantity'] ?? 0);
        $productId = (int)$productId;

        if ($productId <= 0 || !isset($_SESSION['cart'][$productId])) {
            send_json_response(404, ['success' => false, 'message' => 'Product not found in cart.']);
            return;
        }

        // If quantity is zero or less, treat it as a removal
        if ($quantity <= 0) {
            $this->removeItem($productId);
            return;
        }

        $validation = validateProduct($this->conn, $productId);
        if (!$validation['valid']) {
            send_json_response(404, ['success' => false, 'message' => $validation['message']]);
            return;
        }

        $product = $validation['product'];
        if ($quantity > $product['stock']) {
            send_json_response(409, [
                'success' => false, 
                'message' => "Only {$product['stock']} items available. Please update your cart.",
                'max_quantity' => $product['stock']
            ]);
            return;
        }

        $_SESSION['cart'][$productId]['quantity'] = $quantity;
        saveUserCart($this->conn, $_SESSION['cart']);
        
        $totals = calculateCartTotals($_SESSION['cart'], $_SESSION['applied_coupon'] ?? null);
        $cart_data = getCartData($_SESSION['cart']);

        send_json_response(200, [
            'success' => true,
            'message' => 'Cart updated successfully.',
            'cart_count' => countCartItems($_SESSION['cart']),
            'totals' => $totals,
            'new_subtotal' => number_format($totals['subtotal'], 2),
            'new_total' => number_format($totals['total'], 2),
            'cart' => $cart_data
        ]);
    }

    /**
     * Handles DELETE /api/cart/items/{id}
     * Removes an item from the cart.
     */
    public function removeItem($productId) {
        $productId = (int)$productId;

        if ($productId <= 0 || !isset($_SESSION['cart'][$productId])) {
            send_json_response(404, ['success' => false, 'message' => 'Product not found in cart.']);
            return;
        }

        $product_name = $_SESSION['cart'][$productId]['name'];
        unset($_SESSION['cart'][$productId]);
        saveUserCart($this->conn, $_SESSION['cart']);

        $totals = calculateCartTotals($_SESSION['cart'], $_SESSION['applied_coupon'] ?? null);
        $cart_data = getCartData($_SESSION['cart']);

        send_json_response(200, [
            'success' => true,
            'message' => "{$product_name} removed from cart.",
            'cart_count' => countCartItems($_SESSION['cart']),
            'totals' => $totals,
            'new_subtotal' => number_format($totals['subtotal'], 2),
            'new_total' => number_format($totals['total'], 2),
            'cart' => $cart_data,
            'removed_product_id' => $productId
        ]);
    }

    /**
     * Handles DELETE /api/cart
     * Clears the entire cart.
     */
    public function clearCart() {
        $_SESSION['cart'] = [];
        if (isset($_SESSION['user_id'])) {
            $this->conn->query("DELETE FROM user_carts WHERE user_id = " . (int)$_SESSION['user_id']);
        }
        
        $totals = [
            'subtotal' => 0,
            'shipping' => 0,
            'discount' => 0,
            'total' => 0
        ];

        send_json_response(200, [
            'success' => true,
            'message' => 'Cart cleared successfully.',
            'cart_count' => 0,
            'totals' => $totals,
            'new_subtotal' => '0.00',
            'new_total' => '0.00',
            'cart' => []
        ]);
    }
}