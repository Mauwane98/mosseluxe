<?php
/**
 * Create missing cart table for persistent cart storage
 */

require_once 'includes/config.php';
require_once 'includes/db_connect.php';

$conn = get_db_connection();

// Create cart table without foreign key constraint for now
$sql = "CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `session_id` VARCHAR(255) DEFAULT NULL,
    `product_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `session_id` (`session_id`),
    KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "✓ Cart table created successfully\n";
} else {
    echo "✗ Error creating cart table: " . $conn->error . "\n";
}

$conn->close();
echo "\nDone!\n";
