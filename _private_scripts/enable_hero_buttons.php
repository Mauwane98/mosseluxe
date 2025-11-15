<?php
include_once dirname(__DIR__) . '/includes/bootstrap.php';

$conn = get_db_connection();

$stmt = $conn->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?');
$key = 'hero_buttons_enabled';
$value = '1';
$stmt->bind_param('sss', $key, $value, $value);

echo $stmt->execute() ? '✅ Hero buttons enabled!' : '❌ Failed to enable hero buttons';

$conn->close();
?>
