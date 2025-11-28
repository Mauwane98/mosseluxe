<?php
// Test wishlist endpoint directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Wishlist Endpoint</h2>";
echo "<pre>";

// Simulate a logged-in user
session_start();
$_SESSION['loggedin'] = true;
$_SESSION['user_id'] = 1; // Change to a valid user ID

// Test 1: Check action via GET
echo "Test 1: GET request with action=check\n";
echo "URL: wishlist_actions.php?action=check&product_id=1\n\n";

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'check';
$_GET['product_id'] = '1';

ob_start();
include 'wishlist_actions.php';
$output = ob_get_clean();

echo "Response:\n";
echo $output;
echo "\n\n";

// Validate JSON
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✓ Valid JSON response\n";
    print_r($json);
} else {
    echo "✗ Invalid JSON response\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "Raw output (first 500 chars):\n";
    echo substr($output, 0, 500);
}

echo "</pre>";
?>
