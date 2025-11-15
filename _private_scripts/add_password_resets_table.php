<?php
/**
 * Database migration: Add password_resets table
 *
 * This table stores password reset tokens for the forgot password functionality.
 */

require_once '../includes/bootstrap.php';

$conn = get_db_connection();

echo "Starting database migration: Add password_resets table\n";

// Check if table already exists
$table_exists = $conn->query("SHOW TABLES LIKE 'password_resets'");
if ($table_exists->num_rows > 0) {
    echo "Table 'password_resets' already exists. Skipping migration.\n";
    $conn->close();
    exit;
}

// Create password_resets table
$sql = "CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `email` (`email`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'password_resets' created successfully.\n";

    // Create index for faster lookups
    $index_sql = "CREATE INDEX idx_password_resets_email_token ON password_resets(email, token);";
    if ($conn->query($index_sql) === TRUE) {
        echo "✅ Index created on password_resets(email, token).\n";
    } else {
        echo "⚠️ Warning: Could not create index: " . $conn->error . "\n";
    }

    // Add a cleanup job suggestion
    echo "\n📋 Suggested: Consider setting up a cron job to clean expired tokens:\n";
    echo "   DELETE FROM password_resets WHERE expires_at < NOW();\n";
    echo "   Run this daily to keep the table clean.\n\n";

} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

$conn->close();
echo "Database migration completed.\n";
?>
