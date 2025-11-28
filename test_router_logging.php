<?php
/**
 * Test script to verify router.php error logging
 * This script simulates various router scenarios to test error logging
 */

echo "<h1>Router Error Logging Test</h1>\n";
echo "<pre>\n";

// Check if logs directory exists
$logs_dir = __DIR__ . '/logs';
if (!file_exists($logs_dir)) {
    echo "Creating logs directory...\n";
    mkdir($logs_dir, 0755, true);
} else {
    echo "✓ Logs directory exists\n";
}

// Check if router_errors.log exists
$log_file = $logs_dir . '/router_errors.log';
if (file_exists($log_file)) {
    echo "✓ Router error log file exists\n";
    echo "\n--- Last 20 lines of router_errors.log ---\n";
    $lines = file($log_file);
    $last_lines = array_slice($lines, -20);
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line);
    }
} else {
    echo "⚠ Router error log file does not exist yet (will be created on first router access)\n";
}

// Check if router.php exists
if (file_exists(__DIR__ . '/router.php')) {
    echo "\n✓ router.php exists\n";
} else {
    echo "\n✗ router.php NOT found\n";
}

// Check if index.php exists
if (file_exists(__DIR__ . '/index.php')) {
    echo "✓ index.php exists\n";
} else {
    echo "✗ index.php NOT found\n";
}

// Check if 404.php exists
if (file_exists(__DIR__ . '/404.php')) {
    echo "✓ 404.php exists\n";
} else {
    echo "✗ 404.php NOT found\n";
}

// Check if product-details.php exists
if (file_exists(__DIR__ . '/product-details.php')) {
    echo "✓ product-details.php exists\n";
} else {
    echo "✗ product-details.php NOT found\n";
}

// Check if api/index.php exists
if (file_exists(__DIR__ . '/api/index.php')) {
    echo "✓ api/index.php exists\n";
} else {
    echo "✗ api/index.php NOT found\n";
}

echo "\n--- Test URLs to try ---\n";
echo "1. Homepage: http://localhost/mosseluxe/\n";
echo "2. Shop page: http://localhost/mosseluxe/shop\n";
echo "3. API endpoint: http://localhost/mosseluxe/api/cart\n";
echo "4. Product page: http://localhost/mosseluxe/product/1/test-product\n";
echo "5. Non-existent page (404): http://localhost/mosseluxe/nonexistent\n";

echo "\n--- Instructions ---\n";
echo "1. Visit the URLs above in your browser\n";
echo "2. Refresh this page to see the logged errors\n";
echo "3. Check the router_errors.log file for detailed error information\n";

echo "\n--- PHP Error Settings ---\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "error_log: " . ini_get('error_log') . "\n";
echo "error_reporting: " . error_reporting() . "\n";

echo "</pre>\n";
?>
