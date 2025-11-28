<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$tables = ['products', 'product_images', 'product_variants', 'cart_items', 'carts', 'orders_or_inquiries', 'users'];

foreach ($tables as $table) {
    echo "Table: $table\n";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  " . $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } else {
        echo "  Table does not exist or error: " . $conn->error . "\n";
    }
    echo "\n";
}

echo "Done.\n";
?>
