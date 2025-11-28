<?php
// Test actual page loading
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing Page Loading</h1>";

try {
    echo "Testing shop.php include...<br>";
    ob_start();
    include 'shop.php';
    $output = ob_get_clean();
    if (strpos($output, 'Fatal error') !== false || strpos($output, 'Parse error') !== false) {
        echo "❌ Shop.php has errors<br>";
        echo "<pre>" . substr($output, 0, 500) . "...</pre>";
    } else {
        echo "✅ Shop.php loaded without fatal errors<br>";
    }
} catch (Exception $e) {
    echo "❌ Exception in shop.php: " . $e->getMessage() . "<br>";
}

echo "<hr>";

try {
    echo "Testing product.php with ID 1...<br>";
    $_GET['id'] = '1'; // Set a test ID
    ob_start();
    include 'product.php';
    $output = ob_get_clean();
    if (strpos($output, 'Fatal error') !== false || strpos($output, 'Parse error') !== false) {
        echo "❌ Product.php has errors<br>";
        echo "<pre>" . substr($output, 0, 500) . "...</pre>";
    } else {
        echo "✅ Product.php loaded without fatal errors<br>";
    }
} catch (Exception $e) {
    echo "❌ Exception in product.php: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "Test completed.";
?>
