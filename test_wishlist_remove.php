<?php
require_once 'includes/bootstrap.php';

echo "<h2>Wishlist Remove Function Test</h2>";
echo "<pre>";

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo "❌ Not logged in. Please log in first.\n";
    echo "<a href='login.php'>Go to Login</a>\n";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "✓ Logged in as User ID: $user_id\n\n";

$conn = get_db_connection();

// Check wishlists table
echo "=== Checking Wishlists Table ===\n";
$result = $conn->query("SHOW TABLES LIKE 'wishlists'");
if ($result->num_rows > 0) {
    echo "✓ Table 'wishlists' exists\n\n";
} else {
    echo "❌ Table 'wishlists' does NOT exist\n";
    exit;
}

// Get current wishlist items
echo "=== Current Wishlist Items ===\n";
$stmt = $conn->prepare("SELECT w.id, w.product_id, p.name 
                        FROM wishlists w 
                        LEFT JOIN products p ON w.product_id = p.id 
                        WHERE w.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Found {$result->num_rows} items:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: {$row['id']} | Product ID: {$row['product_id']} | Name: {$row['name']}\n";
    }
} else {
    echo "No items in wishlist\n";
}
$stmt->close();

echo "\n=== Testing Remove Function ===\n";

// Test with a product that exists in wishlist
$test_stmt = $conn->prepare("SELECT product_id FROM wishlists WHERE user_id = ? LIMIT 1");
$test_stmt->bind_param("i", $user_id);
$test_stmt->execute();
$test_result = $test_stmt->get_result();

if ($test_result->num_rows > 0) {
    $test_row = $test_result->fetch_assoc();
    $test_product_id = $test_row['product_id'];
    echo "Testing removal of Product ID: $test_product_id\n";
    
    // Simulate the remove action
    $remove_stmt = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
    $remove_stmt->bind_param("ii", $user_id, $test_product_id);
    
    if ($remove_stmt->execute()) {
        if ($remove_stmt->affected_rows > 0) {
            echo "✓ Successfully removed product $test_product_id\n";
            
            // Add it back for testing
            $add_back = $conn->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
            $add_back->bind_param("ii", $user_id, $test_product_id);
            $add_back->execute();
            echo "✓ Added product back to wishlist\n";
            $add_back->close();
        } else {
            echo "⚠ No rows affected (product might not be in wishlist)\n";
        }
    } else {
        echo "❌ Error executing delete: " . $remove_stmt->error . "\n";
    }
    $remove_stmt->close();
} else {
    echo "⚠ No items in wishlist to test removal\n";
}
$test_stmt->close();

echo "\n=== CSRF Token Test ===\n";
$csrf_token = generate_csrf_token();
echo "CSRF Token: " . substr($csrf_token, 0, 20) . "...\n";
echo "Token verification: " . (verify_csrf_token($csrf_token) ? "✓ Valid" : "❌ Invalid") . "\n";

echo "\n=== Endpoint Test ===\n";
echo "Wishlist Actions Endpoint: " . SITE_URL . "wishlist_actions.php\n";
echo "File exists: " . (file_exists(__DIR__ . '/wishlist_actions.php') ? "✓ Yes" : "❌ No") . "\n";

echo "\n=== JavaScript Test ===\n";
echo "To test in browser:\n";
echo "1. Go to wishlist page: " . SITE_URL . "wishlist.php\n";
echo "2. Open browser console (F12)\n";
echo "3. Check for JavaScript errors\n";
echo "4. Try clicking the remove button on a wishlist item\n";
echo "5. Confirm the modal appears\n";
echo "6. Click 'Remove' and check network tab for the request\n";

echo "\n=== Common Issues ===\n";
echo "Issue 1: Modal doesn't appear\n";
echo "  - Check if modals.js is loaded in footer\n";
echo "  - Check browser console for errors\n";
echo "  - Verify window.showConfirm is defined\n\n";

echo "Issue 2: CSRF token error\n";
echo "  - Verify CSRF token is being sent\n";
echo "  - Check session is active\n";
echo "  - Verify verify_csrf_token() function exists\n\n";

echo "Issue 3: Item not removed\n";
echo "  - Check network tab for response\n";
echo "  - Verify correct product_id is sent\n";
echo "  - Check database connection\n";

echo "</pre>";

echo "<hr>";
echo "<h3>Quick Actions</h3>";
echo "<a href='wishlist.php' style='display:inline-block;padding:10px 20px;background:#000;color:#fff;text-decoration:none;border-radius:5px;margin:5px;'>Go to Wishlist</a>";
echo "<a href='shop.php' style='display:inline-block;padding:10px 20px;background:#000;color:#fff;text-decoration:none;border-radius:5px;margin:5px;'>Go to Shop</a>";
?>
