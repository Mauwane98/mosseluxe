<?php
include_once dirname(__DIR__) . '/includes/bootstrap.php';

$conn = get_db_connection();

// Create a sample hero slide with Shop button
$sql = "INSERT INTO hero_slides (title, subtitle, button_text, button_url, image_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$title = "Welcome to Mossé Luxe";
$subtitle = "Discover premium urban fashion";
$button_text = "Shop Now";
$button_url = "/shop";
$image_url = "assets/images/hero/69173b312fc02-hero2.webp"; // Use existing hero image
$is_active = 1;
$sort_order = 1;

$stmt->bind_param('sssssii', $title, $subtitle, $button_text, $button_url, $image_url, $is_active, $sort_order);

if ($stmt->execute()) {
    echo "✅ Sample hero slide with Shop button created successfully!\n";
    echo "Title: $title\n";
    echo "Button Text: $button_text\n";
    echo "Button URL: $button_url\n";
} else {
    echo "❌ Failed to create hero slide.\n";
}

$stmt->close();
$conn->close();
?>
