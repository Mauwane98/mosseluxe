<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$sql = "UPDATE pages SET status = 1 WHERE status = 0";
$result = $conn->query($sql);

if ($result) {
    echo "Updated " . $conn->affected_rows . " pages to status 1.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
