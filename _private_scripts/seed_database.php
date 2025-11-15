<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Prevent this script from running in production
if (APP_ENV === 'production') {
    die("Error: Database seeding script cannot be run in production environment.");
}

require_once __DIR__ . '/../includes/db_connect.php';
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

// --- Seed Hero Slides ---
    echo "--- Seeding Hero Slides ---\n";
    $hero_slides = [
        ['The Art of Luxe', 'Where street style meets high fashion craftsmanship.', 'Shop Now', 'shop.php', 'https://placehold.co/1920x1080/e0e0e0/000000?text=Hero+Slide+1', 1, 10],
        ['Details Matter', 'Discover our collection of meticulously crafted accessories.', 'Explore Accessories', 'shop.php?category=accessories', 'https://placehold.co/1920x1080/d0d0d0/000000?text=Hero+Slide+2', 1, 20]
    ];
    $stmt_hero = $conn->prepare("INSERT INTO hero_slides (title, subtitle, button_text, button_url, image_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt_hero) {
        echo "Error preparing hero slides statement: " . $conn->error . "\n";
    } else {
        foreach ($hero_slides as $slide) {
            $stmt_hero->bind_param("sssssii", $slide[0], $slide[1], $slide[2], $slide[3], $slide[4], $slide[5], $slide[6]);
            $stmt_hero->execute();
            echo "Hero slide '{$slide[0]}' created.\n";
        }
        $stmt_hero->close();
    }
    echo "--- Hero slides seeded ---\n\n";


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
        ['John Doe', 'john.doe@example.com', 'password123', 'customer'],
        ['Admin User', 'admin@mosse-luxe.com', 'adminpassword', 'admin']
    ];
    $user_ids = [];
    $stmt_user = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    foreach ($users as $user) {
        $hashed_password = password_hash($user[2], PASSWORD_DEFAULT);
        $stmt_user->bind_param("ssss", $user[0], $user[1], $hashed_password, $user[3]);
        $stmt_user->execute();
        $user_ids[$user[1]] = $conn->insert_id;
        echo "User '{$user[1]}' created with ID: " . $user_ids[$user[1]] . "\n";
    }
    $stmt_user->close();
    echo "--- Users seeded ---\n\n";


// --- 4. Seed Products ---
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
        ['Draft Product', 'This is a product that is not yet published.', 100.00, null, $category_ids['Accessories'], 5, 0, 0, 'https://placehold.co/600x600/000000/FFFFFF?text=Draft+Product'], // Draft product
    ];
    $product_ids = [];
    $stmt_product = $conn->prepare("INSERT INTO products (name, description, price, sale_price, category, stock, status, is_featured, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt_product) {
        echo "Error preparing products statement: " . $conn->error . "\n";
    } else {
        foreach ($products as $product) {
            $stmt_product->bind_param("ssddiiiis", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6], $product[7], $product[8]);
            $stmt_product->execute();
            $product_ids[] = $conn->insert_id;
            echo "Product '{$product[0]}' created.\n";
        }
        $stmt_product->close();
    }
    echo "--- Products seeded ---\n\n";


// --- 5. Seed New Arrivals ---
    echo "--- Seeding New Arrivals ---\n";
    $new_arrival_product_ids = array_slice($product_ids, 0, 4); // Get the first 4 product IDs
    $stmt_new_arrival = $conn->prepare("INSERT INTO new_arrivals (product_id, display_order) VALUES (?, ?)");
    $display_order = 1;
    foreach ($new_arrival_product_ids as $product_id) {
        $stmt_new_arrival->bind_param("ii", $product_id, $display_order);
        $stmt_new_arrival->execute();
        echo "Product ID $product_id added to new arrivals with display order $display_order.\n";
        $display_order++;
    }
    $stmt_new_arrival->close();
    echo "--- New arrivals seeded ---\n\n";


// --- 6. Seed "Launching Soon" Items ---
    echo "--- Seeding 'Launching Soon' Items ---\n";
    $launching_items = [
        ['The Luxe Hoodie', 'https://placehold.co/800x800/000000/FFFFFF?text=The+Luxe+Hoodie', 1],
        ['The Weekender Bag', 'https://placehold.co/800x800/000000/FFFFFF?text=The+Weekender+Bag', 1]
    ];
    $stmt_launching = $conn->prepare("INSERT INTO launching_soon (name, image, status) VALUES (?, ?, ?)");
    foreach ($launching_items as $item) {
        $stmt_launching->bind_param("ssi", $item[0], $item[1], $item[2]);
        $stmt_launching->execute();
        echo "Launching soon item '{$item[0]}' created.\n";
    }
    $stmt_launching->close();
    echo "--- 'Launching Soon' items seeded ---\n\n";


// --- 7. Seed Discount Codes ---
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


