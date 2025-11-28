<?php
/**
 * Update Contact Information in Database
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

echo "===========================================\n";
echo "UPDATING CONTACT INFORMATION\n";
echo "===========================================\n\n";

// Contact information to update
$updates = [
    'whatsapp_number' => '+27676162809',
    'contact_phone' => '067 616 2809',
    'contact_email' => 'info@mosseluxe.co.za',
    'whatsapp_enabled' => '1'
];

$updated = 0;
$created = 0;

foreach ($updates as $key => $value) {
    // Check if setting exists
    $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing
        $stmt->close();
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        
        if ($stmt->execute()) {
            echo "✅ Updated: $key = $value\n";
            $updated++;
        } else {
            echo "❌ Failed to update: $key\n";
        }
    } else {
        // Create new
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $key, $value);
        
        if ($stmt->execute()) {
            echo "✅ Created: $key = $value\n";
            $created++;
        } else {
            echo "❌ Failed to create: $key\n";
        }
    }
    
    $stmt->close();
}

echo "\n===========================================\n";
echo "UPDATE COMPLETE\n";
echo "===========================================\n\n";

echo "📊 Summary:\n";
echo "   Updated: $updated settings\n";
echo "   Created: $created settings\n\n";

echo "✅ Your contact information is now:\n";
echo "   📞 Phone: 067 616 2809\n";
echo "   📱 WhatsApp: +27676162809\n";
echo "   📧 Email: info@mosseluxe.co.za\n";
echo "   📍 Location: Pretoria, South Africa\n\n";

echo "🔗 Test WhatsApp link:\n";
echo "   https://wa.me/27676162809?text=Hi%20Mossé%20Luxe!\n\n";

$conn->close();
?>
