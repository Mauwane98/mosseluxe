<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Insert basic settings
$settings = [
    ['setting_key' => 'site_name', 'setting_value' => 'Mossé Luxe'],
    ['setting_key' => 'site_description', 'setting_value' => 'Premium luxury streetwear'],
    ['setting_key' => 'site_email', 'setting_value' => 'info@mosseluxe.com'],
    ['setting_key' => 'whatsapp_enabled', 'setting_value' => '1'],
    ['setting_key' => 'whatsapp_number', 'setting_value' => '+27821234567'],
    ['setting_key' => 'whatsapp_general_message', 'setting_value' => 'Hi! I\'d like to inquire about your products.'],
    ['setting_key' => 'whatsapp_product_message', 'setting_value' => 'Hi! I\'m interested in %PRODUCT_NAME%. Can you tell me more about it?'],
    ['setting_key' => 'shop_title', 'setting_value' => 'Shop - Mossé Luxe'],
    ['setting_key' => 'shop_h1_title', 'setting_value' => 'Premium Collection'],
    ['setting_key' => 'shop_sub_title', 'setting_value' => 'Discover our curated collection of luxury streetwear.'],
    ['setting_key' => 'hero_buttons_enabled', 'setting_value' => '1'],
    ['setting_key' => 'footer_company_title', 'setting_value' => 'Company'],
    ['setting_key' => 'footer_help_title', 'setting_value' => 'Help'],
    ['setting_key' => 'footer_legal_title', 'setting_value' => 'Legal'],
    ['setting_key' => 'footer_follow_title', 'setting_value' => 'Follow Us']
];

$stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");

foreach ($settings as $setting) {
    $stmt->bind_param("ss",
        $setting['setting_key'],
        $setting['setting_value']
    );

    if ($stmt->execute()) {
        echo "Inserted setting: {$setting['setting_key']}\n";
    } else {
        echo "Error: " . $stmt->error . "\n";
    }
}

$stmt->close();

echo "Basic settings added.\n";

$conn->close();
?>
