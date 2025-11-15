<?php
/**
 * Database migration: Add user_carts table for persistent shopping carts
 *
 * This table allows logged-in users to have persistent carts across sessions.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

echo "Starting database migration: Add user_carts table\n";

// Check if table already exists
$table_exists = $conn->query("SHOW TABLES LIKE 'user_carts'");
if ($table_exists->num_rows > 0) {
    echo "Table 'user_carts' already exists. Skipping migration.\n";
    $conn->close();
    exit;
}

// Create user_carts table
$sql = "CREATE TABLE `user_carts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_user_carts_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_carts_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'user_carts' created successfully.\n";

    // Create index for better performance
    $index_sql = "CREATE INDEX idx_user_carts_updated_at ON user_carts(updated_at);";
    if ($conn->query($index_sql) === TRUE) {
        echo "✅ Index created on user_carts(updated_at).\n";
    } else {
        echo "⚠️ Warning: Could not create index: " . $conn->error . "\n";
    }

} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

$conn->close();
echo "Database migration completed.\n";
?>
