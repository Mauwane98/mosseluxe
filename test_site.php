<?php
// Test script to check if site loads without execution
require_once 'includes/bootstrap.php';

try {
    $conn = get_db_connection();
    echo "Database connection: SUCCESS\n";

    // Test query
    $result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 1");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Products found: " . $row['total'] . "\n";
        $result->close();
    }

    echo "Base site functionality: OK\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
