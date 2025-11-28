<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Create new_arrivals table
$sql = "CREATE TABLE IF NOT EXISTS `new_arrivals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 1,
  `release_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`),
  KEY `display_order` (`display_order`),
  KEY `release_date` (`release_date`),
  CONSTRAINT `fk_new_arrivals_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $conn->query($sql);
    echo "Created new_arrivals table\n";
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}

// Insert default settings
$sql2 = "INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('new_arrivals_message', 'New arrivals will be available soon. Please check back later.'),
('new_arrivals_display_count', '4')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)";

try {
    $conn->query($sql2);
    echo "Added default settings\n";
} catch (Exception $e) {
    echo "Error inserting settings: " . $e->getMessage() . "\n";
}

// Check if new_arrivals table exists
$result = $conn->query("SHOW TABLES LIKE 'new_arrivals'");
if ($result->num_rows > 0) {
    echo "new_arrivals table exists\n";

    // Check table structure
    $result = $conn->query("DESCRIBE new_arrivals");
    echo "Table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} - {$row['Type']}\n";
    }
} else {
    echo "new_arrivals table does not exist\n";
}

// Check settings
$result = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('new_arrivals_message', 'new_arrivals_display_count')");
echo "\nSettings:\n";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "{$row['setting_key']}: {$row['setting_value']}\n";
    }
} else {
    echo "No new arrivals settings found\n";
}
?>
