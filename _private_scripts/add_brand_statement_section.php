<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

// Brand Statement section data from seed_database.php
$section_data = [
    'section_key' => 'brand_statement',
    'section_name' => 'Brand Statement',
    'title' => 'Redefining Urban Luxury',
    'content' => "Mossé Luxe is not just a brand; it's a statement. We merge the raw energy of street culture with the finesse of high-end fashion, creating pieces that are both timeless and contemporary. Each item is crafted with meticulous attention to detail, designed for the discerning individual who values both style and substance.",
    'button_text' => 'Our Story',
    'button_url' => '/about.php',
    'is_active' => 1,
    'sort_order' => 10
];

// Check if exists
$stmt_check = $conn->prepare("SELECT id FROM homepage_sections WHERE section_key = ?");
$stmt_check->bind_param("s", $section_data['section_key']);
$stmt_check->execute();
$result = $stmt_check->get_result();
if ($result->num_rows > 0) {
    echo "Brand statement section already exists.\n";
    $stmt_check->close();
    exit;
}
$stmt_check->close();

$sql = "INSERT INTO homepage_sections (section_key, section_name, title, content, button_text, button_url, is_active, sort_order) VALUES (
    '{$section_data['section_key']}',
    '{$section_data['section_name']}',
    '{$section_data['title']}',
    '{$section_data['content']}',
    '{$section_data['button_text']}',
    '{$section_data['button_url']}',
    {$section_data['is_active']},
    {$section_data['sort_order']}
)";

if ($conn->query($sql)) {
    echo "Brand statement section added successfully. This section appears above New Arrivals with an 'Our Story' button linking to about.php.\n";
    echo "\nYou can edit this section at: admin/manage_homepage.php\n";
} else {
    echo "Error adding brand statement section: " . $conn->error . "\n";
}
$conn->close();
?>
