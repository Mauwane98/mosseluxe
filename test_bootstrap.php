<?php
// Simple bootstrap test script
require_once 'includes/bootstrap.php';

try {
    echo "Testing bootstrap includes...\n";
    echo "✅ bootstrap.php loaded successfully\n";

    // Test functions
    if (function_exists('generate_csrf_token')) {
        echo "✅ CSRF functions loaded\n";
    } else {
        echo "❌ CSRF functions missing\n";
    }

    if (function_exists('get_product_variants_by_type')) {
        echo "✅ Variant service functions loaded\n";
    } else {
        echo "❌ Variant service functions missing\n";
    }

    if (defined('SITE_URL')) {
        echo "✅ Config constants loaded\n";
    } else {
        echo "❌ Config constants missing\n";
    }

    echo "Core bootstrapping completed successfully!\n";

} catch (Exception $e) {
    echo "Error during bootstrap test: " . $e->getMessage() . "\n";
}
?>
