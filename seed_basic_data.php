<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

echo "Adding basic data...\n";

try {
    // Add categories directly using INSERT IGNORE to avoid errors
    echo "Adding categories...\n";
    $categories = [
        [1, 'Accessories', 'accessories'],
        [2, 'Clothing', 'clothing'],
        [3, 'Footwear', 'footwear'],
        [4, 'Bags', 'bags']
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO categories (id, name, slug) VALUES (?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->bind_param("iss", $cat[0], $cat[1], $cat[2]);
        if ($stmt->execute()) {
            echo "✅ Added category: {$cat[1]}\n";
        } else {
            echo "⚠️  Category {$cat[1]} may already exist\n";
        }
    }
    $stmt->close();

    // Add sample products
    echo "Adding sample products...\n";
    $products = [
        [
            'name' => 'Sample Product 1',
            'description' => 'This is a sample product description.',
            'price' => 100.00,
            'image' => 'assets/images/sample1.jpg',
            'stock' => 10,
            'category' => 1,
            'status' => 1
        ],
        [
            'name' => 'Sample Product 2',
            'description' => 'Another sample product.',
            'price' => 150.00,
            'image' => 'assets/images/sample2.jpg',
            'stock' => 5,
            'category' => 2,
            'status' => 1
        ]
    ];

    $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, stock, category, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($products as $product) {
        $stmt->bind_param("ssdsiis", $product['name'], $product['description'], $product['price'], $product['image'], $product['stock'], $product['category'], $product['status']);
        if ($stmt->execute()) {
            echo "✅ Added product: {$product['name']}\n";
        } else {
            echo "❌ Error adding product: {$product['name']} - " . $stmt->error . "\n";
        }
    }
    $stmt->close();

    // Add basic settings
    echo "Adding basic settings...\n";
    $settings = [
        ['shop_title', 'Mossé Luxe Shop'],
        ['shop_h1_title', 'All Products'],
        ['shop_sub_title', 'Discover our curated collection']
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $setting) {
        $stmt->bind_param("ss", $setting[0], $setting[1]);
        $stmt->execute();
    }
    $stmt->close();

    echo "✅ Basic data seeding complete\n";

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

$conn->close();
echo "Done.\n";
?>
