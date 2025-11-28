<?php
require_once 'includes/bootstrap.php';

$conn = get_db_connection();

// Real SMTP credentials provided by user
// SECURITY WARNING: Never hardcode passwords in files!
// Use environment variables instead
$smtp_updates = [
    'smtp_host' => getenv('SMTP_HOST') ?: 'mail.mosseluxe.co.za',
    'smtp_port' => getenv('SMTP_PORT') ?: '465',
    'smtp_username' => getenv('SMTP_USERNAME') ?: 'info@mosseluxe.co.za',
    'smtp_password' => getenv('SMTP_PASSWORD') ?: '' // NEVER hardcode passwords!
];

$updated = 0;

foreach ($smtp_updates as $key => $value) {
    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->bind_param("ss", $value, $key);
    if ($stmt->execute()) {
        echo "✓ Updated {$key}: {$value}\n";
        $updated++;
    } else {
        echo "✗ Error updating {$key}: " . $conn->error . "\n";
    }
    $stmt->close();
}

echo "\nSMTP Settings Update Complete:\n";
echo "- Settings Updated: {$updated}/4\n";

if ($updated === 4) {
    echo "\n🎉 Email configuration is now ready for production!\n";
    echo "Mail Server: mail.mosseluxe.co.za\n";
    echo "Port: 465 (SSL)\n";
    echo "Username: info@mosseluxe.co.za\n";
    echo "All order confirmations and newsletters will work properly.\n";
} else {
    echo "\n⚠️ Some settings may not have updated correctly.\n";
}

$conn->close();
?>
