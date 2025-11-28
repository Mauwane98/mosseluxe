<?php
// Prevent direct access to configuration file
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    http_response_code(403);
    die("Access denied");
}
// UTF-8 encoding and error reporting is now handled in bootstrap.php

// Database credentials
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'mosse_luxe_db');

// Define the absolute path to the project's root directory
if (!defined('ABSPATH')) define('ABSPATH', dirname(__DIR__));

// Define the base URL of your site dynamically
if (!defined('SITE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // For PHP built-in server, always use root path
    // For Apache with subdirectory, use /mosseluxe/
    if (php_sapi_name() === 'cli-server') {
        $basePath = '/';
    } else {
        $basePath = '/mosseluxe/';
    }

    define('SITE_URL', $protocol . '://' . $host . $basePath);
}

// Define the application environment
if (!defined('APP_ENV')) define('APP_ENV', $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development');

// Other constants - moved after SITE_URL to avoid dependency issues
// Yoco Payment Gateway Configuration
if (!defined('YOCO_PUBLIC_KEY')) define('YOCO_PUBLIC_KEY', $_ENV['YOCO_PUBLIC_KEY'] ?? getenv('YOCO_PUBLIC_KEY') ?: 'pk_test_your_yoco_public_key_here');
if (!defined('YOCO_SECRET_KEY')) define('YOCO_SECRET_KEY', $_ENV['YOCO_SECRET_KEY'] ?? getenv('YOCO_SECRET_KEY') ?: 'sk_test_your_yoco_secret_key_here');
if (!defined('YOCO_WEBHOOK_SECRET')) define('YOCO_WEBHOOK_SECRET', $_ENV['YOCO_WEBHOOK_SECRET'] ?? getenv('YOCO_WEBHOOK_SECRET') ?: 'whsec_your_webhook_secret_here');
if (!defined('YOCO_MODE')) define('YOCO_MODE', $_ENV['YOCO_MODE'] ?? getenv('YOCO_MODE') ?: 'sandbox');

// PayFast Payment Gateway Configuration
if (!defined('PAYFAST_MERCHANT_ID')) define('PAYFAST_MERCHANT_ID', $_ENV['PAYFAST_MERCHANT_ID'] ?? getenv('PAYFAST_MERCHANT_ID') ?: 'your_payfast_merchant_id_here');
if (!defined('PAYFAST_MERCHANT_KEY')) define('PAYFAST_MERCHANT_KEY', $_ENV['PAYFAST_MERCHANT_KEY'] ?? getenv('PAYFAST_MERCHANT_KEY') ?: 'your_payfast_merchant_key_here');
if (!defined('PAYFAST_URL')) define('PAYFAST_URL', $_ENV['PAYFAST_URL'] ?? getenv('PAYFAST_URL') ?: 'https://sandbox.payfast.co.za/eng/process');
if (!defined('PAYFAST_VALIDATE_URL')) define('PAYFAST_VALIDATE_URL', $_ENV['PAYFAST_VALIDATE_URL'] ?? getenv('PAYFAST_VALIDATE_URL') ?: 'https://sandbox.payfast.co.za/eng/query/validate');

// Shipping Configuration
if (!defined('SHIPPING_COST')) define('SHIPPING_COST', 100.00); // Standard delivery
if (!defined('PAXI_STANDARD_COST')) define('PAXI_STANDARD_COST', 59.95); // Paxi Standard (7-9 days)
if (!defined('PAXI_EXPRESS_COST')) define('PAXI_EXPRESS_COST', 109.95); // Paxi Express (3-5 days)
if (!defined('PAXI_COST')) define('PAXI_COST', PAXI_STANDARD_COST); // Default Paxi cost
if (!defined('PAXI_API_KEY')) define('PAXI_API_KEY', $_ENV['PAXI_API_KEY'] ?? getenv('PAXI_API_KEY') ?: ''); // Paxi API key
if (!defined('FREE_SHIPPING_THRESHOLD')) define('FREE_SHIPPING_THRESHOLD', 900.00); // Free shipping over R900

// Image Upload Configuration
if (!defined('PRODUCT_IMAGE_WIDTH')) define('PRODUCT_IMAGE_WIDTH', 800);
if (!defined('PRODUCT_IMAGE_HEIGHT')) define('PRODUCT_IMAGE_HEIGHT', 800);
if (!defined('PRODUCT_IMAGE_DIR')) define('PRODUCT_IMAGE_DIR', 'assets/images/');

// Contact & Business Information
if (!defined('CONTACT_PHONE')) define('CONTACT_PHONE', '067 616 2809');
if (!defined('WHATSAPP_NUMBER')) define('WHATSAPP_NUMBER', '+27676162809');
if (!defined('CONTACT_ADDRESS')) define('CONTACT_ADDRESS', 'Pretoria, South Africa');
if (!defined('CONTACT_EMAIL')) define('CONTACT_EMAIL', 'info@mosseluxe.co.za');

// Social Media Links
if (!defined('INSTAGRAM_URL')) define('INSTAGRAM_URL', 'https://www.instagram.com/mosseluxe/');
if (!defined('FACEBOOK_URL')) define('FACEBOOK_URL', 'https://www.facebook.com/mosseluxe');
if (!defined('TWITTER_URL')) define('TWITTER_URL', 'https://twitter.com/mosseluxe');
if (!defined('TIKTOK_URL')) define('TIKTOK_URL', 'https://www.tiktok.com/@mosseluxe');
if (!defined('YOUTUBE_URL')) define('YOUTUBE_URL', 'https://www.youtube.com/@mosseluxe');

// SMTP/Email Configuration
// IMPORTANT: Set SMTP_PASSWORD in .env file for production
if (!defined('SMTP_HOST')) define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'mail.mosseluxe.co.za');
if (!defined('SMTP_PORT')) define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 465);
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', $_ENV['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?: 'ssl');
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?: 'info@mosseluxe.co.za');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?: '');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL') ?: 'info@mosseluxe.co.za');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?: 'Mosse Luxe');

// Email debugging - set to true to log email errors
if (!defined('EMAIL_DEBUG')) define('EMAIL_DEBUG', $_ENV['EMAIL_DEBUG'] ?? getenv('EMAIL_DEBUG') ?: false);
// No closing PHP tag - prevents accidental whitespace output