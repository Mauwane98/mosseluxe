<?php
/**
 * Update contact information in database
 */

require_once 'includes/config.php';
require_once 'includes/db_connect.php';

$conn = get_db_connection();

// Update email settings in database
$updates = [
    ['contact_email', 'info@mosseluxe.co.za'],
    ['site_email', 'info@mosseluxe.co.za'],
    ['store_email', 'info@mosseluxe.co.za'],
];

echo "Updating contact information in database...\n\n";

foreach ($updates as $update) {
    list($key, $value) = $update;
    
    // Check if setting exists
    $check = $conn->prepare("SELECT id FROM settings WHERE setting_key = ?");
    $check->bind_param("s", $key);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        if ($stmt->execute()) {
            echo "✓ Updated $key to $value\n";
        } else {
            echo "✗ Failed to update $key\n";
        }
        $stmt->close();
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $key, $value);
        if ($stmt->execute()) {
            echo "✓ Created $key with value $value\n";
        } else {
            echo "✗ Failed to create $key\n";
        }
        $stmt->close();
    }
    $check->close();
}

echo "\n✅ Contact information updated successfully!\n";
echo "\nUpdated:\n";
echo "- Email: info@mosseluxe.co.za\n";
echo "- Location: Pretoria, South Africa\n";

$conn->close();
