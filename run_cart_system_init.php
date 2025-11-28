<?php
// Cart System Initialization Script
// Creates necessary database tables and ensures proper setup

require_once 'includes/bootstrap.php';

try {
    $conn = get_db_connection();

    echo "Initializing Cart System...\n";

    // 1. Drop existing user_carts table if it exists (to avoid foreign key issues)
    $conn->query("DROP TABLE IF EXISTS `user_carts`");

    // 1. Create user_carts table for logged-in user persistence
    $create_user_carts_sql = "
        CREATE TABLE `user_carts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `quantity` int(11) NOT NULL DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_product` (`user_id`, `product_id`),
            KEY `user_id` (`user_id`),
            KEY `product_id` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    if ($conn->query($create_user_carts_sql)) {
        echo "✓ user_carts table created (without foreign keys - handled programmatically)\n";
    } else {
        echo "✗ Error creating user_carts table: " . $conn->error . "\n";
    }

    // 2. Create coupon_codes table for shopping cart discounts
    $create_coupons_sql = "
        CREATE TABLE IF NOT EXISTS `coupon_codes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(50) NOT NULL,
            `description` varchar(255) DEFAULT NULL,
            `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
            `discount_value` decimal(10,2) NOT NULL,
            `min_order_amount` decimal(10,2) DEFAULT NULL,
            `max_discount_amount` decimal(10,2) DEFAULT NULL,
            `usage_limit` int(11) DEFAULT NULL,
            `usage_count` int(11) DEFAULT 0,
            `start_date` datetime DEFAULT NULL,
            `end_date` datetime DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`),
            KEY `is_active` (`is_active`),
            KEY `start_end_date` (`start_date`, `end_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    if ($conn->query($create_coupons_sql)) {
        echo "✓ coupon_codes table created\n";
    } else {
        echo "✗ Error creating coupon_codes table: " . $conn->error . "\n";
    }

    // 3. Create order_carts table for checkout cart persistence (no foreign keys)
    $create_order_carts_sql = "
        CREATE TABLE IF NOT EXISTS `order_carts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `session_id` varchar(255) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `product_id` int(11) NOT NULL,
            `variant_id` int(11) DEFAULT NULL,
            `quantity` int(11) NOT NULL,
            `price` decimal(10,2) NOT NULL,
            `total` decimal(10,2) NOT NULL,
            `added_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `session_id` (`session_id`),
            KEY `user_id` (`user_id`),
            KEY `product_id` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    if ($conn->query($create_order_carts_sql)) {
        echo "✓ order_carts table created\n";
    } else {
        echo "✗ Error creating order_carts table: " . $conn->error . "\n";
    }

    // 4. Drop existing cart_sessions table if it exists (to avoid tablespace issues)
    $conn->query("DROP TABLE IF EXISTS `cart_sessions`");

    // 4. Create cart_sessions table for abandoned cart recovery
    $create_cart_sessions_sql = "
        CREATE TABLE `cart_sessions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `session_id` varchar(255) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `cart_data` longtext,
            `last_activity` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text,
            PRIMARY KEY (`id`),
            UNIQUE KEY `session_id` (`session_id`),
            KEY `user_id` (`user_id`),
            KEY `last_activity` (`last_activity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    if ($conn->query($create_cart_sessions_sql)) {
        echo "✓ cart_sessions table created\n";
    } else {
        echo "✗ Error creating cart_sessions table: " . $conn->error . "\n";
    }

    // 5. Clean up old/unused cart data (older than 30 days)
    $cleanup_sql = "DELETE FROM user_carts WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    if ($conn->query($cleanup_sql)) {
        echo "✓ Cleaned up old user cart data\n";
    }

    // 6. Insert some sample coupon codes for testing
    $insert_coupons_sql = "
        INSERT IGNORE INTO coupon_codes (code, description, discount_type, discount_value, min_order_amount, usage_limit, start_date, end_date) VALUES
        ('WELCOME10', 'Welcome discount for new customers', 'percentage', 10.00, 200.00, 100, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR)),
        ('SAVE50', 'Save R50 on orders over R500', 'fixed', 50.00, 500.00, NULL, NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH)),
        ('FLASH20', 'Flash sale 20% off', 'percentage', 20.00, 100.00, 50, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY));
    ";

    if ($conn->query($insert_coupons_sql)) {
        echo "✓ Sample coupon codes added\n";
    }

    echo "\nCart system initialization completed successfully!\n";

} catch (Exception $e) {
    echo "Error during cart system initialization: " . $e->getMessage() . "\n";
}
?>
