<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

echo "Creating basic tables...\n";

// Create categories table first (no foreign keys)
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(500) DEFAULT NULL,
    status TINYINT(1) DEFAULT 1,
    sort_order INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "✅ Categories table created\n";
} else {
    echo "❌ Error creating categories: " . $conn->error . "\n";
}

// Create products table (references categories, but let's make it work)
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) DEFAULT NULL,
    description TEXT,
    short_description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    category INT(11) DEFAULT NULL,
    stock INT(11) DEFAULT 0,
    stock_status VARCHAR(50) DEFAULT 'in_stock',
    image VARCHAR(500) DEFAULT NULL,
    gallery TEXT,
    sku VARCHAR(100) DEFAULT NULL,
    status TINYINT(1) DEFAULT 1 COMMENT '1=active, 0=inactive',
    is_featured TINYINT(1) DEFAULT 0,
    is_bestseller TINYINT(1) DEFAULT 0,
    is_new TINYINT(1) DEFAULT 0,
    is_coming_soon TINYINT(1) DEFAULT 0,
    weight DECIMAL(10,2) DEFAULT NULL,
    dimensions VARCHAR(100) DEFAULT NULL,
    tags VARCHAR(500) DEFAULT NULL,
    seo_title VARCHAR(255) DEFAULT NULL,
    seo_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY category (category),
    KEY status (status),
    UNIQUE KEY slug (slug)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "✅ Products table created\n";
} else {
    echo "❌ Error creating products: " . $conn->error . "\n";
}

// Create product_images table
$sql = "CREATE TABLE IF NOT EXISTS product_images (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    media_type ENUM('image','video','360') DEFAULT 'image',
    variant_color VARCHAR(100) DEFAULT NULL,
    variant_size VARCHAR(100) DEFAULT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT(11) DEFAULT 0,
    is_360_view TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY product_id (product_id),
    KEY media_type (media_type)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "✅ Product images table created\n";
} else {
    echo "❌ Error creating product_images: " . $conn->error . "\n";
}

// Create settings table
$sql = "CREATE TABLE IF NOT EXISTS settings (
    id INT(11) NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(255) NOT NULL,
    setting_value LONGTEXT,
    setting_group VARCHAR(100) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY setting_key (setting_key),
    KEY setting_group (setting_group)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "✅ Settings table created\n";
} else {
    echo "❌ Error creating settings: " . $conn->error . "\n";
}

echo "Basic tables creation complete!\n";
?>
