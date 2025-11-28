<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

if ($conn) {
    echo "Connection successful.\n";
    $result = $conn->query("SELECT VERSION() as version");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "MySQL version: " . $row['version'] . "\n";
    }
} else {
    echo "Connection failed.\n";
}
?>
