<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Insert homepage sections
$sections = [
    [
        'section_key' => 'brand_statement',
        'section_name' => 'Brand Statement',
        'title' => 'Redefining Urban Luxury',
        'subtitle' => '',
        'content' => "Mossé Luxe is not just a brand; it's a statement. We merge the raw energy of street culture with the finesse of high-end fashion, creating pieces that are both timeless and contemporary. Each item is crafted with meticulous attention to detail, designed for the discerning individual who values both style and substance.",
        'button_text' => 'Our Story',
        'button_url' => '/about.php',
        'is_active' => 1,
        'sort_order' => 10
    ],
    [
        'section_key' => 'newsletter',
        'section_name' => 'Newsletter',
        'title' => 'Join The Inner Circle',
        'content' => 'Enter your email address',
        'button_text' => 'Subscribe',
        'button_url' => '',
        'is_active' => 1,
        'sort_order' => 30
    ]
];

$stmt = $conn->prepare("INSERT INTO homepage_sections (section_key, section_name, title, subtitle, content, button_text, button_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content), button_text=VALUES(button_text), is_active=VALUES(is_active), sort_order=VALUES(sort_order)");

foreach ($sections as $section) {
    $stmt->bind_param("sssssssii",
        $section['section_key'],
        $section['section_name'],
        $section['title'],
        $section['subtitle'],
        $section['content'],
        $section['button_text'],
        $section['button_url'],
        $section['is_active'],
        $section['sort_order']
    );

    if ($stmt->execute()) {
        echo "Inserted section: {$section['section_name']}\n";
    } else {
        echo "Error: " . $stmt->error . "\n";
    }
}

$stmt->close();

echo "Homepage sections added.\n";

$conn->close();
?>
