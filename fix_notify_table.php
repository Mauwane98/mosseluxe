<?php
/**
 * Fix back_in_stock_alerts table to support guest users
 * Run this once to update the table structure
 */

require_once 'includes/bootstrap.php';
$conn = get_db_connection();

echo "<pre>";
echo "Fixing back_in_stock_alerts table for guest user support...\n\n";

// First, check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'back_in_stock_alerts'");

if ($table_check->num_rows == 0) {
    // Create the table with guest support
    $create_sql = "CREATE TABLE `back_in_stock_alerts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `product_id` int(11) NOT NULL,
        `email` varchar(255) NOT NULL,
        `size_variant` varchar(50) DEFAULT NULL,
        `color_variant` varchar(50) DEFAULT NULL,
        `is_notified` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `notified_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`),
        KEY `is_notified` (`is_notified`),
        KEY `email` (`email`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($create_sql)) {
        echo "✓ Table 'back_in_stock_alerts' created successfully with guest support!\n";
    } else {
        echo "✗ Error creating table: " . $conn->error . "\n";
    }
} else {
    // Table exists, modify user_id to allow NULL
    $alter_sql = "ALTER TABLE `back_in_stock_alerts` MODIFY `user_id` int(11) DEFAULT NULL";
    
    if ($conn->query($alter_sql)) {
        echo "✓ Modified 'user_id' column to allow NULL (guest users)\n";
    } else {
        // Might already be nullable, check the error
        if (strpos($conn->error, 'Duplicate') !== false) {
            echo "ℹ Column already supports NULL values\n";
        } else {
            echo "✗ Error modifying column: " . $conn->error . "\n";
        }
    }
    
    // Drop the unique constraint if it exists (it requires user_id which guests don't have)
    $conn->query("ALTER TABLE `back_in_stock_alerts` DROP INDEX `user_product_variant`");
    echo "ℹ Removed old unique constraint (if existed)\n";
    
    // Add new index on email + product for better querying
    $conn->query("ALTER TABLE `back_in_stock_alerts` ADD INDEX `email` (`email`)");
    echo "ℹ Added email index (if not existed)\n";
}

echo "\n✓ Table fix completed!\n";
echo "</pre>";
