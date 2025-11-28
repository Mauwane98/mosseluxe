<?php
// Direct test of wishlist_actions.php to see raw output
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Direct Wishlist Test</h2>";
echo "<h3>Simulating AJAX Request</h3>";
echo "<pre>";

// Set up session
session_start();
$_SESSION['loggedin'] = true;
$_SESSION['user_id'] = 1; // Change to valid user ID

// Simulate GET request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'check';
$_GET['product_id'] = '1';

echo "Request: GET wishlist_actions.php?action=check&product_id=1\n";
echo "Session: loggedin=true, user_id=1\n\n";
echo "Capturing output...\n";
echo str_repeat("=", 80) . "\n";

// Capture the output
ob_start();
include 'wishlist_actions.php';
$output = ob_get_clean();

echo str_repeat("=", 80) . "\n\n";

echo "Output Length: " . strlen($output) . " bytes\n\n";

echo "First 500 characters:\n";
echo substr($output, 0, 500) . "\n\n";

if (strlen($output) > 500) {
    echo "Last 200 characters:\n";
    echo substr($output, -200) . "\n\n";
}

echo "Full Output (with visible special chars):\n";
echo htmlspecialchars($output) . "\n\n";

// Try to parse as JSON
echo "JSON Parse Test:\n";
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✓ Valid JSON\n";
    print_r($json);
} else {
    echo "✗ Invalid JSON\n";
    echo "Error: " . json_last_error_msg() . "\n";
    echo "Error Code: " . json_last_error() . "\n\n";
    
    // Show where the error is
    echo "Character-by-character analysis (first 100):\n";
    for ($i = 0; $i < min(100, strlen($output)); $i++) {
        $char = $output[$i];
        $ord = ord($char);
        echo sprintf("Pos %3d: '%s' (ASCII %3d)\n", $i, $char === "\n" ? "\\n" : ($char === "\r" ? "\\r" : $char), $ord);
    }
}

echo "</pre>";
?>
