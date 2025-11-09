<?php
require_once 'includes/db_connect.php';
$conn = get_db_connection();

echo "<h3>Applying database patch...</h3>";

// SQL statement to add the 'is_featured' column to the 'products' table if it doesn't exist.
$sql = "ALTER TABLE `products` ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Featured, 0=Not Featured' AFTER `status`;";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>Successfully added the 'is_featured' column to the 'products' table.</p>";
} else {
    // Check if the error is because the column already exists, which is not a problem.
    if ($conn->errno == 1060) { // Error code for "Duplicate column name"
        echo "<p style='color: orange;'>The 'is_featured' column already exists. No changes were made.</p>";
    } else {
        echo "<p style='color: red;'>Error adding column: " . $conn->error . "</p>";
    }
}

echo "<p>Database patch script finished.</p>";
echo "<p><a href='admin/products.php'>Return to Admin Products Page</a></p>";

$conn->close();

?>