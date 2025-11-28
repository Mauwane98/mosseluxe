<?php
/**
 * Quick Email Configuration Verification
 */

require_once __DIR__ . '/includes/bootstrap.php';

echo "<h1>Email Configuration Status</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} table{border-collapse:collapse;margin:20px 0;} td,th{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f5f5f5;}</style>";

echo "<table>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$config = [
    'SMTP_HOST' => SMTP_HOST,
    'SMTP_PORT' => SMTP_PORT,
    'SMTP_SECURE' => SMTP_SECURE,
    'SMTP_USERNAME' => SMTP_USERNAME,
    'SMTP_PASSWORD' => !empty(SMTP_PASSWORD) ? '***CONFIGURED***' : 'NOT SET',
    'SMTP_FROM_EMAIL' => SMTP_FROM_EMAIL,
    'SMTP_FROM_NAME' => SMTP_FROM_NAME,
];

foreach ($config as $key => $value) {
    $status = '';
    if ($key === 'SMTP_PASSWORD') {
        $status = ($value === '***CONFIGURED***') ? '<span class="success">✓ OK</span>' : '<span class="error">✗ MISSING</span>';
    } else {
        $status = !empty($value) ? '<span class="success">✓ OK</span>' : '<span class="error">✗ EMPTY</span>';
    }
    echo "<tr><td><strong>$key</strong></td><td>$value</td><td>$status</td></tr>";
}

echo "</table>";

// Overall status
if (!empty(SMTP_PASSWORD) && !empty(SMTP_HOST) && !empty(SMTP_USERNAME)) {
    echo "<h2 class='success'>✓ Email Configuration is Complete!</h2>";
    echo "<p class='info'>Your email system should now be working. Test it by:</p>";
    echo "<ol>";
    echo "<li>Visiting <a href='test_email.php'>test_email.php</a> to send a test email</li>";
    echo "<li>Registering a new user account to test welcome emails</li>";
    echo "<li>Placing a test order to verify order confirmation emails</li>";
    echo "</ol>";
} else {
    echo "<h2 class='error'>✗ Email Configuration is Incomplete</h2>";
    echo "<p>Please check the missing values above.</p>";
}

echo "<hr>";
echo "<h3>Environment File Status:</h3>";
if (file_exists(__DIR__ . '/.env')) {
    echo "<p class='success'>✓ .env file exists</p>";
    echo "<p class='info'>Location: " . __DIR__ . "/.env</p>";
} else {
    echo "<p class='error'>✗ .env file not found</p>";
}

echo "<hr>";
echo "<p><a href='test_email.php'>→ Go to Email Testing Tool</a></p>";
?>
