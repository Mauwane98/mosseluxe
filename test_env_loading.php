<?php
require_once 'vendor/autoload.php';

echo "<h2>Testing .env Loading</h2>";
echo "<pre>";

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "After loading .env:\n";
echo "SMTP_PASSWORD from \$_ENV: " . ($_ENV['SMTP_PASSWORD'] ?? 'NOT SET') . "\n";
echo "SMTP_PASSWORD from \$_SERVER: " . ($_SERVER['SMTP_PASSWORD'] ?? 'NOT SET') . "\n";
echo "SMTP_PASSWORD from getenv(): " . (getenv('SMTP_PASSWORD') ?: 'NOT SET') . "\n";

echo "\n\nAll environment variables:\n";
print_r($_ENV);

echo "</pre>";
?>
