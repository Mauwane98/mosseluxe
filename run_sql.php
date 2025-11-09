<?php
require_once 'includes/db_connect.php';

$conn = get_db_connection();

$sql = file_get_contents('create_homepage_table.sql');

if ($conn->multi_query($sql)) {
    echo "✓ Homepage sections table created and populated successfully!<br>";
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

$conn->close();

echo "<a href='admin/manage_homepage.php'>Go to Homepage Management</a>";
?>
