<?php
// Ensure UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', (defined('APP_ENV') && APP_ENV === 'development') ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// Database credentials
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'mosse_luxe_db');

// Yoco Payment Gateway Configuration
if (!defined('YOCO_PUBLIC_KEY')) define('YOCO_PUBLIC_KEY', getenv('YOCO_PUBLIC_KEY') ?: 'pk_test_your_yoco_public_key_here');
if (!defined('YOCO_SECRET_KEY')) define('YOCO_SECRET_KEY', getenv('YOCO_SECRET_KEY') ?: 'sk_test_your_yoco_secret_key_here');
if (!defined('YOCO_WEBHOOK_SECRET')) define('YOCO_WEBHOOK_SECRET', getenv('YOCO_WEBHOOK_SECRET') ?: 'whsec_your_webhook_secret_here');
if (!defined('YOCO_MODE')) define('YOCO_MODE', getenv('YOCO_MODE') ?: 'sandbox'); // 'sandbox' or 'live'

// PayFast Payment Gateway Configuration
if (!defined('PAYFAST_MERCHANT_ID')) define('PAYFAST_MERCHANT_ID', getenv('PAYFAST_MERCHANT_ID') ?: 'your_payfast_merchant_id_here');
if (!defined('PAYFAST_MERCHANT_KEY')) define('PAYFAST_MERCHANT_KEY', getenv('PAYFAST_MERCHANT_KEY') ?: 'your_payfast_merchant_key_here');
if (!defined('PAYFAST_URL')) define('PAYFAST_URL', getenv('PAYFAST_URL') ?: 'https://sandbox.payfast.co.za/eng/process');
if (!defined('PAYFAST_VALIDATE_URL')) define('PAYFAST_VALIDATE_URL', getenv('PAYFAST_VALIDATE_URL') ?: 'https://sandbox.payfast.co.za/eng/query/validate');

// Define the absolute path to the project's root directory
if (!defined('ABSPATH')) define('ABSPATH', dirname(__DIR__));

// Define the base URL of your site dynamically
if (!defined('SITE_URL')) {
    // Detect protocol and host dynamically for both localhost and IP access
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = '/mosse-luxe/';

    // If host is localhost, explicitly use localhost (for consistency)
    if ($host === 'localhost' || str_starts_with($host, '127.')) {
        $host = 'localhost';
    }

    define('SITE_URL', $protocol . '://' . $host . $basePath);
}

// Define the application environment
if (!defined('APP_ENV')) define('APP_ENV', getenv('APP_ENV') ?: 'production');

// Shipping Cost
if (!defined('SHIPPING_COST')) define('SHIPPING_COST', 100.00); // Example shipping cost in ZAR

// --- Image Upload Configuration ---
if (!defined('PRODUCT_IMAGE_WIDTH')) define('PRODUCT_IMAGE_WIDTH', 800);
if (!defined('PRODUCT_IMAGE_HEIGHT')) define('PRODUCT_IMAGE_HEIGHT', 800);
if (!defined('PRODUCT_IMAGE_DIR')) define('PRODUCT_IMAGE_DIR', 'assets/images/');

// --- Contact & Business Information ---
if (!defined('CONTACT_PHONE')) define('CONTACT_PHONE', '067 616 0928');
if (!defined('CONTACT_ADDRESS')) define('CONTACT_ADDRESS', 'Pretoria, South Africa');
if (!defined('CONTACT_EMAIL')) define('CONTACT_EMAIL', getenv('CONTACT_EMAIL') ?: 'info@mosse-luxe.com');

// --- SMTP/Email Configuration ---
if (!defined('SMTP_HOST')) define('SMTP_HOST', getenv('SMTP_HOST') ?: 'mail.mosseluxe.co.za');
if (!defined('SMTP_PORT')) define('SMTP_PORT', getenv('SMTP_PORT') ?: 465);
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'ssl'); // SSL for port 465
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: 'info@mosseluxe.co.za');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'info@mosseluxe.co.za');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Mossé Luxe');

?>
