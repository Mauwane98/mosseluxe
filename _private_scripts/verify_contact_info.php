<?php
/**
 * Verify Contact Information Configuration
 */

require_once __DIR__ . '/../includes/bootstrap.php';

echo "===========================================\n";
echo "CONTACT INFORMATION VERIFICATION\n";
echo "===========================================\n\n";

// Check constants
echo "📋 CONSTANTS:\n";
echo "  CONTACT_PHONE: " . (defined('CONTACT_PHONE') ? CONTACT_PHONE : 'NOT SET') . "\n";
echo "  WHATSAPP_NUMBER: " . (defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : 'NOT SET') . "\n";
echo "  CONTACT_EMAIL: " . (defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'NOT SET') . "\n";
echo "  CONTACT_ADDRESS: " . (defined('CONTACT_ADDRESS') ? CONTACT_ADDRESS : 'NOT SET') . "\n\n";

// Check database settings
echo "💾 DATABASE SETTINGS:\n";
$conn = get_db_connection();

$settings_to_check = [
    'whatsapp_enabled',
    'whatsapp_number',
    'whatsapp_general_message',
    'whatsapp_product_message',
    'whatsapp_order_message',
    'whatsapp_size_message',
    'contact_phone',
    'contact_email'
];

foreach ($settings_to_check as $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $value = $row['setting_value'];
        
        // Truncate long messages
        if (strlen($value) > 60) {
            $value = substr($value, 0, 60) . '...';
        }
        
        echo "  $key: $value\n";
    } else {
        echo "  $key: [NOT SET]\n";
    }
    $stmt->close();
}

echo "\n";
echo "===========================================\n";
echo "VERIFICATION COMPLETE\n";
echo "===========================================\n\n";

echo "✅ Your contact information:\n";
echo "   📞 Phone/WhatsApp: 067 616 2809 / +27676162809\n";
echo "   📧 Email: info@mosseluxe.co.za\n";
echo "   📍 Location: Pretoria, South Africa\n\n";

echo "🔗 WhatsApp Links:\n";
echo "   Click to Chat: https://wa.me/27676162809\n";
echo "   With Message: https://wa.me/27676162809?text=Hi%20Mossé%20Luxe!\n\n";

$conn->close();
?>
