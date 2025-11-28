<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS product_images (
          id INT(11) NOT NULL AUTO_INCREMENT,
          product_id INT(11) NOT NULL,
          image_path VARCHAR(500) NOT NULL,
          media_type ENUM('image','video') DEFAULT 'image',
          variant_color VARCHAR(50) DEFAULT NULL,
          variant_size VARCHAR(50) DEFAULT NULL,
          is_primary BOOLEAN DEFAULT FALSE,
          sort_order INT(11) DEFAULT 0,
          is_360_view BOOLEAN DEFAULT FALSE,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY product_id (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Created product_images table\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Now add the new columns
try {
    $conn->query("ALTER TABLE product_images ADD COLUMN IF NOT EXISTS position INT(11) DEFAULT 0 AFTER is_primary");
    echo "Added position column\n";
} catch (Exception $e) {
    echo "Error adding position: " . $e->getMessage() . "\n";
}

try {
    $conn->query("ALTER TABLE product_images ADD COLUMN IF NOT EXISTS type ENUM('image','360') DEFAULT 'image' AFTER position");
    echo "Added type column\n";
} catch (Exception $e) {
    echo "Error adding type: " . $e->getMessage() . "\n";
}

try {
    $conn->query("ALTER TABLE product_images ADD COLUMN IF NOT EXISTS url VARCHAR(500) DEFAULT NULL AFTER is_primary");
    echo "Added url column\n";
} catch (Exception $e) {
    echo "Error adding url: " . $e->getMessage() . "\n";
}

// Update type based on existing
try {
    $conn->query("UPDATE product_images SET type = '360' WHERE is_360_view = 1");
    echo "Updated types\n";
} catch (Exception $e) {
    echo "Error updating types (table may be empty)\n";
}

// Create product_variants
try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS product_variants (
          id INT(11) NOT NULL AUTO_INCREMENT,
          product_id INT(11) NOT NULL,
          name VARCHAR(255) NOT NULL,
          sku VARCHAR(255) DEFAULT NULL,
          price DECIMAL(10,2) DEFAULT NULL,
          stock INT(11) NOT NULL DEFAULT 0,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY product_id (product_id),
          FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Created product_variants table\n";
} catch (Exception $e) {
    echo "Error creating product_variants: " . $e->getMessage() . "\n";
}

// Create cart_items
try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS cart_items (
          id INT(11) NOT NULL AUTO_INCREMENT,
          cart_id INT(11) NOT NULL,
          product_id INT(11) NOT NULL,
          variant_id INT(11) DEFAULT NULL,
          qty INT(11) NOT NULL DEFAULT 1,
          price_at_add DECIMAL(10,2) NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY cart_id (cart_id),
          KEY product_id (product_id),
          KEY variant_id (variant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Created cart_items table\n";
    // Add foreign keys separately to avoid issues
    $conn->query("ALTER TABLE cart_items ADD CONSTRAINT fk_cart_id FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE");
    $conn->query("ALTER TABLE cart_items ADD CONSTRAINT fk_product_item FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE");
    echo "Added cart_items foreign keys\n";
} catch (Exception $e) {
    echo "Error with cart_items: " . $e->getMessage() . "\n";
}

echo "Done.\n";
?>
