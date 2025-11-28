<?php
// Simulate browser loading of product.php?id=1
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['id'] = '1';

echo "<pre>Testing product page loading...\n";

try {
    // Include bootstrap first, just like the real page does
    require_once __DIR__ . '/includes/bootstrap.php';
    // Now include product.php logic
    require_once __DIR__ . '/product.php';
    echo "</pre><hr>";
} catch (Exception $e) {
    echo "</pre><hr>EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
