<?php
require_once '../includes/bootstrap.php';
$conn = get_db_connection();

// Check if button_style column exists, if not, add it
$sql = "SHOW COLUMNS FROM hero_slides LIKE 'button_style'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $alter_sql = "ALTER TABLE hero_slides ADD COLUMN button_style VARCHAR(50) DEFAULT 'wide' AFTER sort_order";
    if ($conn->query($alter_sql)) {
        echo "✓ Successfully added 'button_style' column to hero_slides table\n";
        echo "✓ Default value set to 'wide'\n";
    } else {
        echo "✗ Failed to add 'button_style' column: " . $conn->error . "\n";
    }
} else {
    echo "✓ 'button_style' column already exists in hero_slides table\n";
}

$conn->close();
?>
