<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

try {
    // Drop tables if they exist with issues
    $conn->query("DROP TABLE IF EXISTS order_items");
    $conn->query("DROP TABLE IF EXISTS orders");
    $conn->query("DROP TABLE IF EXISTS user_carts");

    // Create orders table
    $conn->query("
        CREATE TABLE orders (
          id INT(11) NOT NULL AUTO_INCREMENT,
          user_id INT(11) DEFAULT NULL,
          order_id VARCHAR(50) NOT NULL UNIQUE,
          total_price DECIMAL(10,2) NOT NULL,
          status ENUM('Pending','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
          shipping_address_json JSON DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY user_id (user_id),
          KEY status (status),
          KEY created_at (created_at)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Created orders table\n";

    // Create order_items table
    $conn->query("
        CREATE TABLE IF NOT EXISTS order_items (
          id INT(11) NOT NULL AUTO_INCREMENT,
          order_id INT(11) NOT NULL,
          product_id INT(11) NOT NULL,
          quantity INT(11) NOT NULL,
          price DECIMAL(10,2) NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY order_id (order_id),
          KEY product_id (product_id),
          FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
          FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Created order_items table\n";

    // Create user_carts table for persistent carts
    $conn->query("
        CREATE TABLE IF NOT EXISTS user_carts (
          id INT(11) NOT NULL AUTO_INCREMENT,
          user_id INT(11) NOT NULL,
          product_id INT(11) NOT NULL,
          quantity INT(11) NOT NULL DEFAULT 1,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY unique_user_product (user_id, product_id),
          KEY user_id (user_id),
          KEY product_id (product_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Created user_carts table\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Done.\n";
?>
