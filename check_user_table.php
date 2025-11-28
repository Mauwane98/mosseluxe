<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();
$result = $conn->query('DESCRIBE users');
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo 'Users table columns: ' . implode(', ', $columns);
?>
