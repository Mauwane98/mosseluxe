<?php
// Direct test of wishlist endpoint
session_start();

// Simulate logged in user - CHANGE THIS TO YOUR USER ID
$_SESSION['loggedin'] = true;
$_SESSION['user_id'] = 1;

echo "<h2>Wishlist Endpoint Test</h2>";
echo "<pre>";

// Test the actual endpoint
echo "Testing: POST wishlist_actions.php with action=add\n\n";

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'add';
$_POST['product_id'] = '1';
$_POST['csrf_token'] = 'test'; // Will fail CSRF but we can see the response format

// Capture the output
ob_start();
include 'wishlist_actions.php';
$response = ob_get_clean();

echo "Raw Response:\n";
echo $response;
echo "\n\n";

echo "Response Length: " . strlen($response) . " bytes\n";
echo "First 100 chars: " . substr($response, 0, 100) . "\n\n";

// Check if it's valid JSON
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✓ Valid JSON\n";
    print_r($decoded);
} else {
    echo "✗ Invalid JSON\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "Error Code: " . json_last_error() . "\n";
}

echo "</pre>";
?>
