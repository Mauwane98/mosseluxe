<?php
/**
 * Check Apache and PHP error logs
 */

echo "<h1>Apache & PHP Error Log Checker</h1>\n";
echo "<pre>\n";

// Common XAMPP error log locations
$possible_logs = [
    'C:/xampp/apache/logs/error.log',
    'C:/xamppp/apache/logs/error.log',
    __DIR__ . '/logs/php_errors.log',
    __DIR__ . '/logs/router_errors.log',
    ini_get('error_log')
];

echo "=== Checking Error Log Files ===\n\n";

foreach ($possible_logs as $log_path) {
    if (empty($log_path)) continue;
    
    echo "Checking: $log_path\n";
    if (file_exists($log_path)) {
        echo "  ✓ File exists\n";
        $size = filesize($log_path);
        echo "  Size: " . number_format($size) . " bytes\n";
        
        if ($size > 0) {
            echo "  --- Last 30 lines ---\n";
            $lines = file($log_path);
            $last_lines = array_slice($lines, -30);
            foreach ($last_lines as $line) {
                echo "  " . htmlspecialchars($line);
            }
            echo "  --- End of log ---\n";
        } else {
            echo "  (File is empty)\n";
        }
    } else {
        echo "  ✗ File not found\n";
    }
    echo "\n";
}

echo "\n=== PHP Configuration ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server API: " . php_sapi_name() . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "display_startup_errors: " . ini_get('display_startup_errors') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "error_log: " . ini_get('error_log') . "\n";
echo "error_reporting: " . error_reporting() . " (" . decbin(error_reporting()) . ")\n";

echo "\n=== Server Information ===\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Unknown') . "\n";

echo "\n=== Test Error Logging ===\n";
$test_log = __DIR__ . '/logs/test_error.log';
$test_message = "[" . date('Y-m-d H:i:s') . "] Test error log entry\n";
$result = error_log($test_message, 3, $test_log);
if ($result) {
    echo "✓ Successfully wrote test error to: $test_log\n";
    if (file_exists($test_log)) {
        echo "✓ Test log file created\n";
        echo "Content: " . file_get_contents($test_log);
    }
} else {
    echo "✗ Failed to write test error log\n";
}

echo "</pre>\n";
?>
