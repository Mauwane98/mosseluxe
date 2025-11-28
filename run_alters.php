<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Add columns to products
try {
    $conn->query("ALTER TABLE products ADD COLUMN sku VARCHAR(255) DEFAULT NULL AFTER id");
    echo "Added sku column\n";
} catch (Exception $e) {
    echo "Error adding sku: " . $e->getMessage() . "\n";
}

try {
    $conn->query("ALTER TABLE products ADD COLUMN subtitle VARCHAR(255) DEFAULT NULL AFTER name");
    echo "Added subtitle column\n";
} catch (Exception $e) {
    echo "Error adding subtitle: " . $e->getMessage() . "\n";
}

try {
    $conn->query("ALTER TABLE products ADD COLUMN slug VARCHAR(255) UNIQUE DEFAULT NULL AFTER subtitle");
    echo "Added slug column\n";
} catch (Exception $e) {
    echo "Error adding slug: " . $e->getMessage() . "\n";
}

try {
    $conn->query("ALTER TABLE products ADD COLUMN currency VARCHAR(10) DEFAULT 'ZAR' AFTER slug");
    echo "Added currency column\n";
} catch (Exception $e) {
    echo "Error adding currency: " . $e->getMessage() . "\n";
}
?>
