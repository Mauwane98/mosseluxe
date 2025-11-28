<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

echo "Creating essential tables...\n";

try {
    // Drop problematic tables first (with discard tablespace if needed)
    $tables_to_drop = ['settings', 'products', 'categories', 'product_images'];

    foreach ($tables_to_drop as $table) {
        echo "Dropping table: $table\n";
        $conn->query("DROP TABLE IF EXISTS `$table`");
    }

    // Create settings table
    echo "Creating settings table...\n";
    $sql = "CREATE TABLE settings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(255) NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY setting_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql)) {
        echo "✅ Settings table created\n";
    } else {
        echo "❌ Settings table error: " . $conn->error . "\n";
    }

    // Create categories table
    echo "Creating categories table...\n";
    $sql = "CREATE TABLE categories (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) DEFAULT NULL,
        status TINYINT(1) DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE KEY unique_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql)) {
        echo "✅ Categories table created\n";
    } else {
        echo "❌ Categories table error: " . $conn->error . "\n";
    }

    // Create products table
    echo "Creating products table...\n";
    $sql = "CREATE TABLE products (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        sale_price DECIMAL(10,2) DEFAULT NULL,
        image VARCHAR(500) DEFAULT NULL,
        stock INT(11) NOT NULL DEFAULT 0,
        category INT(11) DEFAULT NULL,
        is_featured TINYINT(1) DEFAULT 0,
        is_new TINYINT(1) DEFAULT 0,
        is_coming_soon TINYINT(1) DEFAULT 0,
        is_bestseller TINYINT(1) DEFAULT 0,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql)) {
        echo "✅ Products table created\n";
    } else {
        echo "❌ Products table error: " . $conn->error . "\n";
    }

    // Add some basic data
    echo "Adding basic settings...\n";
    $settings = [
        ['shop_title', 'Shop'],
        ['shop_h1_title', 'All Products'],
        ['shop_sub_title', 'Discover our collection']
    ];

    $stmt_setting = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $setting) {
        $stmt_setting->bind_param("ss", $setting[0], $setting[1]);
        if ($stmt_setting->execute()) {
            echo "✅ Added setting: {$setting[0]}\n";
        }
    }
    $stmt_setting->close();

    echo "Adding categories...\n";
    $categories = [
        ['Accessories', 'accessories'],
        ['Clothing', 'clothing']
    ];

    $stmt_cat = $conn->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
    foreach ($categories as $cat) {
        $stmt_cat->bind_param("ss", $cat[0], $cat[1]);
        if ($stmt_cat->execute()) {
            echo "✅ Added category: {$cat[0]}\n";
        }
    }
    $stmt_cat->close();

    echo "Adding sample product...\n";
    $product = [
        'Sample Product',
        'This is a sample product description.',
        100.00,
        'assets/images/sample.jpg',
        10,
        1,
        1
    ];

    $stmt_prod = $conn->prepare("INSERT INTO products (name, description, price, image, stock, category, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_prod->bind_param("ssdsiis", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6]);
    if ($stmt_prod->execute()) {
        echo "✅ Added sample product\n";
    }
    $stmt_prod->close();

    echo "✅ Essential tables and data created\n";

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

$conn->close();
echo "Done.\n";
?>
