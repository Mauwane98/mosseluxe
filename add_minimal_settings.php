<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Add some basic settings
$settings = [
    ['shop_title', 'Mossé Luxe Shop'],
    ['shop_h1_title', 'All Products'],
    ['shop_sub_title', 'Discover our curated collection']
];

foreach ($settings as $setting) {
    $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->bind_param("ss", $setting[0], $setting[1]);
    $stmt->execute();
    $stmt->close();
    echo "Added setting: {$setting[0]}\n";
}

echo "Settings completed.\n";
$conn->close();
?>
