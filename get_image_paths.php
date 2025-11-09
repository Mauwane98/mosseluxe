<?php
require_once 'includes/db_connect.php';
$conn = get_db_connection();

function get_image_paths($conn, $table) {
    $sql = "SELECT image FROM $table";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        echo "<h2>Image Paths from $table:</h2>";
        echo "<ul>";
        while($row = $result->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($row['image']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<h2>No image paths found in $table.</h2>";
    }
}

get_image_paths($conn, 'products');
get_image_paths($conn, 'launching_soon');

$conn->close();
?>