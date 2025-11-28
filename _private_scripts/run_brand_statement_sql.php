<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

$sql = "INSERT IGNORE INTO homepage_sections (section_key, section_name, title, content, button_text, button_url, is_active, sort_order) VALUES (
    'brand_statement',
    'Brand Statement',
    'Redefining Urban Luxury',
    'Mossé Luxe is not just a brand; it\'s a statement. We merge the raw energy of street culture with the finesse of high-end fashion, creating pieces that are both timeless and contemporary. Each item is crafted with meticulous attention to detail, designed for the discerning individual who values both style and substance.',
    'Our Story',
    '/about.php',
    1,
    10
)";

if ($conn->query($sql)) {
    echo "✓ Brand statement section added successfully!\n";
    echo "This section will appear above New Arrivals with the title 'Redefining Urban Luxury' and an 'Our Story' button linking to about.php.\n";
} else {
    echo "✗ Error adding brand statement section: " . $conn->error . "\n";
}

$conn->close();
?>
