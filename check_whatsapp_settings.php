<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$result = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'whatsapp_%'");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo $row['setting_key'] . ' = ' . $row['setting_value'] . "\n";
    }
} else {
    echo "No WhatsApp settings found in database.\n";
}

// Check if WhatsApp enabled setting exists
$check_enabled = $conn->query("SELECT COUNT(*) as count FROM settings WHERE setting_key = 'whatsapp_enabled'");
$row = $check_enabled->fetch_assoc();
if ($row['count'] == 0) {
    echo "WhatsApp enabled setting missing. Adding default settings...\n";

    $settings = [
        ['whatsapp_enabled', '1'],
        ['whatsapp_number', '+27676162809'],
        ['whatsapp_general_message', 'Hi! I\'m interested in your luxury streetwear collection. Can you tell me more about your latest arrivals?'],
        ['whatsapp_order_message', 'Hi! I need help with my order. Can you assist me with shipping details or order tracking?'],
        ['whatsapp_size_message', 'Hi! I\'m not sure about the sizing for your leather goods. Can you help me find the perfect fit and share your size guide?']
    ];

    foreach ($settings as $setting) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $setting[0], $setting[1]);
        $stmt->execute();
        $stmt->close();
        echo "Added: {$setting[0]} = {$setting[1]}\n";
    }
}
?>
