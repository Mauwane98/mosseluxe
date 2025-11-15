<?php
// Direct database connection for debugging
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

// Check hero slides data
$sql = "SELECT id, title, button_style FROM hero_slides ORDER BY sort_order ASC";
$result = $conn->query($sql);

echo "Hero Slides Data:\n";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Title: '{$row['title']}', Button Style: '{$row['button_style']}'\n";
    }
} else {
    echo "No hero slides found\n";
}

// Also check if button_style column exists
$result = $conn->query("SHOW COLUMNS FROM hero_slides LIKE 'button_style'");
if ($result->num_rows > 0) {
    echo "✓ button_style column exists\n";
} else {
    echo "✗ button_style column does not exist\n";
}

$conn->close();
?>
