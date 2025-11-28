<?php
// Test what the wishlist endpoint actually returns
session_start();

// Simulate logged in user
$_SESSION['loggedin'] = true;
$_SESSION['user_id'] = 1;

// Test GET request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'check';
$_GET['product_id'] = '1';

echo "=== TESTING WISHLIST ENDPOINT ===\n\n";

// Capture raw output
ob_start();
include 'wishlist_actions.php';
$output = ob_get_clean();

echo "Output Length: " . strlen($output) . " bytes\n\n";

echo "First 500 characters (raw):\n";
echo substr($output, 0, 500) . "\n\n";

echo "First 500 characters (HTML escaped):\n";
echo htmlspecialchars(substr($output, 0, 500)) . "\n\n";

echo "Character-by-character (first 100):\n";
for ($i = 0; $i < min(100, strlen($output)); $i++) {
    $char = $output[$i];
    $ascii = ord($char);
    $display = ($char === "\n") ? "\\n" : (($char === "\r") ? "\\r" : (($char === "\t") ? "\\t" : $char));
    printf("Pos %3d: '%s' (ASCII %3d) %s\n", $i, $display, $ascii, ($ascii < 32 || $ascii > 126) ? "CONTROL" : "");
}

echo "\n\nJSON Parse Test:\n";
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✓ Valid JSON\n";
    print_r($json);
} else {
    echo "✗ Invalid JSON\n";
    echo "Error: " . json_last_error_msg() . "\n";
}
?>
