<?php
require_once __DIR__ . '/../includes/bootstrap.php';

function createReferralTable() {
    $conn = get_db_connection();

    $sql = "CREATE TABLE IF NOT EXISTS `user_referrals` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `referrer_id` int(11) NOT NULL,
        `referee_id` int(11) DEFAULT NULL,
        `referral_code` varchar(50) NOT NULL UNIQUE,
        `referral_link` varchar(255) NOT NULL,
        `referee_email` varchar(255) DEFAULT NULL,
        `status` enum('pending','registered','completed_order','expired') DEFAULT 'pending',
        `referrer_discount_applied` tinyint(1) DEFAULT 0,
        `referee_discount_applied` tinyint(1) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_referrer_id` (`referrer_id`),
        INDEX `idx_referral_code` (`referral_code`),
        INDEX `idx_referee_id` (`referee_id`),
        FOREIGN KEY (`referrer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`referee_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Referral table created successfully.\n";
        return true;
    } else {
        echo "❌ Error creating referral table: " . $conn->error . "\n";
        return false;
    }
}

function createReferralDiscountCodesTable() {
    $conn = get_db_connection();

    $sql = "CREATE TABLE IF NOT EXISTS `referral_discount_codes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `referral_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `discount_code` varchar(20) NOT NULL UNIQUE,
        `type` enum('percentage','fixed') NOT NULL,
        `value` decimal(10,2) NOT NULL,
        `used` tinyint(1) DEFAULT 0,
        `expires_at` datetime DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_referral_id` (`referral_id`),
        INDEX `idx_discount_code` (`discount_code`),
        FOREIGN KEY (`referral_id`) REFERENCES `user_referrals`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Referral discount codes table created successfully.\n";
        return true;
    } else {
        echo "❌ Error creating referral discount codes table: " . $conn->error . "\n";
        return false;
    }
}

if ($argc > 1 && $argv[1] === 'run') {
    if (createReferralTable() && createReferralDiscountCodesTable()) {
        echo "🎉 All referral tables created successfully!\n";
    } else {
        echo "💥 Failed to create some referral tables.\n";
        exit(1);
    }
} else {
    die("This script should be run with 'run' parameter: php create_referral_table.php run\n");
}
?>
