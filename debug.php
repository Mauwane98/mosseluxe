<?php
// Enable all error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Started</h1>";
echo "<h2>PHP Version: " . phpversion() . "</h2>";
echo "<h2>Server: " . $_SERVER['SERVER_NAME'] . "</h2>";
echo "<h2>Request URI: " . $_SERVER['REQUEST_URI'] . "</h2>";

echo "<h3>Checking includes directory...</h3>";
if (file_exists('includes')) {
    echo "✅ includes directory exists<br>";
    $files = scandir('includes');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "- $file<br>";
        }
    }
} else {
    echo "❌ includes directory missing<br>";
}

echo "<h3>Testing bootstrap...</h3>";
try {
    echo "Loading bootstrap.php...<br>";
    require_once __DIR__ . '/includes/bootstrap.php';
    echo "✅ Bootstrap loaded successfully<br>";

    echo "Testing DB connection...<br>";
    $conn = get_db_connection();
    if ($conn->ping()) {
        echo "✅ Database connection working<br>";

        $result = $conn->query('SELECT 1 as test');
        $row = $result->fetch_assoc();
        echo "✅ Basic query works: " . $row['test'] . "<br>";
    } else {
        echo "❌ Database ping failed<br>";
    }
} catch (Exception $e) {
    echo "❌ ERROR in bootstrap: " . $e->getMessage() . "<br>";
}

echo "<h3>Testing product query...</h3>";
try {
    $test_product_id = isset($_GET['id']) ? $_GET['id'] : 1;

    $sql = "SELECT id, name, status FROM products WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $test_product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo "✅ Product found: ID " . $row['id'] . " - " . $row['name'] . " (status: " . $row['status'] . ")<br>";
    } else {
        echo "❌ No product found with ID " . $test_product_id . "<br>";

        // Try to find any products
        $result = $conn->query('SELECT id, name FROM products LIMIT 5');
        echo "Available products:<br>";
        while ($row = $result->fetch_assoc()) {
            echo "- ID: " . $row['id'] . ", Name: " . $row['name'] . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ ERROR in product query: " . $e->getMessage() . "<br>";
}

// Show all product IDs for testing
echo "<h3>All Available Products:</h3>";
try {
    $result = $conn->query('SELECT id, name, status FROM products ORDER BY id');
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Status</th><th>Test Link</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $test_url = "http://localhost/mosseluxe/product-details.php?id=" . $row['id'];
        $status = $row['status'] == 1 ? 'Active' : 'Inactive';
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>$status</td><td><a href='$test_url' target='_blank'>Test Product</a></td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ ERROR getting product list: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Add More Sample Products:</h3>";
echo "<a href='add_sample_products.php' target='_blank'>➕ Add 4 More Sample Products</a><br><br>";
echo "<a href='shop.php' target='_blank'>🛒 View Shop</a><br>";
echo "<a href='admin/products.php' target='_blank'>⚙️ Admin Products</a><br>";
?>
