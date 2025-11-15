<?php
// Start output buffering at the very beginning
ob_start();

// Start session to maintain state
session_start();

// Define ABSPATH if it's not already defined (it should be in config.php)
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__));
}

// Define APP_ENV for verbose error reporting (if needed for debugging)
if (!defined('APP_ENV')) {
    define('APP_ENV', 'development');
}

echo "Including checkout.php...\n";

// Simulate a GET request for checkout.php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/checkout.php';
$_SERVER['PHP_SELF'] = '/checkout.php';

try {
    require __DIR__ . '/checkout.php';
    echo "checkout.php loaded successfully.\n";
} catch (Exception $e) {
    echo "Error loading checkout.php: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();
echo $output;

echo "\nTest of checkout.php complete.\n";
?>