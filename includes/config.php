<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// Database credentials
define('DB_SERVER', getenv('DB_SERVER') ?: 'localhost');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', '');
define('DB_NAME', getenv('DB_NAME') ?: 'mosse_luxe_db');

// Database credentials// PayFast Configuration (Sandbox details)
define('PAYFAST_MERCHANT_ID', getenv('PAYFAST_MERCHANT_ID') ?: '10000100');
define('PAYFAST_MERCHANT_KEY', getenv('PAYFAST_MERCHANT_KEY') ?: '46f0cd694581a');
define('PAYFAST_PASSPHRASE', getenv('PAYFAST_PASSPHRASE') ?: 'payfast_passphrase');
define('PAYFAST_URL', 'https://sandbox.payfast.co.za/eng/process'); // Sandbox URL
define('PAYFAST_VALIDATE_URL', 'https://sandbox.payfast.co.za/eng/query/validate'); // Sandbox ITN validation URL

// Shipping Cost
define('SHIPPING_COST', 100.00); // Example shipping cost in ZAR

// --- Image Upload Configuration ---
define('PRODUCT_IMAGE_WIDTH', 800);
define('PRODUCT_IMAGE_HEIGHT', 800);

// --- PHPMailer SMTP Configuration ---
// These details are used for sending emails from the website (e.g., password resets).
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'mail.mosseluxe.co.za');
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: 'info@mosseluxe.co.za');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 465);
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'ssl');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'info@mosseluxe.co.za');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Mossé Luxe');

// --- Contact & Business Information ---
define('CONTACT_PHONE', '067 616 0928');
define('CONTACT_ADDRESS', 'Pretoria, South Africa');

?>
