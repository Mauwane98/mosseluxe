<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$result = $conn->query('SELECT * FROM categories');
echo "Categories:\n";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo $row['id'] . " - " . $row['name'] . "\n";
    }
} else {
    echo "No categories found.\n";
}
?>
