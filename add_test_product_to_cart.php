<?php
echo "<h1>🧪 TEST: Add Product to Cart</h1>";
echo "<style>body{font-family:monospace; margin:20px;} .test{margin:10px 0; padding:10px; border:1px solid #ccc; background:#f9f9f9;} .success{color:#28a745;} .error{color:#dc3545;} .button{background:#007bff;color:white;padding:10px 20px;border:none;border-radius:5px;text-decoration:none;display:inline-block;margin:10px 5px;}</style>";

require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Get a test product
echo "<div class='test'><h3>Test Product</h3>";
$product = $conn->query("SELECT id, name, price FROM products WHERE status = 1 LIMIT 1");
if ($product && $row = $product->fetch_assoc()) {
    echo "Product: {$row['name']} (ID: {$row['id']}) - Price: R{$row['price']}<br>";

    // Test 1: Direct session add (no AJAX)
    echo "<h4>TEST 1: Direct Session Add</h4>";
    $_SESSION['cart'][$row['id']] = [
        'name' => $row['name'],
        'price' => $row['price'],
        'quantity' => 2,
        'image' => 'placeholder.jpg'
    ];
    echo "Added to session directly<br>";
    echo "Session cart count: " . count($_SESSION['cart'] ?? []) . "<br>";

    // Test 2: AJAX simulation
    echo "<h4>TEST 2: AJAX Call Simulation</h4>";
    ob_start();
    $_POST = [
        'action' => 'add',
        'product_id' => $row['id'],
        'quantity' => 1,
        'csrf_token' => 'test123' // Will be rejected due to invalid token, but test response
    ];
    include 'ajax_cart_handler.php';
    $ajax_response = ob_get_clean();
    $ajax_result = json_decode($ajax_response, true);

    if ($ajax_result) {
        echo "AJAX Response: " . $ajax_result['message'] . "<br>";
        if (isset($ajax_result['cart_count'])) {
            echo "Cart count after AJAX: {$ajax_result['cart_count']}<br>";
        }
    } else {
        echo "<span class='error'>AJAX call failed or returned invalid response</span><br>";
    }

    // Test 3: Database check
    echo "<h4>TEST 3: Database Cart Check</h4>";
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $db_cart = $conn->query("SELECT * FROM user_carts WHERE user_id = $user_id");
        echo "User cart items in DB: " . ($db_cart ? $db_cart->num_rows : 'query failed') . "<br>";
    } else {
        echo "User not logged in - no database cart<br>";
    }

} else {
    echo "<span class='error'>No products found for testing!</span>";
}
echo "</div>";

// Navigation buttons
echo "<div class='test'><h3>Navigate to Test Cart</h3>";
echo "<a href='cart.php' class='button' target='_blank'>View Cart Page</a>";
echo "<a href='shop.php' class='button' target='_blank'>View Shop Page</a>";
echo "<a href='browser_test.php' class='button'>Back to Browser Test</a>";
echo "</div>";

// Display current cart state
echo "<div class='test'><h3>Current Cart State</h3>";
$session_cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
echo "<strong>Session Cart Items:</strong> $session_cart_count<br>";
if ($session_cart_count > 0) {
    foreach ($_SESSION['cart'] as $id => $item) {
        echo "  • $id: {$item['name']} x{$item['quantity']} @ R{$item['price']}<br>";
    }
} else {
    echo "<span class='error'>Session cart is empty</span><br>";
}


echo "</div>";
?>
