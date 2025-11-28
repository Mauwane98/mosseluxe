<?php
echo "<h1>Testing Variant Functions</h1>";

try {
    require_once __DIR__ . '/includes/bootstrap.php';
    echo "✅ Bootstrap loaded<br>";

    if (function_exists('get_product_variants_by_type')) {
        echo "✅ get_product_variants_by_type exists<br>";

        // Test the function with product ID 1
        try {
            $variants = get_product_variants_by_type(1);
            echo "✅ Function called successfully<br>";
            echo "Variants returned: " . count($variants) . " types<br>";
        } catch (Exception $e) {
            echo "❌ Function call failed: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ get_product_variants_by_type function MISSING<br>";
    }
} catch (Exception $e) {
    echo "❌ Bootstrap failed: " . $e->getMessage() . "<br>";
}

echo "Test complete.<br>";
?>
