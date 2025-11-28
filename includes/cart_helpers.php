<?php

/**
 * This file contains helper functions extracted from ajax_cart_handler.php
 * to be shared between the old handler and the new API controllers.
 */

if (!function_exists('countCartItems')) {
    function countCartItems($cart) {
        $count = 0;
        if (empty($cart)) return 0;
        foreach ($cart as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }
}

if (!function_exists('validateProduct')) {
    function validateProduct($conn, $product_id) {
        if ($product_id <= 0) {
            return ['valid' => false, 'message' => 'Invalid product ID.'];
        }

        $stmt = $conn->prepare("SELECT id, name, price, sale_price, stock, status, image FROM products WHERE id = ? AND status = 1");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['valid' => false, 'message' => 'Product not found or not available.'];
        }

        $product = $result->fetch_assoc();
        $stmt->close();

        if ($product['stock'] <= 0) {
            return ['valid' => false, 'message' => 'Product is out of stock.'];
        }

        return ['valid' => true, 'product' => $product];
    }
}

if (!function_exists('calculateCartTotals')) {
    function calculateCartTotals($cart, $coupon = null) {
        $subtotal = 0;
        $shipping = defined('SHIPPING_COST') ? SHIPPING_COST : 100.00;

        if (!empty($cart)) {
            foreach ($cart as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
        }

        $discount = 0;
        if ($coupon) {
            if ($coupon['discount_type'] === 'percentage') {
                $discount = $subtotal * ($coupon['discount_value'] / 100);
            } else {
                $discount = min($coupon['discount_value'], $subtotal);
            }

            if ($coupon['max_discount_amount'] && $discount > $coupon['max_discount_amount']) {
                $discount = $coupon['max_discount_amount'];
            }
        }

        $total = $subtotal + $shipping - $discount;

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => max(0, $total)
        ];
    }
}

if (!function_exists('getCartData')) {
    function getCartData($cart) {
        $cart_data = [];
        if (empty($cart)) return [];
        foreach ($cart as $product_id => $item) {
            $cart_data[] = [
                'product_id' => $product_id,
                'name' => $item['name'],
                'price' => number_format($item['price'], 2),
                'quantity' => $item['quantity'],
                'image' => $item['image'],
                'total' => number_format($item['price'] * $item['quantity'], 2)
            ];
        }
        return $cart_data;
    }
}

if (!function_exists('saveUserCart')) {
    function saveUserCart($conn, $cart_data) {
        if (!isset($_SESSION['user_id']) || empty($cart_data)) {
            return;
        }

        $user_id = $_SESSION['user_id'];
        $conn->query("DELETE FROM user_carts WHERE user_id = $user_id");

        $stmt = $conn->prepare("INSERT INTO user_carts (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = NOW()");
        foreach ($cart_data as $product_id => $item) {
            $stmt->bind_param("iii", $user_id, $product_id, $item['quantity']);
            $stmt->execute();
        }
        $stmt->close();
    }
}

if (!function_exists('loadUserCart')) {
    function loadUserCart($conn, $user_id) {
        if (!$user_id) return [];

        $cart = [];
        $stmt = $conn->prepare("
            SELECT uc.product_id, uc.quantity, p.name, p.price, p.sale_price, p.image
            FROM user_carts uc
            JOIN products p ON uc.product_id = p.id
            WHERE uc.user_id = ? AND p.status = 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $cart[$row['product_id']] = [
                'name' => $row['name'],
                'price' => $row['sale_price'] > 0 ? $row['sale_price'] : $row['price'],
                'image' => $row['image'],
                'quantity' => $row['quantity']
            ];
        }

        $stmt->close();
        return $cart;
    }
}

if (!function_exists('validateCoupon')) {
    function validateCoupon($conn, $code) {
        $stmt = $conn->prepare("
            SELECT * FROM coupon_codes
            WHERE code = ? AND is_active = 1
            AND (start_date IS NULL OR start_date <= NOW())
            AND (end_date IS NULL OR end_date >= NOW())
            AND (usage_limit IS NULL OR usage_count < usage_limit)
        ");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false;
        }

        return $result->fetch_assoc();
    }
}

// The original functions from ajax_cart_handler.php that perform actions
// will be moved into the CartController methods over time.
// For now, we keep them here to ensure the old handler still works.

function addToCart($conn, $product_id, $quantity) { /* ... original logic ... */ }
function updateCartItem($conn, $product_id, $quantity) { /* ... original logic ... */ }
function removeFromCart($conn, $product_id) { /* ... original logic ... */ }
function clearCart($conn) { /* ... original logic ... */ }
function applyCoupon($conn, $coupon_code) { /* ... original logic ... */ }
function removeCoupon() { /* ... original logic ... */ }

// No closing PHP tag - prevents accidental whitespace output