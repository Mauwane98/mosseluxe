<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

echo "Creating missing tables needed for product page...\n";

// Create users table first (referenced by product_reviews)
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT,
    status TINYINT(1) DEFAULT 1,
    email_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email (email)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "✅ Users table created\n";
} else {
    echo "❌ Error creating users: " . $conn->error . "\n";
}

// Create product_reviews table
$sql = "CREATE TABLE IF NOT EXISTS product_reviews (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    rating TINYINT(1) NOT NULL,
    review_text TEXT DEFAULT NULL,
    review_photos JSON DEFAULT NULL COMMENT 'JSON array of photo URLs',
    verified_purchase TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Verified purchaser',
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_product_review (user_id, product_id),
    KEY product_id (product_id),
    KEY verified_purchase (verified_purchase)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "✅ Product reviews table created\n";
} else {
    echo "❌ Error creating product_reviews: " . $conn->error . "\n";
}

echo "Missing tables creation complete!\n";
?>
