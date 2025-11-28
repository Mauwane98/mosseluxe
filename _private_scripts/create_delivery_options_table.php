<?php
require_once __DIR__ . '/../includes/bootstrap.php';

function createDeliveryOptionsTable() {
    $conn = get_db_connection();

    $sql = "CREATE TABLE IF NOT EXISTS `delivery_options` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(50) NOT NULL,
        `type` enum('pickup','courier','locker') NOT NULL,
        `description` varchar(255) DEFAULT NULL,
        `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
        `estimated_days` int(11) DEFAULT 1,
        `is_active` tinyint(1) DEFAULT 1,
        `sort_order` int(11) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_type` (`type`),
        INDEX `idx_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Delivery options table created successfully.\n";
        return true;
    } else {
        echo "❌ Error creating delivery options table: " . $conn->error . "\n";
        return false;
    }
}

function createPickupLocationsTable() {
    $conn = get_db_connection();

    $sql = "CREATE TABLE IF NOT EXISTS `pickup_locations` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `address` varchar(255) NOT NULL,
        `city` varchar(50) NOT NULL,
        `province` varchar(50) DEFAULT NULL,
        `postal_code` varchar(10) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `opening_hours` varchar(255) DEFAULT NULL,
        `latitude` decimal(10,8) DEFAULT NULL,
        `longitude` decimal(11,8) DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `sort_order` int(11) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_active` (`is_active`),
        INDEX `idx_city` (`city`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Pickup locations table created successfully.\n";
        return true;
    } else {
        echo "❌ Error creating pickup locations table: " . $conn->error . "\n";
        return false;
    }
}

function addDeliveryOptionToOrders() {
    $conn = get_db_connection();

    // Check if column already exists
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_option_id'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE orders ADD COLUMN delivery_option_id INT(11) DEFAULT NULL AFTER shipping_cost,
                ADD COLUMN pickup_location_id INT(11) DEFAULT NULL AFTER delivery_option_id,
                ADD INDEX idx_delivery_option_id (delivery_option_id),
                ADD INDEX idx_pickup_location_id (pickup_location_id),";

        if ($conn->query($sql) === TRUE) {
            echo "✅ Added delivery option columns to orders table.\n";
            return true;
        } else {
            echo "❌ Error adding delivery option columns: " . $conn->error . "\n";
            return false;
        }
    } else {
        echo "ℹ️ Delivery option columns already exist in orders table.\n";
        return true;
    }
}

function seedDeliveryOptions() {
    $conn = get_db_connection();

    $options = [
        ['Standard Courier', 'courier', 'Delivered to your door within 3-5 business days', 150.00, 5, 1],
        ['Express Courier', 'courier', 'Delivered to your door within 1-2 business days', 250.00, 2, 2],
        ['Free Shipping', 'courier', 'Free delivery for orders over R2000', 0.00, 7, 3],
        ['Store Pickup', 'pickup', 'Collect from our store location', 0.00, 1, 4],
        ['Smart Locker', 'locker', 'Drop off at a convenient locker location', 50.00, 3, 5]
    ];

    $stmt = $conn->prepare("INSERT INTO delivery_options (name, type, description, cost, estimated_days, sort_order) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE description = VALUES(description), cost = VALUES(cost), estimated_days = VALUES(estimated_days), sort_order = VALUES(sort_order)");

    if ($stmt) {
        foreach ($options as $option) {
            $stmt->bind_param("sssddi", $option[0], $option[1], $option[2], $option[3], $option[4], $option[5]);
            $stmt->execute();
        }
        echo "✅ Delivery options seeded successfully.\n";
        $stmt->close();
        return true;
    } else {
        echo "❌ Error preparing delivery options statement.\n";
        return false;
    }
}

function seedPickupLocations() {
    $conn = get_db_connection();

    $locations = [
        ['Mossé Luxe Sandton', 'Shop 204, Sandton City, Sandown Road', 'Johannesburg', 'Gauteng', '2196', '+27 11 234 5678', 'Mon-Sat: 9AM-8PM, Sun: 10AM-6PM', null, null],
        ['Mossé Luxe Cape Town', 'Victoria Wharf, Waterfront', 'Cape Town', 'Western Cape', '8001', '+27 21 345 6789', 'Mon-Sun: 10AM-7PM', null, null],
        ['Mossé Luxe Pretoria', 'Menlyn Park Shopping Centre', 'Pretoria', 'Gauteng', '0063', '+27 12 456 7890', 'Mon-Sat: 9AM-8PM, Sun: 11AM-5PM', null, null]
    ];

    $stmt = $conn->prepare("INSERT INTO pickup_locations (name, address, city, province, postal_code, phone, opening_hours) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE address = VALUES(address), phone = VALUES(phone), opening_hours = VALUES(opening_hours)");

    if ($stmt) {
        foreach ($locations as $location) {
            $stmt->bind_param("sssssss", $location[0], $location[1], $location[2], $location[3], $location[4], $location[5], $location[6]);
            $stmt->execute();
        }
        echo "✅ Pickup locations seeded successfully.\n";
        $stmt->close();
        return true;
    } else {
        echo "❌ Error preparing pickup locations statement.\n";
        return false;
    }
}

if ($argc > 1 && $argv[1] === 'run') {
    $success = true;
    $success &= createDeliveryOptionsTable();
    $success &= createPickupLocationsTable();
    $success &= addDeliveryOptionToOrders();
    $success &= seedDeliveryOptions();
    $success &= seedPickupLocations();

    if ($success) {
        echo "🎉 All delivery options setup completed successfully!\n";
    } else {
        echo "💥 Some delivery options setup failed.\n";
        exit(1);
    }
} else {
    die("This script should be run with 'run' parameter: php create_delivery_options_table.php run\n");
}
?>
