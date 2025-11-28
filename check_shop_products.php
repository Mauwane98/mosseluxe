<?php
require_once 'includes/bootstrap.php';

$conn = get_db_connection();

echo "=== CHECKING SHOP PRODUCTS ===\n\n";

// Check the latest product details
$result = $conn->query("SELECT id, name, status, category, price, image FROM products WHERE id = 26");
$product = $result->fetch_assoc();

echo "Product ID: {$product['id']}\n";
echo "Name: {$product['name']}\n";
echo "Status: {$product['status']} " . ($product['status'] == 1 ? '(Active - GOOD)' : '(Inactive - BAD)') . "\n";
echo "Category: {$product['category']}\n";
echo "Price: R {$product['price']}\n";
echo "Image: {$product['image']}\n\n";

// Check if category exists
if ($product['category']) {
    $cat_result = $conn->query("SELECT id, name FROM categories WHERE id = {$product['category']}");
    if ($cat_result && $cat_result->num_rows > 0) {
        $cat = $cat_result->fetch_assoc();
        echo "✓ Category exists: {$cat['name']}\n";
    } else {
        echo "✗ Category ID {$product['category']} does NOT exist!\n";
    }
} else {
    echo "✗ Product has NO category assigned!\n";
}

echo "\n=== ALL ACTIVE PRODUCTS IN SHOP ===\n\n";
$shop_result = $conn->query("SELECT id, name, category FROM products WHERE status = 1 ORDER BY id DESC");
echo "Total active products: " . $shop_result->num_rows . "\n\n";

while ($row = $shop_result->fetch_assoc()) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Category: {$row['category']}\n";
}
?>
