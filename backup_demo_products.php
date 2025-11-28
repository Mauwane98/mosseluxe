<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// First, create backup table if needed (but since not in dump, but for demonstration)
$create_backup_sql = "
CREATE TABLE IF NOT EXISTS demo_backup_products (
  id INT(11) NOT NULL AUTO_INCREMENT,
  original_id INT(11) DEFAULT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  sale_price DECIMAL(10,2) DEFAULT NULL,
  category INT(11) NOT NULL,
  stock INT(11) NOT NULL DEFAULT 0,
  image VARCHAR(255) NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

$conn->query($create_backup_sql);

// Move demo products to backup (those with placeholder images)
$sql = "SELECT * FROM products WHERE image LIKE '%placehold.co%' OR image LIKE '%https://placehold%' OR name LIKE '%test%' OR name LIKE '%Test%'";
$result = $conn->query($sql);
$backed_up = false;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ins_sql = "INSERT INTO demo_backup_products (original_id, name, description, price, sale_price, category, stock, image, status, is_featured, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($ins_sql);
        $stmt->bind_param("issddiiisii", $row['id'], $row['name'], $row['description'], $row['price'], $row['sale_price'], $row['category'], $row['stock'], $row['image'], $row['status'], $row['is_featured'], $row['created_at']);
        if ($stmt->execute()) {
            echo "Backed up demo product: " . $row['name'] . "\n";
            $backed_up = true;
        } else {
            echo "Error backing up: " . $row['name'] . "\n";
        }
        $stmt->close();
    }
    
    // Delete from products
    if ($backed_up) {
        $conn->query("DELETE FROM products WHERE image LIKE '%placehold.co%' OR image LIKE '%https://placehold%' OR name LIKE '%test%' OR name LIKE '%Test%'");
        echo "Deleted demo products from products table.\n";
    }
} else {
    echo "No demo products found to backup.\n";
}

// Now, add seed placeholders for testing flows
$seed_products = [
    [
        'name' => 'Luxury Leather Belt',
        'description' => 'Premium handcrafted leather belt with gold buckle.',
        'price' => 250.00,
        'sale_price' => NULL,
        'category' => 1, // Assuming category exists
        'stock' => 20,
        'image' => 'https://placehold.co/600x600/333000/FFFFFF?text=Seed+Belt+1',
        'status' => 0, // draft
        'is_featured' => 0
    ],
    [
        'name' => 'Designer Wallet',
        'description' => 'Elegant wallet made from finest leather materials.',
        'price' => 150.00,
        'sale_price' => 120.00,
        'category' => 1,
        'stock' => 15,
        'image' => 'https://placehold.co/600x600/003333/FFFFFF?text=Seed+Wallet+1',
        'status' => 1, // published for testing
        'is_featured' => 1
    ]
];

foreach ($seed_products as $product) {
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, sale_price, category, stock, image, status, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdiiiisi", $product['name'], $product['description'], $product['price'], $product['sale_price'], $product['category'], $product['stock'], $product['image'], $product['status'], $product['is_featured']);
    if ($stmt->execute()) {
        echo "Added seed product: " . $product['name'] . " (status: " . ($product['status'] ? 'published' : 'draft') . ")\n";
    } else {
        echo "Error adding seed product: " . $product['name'] . " - " . $stmt->error . "\n";
    }
    $stmt->close();
}

echo "Demo products backed up and seeds added.\n";
?>
