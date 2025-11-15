<?php
// Set default button styles for all existing hero slides
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

// Update all existing slides that have NULL or empty button_style to 'largest'
$sql = "UPDATE hero_slides SET button_style = 'largest' WHERE button_style IS NULL OR button_style = ''";
if ($conn->query($sql) === TRUE) {
    echo "✓ Updated all existing hero slides to use 'largest' button style\n";
    $affected = $conn->affected_rows;
    echo "✓ Rows affected: $affected\n";
} else {
    echo "✗ Error updating button styles: " . $conn->error . "\n";
}

echo "\nUpdated Hero Slides Data:\n";
$sql = "SELECT id, title, button_style FROM hero_slides ORDER BY sort_order ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Title: '{$row['title']}', Button Style: '{$row['button_style']}'\n";
    }
} else {
    echo "No hero slides found\n";
}

$conn->close();
echo "\nRefresh your homepage to see the largest buttons!\n";
?>
