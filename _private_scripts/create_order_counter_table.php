<?php
/**
 * Create Order Counter Table
 * This table stores atomic counters for order ID generation
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

echo "Creating order_counters table...\n";

// Create order_counters table
$sql = "CREATE TABLE IF NOT EXISTS order_counters (
    year INT(4) NOT NULL PRIMARY KEY,
    counter INT(11) NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "✓ order_counters table created successfully\n";
} else {
    echo "✗ Error creating table: " . $conn->error . "\n";
    exit(1);
}

// Initialize counter for current year
$current_year = date('Y');
$stmt = $conn->prepare("INSERT IGNORE INTO order_counters (year, counter) VALUES (?, 0)");
$stmt->bind_param("i", $current_year);

if ($stmt->execute()) {
    echo "✓ Initialized counter for year {$current_year}\n";
} else {
    echo "✗ Error initializing counter: " . $stmt->error . "\n";
}
$stmt->close();

// Sync existing orders
echo "\nSyncing existing orders...\n";
$stmt = $conn->prepare("
    SELECT MAX(CAST(SUBSTRING(order_id, 10) AS UNSIGNED)) as max_num 
    FROM orders 
    WHERE order_id LIKE ?
");
$pattern = "MSL-{$current_year}-%";
$stmt->bind_param("s", $pattern);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$max_num = $row['max_num'] ?? 0;
$stmt->close();

if ($max_num > 0) {
    $update_stmt = $conn->prepare("UPDATE order_counters SET counter = ? WHERE year = ?");
    $update_stmt->bind_param("ii", $max_num, $current_year);
    $update_stmt->execute();
    $update_stmt->close();
    echo "✓ Synced counter to {$max_num} based on existing orders\n";
} else {
    echo "✓ No existing orders for {$current_year}, counter remains at 0\n";
}

echo "\n✅ Order counter table setup complete!\n";
?>
