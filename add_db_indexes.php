<?php
require_once 'includes/bootstrap.php';

$conn = get_db_connection();

// Add essential database indexes for production performance
$indexes = [
    // Products table indexes
    "CREATE INDEX idx_products_category ON products(category)",
    "CREATE INDEX idx_products_status ON products(status)",
    "CREATE INDEX idx_products_price ON products(price)",
    "CREATE INDEX idx_products_featured ON products(is_featured)",
    "CREATE INDEX idx_products_new ON products(is_new)",

    // Users table indexes
    "CREATE INDEX idx_users_email ON users(email)",
    "CREATE INDEX idx_users_role ON users(role)",

    // Orders table indexes
    "CREATE INDEX idx_orders_user_id ON orders(user_id)",
    "CREATE INDEX idx_orders_status ON orders(status)",
    "CREATE INDEX idx_orders_created_at ON orders(created_at)",

    // Hero slides indexes
    "CREATE INDEX idx_hero_slides_active ON hero_slides(is_active)",
    "CREATE INDEX idx_hero_slides_order ON hero_slides(sort_order)",

    // Settings table index
    "CREATE INDEX idx_settings_key ON settings(setting_key)",

    // Product reviews indexes
    "CREATE INDEX idx_product_reviews_product ON product_reviews(product_id)",
    "CREATE INDEX idx_product_reviews_user ON product_reviews(user_id)",
    "CREATE INDEX idx_product_reviews_approved ON product_reviews(is_approved)",

    // Wishlist indexes
    "CREATE INDEX idx_wishlist_user ON wishlist(user_id)",
    "CREATE INDEX idx_wishlist_product ON wishlist(product_id)",

    // Cart sessions indexes
    "CREATE INDEX idx_cart_sessions_session ON cart_sessions(session_id)",
    "CREATE INDEX idx_cart_sessions_product ON cart_sessions(product_id)"
];

echo "Adding database indexes for production performance...\n";
$added = 0;
$skipped = 0;

foreach ($indexes as $index_sql) {
    // Extract index name to check if it exists
    if (preg_match('/CREATE INDEX ([^\s]+)/', $index_sql, $matches)) {
        $index_name = $matches[1];

        // Check if index already exists
        $table_name = explode(' ON ', $index_sql)[1];
        $table_name = explode('(', $table_name)[0];
        $table_name = trim($table_name);

        $check_sql = "SHOW INDEX FROM `$table_name` WHERE Key_name = '$index_name'";
        $result = $conn->query($check_sql);

        if ($result->num_rows == 0) {
            if ($conn->query($index_sql)) {
                echo "✓ Added index: $index_name\n";
                $added++;
            } else {
                echo "✗ Failed to add index: $index_name - " . $conn->error . "\n";
            }
        } else {
            echo "⚠ Index already exists: $index_name\n";
            $skipped++;
        }
    }
}

echo "\nIndex Summary:\n";
echo "- Added: $added\n";
echo "- Already existed: $skipped\n";

$conn->close();
?>
