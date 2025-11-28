<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();
$res = $conn->query('SELECT COUNT(*) as count FROM categories');
$row = $res->fetch_assoc();
echo 'Categories: ' . $row['count'] . PHP_EOL;

// Also list them if any
if ($row['count'] > 0) {
    $res = $conn->query('SELECT id, name FROM categories');
    while ($cat = $res->fetch_assoc()) {
        echo "- " . $cat['name'] . " (ID: " . $cat['id'] . ")" . PHP_EOL;
    }
} else {
    echo "No categories found. Please run the category seeder.\n";
}
?>
