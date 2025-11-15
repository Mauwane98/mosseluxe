<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

$page_titles = [
    ['setting_key' => 'shop_title', 'setting_value' => 'Shop'],
    ['setting_key' => 'shop_h1_title', 'setting_value' => 'All Products'],
    ['setting_key' => 'shop_sub_title', 'setting_value' => 'Discover our curated collection of luxury streetwear, crafted with precision and passion.'],
    ['setting_key' => 'about_title', 'setting_value' => 'About Us'],
    ['setting_key' => 'contact_title', 'setting_value' => 'Contact Us'],
    ['setting_key' => 'faq_title', 'setting_value' => 'Frequently Asked Questions'],
    // Add more as needed
];

$stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
foreach ($page_titles as $title) {
    $stmt->bind_param("ss", $title['setting_key'], $title['setting_value']);
    $stmt->execute();
}
$stmt->close();
$conn->close();

echo "Page titles added or updated in settings.\n";
?>
