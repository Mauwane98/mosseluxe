<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Check if wishlists table exists
$result = $conn->query("SHOW TABLES LIKE 'wishlists'");
if ($result->num_rows > 0) {
    echo "✅ wishlists table exists\n";
} else {
    echo "❌ wishlists table missing - creating now...\n";
    
    // Create wishlists table (without foreign keys to avoid issues)
    $sql = "CREATE TABLE IF NOT EXISTS wishlists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_wishlist (user_id, product_id),
        INDEX idx_user_id (user_id),
        INDEX idx_product_id (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql)) {
        echo "✅ wishlists table created successfully\n";
    } else {
        echo "❌ Error creating table: " . $conn->error . "\n";
    }
}

$conn->close();
echo "\nDone!";
?>
