<?php

// Count total items in cart
function countCartItems($cart) {
    $count = 0;
    foreach ($cart as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

// Validate product exists and is available
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

// Add item to cart
function addToCart($conn, $product_id, $quantity) {
    if ($product_id <= 0 || $quantity <= 0) {
        return [
            'success' => false,
            'message' => 'Invalid product or quantity.',
            'cart_count' => countCartItems($_SESSION['cart'])
        ];
    }

    // Validate product
    $validation = validateProduct($conn, $product_id);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'message' => $validation['message'],
            'cart_count' => countCartItems($_SESSION['cart'])
        ];
    }

    $product = $validation['product'];
    $current_quantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
    $new_total_quantity = $current_quantity + $quantity;

    // Check stock limits
    if ($new_total_quantity > $product['stock']) {
        return [
            'success' => false,
            'message' => "Only {$product['stock']} items available in stock.",
            'cart_count' => countCartItems($_SESSION['cart'])
        ];
    }

    // Add/update cart item
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] = $new_total_quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'price' => $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'],
            'image' => $product['image'],
            'quantity' => $quantity,
            'added_at' => time()
        ];
    }

    // Save to user cart if logged in
    saveUserCart($conn, $_SESSION['cart']);

    $totals = calculateCartTotals($_SESSION['cart']);
    $cart_data = getCartData($_SESSION['cart']);

    return [
        'success' => true,
        'message' => htmlspecialchars($product['name']) . " added to cart successfully.",
        'cart_count' => countCartItems($_SESSION['cart']),
        'new_subtotal' => number_format($totals['subtotal'], 2),
        'new_total' => number_format($totals['total'], 2),
        'product_name' => $product['name'],
        'cart_data' => $cart_data,
        'totals' => $totals
    ];
}

// Update cart item quantity
function updateCartItem($conn, $product_id, $quantity) {
    if ($product_id <= 0) {
        return [
            'success' => false,
            'message' => 'Invalid product ID.',
            'cart_count' => countCartItems($_SESSION['cart'])
        ];
    }

    if (!isset($_SESSION['cart'][$product_id])) {
        return [
            'success' => false,
            'message' => 'Product not found in cart.',
            'cart_count' => countCartItems($_SESSION['cart'])
        ];
    }

    // If quantity is 0, remove the item
    if ($quantity <= 0) {
        return removeFromCart($conn, $product_id);
    }

    // Validate new quantity against stock
    $validation = validateProduct($conn, $product_id);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'message' => $validation['message'],
            'cart_count' => countCartItems($_SESSION['cart'])
        ];
    }

    $product = $validation['product'];
    if ($quantity > $product['stock']) {
        return [
            'success' => false,
            'message' => "Only {$product['stock']} items available in stock.",
            'cart_count' => countCartItems($_SESSION['cart'])
        ];
    }

    // Update quantity
    $_SESSION['cart'][$product_id]['quantity'] = $quantity;

    // Save to user cart if logged in
    saveUserCart($conn, $_SESSION['cart']);

    $totals = calculateCartTotals($_SESSION['cart']);
    $cart_data = getCartData($_SESSION['cart']);

    return [
        'success' => true,
        'message' => 'Cart updated successfully.',
        'cart_count' => countCartItems($_SESSION['cart']),
        'new_subtotal' => number_format($totals['subtotal'], 2),
        'new_total' => number_format($totals['total'], 2),
        'cart_data' => $cart_data,
        'totals' => $totals
    ];
}

// Remove item from cart
function removeFromCart($conn, $product_id) {
    if ($product_id <= 0 || !isset($_SESSION['cart'][$product_id])) {
        return [
            'success' => false,
            'message' => 'Product not found in cart.',
            'cart_count' => countCartItems($_SESSION['cart'])
        ];
    }

    $product_name = $_SESSION['cart'][$product_id]['name'];
    unset($_SESSION['cart'][$product_id]);

    // Save to user cart if logged in
    saveUserCart($conn, $_SESSION['cart']);

    $totals = calculateCartTotals($_SESSION['cart']);
    $cart_data = getCartData($_SESSION['cart']);

    return [
        'success' => true,
        'message' => htmlspecialchars($product_name) . " removed from cart.",
        'cart_count' => countCartItems($_SESSION['cart']),
        'new_subtotal' => number_format($totals['subtotal'], 2),
        'new_total' => number_format($totals['total'], 2),
        'removed_product_id' => $product_id,
        'cart_data' => $cart_data,
        'totals' => $totals
    ];
}

