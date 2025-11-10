<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mosse_luxe_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL
define('BASE_URL', 'http://localhost/mosse-luxe');
define('SITE_URL', 'http://localhost/mosse-luxe');

// Image configuration
define('PRODUCT_IMAGE_WIDTH', 800);
define('PRODUCT_IMAGE_HEIGHT', 800);
define('PRODUCT_IMAGE_DIR', 'assets/images/');

// Shipping configuration
define('SHIPPING_COST', 150.00);

// PayFast configuration (replace with your actual credentials)
define('PAYFAST_MERCHANT_ID', 'your_merchant_id');
define('PAYFAST_MERCHANT_KEY', 'your_merchant_key');
define('PAYFAST_URL', 'https://sandbox.payfast.co.za/eng/process');

// Contact information
define('CONTACT_ADDRESS', '123 Fashion Street, Cape Town, South Africa');
define('CONTACT_PHONE', '+27 12 345 6789');
