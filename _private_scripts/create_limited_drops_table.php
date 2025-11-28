<?php
require_once __DIR__ . '/../includes/bootstrap.php';

function createLimitedDropsTable() {
    $conn = get_db_connection();

    $sql = "CREATE TABLE IF NOT EXISTS `limited_drops` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_id` int(11) NOT NULL,
        `drop_name` varchar(255) NOT NULL,
        `description` text,
        `start_date` datetime DEFAULT NULL,
        `end_date` datetime NOT NULL,
        `max_quantity` int(11) DEFAULT NULL,
        `current_quantity` int(11) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `show_countdown` tinyint(1) DEFAULT 1,
        `drop_type` enum('exclusive','limited','flash','new_arrival') DEFAULT 'limited',
        `priority` int(11) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_product_id` (`product_id`),
        KEY `idx_end_date` (`end_date`),
        KEY `idx_active` (`is_active`),
        KEY `idx_drop_type` (`drop_type`),
        CONSTRAINT `fk_limited_drop_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Limited drops table created successfully.\n";
        return true;
    } else {
        echo "❌ Error creating limited drops table: " . $conn->error . "\n";
        return false;
    }
}

function createStorySectionsTable() {
    $conn = get_db_connection();

    $sql = "CREATE TABLE IF NOT EXISTS `story_sections` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `section_title` varchar(255) NOT NULL,
        `section_content` text,
        `background_image` varchar(255) DEFAULT NULL,
        `section_order` int(11) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `animation_type` varchar(50) DEFAULT 'slide-up',
        `animation_delay` int(11) DEFAULT 0,
        `parallax_rate` decimal(3,2) DEFAULT 0.00,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_is_active` (`is_active`),
        KEY `idx_section_order` (`section_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Story sections table created successfully.\n";
        return true;
    } else {
        echo "❌ Error creating story sections table: " . $conn->error . "\n";
        return false;
    }
}

function seedSampleData() {
    $conn = get_db_connection();

    // Sample limited drop data
    $limitedDropsData = [
        [
            'product_id' => 1, // Assuming product ID exists
            'drop_name' => 'Mossé Luxe Exclusive Black Leather Jacket',
            'description' => 'Limited edition handcrafted black leather jacket with custom Mossé Luxe engraving. Only 50 pieces available.',
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'max_quantity' => 50,
            'current_quantity' => 23,
            'is_active' => 1,
            'show_countdown' => 1,
            'drop_type' => 'exclusive',
            'priority' => 1
        ],
        [
            'product_id' => 2, // Assuming product ID exists
            'drop_name' => 'Summer Collection Flash Sale',
            'description' => 'Flash sale! 50% off our entire summer collection. Limited time offer.',
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s', strtotime('+8 hours')),
            'max_quantity' => null,
            'current_quantity' => 0,
            'is_active' => 1,
            'show_countdown' => 1,
            'drop_type' => 'flash',
            'priority' => 2
        ]
    ];

    // Sample story section data
    $storySectionsData = [
        [
            'section_title' => 'Craftsmanship',
            'section_content' => 'Every piece in our collection is meticulously crafted by master artisans who have dedicated their lives to perfecting the art of luxury streetwear. We believe in quality over quantity, taking the time to ensure every stitch, every detail, tells a story of excellence.',
            'background_image' => 'assets/images/story-craftsmanship.jpg',
            'section_order' => 1,
            'is_active' => 1,
            'animation_type' => 'slide-left',
            'animation_delay' => 0,
            'parallax_rate' => 0.2
        ],
        [
            'section_title' => 'Heritage',
            'section_content' => 'Drawing inspiration from luxury fashion houses while embracing the raw energy of street culture, Mossé Luxe bridges the gap between old-world craftsmanship and modern urban style. Our pieces are not just clothing—they are expressions of identity.',
            'background_image' => 'assets/images/story-heritage.jpg',
            'section_order' => 2,
            'is_active' => 1,
            'animation_type' => 'slide-right',
            'animation_delay' => 200,
            'parallax_rate' => 0.3
        ],
        [
            'section_title' => 'Innovation',
            'section_content' => 'We constantly push the boundaries of what luxury streetwear can be, incorporating sustainable materials, innovative designs, and timeless quality that stands the test of time. Every season brings new innovations to redefine street luxury.',
            'background_image' => 'assets/images/story-innovation.jpg',
            'section_order' => 3,
            'is_active' => 1,
            'animation_type' => 'scale-in',
            'animation_delay' => 400,
            'parallax_rate' => 0.1
        ]
    ];

    $success = true;

    // Seed limited drops (skip if no products exist)
    $stmtCheck = $conn->prepare("SELECT COUNT(*) as count FROM products");
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    $productCount = $result->fetch_assoc()['count'];
    $stmtCheck->close();

    if ($productCount > 0) {
        $stmtDrops = $conn->prepare("INSERT INTO limited_drops (product_id, drop_name, description, start_date, end_date, max_quantity, current_quantity, is_active, show_countdown, drop_type, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE max_quantity = VALUES(max_quantity), current_quantity = VALUES(current_quantity), priority = VALUES(priority)");

        foreach ($limitedDropsData as $drop) {
            $stmtDrops->bind_param("issssiidiis", $drop['product_id'], $drop['drop_name'], $drop['description'], $drop['start_date'], $drop['end_date'], $drop['max_quantity'], $drop['current_quantity'], $drop['is_active'], $drop['show_countdown'], $drop['drop_type'], $drop['priority']);
            if (!$stmtDrops->execute()) {
                echo "❌ Error seeding limited drops: " . $stmtDrops->error . "\n";
                $success = false;
            }
        }
        $stmtDrops->close();

        if ($success) {
            echo "✅ Limited drops seeded successfully.\n";
        }
    } else {
        echo "ℹ️ No products found, skipping limited drops seeding.\n";
    }

    // Seed story sections
    $stmtStory = $conn->prepare("INSERT INTO story_sections (section_title, section_content, background_image, section_order, is_active, animation_type, animation_delay, parallax_rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE section_content = VALUES(section_content), section_order = VALUES(section_order), animation_type = VALUES(animation_type), animation_delay = VALUES(animation_delay)");

    foreach ($storySectionsData as $section) {
        $stmtStory->bind_param("ssssissd", $section['section_title'], $section['section_content'], $section['background_image'], $section['section_order'], $section['is_active'], $section['animation_type'], $section['animation_delay'], $section['parallax_rate']);
        if (!$stmtStory->execute()) {
            echo "❌ Error seeding story sections: " . $stmtStory->error . "\n";
            $success = false;
        }
    }
    $stmtStory->close();

    if ($success) {
        echo "✅ Story sections seeded successfully.\n";
    }

    return $success;
}

if ($argc > 1 && $argv[1] === 'run') {
    $success = true;
    $success &= createLimitedDropsTable();
    $success &= createStorySectionsTable();
    $success &= seedSampleData();

    if ($success) {
        echo "🎉 All interactive features setup completed successfully!\n";
    } else {
        echo "💥 Some interactive features setup failed.\n";
        exit(1);
    }
} else {
    die("This script should be run with 'run' parameter: php create_limited_drops_table.php run\n");
}
?>
