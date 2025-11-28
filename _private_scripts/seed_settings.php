<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

if (!$conn) {
    echo "No DB connection\n";
    exit;
}

$result = $conn->query("SHOW TABLES LIKE 'settings'");
if ($result->num_rows == 0) {
    echo "settings table doesn't exist\n";
    exit;
}

$default_settings = [
    ['setting_key' => 'shop_title', 'setting_value' => 'Shop'],
    ['setting_key' => 'shop_h1_title', 'setting_value' => 'All Products'],
    ['setting_key' => 'shop_sub_title', 'setting_value' => 'Discover our curated collection of luxury streetwear, crafted with precision and passion.'],
    ['setting_key' => 'site_name', 'setting_value' => 'Mossé Luxe'],
    ['setting_key' => 'site_description', 'setting_value' => 'Luxury streetwear for the modern individual'],
    ['setting_key' => 'contact_email', 'setting_value' => 'info@mosseluxe.com'],
    ['setting_key' => 'contact_phone', 'setting_value' => '+27 21 123 4567'],
];

echo "Inserting default settings...\n";

foreach ($default_settings as $setting) {
    $sql = "INSERT IGNORE INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $setting['setting_key'], $setting['setting_value']);

    if ($stmt->execute()) {
        echo "✓ Inserted or already exists: {$setting['setting_key']}\n";
    } else {
        echo "✗ Failed to insert: {$setting['setting_key']} - " . $stmt->error . "\n";
    }
    $stmt->close();
}

$conn->close();
echo "\nSettings seeding completed!\n";
?>
