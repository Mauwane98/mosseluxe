<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$result = $conn->query('SELECT id, name, image, status FROM products LIMIT 5');

echo "<h1>Products in Database</h1>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . ", Name: " . $row['name'] . ", Image: " . $row['image'] . ", Status: " . $row['status'] . "<br>";
    }
} else {
    echo "No products found in database.";
}
?>
