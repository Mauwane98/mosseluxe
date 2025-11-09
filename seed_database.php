<?php
require_once 'includes/db_connect.php';
$conn = get_db_connection();

echo "<pre>"; // Use preformatted text for better readability in browser

// --- 1. Clear Existing Data ---
echo "--- Clearing all existing data ---\n";
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$tables_to_truncate = [
    'products', 'categories', 'users', 'orders', 'order_items', 
    'product_reviews', 'launching_soon', 'discount_codes', 
    'stock_notifications', 'admin_auth_tokens', 'messages', 'wishlist'
];
foreach ($tables_to_truncate as $table) {
    $conn->query("TRUNCATE TABLE `$table`");
    echo "Table `$table` cleared.\n";
}
$conn->query("SET FOREIGN_KEY_CHECKS = 1");
echo "--- Data clearing complete ---\n\n";


// --- 2. Seed Categories ---
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


// --- 3. Seed Users ---
echo "--- Seeding Users ---\n";
$users = [
    ['Admin User', 'admin@mosseluxe.com', password_hash('admin123', PASSWORD_DEFAULT), 'admin'],
    ['John Doe', 'john.doe@example.com', password_hash('password123', PASSWORD_DEFAULT), 'user']
];
$user_ids = [];
$stmt_user = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
foreach ($users as $user) {
    $stmt_user->bind_param("ssss", $user[0], $user[1], $user[2], $user[3]);
    $stmt_user->execute();
    $user_ids[$user[1]] = $conn->insert_id;
    echo "User '{$user[0]}' created with ID: " . $user_ids[$user[1]] . "\n";
}
$stmt_user->close();
echo "--- Users seeded ---\n\n";


// --- 4. Seed Products ---
echo "--- Seeding Products ---\n";
$products = [
    ['Classic Leather Belt', 'A timeless black leather belt, perfect for any occasion.', 750.00, null, $category_ids['Belts'], 25, 1, 1, 'assets/images/product-belt-1.jpg'],
    ['Woven Fabric Belt', 'A casual and stylish woven belt in navy blue.', 550.00, 499.99, $category_ids['Belts'], 15, 1, 0, 'assets/images/product-belt-2.jpg'],
    ['Gold Chain Bracelet', 'An elegant and minimalist gold chain bracelet.', 1200.00, null, $category_ids['Bracelets'], 10, 1, 1, 'assets/images/product-bracelet-1.jpg'],
    ['Beaded Stone Bracelet', 'A stylish bracelet made with natural stone beads.', 450.00, null, $category_ids['Bracelets'], 30, 1, 0, 'assets/images/product-bracelet-2.jpg'],
    ['Minimalist Card Holder', 'A slim and sleek card holder in genuine leather.', 600.00, null, $category_ids['Card Holders'], 0, 1, 1, 'assets/images/product-cardholder-1.jpg'], // Out of stock
    ['Signature Logo T-Shirt', 'A premium cotton t-shirt with the Mossé Luxe logo.', 850.00, null, $category_ids['T-Shirts'], 50, 1, 1, 'assets/images/product-tshirt-1.jpg'],
    ['Monogram Scarf', 'A luxurious silk scarf with the Mossé Luxe monogram.', 1500.00, 1250.00, $category_ids['Accessories'], 12, 1, 0, 'assets/images/product-scarf-1.jpg'],
    ['Silver Cufflinks', 'Elegant silver cufflinks for a sophisticated look.', 950.00, null, $category_ids['Accessories'], 20, 1, 0, 'assets/images/product-cufflinks-1.jpg'],
    ['Draft Product', 'This is a product that is not yet published.', 100.00, null, $category_ids['Accessories'], 5, 0, 0, 'assets/images/placeholder.jpg'], // Draft product
];
$product_ids = [];
$stmt_product = $conn->prepare("INSERT INTO products (name, description, price, sale_price, category, stock, status, is_featured, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($products as $product) {
    $stmt_product->bind_param("ssddiiiss", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6], $product[7], $product[8]);
    $stmt_product->execute();
    $product_ids[] = $conn->insert_id;
    echo "Product '{$product[0]}' created.\n";
}
$stmt_product->close();
echo "--- Products seeded ---\n\n";


