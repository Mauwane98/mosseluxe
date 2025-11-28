<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();
$result = $conn->query('SELECT id, name, stock, status FROM products WHERE id = 26');
$row = $result->fetch_assoc();
echo "ID: {$row['id']}\n";
echo "Name: {$row['name']}\n";
echo "Stock: " . ($row['stock'] ?? 'NULL') . "\n";
echo "Status: {$row['status']}\n";

if ($row['stock'] === null || $row['stock'] <= 0) {
    echo "\n⚠️ PROBLEM: Stock is 0 or NULL - this will prevent adding to cart!\n";
    echo "Fixing stock...\n";
    $conn->query("UPDATE products SET stock = 100 WHERE id = 26");
    echo "✓ Stock updated to 100\n";
}
?>
