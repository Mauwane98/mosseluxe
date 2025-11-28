<?php
// Test product page loading for product ID=2
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['id'] = '2';

echo "<h1>Testing Product ID=2</h1>";

try {
    require_once __DIR__ . '/includes/bootstrap.php';
    $conn = get_db_connection();

    // Check if product 2 exists
    $sql = "SELECT id, name, price, stock FROM products WHERE id = 2 AND status = 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            echo "✅ Product 2 found: " . htmlspecialchars($product['name']) . "<br>";
        } else {
            echo "❌ Product 2 not found<br>";
        }
        $stmt->close();
    }

    // Include product.php logic
    require_once __DIR__ . '/product.php';
    echo "<hr><strong>Product page loaded successfully!</strong>";

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "<br>";
}
?>
