<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Simulate a browser session - test the get_cart AJAX call first
echo "<h1>Test Real Cart Flow</h1>";
echo "<p>Step 1: Simulating page load - call get_cart</p>";

// Simulate the get_cart action as cart.js does on page load
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['action' => 'get_count'];

ob_start();
include 'ajax_cart_handler.php';
$getCartOutput = ob_get_clean();

echo "<p>Get cart output: " . htmlspecialchars($getCartOutput) . "</p>";

// Now add an item
echo "<p>Step 2: Adding item to cart</p>";

$_POST = [
    'action' => 'add',
    'product_id' => 19,
    'quantity' => 1,
    'csrf_token' => $_SESSION['csrf_token']
];

ob_start();
include 'ajax_cart_handler.php';
$addOutput = ob_get_clean();

echo "<p>Add to cart output: " . htmlspecialchars($addOutput) . "</p>";

// Test the get_cart call again
echo "<p>Step 3: Get cart after adding item</p>";

$_POST = ['action' => 'get_cart'];

ob_start();
include 'ajax_cart_handler.php';
$getCartAfterOutput = ob_get_clean();

echo "<p>Get cart after add output: " . htmlspecialchars($getCartAfterOutput) . "</p>";

// Display current session state
echo "<h2>Current Session State</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Cart in session: " . (isset($_SESSION['cart']) ? count($_SESSION['cart']) . ' items' : 'not set') . "</p>";
echo "<pre>Cart data: " . print_r($_SESSION['cart'], true) . "</pre>";

?>
