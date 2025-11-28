<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

echo "Checking new arrivals section in homepage_sections:\n\n";

$result = $conn->query("SELECT * FROM homepage_sections WHERE section_key = 'new_arrivals'");
if ($result->num_rows > 0) {
    $section = $result->fetch_assoc();
    echo "✓ New arrivals section exists:\n";
    echo "  - ID: " . $section['id'] . "\n";
    echo "  - Title: " . $section['title'] . "\n";
    echo "  - Subtitle: " . $section['subtitle'] . "\n";
    echo "  - Content: " . substr($section['content'], 0, 50) . "...\n";
    echo "  - Is Active: " . ($section['is_active'] ? 'Yes' : 'No') . "\n";
    echo "  - Sort Order: " . $section['sort_order'] . "\n";
    echo "\nYou can edit this section at: admin/manage_homepage.php\n";
} else {
    echo "✗ New arrivals section not found in homepage_sections\n";
}

$conn->close();
?>
