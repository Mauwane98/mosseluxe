<?php
// Check full hero slide data to see button_text and button_url
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mosse_luxe_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Complete Hero Slides Data:\n";
$sql = "SELECT id, title, subtitle, button_text, button_url, button_style, is_active FROM hero_slides ORDER BY sort_order ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "  Title: '{$row['title']}' ({$row['button_style']})\n";
        echo "  Button Text: '{$row['button_text']}' ({$row['button_style']})\n";
        echo "  Button URL: '{$row['button_url']}' ({$row['button_style']})\n";
        echo "  Active: {$row['is_active']}\n";
        echo "  Button will show: " . ($row['button_text'] && $row['button_url'] ? 'YES' : 'NO - missing button_text or button_url') . "\n\n";
    }
} else {
    echo "No hero slides found\n";
}

// Check if hero buttons are globally enabled
echo "Global Hero Buttons Setting:\n";
$result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'hero_buttons_enabled'");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "hero_buttons_enabled: {$row['setting_value']} (1=true, 0=false)\n";
} else {
    echo "Setting not found - defaults to enabled\n";
}

echo "\nAdding button_visibility column if not exists...\n";
$alter_sql = "ALTER TABLE hero_slides ADD COLUMN button_visibility TINYINT(1) DEFAULT 1";
if ($conn->query($alter_sql) === TRUE) {
    echo "Column button_visibility added successfully\n";
} else {
    if (strpos($conn->error, "Duplicate column") !== false) {
        echo "Column button_visibility already exists\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}

echo "\nUpdating button_visibility to 1 for existing slides where button_text and button_url are not empty...\n";
$update_sql = "UPDATE hero_slides SET button_visibility = 1 WHERE button_text IS NOT NULL AND button_text != '' AND button_url IS NOT NULL AND button_url != ''";
if ($conn->query($update_sql) === TRUE) {
    echo "Updated " . $conn->affected_rows . " rows\n";
} else {
    echo "Error updating: " . $conn->error . "\n";
}

$conn->close();
?>
