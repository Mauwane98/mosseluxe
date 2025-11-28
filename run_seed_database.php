<?php
// Temporary script to seed database
require_once 'includes/bootstrap.php';

require_once 'includes/db_connect.php';
$conn = get_db_connection();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<pre>"; // Use preformatted text for better readability in browser

// --- 1. Clear Existing Data ---
    echo "--- Clearing all existing data ---\n";
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $tables_to_truncate = [
        'products', 'categories', 'users', 'orders', 'order_items',
        'product_reviews', 'launching_soon', 'discount_codes',
        'stock_notifications', 'admin_auth_tokens', 'messages', 'wishlist', 'new_arrivals',
        'hero_slides', 'homepage_sections'
    ];
    foreach ($tables_to_truncate as $table) {
        if ($conn->query("TRUNCATE TABLE `$table`")) {
            echo "Table `$table` cleared.\n";
        } else {
            echo "Error clearing table `$table`: " . $conn->error . "\n";
        }
    }
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "--- Data clearing complete ---\n\n";

// --- Seed Categories ---
    echo "--- Seeding Categories ---\n";
    $categories = ['Belts', 'Bracelets', 'Card Holders', 'T-Shirts', 'Accessories'];
    $category_ids = [];
    $stmt_category = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    foreach ($categories as $category) {
        $stmt_category->bind_param("s", $category);
        $stmt_category->execute();
        $category_ids[$category] = $conn->insert_id;
        echo "Category '$category' created with ID: " . $category_ids[$category] . "\n";
    }
    $stmt_category->close();
    echo "--- Categories seeded ---\n\n";

// --- Seed Products ---
    echo "--- Seeding Products ---\n";
    $products = [
        ['Classic Leather Belt', 'A timeless black leather belt, perfect for any occasion.', 750.00, null, $category_ids['Belts'], 25, 1, 1, 'https://placehold.co/600x600/000000/FFFFFF?text=Classic+Leather+Belt'],
        ['Woven Fabric Belt', 'A casual and stylish woven belt in navy blue.', 550.00, 499.99, $category_ids['Belts'], 15, 1, 0, 'https://placehold.co/600x600/000000/FFFFFF?text=Woven+Fabric+Belt'],
        ['Gold Chain Bracelet', 'An elegant and minimalist gold chain bracelet.', 1200.00, null, $category_ids['Bracelets'], 10, 1, 1, 'https://placehold.co/600x600/000000/FFFFFF?text=Gold+Chain+Bracelet'],
        ['Beaded Stone Bracelet', 'A stylish bracelet made with natural stone beads.', 450.00, null, $category_ids['Bracelets'], 30, 1, 0, 'https://placehold.co/600x600/000000/FFFFFF?text=Beaded+Stone+Bracelet'],
        ['Minimalist Card Holder', 'A slim and sleek card holder in genuine leather.', 600.00, null, $category_ids['Card Holders'], 0, 1, 1, 'https://placehold.co/600x600/000000/FFFFFF?text=Minimalist+Card+Holder'], // Out of stock
        ['Signature Logo T-Shirt', 'A premium cotton t-shirt with the Mossé Luxe logo.', 850.00, null, $category_ids['T-Shirts'], 50, 1, 1, 'https://placehold.co/600x600/000000/FFFFFF?text=Signature+Logo+T-Shirt'],
        ['Monogram Scarf', 'A luxurious silk scarf with the Mossé Luxe monogram.', 1500.00, 1250.00, $category_ids['Accessories'], 12, 1, 0, 'https://placehold.co/600x600/000000/FFFFFF?text=Monogram+Scarf'],
        ['Silver Cufflinks', 'Elegant silver cufflinks for a sophisticated look.', 950.00, null, $category_ids['Accessories'], 20, 1, 0, 'https://placehold.co/600x600/000000/FFFFFF?text=Silver+Cufflinks'],
    ];
    $stmt_product = $conn->prepare("INSERT INTO products (name, description, price, sale_price, category, stock, status, is_featured, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt_product) {
        echo "Error preparing products statement: " . $conn->error . "\n";
    } else {
        foreach ($products as $product) {
            $stmt_product->bind_param("ssddiiiis", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6], $product[7], $product[8]);
            $stmt_product->execute();
            echo "Product '{$product[0]}' created.\n";
        }
        $stmt_product->close();
    }
    echo "--- Products seeded ---\n\n";

// Add settings
echo "--- Adding Settings ---\n";
$settings = [
    ['shop_title', 'Mossé Luxe Shop'],
    ['shop_h1_title', 'All Products'],
    ['shop_sub_title', 'Discover our curated collection of luxury streetwear']
];

$stmt_setting = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
foreach ($settings as $setting) {
    $stmt_setting->bind_param("ss", $setting[0], $setting[1]);
    $stmt_setting->execute();
    echo "Setting '{$setting[0]}' added.\n";
}
$stmt_setting->close();
echo "--- Settings added ---\n\n";

    echo "Database seeding complete!\n";
    echo "</pre>";

    $conn->close();
?>
