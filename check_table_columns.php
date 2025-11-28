<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Check pages table columns
echo "Pages table columns:\n";
$result = $conn->query("DESCRIBE pages");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

// Check if hero_slides exists
echo "\nChecking hero_slides table:\n";
$result = $conn->query("SHOW TABLES LIKE 'hero_slides'");
if ($result->num_rows > 0) {
    echo "hero_slides table exists.\n";
} else {
    echo "hero_slides table does NOT exist.\n";
}

// Check footer_links
echo "\nChecking footer_links table:\n";
$result = $conn->query("SHOW TABLES LIKE 'footer_links'");
if ($result->num_rows > 0) {
    echo "footer_links table exists.\n";
} else {
    echo "footer_links table does NOT exist.\n";
}
?>
