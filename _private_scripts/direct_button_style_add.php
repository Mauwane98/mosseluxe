<?php
// Direct database connection for adding button_style column
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mosse_luxe_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if button_style column exists
$result = $conn->query("SHOW COLUMNS FROM hero_slides LIKE 'button_style'");

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE hero_slides ADD COLUMN button_style VARCHAR(50) DEFAULT 'wide' AFTER sort_order";
    if ($conn->query($sql) === TRUE) {
        echo "✓ Successfully added 'button_style' column to hero_slides table\n";
        echo "✓ Default value set to 'wide'\n";
    } else {
        echo "✗ Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "✓ 'button_style' column already exists\n";
}

$conn->close();
?>
