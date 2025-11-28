<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$result = $conn->query('SHOW TABLES');
echo "Current tables in database:\n";
while ($row = $result->fetch_array()) {
    echo $row[0] . "\n";
}
?>
