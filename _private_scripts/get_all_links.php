<?php
require_once '../includes/bootstrap.php';

$conn = get_db_connection();

$links = [];

// Fetch from hero_slides
$hero_slides_sql = "SELECT button_url, image_url FROM hero_slides";
$hero_slides_result = $conn->query($hero_slides_sql);
if ($hero_slides_result) {
    while ($row = $hero_slides_result->fetch_assoc()) {
        if (!empty($row['button_url'])) {
            $links[] = $row['button_url'];
        }
        if (!empty($row['image_url'])) {
            $links[] = $row['image_url'];
        }
    }
}

// Fetch from homepage_sections
$sections_sql = "SELECT button_url, image_url FROM homepage_sections";
$sections_result = $conn->query($sections_sql);
if ($sections_result) {
    while ($row = $sections_result->fetch_assoc()) {
        if (!empty($row['button_url'])) {
            $links[] = $row['button_url'];
        }
        if (!empty($row['image_url'])) {
            $links[] = $row['image_url'];
        }
    }
}

// Fetch from products
$products_sql = "SELECT id, image FROM products";
$products_result = $conn->query($products_sql);
if ($products_result) {
    while ($row = $products_result->fetch_assoc()) {
        $links[] = 'product.php?id=' . $row['id'];
        if (!empty($row['image'])) {
            $links[] = $row['image'];
        }
    }
}

// Fetch from categories
$categories_sql = "SELECT id, image FROM categories";
$categories_result = $conn->query($categories_sql);
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $links[] = 'shop.php?category_id=' . $row['id'];
        if (!empty($row['image'])) {
            $links[] = $row['image'];
        }
    }
}

foreach ($links as $link) {
    echo $link . PHP_EOL;
}