// --- 5. Seed "Launching Soon" Items ---
echo "--- Seeding 'Launching Soon' Items ---\n";
$launching_items = [
    ['The Luxe Hoodie', 'assets/images/launching-hoodie.jpg', 1],
    ['The Weekender Bag', 'assets/images/launching-bag.jpg', 1]
];
$stmt_launching = $conn->prepare("INSERT INTO launching_soon (name, image, status) VALUES (?, ?, ?)");
foreach ($launching_items as $item) {
    $stmt_launching->bind_param("ssi", $item[0], $item[1], $item[2]);
    $stmt_launching->execute();
    echo "Launching soon item '{$item[0]}' created.\n";
}
$stmt_launching->close();
echo "--- 'Launching Soon' items seeded ---\n\n";


// --- 6. Seed Discount Codes ---
echo "--- Seeding Discount Codes ---\n";
$discounts = [
    ['WELCOME10', 'percentage', 10.00, 100, 1, null],
    ['WINTER50', 'fixed', 50.00, 50, 1, '2025-12-31 23:59:59']
];
$stmt_discount = $conn->prepare("INSERT INTO discount_codes (code, type, value, usage_limit, is_active, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($discounts as $discount) {
    $stmt_discount->bind_param("ssdiis", $discount[0], $discount[1], $discount[2], $discount[3], $discount[4], $discount[5]);
    $stmt_discount->execute();
    echo "Discount code '{$discount[0]}' created.\n";
}
$stmt_discount->close();
echo "--- Discount codes seeded ---\n\n";


// --- 7. Seed Orders ---
echo "--- Seeding Orders ---\n";
$shipping_address_json = '{"firstName":"John","lastName":"Doe","email":"john.doe@example.com","address":"123 Main St","address2":"","city":"Johannesburg","province":"Gauteng","zip":"2000"}';

// Order 1: Completed order
$stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address_json) VALUES (?, ?, ?, ?)");
$total_price1 = 750.00 + 1200.00 + 100; // Two products + shipping
$status1 = 'Delivered';
$stmt_order->bind_param("idss", $user_ids['john.doe@example.com'], $total_price1, $status1, $shipping_address_json);
$stmt_order->execute();
$order1_id = $conn->insert_id;
echo "Order #$order1_id created for John Doe.\n";

// Order 1 Items
$stmt_order_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
$qty1 = 1; $price1 = 750.00;
$stmt_order_item->bind_param("iiid", $order1_id, $product_ids[0], $qty1, $price1);
$stmt_order_item->execute();
$qty2 = 1; $price2 = 1200.00;
$stmt_order_item->bind_param("iiid", $order1_id, $product_ids[2], $qty2, $price2);
$stmt_order_item->execute();

// Order 2: Pending order with discount
$stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_price, status, discount_code, discount_amount, shipping_address_json) VALUES (?, ?, ?, ?, ?, ?)");
$subtotal2 = 550.00;
$discount_amount2 = 50.00;
$total_price2 = $subtotal2 - $discount_amount2 + 100; // Product - discount + shipping
$status2 = 'Paid';
$discount_code2 = 'WINTER50';
$stmt_order->bind_param("idssds", $user_ids['john.doe@example.com'], $total_price2, $status2, $discount_code2, $discount_amount2, $shipping_address_json);
$stmt_order->execute();
$order2_id = $conn->insert_id;
echo "Order #$order2_id created for John Doe.\n";

// Order 2 Items
$qty3 = 1; $price3 = 550.00;
$stmt_order_item->bind_param("iiid", $order2_id, $product_ids[1], $qty3, $price3);
$stmt_order_item->execute();

$stmt_order->close();
$stmt_order_item->close();
echo "--- Orders seeded ---\n\n";


// --- 8. Seed Product Reviews ---
echo "--- Seeding Product Reviews ---\n";
$reviews = [
    [$product_ids[0], $user_ids['john.doe@example.com'], 5, 'Absolutely fantastic quality. The leather is soft and it looks very premium. Highly recommended!', 1],
    [$product_ids[2], $user_ids['john.doe@example.com'], 4, 'Beautiful bracelet, I wear it every day. It feels a bit lighter than I expected, but still great.', 1],
    [$product_ids[1], $user_ids['john.doe@example.com'], 5, 'Love this belt! The color is great and it fits perfectly. Will be buying another one.', 0] // Pending approval
];
$stmt_review = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, rating, review_text, is_approved) VALUES (?, ?, ?, ?, ?)");
foreach ($reviews as $review) {
    $stmt_review->bind_param("iiisi", $review[0], $review[1], $review[2], $review[3], $review[4]);
    $stmt_review->execute();
    echo "Review added for product ID {$review[0]}.\n";
}
$stmt_review->close();
echo "--- Product reviews seeded ---\n\n";

echo "Database seeding complete!\n";
echo "</pre>";

$conn->close();
?>
