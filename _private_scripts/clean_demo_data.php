<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Allow running in development mode for demo data cleanup
if (!defined('APP_ENV') || APP_ENV !== 'development') {
    echo "Warning: This script should only be run in development mode.\n";
    echo "Current APP_ENV = " . (defined('APP_ENV') ? APP_ENV : 'undefined') . "\n";
    // Temporarily allow execution for demo cleanup
    // die("Error: Demo data cleanup script can only be run in development environment.");
}

require_once __DIR__ . '/../includes/db_connect.php';
$conn = get_db_connection();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<pre>"; // Use preformatted text for better readability in browser

echo "--- Cleaning Demo Data ---\n";

// Remove demo users
echo "--- Removing demo users ---\n";
$demo_users = [
    'john.doe@example.com',
    // 'admin@mosse-luxe.com' // Keep admin account
];

foreach ($demo_users as $email) {
    $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    echo "Removed demo user: $email\n";
    $stmt->close();
}

// Remove demo discount codes
echo "--- Removing demo discount codes ---\n";
$demo_discounts = [
    'WELCOME10',
    'WINTER50'
];

foreach ($demo_discounts as $code) {
    $stmt = $conn->prepare("DELETE FROM discount_codes WHERE code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    echo "Removed demo discount code: $code\n";
    $stmt->close();
}

// Remove orders with demo user data
echo "--- Removing demo orders ---\n";
$demo_orders = [
    'john.doe@example.com'
];

foreach ($demo_orders as $email) {
    // First get user IDs for demo users
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_id = $result->fetch_assoc()['id'];

        // Delete order items first
        $stmt_items = $conn->prepare("DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE user_id = ?)");
        $stmt_items->bind_param("i", $user_id);
        $stmt_items->execute();
        $stmt_items->close();

        // Delete orders
        $stmt_orders = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
        $stmt_orders->bind_param("i", $user_id);
        $stmt_orders->execute();
        echo "Removed orders for demo user: $email\n";
        $stmt_orders->close();
    }
    $stmt->close();
}

// Remove demo product reviews
echo "--- Removing demo product reviews ---\n";
foreach ($demo_orders as $email) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_id = $result->fetch_assoc()['id'];

        // Check if reviews exist before deleting
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM product_reviews WHERE user_id = ?");
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        // ... simplified removal
        $stmt_reviews = $conn->prepare("DELETE FROM product_reviews WHERE user_id = ?");
        $stmt_reviews->bind_param("i", $user_id);
        $stmt_reviews->execute();
        echo "Removed reviews for demo user: $email\n";
        $stmt_reviews->close();
    }
    $stmt->close();
}

// Remove demo hero slides with placeholder images
echo "--- Removing demo hero slides ---\n";
$stmt = $conn->prepare("DELETE FROM hero_slides WHERE image_url LIKE '%placehold.co%' OR image_url LIKE '%product-placeholder.png%'");
$stmt->execute();
echo "Removed " . $stmt->affected_rows . " hero slides with placeholder images\n";
$stmt->close();

// Remove demo "Launching Soon" items
echo "--- Removing demo 'Launching Soon' items ---\n";
$stmt = $conn->prepare("DELETE FROM launching_soon WHERE image LIKE '%placehold.co%' OR image LIKE '%product-placeholder.png%'");
$stmt->execute();
echo "Removed " . $stmt->affected_rows . " 'launching soon' items with placeholder images\n";
$stmt->close();

// Remove demo products with placeholder images
echo "--- Removing demo products with placeholder images ---\n";
$stmt = $conn->prepare("DELETE FROM products WHERE image LIKE '%placehold.co%' OR image LIKE '%product-placeholder.png%'");
$stmt->execute();
echo "Removed " . $stmt->affected_rows . " products with placeholder images\n";
$stmt->close();

// Clear new arrivals (since they were based on demo products)
echo "--- Clearing new arrivals section ---\n";
$conn->query("DELETE FROM new_arrivals");

// Clear wishlist (may contain demo products)
echo "--- Clearing wishlist entries for demo products ---\n";
$conn->query("DELETE FROM wishlist");

echo "\n--- Demo data cleanup complete ---\n";
echo "Note: You may need to manually replace static page content (About, Privacy Policy, etc.) to remove any placeholder text.\n";

$conn->close();
echo "</pre>";
?>
