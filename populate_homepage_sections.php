<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Define homepage sections to add if missing
$sections = [
    [
        'section_key' => 'featured_products',
        'section_name' => 'Featured Products',
        'title' => 'Featured Collection',
        'subtitle' => 'Discover Our Handpicked Selections',
        'content' => 'Browse through our curated featured products showcasing the best of Mossé Luxe.',
        'button_text' => 'Shop Collection',
        'button_url' => '/shop.php',
        'image_url' => NULL,
        'background_color' => NULL,
        'text_color' => NULL,
        'is_active' => 1,
        'sort_order' => 15
    ],
    [
        'section_key' => 'categories_block',
        'section_name' => 'Categories',
        'title' => 'Shop by Category',
        'subtitle' => 'Explore Our Collections',
        'content' => 'From belts to jackets, find your perfect style in our categorized collections.',
        'button_text' => '',
        'button_url' => '',
        'image_url' => NULL,
        'background_color' => '#f8f9fa',
        'text_color' => '#333',
        'is_active' => 1,
        'sort_order' => 20
    ],
    [
        'section_key' => 'testimonials',
        'section_name' => 'Testimonials',
        'title' => 'What Our Customers Say',
        'subtitle' => 'Real Reviews from Satisfied Customers',
        'content' => 'Trusted by fashion enthusiasts worldwide for quality and style.',
        'button_text' => '',
        'button_url' => '',
        'image_url' => NULL,
        'background_color' => NULL,
        'text_color' => NULL,
        'is_active' => 1,
        'sort_order' => 25
    ],
    [
        'section_key' => 'promo_banner',
        'section_name' => 'Promo Banner',
        'title' => 'Limited Time Offer',
        'subtitle' => 'Sale Now Live - Up to 30% Off',
        'content' => 'Don\'t miss out on our seasonal sale. Premium pieces at unbeatable prices.',
        'button_text' => 'Shop Sale',
        'button_url' => '/shop.php?sale=1',
        'image_url' => NULL,
        'background_color' => '#dc3545',
        'text_color' => '#fff',
        'is_active' => 1,
        'sort_order' => 15
    ]
];

foreach ($sections as $section) {
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM homepage_sections WHERE section_key = ?");
    $stmt->bind_param("s", $section['section_key']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $ins_sql = "INSERT INTO homepage_sections (section_key, section_name, title, subtitle, content, button_text, button_url, image_url, background_color, text_color, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $ins_stmt = $conn->prepare($ins_sql);
        $ins_stmt->bind_param("ssssssssssii", 
            $section['section_key'], $section['section_name'], $section['title'], $section['subtitle'], 
            $section['content'], $section['button_text'], $section['button_url'], $section['image_url'], 
            $section['background_color'], $section['text_color'], $section['is_active'], $section['sort_order']
        );
        if ($ins_stmt->execute()) {
            echo "Added homepage section: " . $section['section_name'] . "\n";
        } else {
            echo "Error adding section: " . $section['section_name'] . " - " . $ins_stmt->error . "\n";
        }
        $ins_stmt->close();
    } else {
        echo "Homepage section '" . $section['section_key'] . "' already exists.\n";
    }
    $stmt->close();
}

echo "Populate homepage sections script complete.\n";
?>
