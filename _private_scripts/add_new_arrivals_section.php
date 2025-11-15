<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

// Insert new arrivals section if not exists
$section_key = 'new_arrivals';
$section_name = 'New Arrivals Section';
$title = 'Current New Arrivals';
$content = 'Discover the latest additions to our premium collection.';
$subtitle = 'Featured';
$button_text = 'Shop All';
$button_url = 'shop.php';
$is_active = 1;
$sort_order = 20;

// Check if exists
$stmt_check = $conn->prepare("SELECT id FROM homepage_sections WHERE section_key = ?");
$stmt_check->bind_param("s", $section_key);
$stmt_check->execute();
$result = $stmt_check->get_result();
if ($result->num_rows > 0) {
    echo "New arrivals section already exists.\n";
    $stmt_check->close();
    exit;
}
$stmt_check->close();

$stmt = $conn->prepare("INSERT INTO homepage_sections (section_key, section_name, title, subtitle, content, button_text, button_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssii", $section_key, $section_name, $title, $subtitle, $content, $button_text, $button_url, $is_active, $sort_order);

if ($stmt->execute()) {
    echo "New arrivals section added successfully.\n";
} else {
    echo "Error adding section: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();
?>
