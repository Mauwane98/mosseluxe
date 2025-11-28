<?php
require_once '../includes/bootstrap.php';

$conn = get_db_connection();

$sql = "INSERT IGNORE INTO homepage_sections (section_key, section_name, title, subtitle, content, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$section_key = 'recently_viewed';
$section_name = 'Recently Viewed';
$title = 'Recently Viewed';
$subtitle = '';
$content = '';
$is_active = 1;
$sort_order = 5;

$stmt->bind_param("sssssii", $section_key, $section_name, $title, $subtitle, $content, $is_active, $sort_order);

if ($stmt->execute()) {
    echo "Recently Viewed section added successfully.\n";
} else {
    echo "Failed to add section.\n";
}

$stmt->close();
$conn->close();
?>