// Clear entire cart
function clearCart($conn) {
    $_SESSION['cart'] = [];

    // Clear user cart from database if logged in
    if (isset($_SESSION['user_id'])) {
        $conn->query("DELETE FROM user_carts WHERE user_id = " . (int)$_SESSION['user_id']);
    }

    $totals = [
        'subtotal' => 0,
        'shipping' => 0,
        'discount' => 0,
        'total' => 0
    ];

    return [
        'success' => true,
        'message' => 'Cart cleared successfully.',
        'cart_count' => 0,
        'new_subtotal' => '0.00',
        'new_total' => '0.00',
        'cart_data' => [],
        'totals' => $totals
    ];
}

// Apply coupon code
function applyCoupon($conn, $coupon_code) {
    if (empty($coupon_code)) {
        return ['success' => false, 'message' => 'Please enter a coupon code.'];
    }

    $coupon = validateCoupon($conn, $coupon_code);
    if (!$coupon) {
        return ['success' => false, 'message' => 'Invalid or expired coupon code.'];
    }

    // Check minimum order amount
    $totals = calculateCartTotals($_SESSION['cart']);
    if ($totals['subtotal'] < $coupon['min_order_amount']) {
        return [
            'success' => false,
            'message' => "Minimum order of R" . number_format($coupon['min_order_amount'], 2) . " required for this coupon."
        ];
    }

    // Save coupon to session
    $_SESSION['applied_coupon'] = $coupon;
    $new_totals = calculateCartTotals($_SESSION['cart'], $coupon);

    return [
        'success' => true,
        'message' => "Coupon '{$coupon_code}' applied successfully!",
        'coupon_discount' => number_format($new_totals['discount'], 2),
        'new_total' => number_format($new_totals['total'], 2),
        'discount_type' => $coupon['discount_type'],
        'discount_value' => $coupon['discount_value']
    ];
}

// Remove coupon
function removeCoupon() {
    unset($_SESSION['applied_coupon']);
    $totals = calculateCartTotals($_SESSION['cart']);

    return [
        'success' => true,
        'message' => 'Coupon removed successfully.',
        'new_total' => number_format($totals['total'], 2)
    ];
}

// Validate coupon code
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

// Calculate cart totals
function calculateCartTotals($cart, $coupon = null) {
    $subtotal = 0;
    $shipping = defined('SHIPPING_COST') ? SHIPPING_COST : 100.00;

    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    $discount = 0;
    if ($coupon) {
        if ($coupon['discount_type'] === 'percentage') {
            $discount = $subtotal * ($coupon['discount_value'] / 100);
        } else {
            $discount = min($coupon['discount_value'], $subtotal);
        }

        // Apply maximum discount limit if set
        if ($coupon['max_discount_amount'] && $discount > $coupon['max_discount_amount']) {
            $discount = $coupon['max_discount_amount'];
        }
    }

    $total = $subtotal + $shipping - $discount;

    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'discount' => $discount,
        'total' => max(0, $total) // Ensure total is never negative
    ];
}

// Get cart data for display
function getCartData($cart) {
    $cart_data = [];
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

// Save user cart to database
function saveUserCart($conn, $cart_data) {
    if (!isset($_SESSION['user_id']) || empty($cart_data)) {
        return;
    }

    $user_id = $_SESSION['user_id'];

    // Clear existing cart items
    $conn->query("DELETE FROM user_carts WHERE user_id = $user_id");

    // Insert current cart items
    $stmt = $conn->prepare("INSERT INTO user_carts (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = NOW()");
    foreach ($cart_data as $product_id => $item) {
        $stmt->bind_param("iii", $user_id, $product_id, $item['quantity']);
        $stmt->execute();
    }
    $stmt->close();
}

// Load user cart from database (for when user logs in)
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

// Sync user cart on login
function syncUserCartOnLogin($conn, $user_id) {
    if (!isset($_SESSION['cart_synced'])) {
        $user_cart = loadUserCart($conn, $user_id);
        if (!empty($user_cart)) {
            // Merge with existing session cart
            foreach ($user_cart as $product_id => $item) {
                if (!isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] = $item;
                } else {
                    // Add quantities
                    $_SESSION['cart'][$product_id]['quantity'] += $item['quantity'];
                }
            }
        }
        $_SESSION['cart_synced'] = true;
    }
}

// Initialize cart session if not exists
function initializeCart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

// Auto-initialize cart
initializeCart();
// No closing PHP tag - prevents accidental whitespace output