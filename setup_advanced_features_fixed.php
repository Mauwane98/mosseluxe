<?php
/**
 * Advanced Features Database Setup - FIXED VERSION
 * Creates tables for: Reviews, Related Products, Abandoned Carts, Analytics, etc.
 */

require_once 'includes/bootstrap.php';

$conn = get_db_connection();

echo "<h1>Setting up Advanced Features...</h1>";

// Check if required tables exist
$required_tables = ['products', 'users', 'orders', 'order_items'];
$missing_tables = [];

foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "<div style='background: #fee; padding: 20px; margin: 20px 0; border: 2px solid #c00;'>";
    echo "<h2 style='color: #c00;'>⚠️ Warning: Missing Required Tables</h2>";
    echo "<p>The following tables are required but don't exist:</p>";
    echo "<ul>";
    foreach ($missing_tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    echo "<p>Some features may not work correctly. Please ensure your database is properly set up.</p>";
    echo "</div>";
}

// 1. Product Reviews Table (NO FOREIGN KEYS)
$sql_reviews = "CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NULL,
    guest_name VARCHAR(100) NULL,
    guest_email VARCHAR(255) NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title VARCHAR(255) NOT NULL,
    review_text TEXT NOT NULL,
    verified_purchase BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_reviews)) {
    echo "<p>✓ Product Reviews table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 2. Review Photos Table
$sql_review_photos = "CREATE TABLE IF NOT EXISTS review_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    photo_path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_review (review_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_review_photos)) {
    echo "<p>✓ Review Photos table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 3. Related Products Table
$sql_related = "CREATE TABLE IF NOT EXISTS related_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    related_product_id INT NOT NULL,
    relation_type ENUM('related', 'upsell', 'cross_sell', 'bundle') DEFAULT 'related',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_relation (product_id, related_product_id, relation_type),
    INDEX idx_product (product_id),
    INDEX idx_related (related_product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_related)) {
    echo "<p>✓ Related Products table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 4. Abandoned Carts Table
$sql_abandoned = "CREATE TABLE IF NOT EXISTS abandoned_carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    cart_data JSON NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    recovery_token VARCHAR(64) UNIQUE,
    recovery_email_sent BOOLEAN DEFAULT FALSE,
    recovery_email_sent_at DATETIME NULL,
    recovered BOOLEAN DEFAULT FALSE,
    recovered_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_abandoned)) {
    echo "<p>✓ Abandoned Carts table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 5. Recently Viewed Products Table
$sql_recently_viewed = "CREATE TABLE IF NOT EXISTS recently_viewed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(255) NOT NULL,
    product_id INT NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_recently_viewed)) {
    echo "<p>✓ Recently Viewed table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 6. Product Attributes Table
$sql_attributes = "CREATE TABLE IF NOT EXISTS product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    attribute_name VARCHAR(100) NOT NULL,
    attribute_value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_name (attribute_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_attributes)) {
    echo "<p>✓ Product Attributes table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 7. Loyalty Points Table
$sql_loyalty = "CREATE TABLE IF NOT EXISTS loyalty_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT DEFAULT 0,
    lifetime_points INT DEFAULT 0,
    tier ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_loyalty)) {
    echo "<p>✓ Loyalty Points table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 8. Loyalty Transactions Table
$sql_loyalty_trans = "CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL,
    transaction_type ENUM('earned', 'redeemed', 'expired', 'adjusted') NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_loyalty_trans)) {
    echo "<p>✓ Loyalty Transactions table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 9. Flash Sales Table
$sql_flash_sales = "CREATE TABLE IF NOT EXISTS flash_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    original_price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NOT NULL,
    discount_percentage INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    quantity_limit INT NULL,
    quantity_sold INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_flash_sales)) {
    echo "<p>✓ Flash Sales table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 10. Product Comparisons Table
$sql_comparisons = "CREATE TABLE IF NOT EXISTS product_comparisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(255) NOT NULL,
    product_ids JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_comparisons)) {
    echo "<p>✓ Product Comparisons table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 11. Analytics Events Table
$sql_analytics = "CREATE TABLE IF NOT EXISTS analytics_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    user_id INT NULL,
    session_id VARCHAR(255) NOT NULL,
    product_id INT NULL,
    category_id INT NULL,
    event_data JSON NULL,
    page_url VARCHAR(500) NULL,
    referrer VARCHAR(500) NULL,
    user_agent VARCHAR(500) NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    INDEX idx_product (product_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_analytics)) {
    echo "<p>✓ Analytics Events table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 12. Size Guide Table
$sql_size_guide = "CREATE TABLE IF NOT EXISTS size_guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NULL,
    category_id INT NULL,
    guide_type ENUM('chart', 'quiz', 'measurement') DEFAULT 'chart',
    guide_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_size_guide)) {
    echo "<p>✓ Size Guide table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 13. Newsletter Subscribers Table
$sql_newsletter = "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(100) NULL,
    status ENUM('subscribed', 'unsubscribed', 'bounced') DEFAULT 'subscribed',
    subscription_source VARCHAR(50) DEFAULT 'website',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at DATETIME NULL,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_newsletter)) {
    echo "<p>✓ Newsletter Subscribers table created</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// Add rating columns to products table if not exists
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS average_rating DECIMAL(3,2) DEFAULT 0.00");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS review_count INT DEFAULT 0");
$conn->query("ALTER TABLE products ADD INDEX IF NOT EXISTS idx_rating (average_rating)");
echo "<p>✓ Added rating columns to products table</p>";

echo "<h2 style='color: green;'>✅ Database setup complete!</h2>";
echo "<p><strong>13 tables created successfully</strong></p>";
echo "<p><a href='index.php' style='background: #000; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Homepage</a></p>";
echo "<p><a href='product/26/test-product' style='background: #000; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Test Reviews on Product Page</a></p>";

$conn->close();
?>
