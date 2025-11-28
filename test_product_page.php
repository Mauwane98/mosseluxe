<?php
// Test product page loading
echo "<h1>Product Page Test</h1>";

try {
    require_once 'includes/bootstrap.php';
    $conn = get_db_connection();
    echo "✅ DB Connection OK<br>";

    // Test loading product with ID=1
    $product_id = 1;
    $sql = "SELECT id, name, description, price, sale_price, image, stock, is_featured, is_coming_soon, is_bestseller, is_new FROM products WHERE id = ? AND status = 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            echo "✅ Product found: " . htmlspecialchars($product['name']) . "<br>";
            echo "Price: R" . number_format($product['price'], 2) . "<br>";
            echo "Stock: " . $product['stock'] . "<br>";
            echo "Status: " . $product['status'] . "<br>";
        } else {
            echo "❌ Product not found<br>";
        }
        $stmt->close();
    } else {
        echo "❌ Query preparation failed<br>";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "Test complete.<br>";
?>
