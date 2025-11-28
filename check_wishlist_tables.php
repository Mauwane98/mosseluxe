<?php
require_once 'includes/bootstrap.php';

$conn = get_db_connection();

echo "<h2>Checking Wishlist Tables</h2>";
echo "<pre>";

// Check for wishlist tables
$result = $conn->query("SHOW TABLES LIKE '%wishlist%'");
echo "Tables matching '%wishlist%':\n";
while ($row = $result->fetch_array()) {
    echo "- " . $row[0] . "\n";
}

// Check table structure
echo "\n\nChecking 'wishlist' table:\n";
$result = $conn->query("DESCRIBE wishlist");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "  Table 'wishlist' does not exist\n";
}

echo "\n\nChecking 'wishlists' table:\n";
$result = $conn->query("DESCRIBE wishlists");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "  Table 'wishlists' does not exist\n";
}

echo "</pre>";
?>
