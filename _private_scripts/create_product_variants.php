<?php
require_once __DIR__ . '/../includes/bootstrap.php';

echo "Creating product variants system...\n";

// Connect to database
$conn = get_db_connection();

echo "Adding product variant type column...\n";
// Add variant_type to products table (e.g., 'none', 'size', 'color', 'size-color')
$alter_sql1 = "ALTER TABLE products ADD COLUMN variant_type ENUM('none', 'size', 'color', 'size-color') DEFAULT 'none' AFTER description";

try {
    if ($conn->query($alter_sql1) === TRUE) {
        echo "✓ Successfully added variant_type column\n";
    } else {
        if ($conn->errno === 1060) { // Column already exists
            echo "✓ variant_type column already exists\n";
        } else {
            throw new Exception("Failed to add column: " . $conn->error);
        }
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "Creating product_variants table...\n";
// Create product_variants table
$create_variants_table = "
CREATE TABLE IF NOT EXISTS product_variants (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    variant_name VARCHAR(100) NOT NULL,
    variant_value VARCHAR(100) NOT NULL,
    sku VARCHAR(100) DEFAULT NULL,
    stock INT(11) NOT NULL DEFAULT 0,
    price_modifier DECIMAL(10,2) DEFAULT 0.00,
    sort_order INT(11) DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY unique_variant (product_id, variant_name, variant_value),
    KEY product_id (product_id),
    KEY variant_name (variant_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    if ($conn->query($create_variants_table) === TRUE) {
        echo "✓ Successfully created product_variants table\n";
    } else {
        throw new Exception("Failed to create product_variants table: " . $conn->error);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "Creating variant_options table for predefined options...\n";
// Create variant_options table for predefined variant options
$create_variant_options_table = "
CREATE TABLE IF NOT EXISTS variant_options (
    id INT(11) NOT NULL AUTO_INCREMENT,
    variant_type ENUM('size', 'color') NOT NULL,
    option_value VARCHAR(100) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    sort_order INT(11) DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY unique_option (variant_type, option_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    if ($conn->query($create_variant_options_table) === TRUE) {
        echo "✓ Successfully created variant_options table\n";
    } else {
        throw new Exception("Failed to create variant_options table: " . $conn->error);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "Populating variant_options with default values...\n";
// Insert default variant options
$default_sizes = [
    ['S', 'Small'],
    ['M', 'Medium'],
    ['L', 'Large'],
    ['XL', 'Extra Large'],
    ['XXL', 'Double Extra Large']
];

$default_colors = [
    ['Black', 'Black'],
    ['White', 'White'],
    ['Red', 'Red'],
    ['Blue', 'Blue'],
    ['Green', 'Green'],
    ['Yellow', 'Yellow'],
    ['Purple', 'Purple'],
    ['Pink', 'Pink'],
    ['Gray', 'Gray'],
    ['Navy', 'Navy']
];

try {
    foreach ($default_sizes as $sort_order => $size) {
        $conn->query("INSERT IGNORE INTO variant_options (variant_type, option_value, display_name, sort_order) VALUES ('size', '{$size[0]}', '{$size[1]}', $sort_order)");
    }

    foreach ($default_colors as $sort_order => $color) {
        $conn->query("INSERT IGNORE INTO variant_options (variant_type, option_value, display_name, sort_order) VALUES ('color', '{$color[0]}', '{$color[1]}', $sort_order)");
    }

    echo "✓ Successfully populated variant_options with default values\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "Updating products table to use variants...\n";
// For existing products, set variant_type to 'none' to maintain compatibility
try {
    $conn->query("UPDATE products SET variant_type = 'none' WHERE variant_type IS NULL");
    echo "✓ Updated existing products to use 'none' variant type\n";
} catch (Exception $e) {
    echo "✗ Error updating existing products: " . $e->getMessage() . "\n";
}

echo "Product variants system setup complete!\n";
echo "\nTo use variants:\n";
echo "1. Edit a product and set variant_type to 'size', 'color', or 'size-color'\n";
echo "2. Add variant options in the variant management section\n";
echo "3. Each variant can have its own stock level and price modifier\n";
echo "\nExample:\n";
echo "- Product: 'T-Shirt' with variant_type: 'size-color'\n";
echo "- Variants: Small Red (+R50), Medium Blue (+R0), Large Black (-R25)\n";

$conn->close();
?>
