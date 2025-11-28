<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$conn = get_db_connection();

$tables_to_drop = ['events', 'gallery', 'member_proofs'];

foreach ($tables_to_drop as $table) {
    $sql = "DROP TABLE IF EXISTS $table";
    if ($conn->query($sql)) {
        echo "Dropped table: $table\n";
    } else {
        echo "Error dropping table $table: " . $conn->error . "\n";
    }
}

// Remove membership_status from users if you want to be thorough
// $conn->query("ALTER TABLE users DROP COLUMN membership_status");

$conn->close();
?>
