<?php
require_once __DIR__ . '/../includes/bootstrap.php';

echo "Adding category-specific size options...\n";

// Connect to database
$conn = get_db_connection();

// Define category-specific sizes
$category_sizes = [
    // T-shirts (clothing sizes)
    'tshirt' => [
        ['XS', 'Extra Small'],
        ['S', 'Small'],
        ['M', 'Medium'],
        ['L', 'Large'],
        ['XL', 'Extra Large'],
        ['XXL', 'Double Extra Large'],
        ['3XL', 'Triple Extra Large']
    ],

    // Belts (waist sizes in inches/cm)
    'belt' => [
        ['28"', '28 inches (71 cm)'],
        ['30"', '30 inches (76 cm)'],
        ['32"', '32 inches (81 cm)'],
        ['34"', '34 inches (86 cm)'],
        ['36"', '36 inches (91 cm)'],
        ['38"', '38 inches (97 cm)'],
        ['40"', '40 inches (102 cm)'],
        ['42"', '42 inches (107 cm)'],
        ['44"', '44 inches (112 cm)'],
        ['46"', '46 inches (117 cm)']
    ],

    // Cardholders (standard sizes)
    'cardholder' => [
        ['Standard', 'Standard Size (3.375" x 2.125")'],
        ['Mini', 'Mini Size (3" x 2")'],
        ['Slim', 'Slim Size (3.5" x 2")'],
        ['RFID', 'RFID Blocking Size (3.375" x 2.125")']
    ],

    // Leather Bracelets (wrist sizes)
    'bracelet' => [
        ['Child-S', 'Child Small (5.5" - 6")'],
        ['Child-M', 'Child Medium (6" - 6.5")'],
        ['Youth-S', 'Youth Small (6.5" - 7")'],
        ['Youth-M', 'Youth Medium (7" - 7.5")'],
        ['Adult-S', 'Adult Small (7.5" - 8")'],
        ['Adult-M', 'Adult Medium (8" - 8.5")'],
        ['Adult-L', 'Adult Large (8.5" - 9")'],
        ['Adult-XL', 'Adult Extra Large (9" - 9.5")'],
        ['Custom', 'Custom Size (Personalized)']
    ]
];

try {
    $stmt = $conn->prepare("INSERT IGNORE INTO variant_options (variant_type, option_value, display_name, sort_order) VALUES (?, ?, ?, ?)");

    $sort_order = 0;
    foreach ($category_sizes as $category => $sizes) {
        echo "Adding sizes for category: $category...\n";
        $category_sort = 0;

        foreach ($sizes as $size) {
            $stmt->bind_param("sssi", $category, $size[0], $size[1], $category_sort);
            $stmt->execute();
            $category_sort++;
        }

        echo "✓ Added " . count($sizes) . " sizes for $category\n";
    }

    $stmt->close();

    // Also add the standard sizes/colors that might be used across categories
    echo "Adding standard sizing options...\n";

    // Standard sizes for various products
    $standard_sizes = [
        ['One Size', 'One Size Fits All'],
        ['Adjustable', 'Adjustable Size'],
        ['Custom', 'Custom Size']
    ];

    foreach ($standard_sizes as $sort_order => $size) {
        $conn->query("INSERT IGNORE INTO variant_options (variant_type, option_value, display_name, sort_order) VALUES ('standard', '" . $conn->real_escape_string($size[0]) . "', '" . $conn->real_escape_string($size[1]) . "', $sort_order)");
    }

    echo "✓ Added standard sizing options\n";

    echo "Category-specific sizes setup complete!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nHow to use category sizes:\n";
echo "\n1. T-SHIRTS:\n";
echo "   - Create product with variant_type: 'tshirt'\n";
echo "   - Available sizes: XS, S, M, L, XL, XXL, 3XL\n";
echo "\n2. BELTS:\n";
echo "   - Create product with variant_type: 'belt'\n";
echo "   - Available sizes: 28\"-46\" (waist measurements)\n";
echo "\n3. CARDHOLDERS:\n";
echo "   - Create product with variant_type: 'cardholder'\n";
echo "   - Available sizes: Standard, Mini, Slim, RFID\n";
echo "\n4. LEATHER BRACELETS:\n";
echo "   - Create product with variant_type: 'bracelet'\n";
echo "   - Available sizes: Child to Adult sizes, plus Custom\n";
echo "\n5. STANDARD OPTIONS:\n";
echo "   - variant_type: 'standard'\n";
echo "   - Available: One Size, Adjustable, Custom\n";

$conn->close();
?>
