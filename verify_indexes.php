<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// List of tables to check for indexes
$tables_to_check = [
    'users' => ['email'],
    'pages' => ['slug', 'status', 'created_at'],
    'products' => ['category', 'status', 'is_featured', 'created_at'],
    'categories' => ['name'],
    'orders' => ['user_id', 'status'],
    'order_items' => ['order_id', 'product_id'],
    'messages' => ['is_read', 'received_at'],
    'homepage_sections' => ['section_key', 'is_active', 'sort_order'],
    'hero_slides' => ['is_active', 'sort_order'],
    'footer_links' => ['link_group', 'status'],
    'products' => ['category'], // FK
    'orders' => ['user_id'], // FK
    'order_items' => ['order_id', 'product_id'], // FKs
    // Add more tables as needed
];

echo "Checking and adding missing indexes...\n";

foreach ($tables_to_check as $table => $columns) {
    // Get current indexes for the table
    $result = $conn->query("SHOW INDEX FROM $table");
    $existing_indexes = [];
    while ($row = $result->fetch_assoc()) {
        $existing_indexes[] = $row['Column_name'];
    }
    
    foreach ($columns as $col) {
        if (!in_array($col, $existing_indexes)) {
            // Add index
            if (strpos($col, '_id') !== false) { // FK columns
                $index_name = "idx_{$table}_{$col}";
                $sql = "CREATE INDEX {$index_name} ON {$table}({$col})";
            } elseif ($col === 'slug') {
                $index_name = "idx_{$table}_{$col}";
                $sql = "CREATE UNIQUE INDEX {$index_name} ON {$table}({$col})";
            } else {
                $index_name = "idx_{$table}_{$col}";
                $sql = "CREATE INDEX {$index_name} ON {$table}({$col})";
            }
            
            if ($conn->query($sql)) {
                echo "Added index on {$table}({$col})\n";
            } else {
                echo "Error adding index on {$table}({$col}): " . $conn->error . "\n";
            }
        } else {
            echo "Index on {$table}({$col}) already exists.\n";
        }
    }
}

echo "Index verification complete.\n";
?>
