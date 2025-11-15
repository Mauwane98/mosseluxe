<?php
require_once __DIR__ . '/../includes/bootstrap.php';

echo "Adding order_id column to orders table...\n";

// Connect to database
$conn = get_db_connection();

// Add order_id column if it doesn't exist
$alter_sql = "ALTER TABLE orders ADD COLUMN order_id VARCHAR(20) UNIQUE DEFAULT NULL AFTER id";

try {
    if ($conn->query($alter_sql) === TRUE) {
        echo "✓ Successfully added order_id column\n";

        // Now populate existing orders with proper order IDs
        require_once __DIR__ . '/../includes/order_service.php';

        echo "Generating order IDs for existing orders...\n";

        $result = $conn->query("SELECT id FROM orders ORDER BY created_at ASC");

        if ($result && $result->num_rows > 0) {
            $count = 0;
            while ($row = $result->fetch_assoc()) {
                $order_id = generate_order_id();

                $update_stmt = $conn->prepare("UPDATE orders SET order_id = ? WHERE id = ?");
                $update_stmt->bind_param("si", $order_id, $row['id']);

                if ($update_stmt->execute()) {
                    $count++;
                    echo "✓ Updated order #{$row['id']} with order ID: $order_id\n";
                } else {
                    echo "✗ Failed to update order #{$row['id']}\n";
                }

                $update_stmt->close();
            }

            echo "\n✓ Successfully updated $count existing orders\n";
        } else {
            echo "✓ No existing orders to update\n";
        }

        if ($result) {
            $result->free();
        }

        // Set order_id column to NOT NULL (since we just populated it)
        $conn->query("ALTER TABLE orders MODIFY COLUMN order_id VARCHAR(20) NOT NULL");

        echo "✓ Migration completed successfully!\n";

    } else {
        if ($conn->errno === 1060) { // Column already exists
            echo "✓ order_id column already exists\n";
        } else {
            throw new Exception("Failed to add column: " . $conn->error);
        }
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
