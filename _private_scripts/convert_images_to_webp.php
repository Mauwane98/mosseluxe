<?php
// This script converts PNG and JPEG images in assets/images to WebP format.

// Check if GD library is enabled
if (!extension_loaded('gd')) {
    echo "Error: GD library is not enabled. Please enable it in your PHP configuration.\n";
    exit(1);
}

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__));
}

$image_dir = ABSPATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;

$files_to_convert = [
    '68fa759a7f151-ml3_Colorway A Copy 1.png',
    '68fa7a051d9d5-ml3_Colorway_A_Copy_1-removebg-preview.png',
    '68fa7a863f9de-ml2_Colorway_A_Copy_1-removebg-preview.png',
    '68fa7b337e96e-ml3_Colorway_A_Copy_1-removebg-preview.png',
    'hero.jpeg',
    'hero1.png',
    'hero2.png',
    'logo-dark.png',
    'logo-light.png',
    'logo.png',
    'ml3_Colorway A Copy 1.png',
    'potrait.png',
    'product-1.jpg',
    'WhatsApp Image 2025-06-18 at 12.01.44.jpeg'
];

echo "Starting image conversion to WebP...\n";

foreach ($files_to_convert as $filename) {
    $full_path = $image_dir . $filename;
    $path_info = pathinfo($full_path);
    $extension = strtolower($path_info['extension']);
    $new_filename = $path_info['filename'] . '.webp';
    $new_full_path = $image_dir . $new_filename;

    if (!file_exists($full_path)) {
        echo "File not found: " . $filename . "\n";
        continue;
    }

    $image = null;
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($full_path);
            break;
        case 'png':
            $image = imagecreatefrompng($full_path);
            // Preserve transparency
            if ($image) {
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
        default:
            echo "Skipping unsupported file type: " . $filename . "\n";
            break; // Changed from 'continue' to 'break' to resolve warning
    }

    if ($image) {
        if (imagewebp($image, $new_full_path, 80)) { // 80 is quality
            echo "Converted " . $filename . " to " . $new_filename . "\n";
            imagedestroy($image);
            // Delete original file
            if (unlink($full_path)) {
                echo "Deleted original file: " . $filename . "\n";
            } else {
                echo "Failed to delete original file: " . $filename . "\n";
            }
        } else {
            echo "Failed to convert " . $filename . " to WebP.\n";
        }
    } else {
        echo "Failed to load image: " . $filename . "\n";
    }
}

echo "Image conversion complete.\n";
?>
