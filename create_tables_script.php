<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Create product_variants table
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

// Create carts table
try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS carts (
          id INT(11) NOT NULL AUTO_INCREMENT,
          session_id VARCHAR(255) DEFAULT NULL,
          user_id INT(11) DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY unique_session (session_id),
          UNIQUE KEY unique_user (user_id),
          KEY session_id (session_id),
          KEY user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Created carts table\n";
} catch (Exception $e) {
    echo "Error creating carts: " . $e->getMessage() . "\n";
}

// Create cart_items table
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
          KEY variant_id (variant_id),
          FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
          FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
          FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Created cart_items table\n";
} catch (Exception $e) {
    echo "Error creating cart_items: " . $e->getMessage() . "\n";
}

// Create orders_or_inquiries table
try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS orders_or_inquiries (
          id INT(11) NOT NULL AUTO_INCREMENT,
          cart_snapshot JSON DEFAULT NULL,
          contact_info JSON DEFAULT NULL,
          type ENUM('order','inquiry','chat') DEFAULT 'inquiry',
          status VARCHAR(50) DEFAULT 'pending',
          reference VARCHAR(255) DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY status (status),
          KEY type (type),
          KEY reference (reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Created orders_or_inquiries table\n";
} catch (Exception $e) {
    echo "Error creating orders_or_inquiries: " . $e->getMessage() . "\n";
}

// Alter product_images table
try {
    $conn->query("ALTER TABLE product_images ADD COLUMN IF NOT EXISTS position INT(11) DEFAULT 0 AFTER is_primary");
    echo "Added position column to product_images\n";
} catch (Exception $e) {
    echo "Error adding position: " . $e->getMessage() . "\n";
}

try {
    $conn->query("ALTER TABLE product_images ADD COLUMN IF NOT EXISTS type ENUM('image','360') DEFAULT 'image' AFTER position");
    echo "Added type column to product_images\n";
} catch (Exception $e) {
    echo "Error adding type: " . $e->getMessage() . "\n";
}

// Update type based on existing data
try {
    $conn->query("UPDATE product_images SET type = '360' WHERE is_360_view = 1");
    echo "Updated 360 types in product_images\n";
} catch (Exception $e) {
    echo "Error updating types: " . $e->getMessage() . "\n";
}

echo "Done.\n";
?>
