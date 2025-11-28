<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Insert hero slides
$slides = [
    [
        'title' => 'Luxury Streetwear',
        'subtitle' => 'Elevate your style with Mossé Luxe',
        'button_text' => 'Shop Now',
        'button_url' => '/shop.php',
        'image_url' => 'assets/images/hero1.png',
        'is_active' => 1,
        'sort_order' => 1
    ],
    [
        'title' => 'Premium Quality',
        'subtitle' => 'Handcrafted pieces for discerning taste',
        'button_text' => 'Explore Collection',
        'button_url' => '/shop.php',
        'image_url' => 'assets/images/hero2.png',
        'is_active' => 1,
        'sort_order' => 2
    ]
];

$stmt = $conn->prepare("INSERT INTO hero_slides (title, subtitle, button_text, button_url, image_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=VALUES(title), subtitle=VALUES(subtitle), button_text=VALUES(button_text), image_url=VALUES(image_url), is_active=VALUES(is_active)");

foreach ($slides as $slide) {
    $stmt->bind_param("sssssii",
        $slide['title'],
        $slide['subtitle'],
        $slide['button_text'],
        $slide['button_url'],
        $slide['image_url'],
        $slide['is_active'],
        $slide['sort_order']
    );

    if ($stmt->execute()) {
        echo "Inserted hero slide: {$slide['title']}\n";
    } else {
        echo "Error: " . $stmt->error . "\n";
    }
}

$stmt->close();

echo "Hero slides added.\n";

$conn->close();
?>
