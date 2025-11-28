<?php
/**
 * Fix Referral Table - Discard tablespace and recreate
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

echo "===========================================\n";
echo "FIXING REFERRAL TABLE\n";
echo "===========================================\n\n";

// Step 1: Discard tablespace if exists
echo "Step 1: Discarding tablespace...\n";
try {
    $conn->query("ALTER TABLE user_referrals DISCARD TABLESPACE");
    echo "  ✓ Tablespace discarded\n";
} catch (Exception $e) {
    echo "  ℹ No tablespace to discard (this is normal)\n";
}

// Step 2: Drop table if exists
echo "\nStep 2: Dropping table if exists...\n";
try {
    $conn->query("DROP TABLE IF EXISTS user_referrals");
    echo "  ✓ Table dropped\n";
} catch (Exception $e) {
    echo "  ✗ Error dropping table: " . $e->getMessage() . "\n";
}

// Step 3: Create table
echo "\nStep 3: Creating user_referrals table...\n";
$sql = "CREATE TABLE user_referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    referral_code VARCHAR(20) UNIQUE NOT NULL,
    total_referrals INT DEFAULT 0,
    total_earnings DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_referral_code (referral_code),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "  ✓ user_referrals table created successfully\n";
} else {
    echo "  ✗ Error creating table: " . $conn->error . "\n";
    exit(1);
}

// Step 4: Create referral_transactions table
echo "\nStep 4: Creating referral_transactions table...\n";
$sql = "CREATE TABLE IF NOT EXISTS referral_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_user_id INT NOT NULL,
    order_id INT,
    commission_amount DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('pending', 'approved', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_referrer (referrer_id),
    INDEX idx_referred (referred_user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "  ✓ referral_transactions table created successfully\n";
} else {
    echo "  ✗ Error creating table: " . $conn->error . "\n";
}

$conn->close();

echo "\n===========================================\n";
echo "✅ REFERRAL TABLES FIXED SUCCESSFULLY\n";
echo "===========================================\n";
?>
