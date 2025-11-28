<?php
require_once 'includes/bootstrap.php';

// Connect to database
$conn = get_db_connection();

// Sample products to be added
$sampleProducts = [
    [
        'name' => 'Classic Black Hoodie',
        'description' => 'A comfortable and stylish black hoodie.',
        'price' => 1200.00,
        'category' => 1, // Assuming category 1 exists
        'stock' => 50,
        'image' => 'assets/images/product-placeholder.png'
    ],
    [
        'name' => 'White Leather Wallet',
        'description' => 'A sleek white leather wallet.',
        'price' => 800.00,
        'category' => 2, // Assuming category 2 exists
        'stock' => 30,
        'image' => 'assets/images/product-placeholder.png'
    ],
    [
        'name' => 'Urban Cargo Pants',
        'description' => 'Stylish and functional cargo pants.',
        'price' => 1500.00,
        'category' => 1,
        'stock' => 40,
        'image' => 'assets/images/product-placeholder.png'
    ],
    [
        'name' => 'Minimalist Belt',
        'description' => 'A simple and elegant belt.',
        'price' => 500.00,
        'category' => 2,
        'stock' => 60,
        'image' => 'assets/images/product-placeholder.png'
    ],
    [
        'name' => 'Mossé Luxe: Moses Edition Tee',
        'description' => 'Limited edition tee.',
        'price' => 950.00,
        'category' => 1,
        'stock' => 20,
        'image' => 'assets/images/product-placeholder.png'
    ]
];

echo "Adding sample products...\n";

$stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock, image, status) VALUES (?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE name=name");

foreach ($sampleProducts as $product) {
    $stmt->bind_param("ssdiis", $product['name'], $product['description'], $product['price'], $product['category'], $product['stock'], $product['image']);
    if ($stmt->execute()) {
        echo "✓ Added/Exists: " . $product['name'] . "\n";
    } else {
        echo "✗ Failed to add: " . $product['name'] . " - " . $stmt->error . "\n";
    }
}

$stmt->close();
$conn->close();

echo "Sample products added successfully!\n";
?>
?>
