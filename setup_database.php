<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

echo "Setting up database tables...\n";

try {
    // Set foreign key checks off
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Create categories table
    echo "Creating categories table...\n";
    $sql = "CREATE TABLE categories (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) DEFAULT NULL,
        description TEXT,
        image VARCHAR(500) DEFAULT NULL,
        status TINYINT(1) DEFAULT 1,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_name (name),
        UNIQUE KEY unique_slug (slug),
        KEY status (status),
        KEY sort_order (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql)) {
        echo "✅ Categories table created\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }

    // Create products table with all required columns
    echo "Creating products table...\n";
    $sql = "CREATE TABLE products (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        subtitle VARCHAR(500) DEFAULT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        sale_price DECIMAL(10,2) DEFAULT NULL,
        currency VARCHAR(10) DEFAULT 'ZAR',
        image VARCHAR(500) DEFAULT NULL,
        sku VARCHAR(100) DEFAULT NULL,
        stock INT(11) NOT NULL DEFAULT 0,
        category INT(11) DEFAULT NULL,
        is_featured TINYINT(1) DEFAULT 0,
        is_new TINYINT(1) DEFAULT 0,
        is_coming_soon TINYINT(1) DEFAULT 0,
        is_bestseller TINYINT(1) DEFAULT 0,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY category (category),
        KEY status (status),
        KEY is_featured (is_featured),
        KEY is_new (is_new),
        KEY is_coming_soon (is_coming_soon),
        KEY is_bestseller (is_bestseller)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql)) {
        echo "✅ Products table created\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }

    // Create product_images table
    echo "Creating product_images table...\n";
    $sql = "CREATE TABLE product_images (
        id INT(11) NOT NULL AUTO_INCREMENT,
        product_id INT(11) NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        media_type VARCHAR(50) DEFAULT 'image',
        variant_color VARCHAR(100) DEFAULT NULL,
        variant_size VARCHAR(100) DEFAULT NULL,
        is_primary TINYINT(1) DEFAULT 0,
        is_360_view TINYINT(1) DEFAULT 0,
        sort_order INT(11) DEFAULT 0,
        position INT(11) DEFAULT 0,
        type ENUM('image','360') DEFAULT 'image',
        url VARCHAR(500) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY product_id (product_id),
        KEY variant_color (variant_color),
        KEY variant_size (variant_size),
        KEY is_primary (is_primary),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql)) {
        echo "✅ Product images table created\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
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
        echo "❌ Error: " . $conn->error . "\n";
    }

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    echo "✅ Database setup complete\n";

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

$conn->close();
echo "Done.\n";
?>
