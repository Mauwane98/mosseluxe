<?php
// Test shop.php loading
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Shop Debug Test</h1>";

try {
    echo "Loading bootstrap...<br>";
    require_once 'includes/bootstrap.php';
    echo "✅ Bootstrap OK<br>";

    $conn = get_db_connection();
    echo "✅ DB Connection OK<br>";

    // Test categories table
    echo "Testing categories query...<br>";
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Categories table exists. Count: " . $row['count'] . "<br>";
    } else {
        echo "❌ Categories table error: " . $conn->error . "<br>";
    }

    // Test products query
    echo "Testing products query...<br>";
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 1");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Products table exists. Active products: " . $row['count'] . "<br>";
    } else {
        echo "❌ Products table error: " . $conn->error . "<br>";
    }

    // Test specific columns used in shop.php
    echo "Testing specific product columns...<br>";
    $sql = "SELECT id, name, price, sale_price, image, is_featured, is_coming_soon, is_bestseller, is_new, created_at FROM products WHERE status = 1 LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✅ Product columns OK. Sample: " . htmlspecialchars($row['name']) . "<br>";
    } else {
        echo "❌ Product columns error: " . $conn->error . "<br>";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}

$conn->close();
?>
