<?php
require_once 'includes/bootstrap.php';

echo "<h2>Wishlist Data Check</h2>";
echo "<pre>";

$conn = get_db_connection();

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'wishlists'");
if ($result->num_rows > 0) {
    echo "✓ Table 'wishlists' exists\n\n";
} else {
    echo "✗ Table 'wishlists' does NOT exist\n\n";
    exit;
}

// Check table structure
echo "Table Structure:\n";
$result = $conn->query("DESCRIBE wishlists");
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}
echo "\n";

// Count total wishlist items
$result = $conn->query("SELECT COUNT(*) as count FROM wishlists");
$row = $result->fetch_assoc();
echo "Total wishlist items: {$row['count']}\n\n";

// Show all wishlist items
echo "All Wishlist Items:\n";
$result = $conn->query("SELECT w.id, w.user_id, w.product_id, w.created_at, p.name as product_name 
                        FROM wishlists w 
                        LEFT JOIN products p ON w.product_id = p.id 
                        ORDER BY w.created_at DESC");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  ID: {$row['id']} | User: {$row['user_id']} | Product: {$row['product_id']} ({$row['product_name']}) | Added: {$row['created_at']}\n";
    }
} else {
    echo "  No items in wishlist\n";
}

echo "\n";

// Check if current user has wishlist items (if logged in)
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "Current User ID: $user_id\n";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM wishlists WHERE user_id = $user_id");
    $row = $stmt->fetch_assoc();
    echo "Items in your wishlist: {$row['count']}\n";
} else {
    echo "Not logged in - cannot check user-specific wishlist\n";
}

echo "</pre>";
?>