// --- 8. Seed Orders ---
    echo "--- Seeding Orders ---\n";
    $shipping_address_json = '{"firstName":"John","lastName":"Doe","email":"john.doe@example.com","address":"123 Main St","address2":"","city":"Johannesburg","province":"Gauteng","zip":"2000"}';

// Order 1: Completed order
    $stmt_order1 = $conn->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address_json) VALUES (?, ?, ?, ?)");
    if(!$stmt_order1) { die("Prepare failed on stmt_order1: " . $conn->error); }
    $total_price1 = 750.00 + 1200.00 + 100; // Two products + shipping
    $status1 = 'Delivered';
    $stmt_order1->bind_param("idss", $user_ids['john.doe@example.com'], $total_price1, $status1, $shipping_address_json);
    $stmt_order1->execute();
    $order1_id = $conn->insert_id;
    echo "Order #$order1_id created for John Doe.\n";
    $stmt_order1->close();

// Order 1 Items
    $stmt_order_item1 = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    if(!$stmt_order_item1) { die("Prepare failed on stmt_order_item1: " . $conn->error); }
    $qty1 = 1; $price1 = 750.00;
    $stmt_order_item1->bind_param("iiid", $order1_id, $product_ids[0], $qty1, $price1);
    $stmt_order_item1->execute();
    $qty2 = 1; $price2 = 1200.00;
    $stmt_order_item1->bind_param("iiid", $order1_id, $product_ids[2], $qty2, $price2);
    $stmt_order_item1->execute();
    $stmt_order_item1->close();

// Order 2: Pending order with discount
    $stmt_order2 = $conn->prepare("INSERT INTO orders (user_id, total_price, status, discount_code, discount_amount, shipping_address_json) VALUES (?, ?, ?, ?, ?, ?)");
    if(!$stmt_order2) { die("Prepare failed on stmt_order2: " . $conn->error); }
    $subtotal2 = 550.00;
    $discount_amount2 = 50.00;
    $total_price2 = $subtotal2 - $discount_amount2 + 100; // Product - discount + shipping
    $status2 = 'Paid';
    $discount_code2 = 'WINTER50';
    $stmt_order2->bind_param("idssds", $user_ids['john.doe@example.com'], $total_price2, $status2, $discount_code2, $discount_amount2, $shipping_address_json);
    $stmt_order2->execute();
    $order2_id = $conn->insert_id;
    echo "Order #$order2_id created for John Doe.\n";
    $stmt_order2->close();

// Order 2 Items
    $stmt_order_item2 = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    if(!$stmt_order_item2) { die("Prepare failed on stmt_order_item2: " . $conn->error); }
    $qty3 = 1; $price3 = 550.00;
    $stmt_order_item2->bind_param("iiid", $order2_id, $product_ids[1], $qty3, $price3);
    $stmt_order_item2->execute();
    $stmt_order_item2->close();

    echo "--- Orders seeded ---\n\n";


// --- 9. Seed Product Reviews ---
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

// --- Seed Homepage Sections ---
    echo "--- Seeding Homepage Sections ---\n";
    $homepage_sections = [
        [
            'section_key' => 'brand_statement',
            'section_name' => 'Brand Statement',
            'title' => 'Redefining Urban Luxury',
            'content' => "Mossé Luxe is not just a brand; it's a statement. We merge the raw energy of street culture with the finesse of high-end fashion, creating pieces that are both timeless and contemporary. Each item is crafted with meticulous attention to detail, designed for the discerning individual who values both style and substance.",
            'button_text' => 'Our Story',
            'button_url' => '/about.php',
            'is_active' => 1,
            'sort_order' => 10,
            'subtitle' => null
        ],
        [
            'section_key' => 'newsletter',
            'section_name' => 'Newsletter',
            'title' => 'Join The Inner Circle',
            'subtitle' => 'Be the first to know about new arrivals, exclusive offers, and behind-the-scenes content.',
            'content' => 'Enter your email address',
            'button_text' => 'Subscribe',
            'button_url' => null,
            'is_active' => 1,
            'sort_order' => 30
        ]
    ];
    $stmt_hp = $conn->prepare("INSERT INTO homepage_sections (section_key, section_name, title, subtitle, content, button_text, button_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt_hp) {
        echo "Error preparing homepage_sections statement: " . $conn->error . "\n";
    } else {
        foreach ($homepage_sections as $section) {
            $stmt_hp->bind_param("sssssssii", $section['section_key'], $section['section_name'], $section['title'], $section['subtitle'], $section['content'], $section['button_text'], $section['button_url'], $section['is_active'], $section['sort_order']);
            $stmt_hp->execute();
            echo "Homepage section '{$section['section_name']}' created.\n";
        }
        $stmt_hp->close();
    }

    echo "Database seeding complete!\n";
    echo "</pre>";

    $conn->close();
?>