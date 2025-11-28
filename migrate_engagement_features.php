<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$tables_to_create = [
    'price_alerts' => "CREATE TABLE IF NOT EXISTS `price_alerts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `alert_price` decimal(10,2) NOT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `last_notified_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_product_alert` (`user_id`,`product_id`),
        KEY `product_id` (`product_id`),
        KEY `is_active` (`is_active`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    'back_in_stock_alerts' => "CREATE TABLE IF NOT EXISTS `back_in_stock_alerts` (
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
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    'loyalty_points' => "CREATE TABLE IF NOT EXISTS `loyalty_points` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `points` int(11) NOT NULL DEFAULT 0,
        `total_earned` int(11) NOT NULL DEFAULT 0,
        `total_spent` int(11) NOT NULL DEFAULT 0,
        `tier` enum('bronze','silver','gold','platinum') NOT NULL DEFAULT 'bronze',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_id` (`user_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    'loyalty_transactions' => "CREATE TABLE IF NOT EXISTS `loyalty_transactions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `transaction_type` enum('earned','spent','expired') NOT NULL,
        `points` int(11) NOT NULL,
        `description` varchar(255) NOT NULL,
        `reference_id` varchar(100) DEFAULT NULL,
        `expires_at` datetime DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `transaction_type` (`transaction_type`),
        KEY `expires_at` (`expires_at`),
        KEY `reference_id` (`reference_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    'loyalty_settings' => "CREATE TABLE IF NOT EXISTS `loyalty_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting_key` varchar(100) NOT NULL,
        `setting_value` text,
        `description` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_key` (`setting_key`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
];

$errors = [];
$successes = [];

foreach ($tables_to_create as $table_name => $sql) {
    if ($conn->query($sql) === TRUE) {
        $successes[] = "Table '$table_name' created or already exists";
    } else {
        $errors[] = "Error creating table '$table_name': " . $conn->error;
    }
}

// Initialize default loyalty settings
$default_settings = [
    [
        'setting_key' => 'points_per_r100',
        'setting_value' => '10',
        'description' => 'Points earned per R100 spent'
    ],
    [
        'setting_key' => 'points_signup',
        'setting_value' => '100',
        'description' => 'Points earned for signing up'
    ],
    [
        'setting_key' => 'points_review',
        'setting_value' => '50',
        'description' => 'Points earned for leaving a product review'
    ],
    [
        'setting_key' => 'points_social_share',
        'setting_value' => '25',
        'description' => 'Points earned for sharing on social media'
    ],
    [
        'setting_key' => 'points_expiry_days',
        'setting_value' => '365',
        'description' => 'Days until points expire'
    ],
    [
        'setting_key' => 'tier_bronze_min',
        'setting_value' => '0',
        'description' => 'Minimum points for Bronze tier'
    ],
    [
        'setting_key' => 'tier_silver_min',
        'setting_value' => '500',
        'description' => 'Minimum points for Silver tier'
    ],
    [
        'setting_key' => 'tier_gold_min',
        'setting_value' => '1500',
        'description' => 'Minimum points for Gold tier'
    ],
    [
        'setting_key' => 'tier_platinum_min',
        'setting_value' => '5000',
        'description' => 'Minimum points for Platinum tier'
    ]
];

foreach ($default_settings as $setting) {
    $check_sql = "SELECT id FROM loyalty_settings WHERE setting_key = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $setting['setting_key']);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows == 0) {
        $insert_sql = "INSERT INTO loyalty_settings (setting_key, setting_value, description) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sss", $setting['setting_key'], $setting['setting_value'], $setting['description']);
        if ($insert_stmt->execute()) {
            $successes[] = "Default loyalty setting '{$setting['setting_key']}' initialized";
        } else {
            $errors[] = "Error initializing setting '{$setting['setting_key']}': " . $conn->error;
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

echo "Database migration completed!\n\n";

if (!empty($successes)) {
    echo "SUCCESSFUL OPERATIONS:\n";
    foreach ($successes as $success) {
        echo "✓ $success\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "ERRORS:\n";
    foreach ($errors as $error) {
        echo "✗ $error\n";
    }
}

echo "\nMigration script completed.\n";
?>
