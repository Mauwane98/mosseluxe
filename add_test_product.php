<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Add category if not exists
$conn->query("INSERT IGNORE INTO categories (id, name) VALUES (1, 'Belts')");

// Add a test product
$sql = "INSERT INTO products (name, description, price, category, stock, status, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$name = 'Test Luxury Belt';
$desc = 'A beautiful test belt for verification.';
$price = 500.00;
$category = 1;
$stock = 10;
$status = 1;
$image = 'https://placehold.co/600x600/000000/FFFFFF?text=Test+Product';

$stmt->bind_param("ssdiiss", $name, $desc, $price, $category, $stock, $status, $image);
if ($stmt->execute()) {
    echo "Test product added successfully.";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();

$conn->close();
?>
