<?php
require_once 'includes/bootstrap.php';

$conn = get_db_connection();

echo "=== LATEST PRODUCTS ===\n\n";
$result = $conn->query("SELECT id, name, status, price, image, created_at FROM products ORDER BY id DESC LIMIT 5");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "Name: {$row['name']}\n";
        echo "Status: " . ($row['status'] == 1 ? 'Active' : 'Inactive') . "\n";
        echo "Price: R {$row['price']}\n";
        echo "Image: {$row['image']}\n";
        echo "Created: {$row['created_at']}\n";
        echo "---\n\n";
    }
} else {
    echo "No products found!\n";
}

echo "\n=== PRODUCTS IN NEW ARRIVALS ===\n\n";
$na_result = $conn->query("SELECT p.id, p.name, na.display_order FROM products p JOIN new_arrivals na ON p.id = na.product_id ORDER BY na.display_order ASC");

if ($na_result && $na_result->num_rows > 0) {
    while ($row = $na_result->fetch_assoc()) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Order: {$row['display_order']}\n";
    }
} else {
    echo "No products in new arrivals!\n";
}
?>
