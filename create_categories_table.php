<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

echo "Creating categories table...\n";

try {
    $sql = "CREATE TABLE IF NOT EXISTS categories (
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
        echo "✅ Categories table created successfully\n";
    } else {
        echo "❌ Error creating categories table: " . $conn->error . "\n";
    }

    // Add some default categories
    $categories = [
        ['Accessories', 'accessories'],
        ['Clothing', 'clothing'],
        ['Footwear', 'footwear'],
        ['Bags', 'bags']
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)");
    foreach ($categories as $cat) {
        $stmt->bind_param("ss", $cat[0], $cat[1]);
        $stmt->execute();
    }
    $stmt->close();

    echo "✅ Default categories added\n";

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

$conn->close();
echo "Done.\n";
?>
