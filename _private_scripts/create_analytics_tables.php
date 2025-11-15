<?php
require_once __DIR__ . '/../includes/bootstrap.php';

echo "Creating analytics tables...\n";

// Connect to database
$conn = get_db_connection();

echo "Creating analytics_page_views table...\n";
// Page views analytics
$create_page_views = "
CREATE TABLE IF NOT EXISTS analytics_page_views (
    id INT(11) NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(255) NOT NULL,
    user_id INT(11) DEFAULT NULL,
    page_type VARCHAR(50) DEFAULT NULL,
    page_id INT(11) DEFAULT NULL,
    user_agent TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    referrer TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY session_id (session_id),
    KEY user_id (user_id),
    KEY page_type (page_type),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    if ($conn->query($create_page_views) === TRUE) {
        echo "✓ Successfully created analytics_page_views table\n";
    } else {
        throw new Exception("Failed to create analytics_page_views table: " . $conn->error);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "Creating analytics_product_interactions table...\n";
// Product interaction analytics
$create_product_interactions = "
CREATE TABLE IF NOT EXISTS analytics_product_interactions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(255) NOT NULL,
    user_id INT(11) DEFAULT NULL,
    product_id INT(11) NOT NULL,
    interaction_type ENUM('view', 'click', 'hover', 'share', 'review_read') DEFAULT 'view',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY session_id (session_id),
    KEY user_id (user_id),
    KEY product_id (product_id),
    KEY interaction_type (interaction_type),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    if ($conn->query($create_product_interactions) === TRUE) {
        echo "✓ Successfully created analytics_product_interactions table\n";
    } else {
        throw new Exception("Failed to create analytics_product_interactions table: " . $conn->error);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "Creating analytics_cart_actions table...\n";
// Cart action analytics
$create_cart_actions = "
CREATE TABLE IF NOT EXISTS analytics_cart_actions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(255) NOT NULL,
    user_id INT(11) DEFAULT NULL,
    product_id INT(11) NOT NULL,
    action ENUM('add', 'remove', 'update', 'checkout', 'purchase') NOT NULL,
    quantity INT(11) DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY session_id (session_id),
    KEY user_id (user_id),
    KEY product_id (product_id),
    KEY action (action),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    if ($conn->query($create_cart_actions) === TRUE) {
        echo "✓ Successfully created analytics_cart_actions table\n";
    } else {
        throw new Exception("Failed to create analytics_cart_actions table: " . $conn->error);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "creating analytics_search_queries table...\n";
// Search analytics
$create_search_queries = "
CREATE TABLE IF NOT EXISTS analytics_search_queries (
    id INT(11) NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(255) NOT NULL,
    user_id INT(11) DEFAULT NULL,
    query TEXT NOT NULL,
    results_count INT(11) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY session_id (session_id),
    KEY user_id (user_id),
    KEY created_at (created_at),
    FULLTEXT KEY query (query)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    if ($conn->query($create_search_queries) === TRUE) {
        echo "✓ Successfully created analytics_search_queries table\n";
    } else {
        throw new Exception("Failed to create analytics_search_queries table: " . $conn->error);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "Adding session_id to orders table...\n";
// Add session_id to orders for analytics correlation
$alter_orders = "ALTER TABLE orders ADD COLUMN session_id VARCHAR(255) DEFAULT NULL AFTER user_id";

try {
    if ($conn->query($alter_orders) === TRUE) {
        echo "✓ Successfully added session_id column to orders table\n";
    } else {
        if ($conn->errno === 1060) { // Column already exists
            echo "✓ session_id column already exists in orders table\n";
        } else {
            throw new Exception("Failed to add session_id column: " . $conn->error);
        }
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "Analytics tables setup complete!\n";
echo "\nTo use analytics features:\n";
echo "1. Add tracking calls in your PHP templates\n";
echo "2. Create analytics dashboard to display insights\n";
echo "3. Set up scheduled cleanup of old analytics data\n";
echo "4. Integrate with Google Analytics and Facebook Pixel\n";
echo "\nExample usage:\n";
echo "- track_page_view('product', \$product_id);\n";
echo "- track_product_interaction(\$product_id, 'view');\n";
echo "- track_cart_action(\$product_id, 'add', \$quantity);\n";

$conn->close();
?>
