<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

echo "Attempting to discard corrupted tablespaces...\n";

try {
    $tables = ['settings', 'categories', 'products'];

    foreach ($tables as $table) {
        echo "Processing table: $table\n";
        try {
            // Try to discard tablespace
            $conn->query("ALTER TABLE `$table` DISCARD TABLESPACE");
            echo "✅ Tablespace discarded for $table\n";

            // Try to import tablespace (this would require .ibd file, but might fail gracefully)
            // $conn->query("ALTER TABLE `$table` IMPORT TABLESPACE");

        } catch (Exception $e) {
            echo "❌ Error with $table: " . $e->getMessage() . "\n";
        }
    }

    echo "Attempting to recreate tables...\n";

    // Now try to create fresh tables
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(255) NOT NULL,
        setting_value TEXT,
        PRIMARY KEY (id),
        UNIQUE KEY setting_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql)) {
        echo "✅ Settings table recreated\n";

        // Add data
        $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", 'shop_title', 'Shop');
        $stmt->execute();
        $stmt->close();
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

$conn->close();
echo "Done.\n";
?>
