<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

// Newsletter section data from seed_database.php
$section_data = [
    'section_key' => 'newsletter',
    'section_name' => 'Newsletter',
    'title' => 'Join The Inner Circle',
    'subtitle' => 'Be the first to know about new arrivals, exclusive offers, and behind-the-scenes content.',
    'content' => 'Enter your email address',
    'button_text' => 'Subscribe',
    'button_url' => null,
    'is_active' => 1,
    'sort_order' => 30
];

// Check if exists
$stmt_check = $conn->prepare("SELECT id FROM homepage_sections WHERE section_key = ?");
$stmt_check->bind_param("s", $section_data['section_key']);
$stmt_check->execute();
$result = $stmt_check->get_result();
if ($result->num_rows > 0) {
    echo "Newsletter section already exists.\n";
    $stmt_check->close();
    exit;
}
$stmt_check->close();

$stmt = $conn->prepare("INSERT INTO homepage_sections (section_key, section_name, title, subtitle, content, button_text, button_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssii", $section_data['section_key'], $section_data['section_name'], $section_data['title'], $section_data['subtitle'], $section_data['content'], $section_data['button_text'], $section_data['button_url'], $section_data['is_active'], $section_data['sort_order']);

if ($stmt->execute()) {
    echo "Newsletter section added successfully.\n";
} else {
    echo "Error adding newsletter section: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();
?>
