<?php
/**
 * Database migration: Add returns table for return request management
 *
 * This table allows customers to submit return requests and admins to process them.
 */

require_once '../includes/bootstrap.php';

$conn = get_db_connection();

echo "Starting database migration: Add returns table\n";

// Check if table already exists
$table_exists = $conn->query("SHOW TABLES LIKE 'returns'");
if ($table_exists->num_rows > 0) {
    echo "Table 'returns' already exists. Skipping migration.\n";
    $conn->close();
    exit;
}

// Create returns table
$sql = "CREATE TABLE `returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `return_reason` varchar(100) NOT NULL,
  `customer_notes` text,
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Approved','Rejected','Refunded') NOT NULL DEFAULT 'Pending',
  `admin_notes` text,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_returns_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_returns_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'returns' created successfully.\n";

    // Create index for better performance
    $index_sql = "CREATE INDEX idx_returns_created_at ON returns(created_at);";
    if ($conn->query($index_sql) === TRUE) {
        echo "✅ Index created on returns(created_at).\n";
    } else {
        echo "⚠️ Warning: Could not create index: " . $conn->error . "\n";
    }

} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

$conn->close();
echo "Database migration completed.\n";
?>
