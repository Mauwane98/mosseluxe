<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Add missing columns to pages table
$pages_columns_to_add = [
    'subtitle' => "ALTER TABLE pages ADD COLUMN IF NOT EXISTS subtitle VARCHAR(255) DEFAULT NULL",
    'meta_title' => "ALTER TABLE pages ADD COLUMN IF NOT EXISTS meta_title VARCHAR(255) DEFAULT NULL",
    'meta_description' => "ALTER TABLE pages ADD COLUMN IF NOT EXISTS meta_description TEXT DEFAULT NULL",
    'updated_at' => "ALTER TABLE pages ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

foreach ($pages_columns_to_add as $col => $sql) {
    try {
        $conn->query($sql);
        echo "Attempted to add column " . htmlspecialchars($col) . " to pages (already exists or added).\n";
    } catch (Exception $e) {
        echo "Error with column " . htmlspecialchars($col) . ": " . htmlspecialchars($e->getMessage()) . "\n";
    }
}

// Create hero_slides table if not exists
$result = $conn->query("SHOW TABLES LIKE 'hero_slides'");
if ($result->num_rows == 0) {
    $sql = "
    CREATE TABLE hero_slides (
      id INT(11) NOT NULL AUTO_INCREMENT,
      title VARCHAR(255) DEFAULT NULL,
      subtitle VARCHAR(255) DEFAULT NULL,
      button_text VARCHAR(100) DEFAULT NULL,
      button_url VARCHAR(255) DEFAULT NULL,
      image_url VARCHAR(255) DEFAULT NULL,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      sort_order INT(11) NOT NULL DEFAULT 0,
      button_style VARCHAR(50) DEFAULT 'wide',
      button_visibility TINYINT(1) DEFAULT 1,
      PRIMARY KEY (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->query($sql);
    echo "Created hero_slides table.\n";
} else {
    echo "hero_slides table already exists.\n";
}

// Create footer_links table if not exists
$result = $conn->query("SHOW TABLES LIKE 'footer_links'");
if ($result->num_rows == 0) {
    $sql = "
    CREATE TABLE footer_links (
      id INT(11) NOT NULL AUTO_INCREMENT,
      link_group VARCHAR(100) NOT NULL,
      title VARCHAR(255) NOT NULL,
      url VARCHAR(255) NOT NULL,
      sort_order INT(11) NOT NULL DEFAULT 0,
      status TINYINT(1) NOT NULL DEFAULT 1,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_footer_group (link_group),
      KEY idx_footer_status (status)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->query($sql);
    echo "Created footer_links table.\n";
} else {
    echo "footer_links table already exists.\n";
}

// Add indexes for hero_slides if not exist (simple check create)
try {
    $conn->query("CREATE INDEX IF NOT EXISTS idx_hero_slides_active ON hero_slides(is_active)");
    echo "Index idx_hero_slides_active added or already exists.\n";
} catch (Exception $e) {
    echo "Index idx_hero_slides_active: " . htmlspecialchars($e->getMessage()) . "\n";
}

try {
    $conn->query("CREATE INDEX IF NOT EXISTS idx_hero_slides_order ON hero_slides(sort_order)");
    echo "Index idx_hero_slides_order added or already exists.\n";
} catch (Exception $e) {
    echo "Index idx_hero_slides_order: " . htmlspecialchars($e->getMessage()) . "\n";
}

echo "Migration script complete.\n";

// Add is_coming_soon column to products table
$products_columns_to_add = [
    'is_coming_soon' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS is_coming_soon TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Coming Soon, 0=Available'"
];

foreach ($products_columns_to_add as $col => $sql) {
    try {
        $conn->query($sql);
        echo "Attempted to add column " . htmlspecialchars($col) . " to products (already exists or added).\n";
    } catch (Exception $e) {
        echo "Error with column " . htmlspecialchars($col) . ": " . htmlspecialchars($e->getMessage()) . "\n";
    }
}

echo "Products columns migration complete.\n";
?>
