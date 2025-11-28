<?php
require_once 'includes/bootstrap.php';

echo "Resetting database...\n";

try {
    $conn = get_db_connection();

    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Drop existing tables if they exist
    $tables = [
        'cart_items',
        'carts',
        'product_images',
        'product_reviews',
        'product_variants',
        'products',
        'categories',
        'users',
        'pages',
        'settings',
        'hero_slides',
        'homepage_sections',
        'orders_or_inquiries'
    ];

    foreach ($tables as $table) {
        echo "Dropping table: $table\n";
        $conn->query("DROP TABLE IF EXISTS `$table`");
    }

    echo "Re-enabling foreign key checks...\n";
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    echo "✅ Database reset complete\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "Done.\n";
?>
