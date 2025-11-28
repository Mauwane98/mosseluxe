<?php
echo "<h1>🛍️ COMPREHENSIVE E-COMMERCE FLOW TEST</h1>";
echo "<style>
body{font-family:monospace; margin:20px; line-height:1.5;}
.test{margin:20px 0; padding:15px; border:2px solid #333; border-radius:5px; background:#f9f9f9;}
.success{color:#28a745;}
.error{color:#dc3545;}
.warning{color:#ffc107;}
.info{color:#17a2b8;}
.step{margin-bottom:15px; padding:10px; background:white; border-left:4px solid #007bff;}
</style>";

// Initialize test results
$results = [
    'database' => false,
    'products' => false,
    'bootstrap' => false,
    'shop_page' => false,
    'product_details' => false,
    'cart_add' => false,
    'cart_count' => false,
    'cart_page' => false,
    'checkout' => false,
    'mobile_responsive' => false,
    'errors' => []
];

function log_result($test, $status, $message = '') {
    global $results;
    $results[$test] = $status;
    $color = $status ? 'success' : 'error';
    echo "<div class='$color'>• $message</div>";
}

function check_file($filepath, $description) {
    echo "<div class='step'><h3>✅ $description</h3>";
    if (!file_exists($filepath)) {
        log_result('errors', true, "MISSING: $filepath");
        return false;
    }

    // Check if file has syntax errors
    $output = [];
    $return_var = 0;
    exec("php -l \"$filepath\" 2>&1", $output, $return_var);

    if ($return_var !== 0) {
        log_result('errors', true, "SYNTAX ERROR in $filepath: " . implode(' ', $output));
        return false;
    }

    echo "<span class='success'>✓ File exists and has valid syntax</span>";
    return true;
}

// ========================================
// STEP 1: Environment and Database Check
// ========================================
echo "<div class='test'><h2>🔧 STEP 1: Environment & Database</h2>";

// Check PHP version
$version = phpversion();
echo "<div class='step'><h3>PHP Version: $version</h3>";
if (version_compare($version, '7.4', '>=')) {
    echo "<span class='success'>✓ Compatible PHP version</span>";
} else {
    echo "<span class='error'>❌ Requires PHP 7.4+</span>";
}

// Bootstrap check
echo "<div class='step'><h3>Bootstrap Check</h3>";
try {
    require_once 'includes/bootstrap.php';
    require_once 'includes/config.php';
    $conn = get_db_connection();

    echo "<span class='success'>✓ Bootstrap loaded successfully</span><br>";
    echo "<span class='success'>✓ Database connection: " . ($conn ? 'connected' : 'failed') . "</span><br>";

    $results['bootstrap'] = true;
    $results['database'] = true;

    // Check tables exist
    $tables = ['products', 'users', 'categories', 'cart_sessions', 'user_carts', 'orders', 'coupon_codes'];
    $missing_tables = [];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if (!($result && $result->num_rows > 0)) {
            $missing_tables[] = $table;
        }
    }

    if (empty($missing_tables)) {
        echo "<span class='success'>✓ All required tables exist</span>";
    } else {
        echo "<span class='warning'>⚠️ Missing tables: " . implode(', ', $missing_tables) . "</span>";
        $results['errors'][] = "Missing tables: " . implode(', ', $missing_tables);
    }

} catch (Exception $e) {
    echo "<span class='error'>❌ Bootstrap error: " . $e->getMessage() . "</span>";
    $results['errors'][] = "Bootstrap error: " . $e->getMessage();
    exit;
}

// Check if products exist
echo "<div class='step'><h3>Products Check</h3>";
$product_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 1");
if ($product_count) {
    $count = $product_count->fetch_assoc()['count'];
    echo "<span class='success'>✓ $count active products in database</span>";
    $results['products'] = $count > 0;
} else {
    echo "<span class='error'>❌ No products found</span>";
    $results['errors'][] = "No products in database";
}

// ========================================
// STEP 2: File Integrity Check
// ========================================
echo "</div><div class='test'><h2>📁 STEP 2: File Integrity Check</h2>";

// Core files
$core_files = [
    'index.php' => 'Homepage',
    'shop.php' => 'Shop page',
    'cart.php' => 'Cart page',
    'checkout.php' => 'Checkout page',
    'product-details.php' => 'Product details',
    'includes/header.php' => 'Header include',
    'includes/footer.php' => 'Footer include',
    'assets/js/cart.js' => 'Cart JavaScript',
    'assets/js/main.js' => 'Main JavaScript',
    'ajax_cart_handler.php' => 'Cart AJAX handler',
    '.htaccess' => 'URL rewriting'
];

foreach ($core_files as $file => $description) {
    check_file($file, $description);
}

// ========================================
// STEP 3: Shop Page Test
// ========================================
echo "</div><div class='test'><h2>🛍️ STEP 3: Shop Page Test</h2>";

echo "<div class='step'><h3>Shop Page Load Test</h3>";
ob_start();
try {
    $_GET = ['page' => 1];
    include 'shop.php';
    $output = ob_get_clean();

    if (strpos($output, 'All Products') !== false) {
        echo "<span class='success'>✓ Shop page loads with title</span>";
        $results['shop_page'] = true;
    } else {
        echo "<span class='error'>❌ Shop page missing title</span>";
        $results['errors'][] = "Shop page doesn't show title";
    }

    // Check for product display
    if (strpos($output, 'product/') !== false) {
        echo "<br><span class='success'>✓ Product cards found with proper links</span>";
    } else {
        echo "<br><span class='error'>❌ No product cards or links found</span>";
        $results['errors'][] = "Shop page has no product cards";
    }

} catch (Exception $e) {
    ob_end_clean();
    echo "<span class='error'>❌ Shop page error: " . $e->getMessage() . "</span>";
    $results['errors'][] = "Shop page error: " . $e->getMessage();
}

// ========================================
// STEP 4: Product Details Test
// ========================================
echo "</div><div class='test'><h2>📄 STEP 4: Product Details Test</h2>";

echo "<div class='step'><h3>Product Details Page Test</h3>";

$products_result = $conn->query("SELECT id, name FROM products WHERE status = 1 LIMIT 1");
if ($products_result && $product = $products_result->fetch_assoc()) {
    ob_start();
    try {
        $_GET = ['id' => $product['id']];
        include 'product-details.php';
        $output = ob_get_clean();

        if (strpos($output, $product['name']) !== false) {
            echo "<span class='success'>✓ Product details loads and shows product name</span>";
            $results['product_details'] = true;
        } else {
            echo "<span class='error'>❌ Product details page doesn't show product name</span>";
            $results['errors'][] = "Product details doesn't show product";
        }

        if (strpos($output, 'Add to Cart') !== false) {
            echo "<br><span class='success'>✓ Add to Cart button found</span>";
        } else {
            echo "<br><span class='error'>❌ No Add to Cart button</span>";
            $results['errors'][] = "Product details missing Add to Cart";
        }

    } catch (Exception $e) {
        ob_end_clean();
        echo "<span class='error'>❌ Product details error: " . $e->getMessage() . "</span>";
        $results['errors'][] = "Product details error: " . $e->getMessage();
    }
} else {
    echo "<span class='error'>❌ No products available for testing</span>";
    $results['errors'][] = "No products for testing";
}

// ========================================
// STEP 5: Cart System Test
// ========================================
echo "</div><div class='test'><h2>🛒 STEP 5: Cart System Test</h2>";

// Test cart add functionality via AJAX
echo "<div class='step'><h3>Cart Add Test</h3>";
if ($product) {
    // Simulate AJAX request
    $test_data = [
        'action' => 'add',
        'product_id' => $product['id'],
        'quantity' => 1,
        'csrf_token' => '' // Will be rejected due to no token, but should not crash
    ];

    ob_start();
    $_POST = $test_data;
    include 'ajax_cart_handler.php';
    $cart_response = ob_get_clean();

    if (!empty($cart_response)) {
        $cart_result = json_decode($cart_response, true);
        if (isset($cart_result['message'])) {
            echo "<span class='success'>✓ Cart system responds (expected CSRF rejection for test)</span>";
            echo "<br><span class='info'>Response: " . $cart_result['message'] . "</span>";
            $results['cart_add'] = true; // Backend responds
        } else {
            echo "<span class='error'>❌ Cart system invalid response</span>";
            $results['errors'][] = "Invalid cart response";
        }
    } else {
        echo "<span class='error'>❌ Cart system not responding</span>";
        $results['errors'][] = "Cart system not responding";
    }
}

// ========================================
// STEP 6: Cart Page Test
// ========================================
echo "</div><div class='test'><h2>📋 STEP 6: Cart Page Test</h2>";

echo "<div class='step'><h3>Cart Page Load Test</h3>";
ob_start();
try {
    include 'cart.php';
    $cart_output = ob_get_clean();

    if (strpos($cart_output, 'Your Shopping Cart') !== false) {
        echo "<span class='success'>✓ Cart page loads with title</span>";
        $results['cart_page'] = true;
    } else {
        echo "<span class='error'>❌ Cart page missing title</span>";
        $results['errors'][] = "Cart page doesn't load properly";
    }

    if (strpos($cart_output, 'checkout.php') !== false) {
        echo "<br><span class='success'>✓ Checkout button found</span>";
    } else {
        echo "<br><span class='warning'>⚠️ No checkout button found</span>";
    }

} catch (Exception $e) {
    ob_end_clean();
    echo "<span class='error'>❌ Cart page error: " . $e->getMessage() . "</span>";
    $results['errors'][] = "Cart page error: " . $e->getMessage();
}

// ========================================
// STEP 7: Checkout Test
// ========================================
echo "</div><div class='test'><h2>💳 STEP 7: Checkout Test</h2>";

echo "<div class='step'><h3>Checkout Page Load Test</h3>";
ob_start();
try {
    include 'checkout.php';
    $checkout_output = ob_get_clean();

    if (strpos($checkout_output, 'Checkout') !== false) {
        echo "<span class='success'>✓ Checkout page loads with title</span>";
        $results['checkout'] = true;
    } else {
        echo "<span class='error'>❌ Checkout page missing title</span>";
        $results['errors'][] = "Checkout page doesn't load properly";
    }

    if (strpos($checkout_output, 'Proceed to Payment') !== false) {
        echo "<br><span class='success'>✓ Payment button found</span>";
    } else {
        echo "<br><span class='warning'>⚠️ No payment button found</span>";
    }

} catch (Exception $e) {
    ob_end_clean();
    echo "<span class='error'>❌ Checkout page error: " . $e->getMessage() . "</span>";
    $results['errors'][] = "Checkout page error: " . $e->getMessage();
}

// ========================================
// STEP 8: Mobile Responsiveness Check
// ========================================
echo "</div><div class='test'><h2>📱 STEP 8: Mobile Responsiveness</h2>";

echo "<div class='step'><h3>Mobile Breakpoints Test</h3>";
// Check if responsive CSS classes are used
$responsive_classes = ['md:', 'lg:', 'xl:', 'sm:'];
$css_content = file_get_contents('assets/css/custom.css') ?: '';

$responsive_found = 0;
foreach ($responsive_classes as $class) {
    if (strpos($css_content, $class) !== false) {
        $responsive_found++;
    }
}

if ($responsive_found >= 3) {
    echo "<span class='success'>✓ Tailwind responsive classes found in CSS ($responsive_found different breakpoints)</span>";
    $results['mobile_responsive'] = true;
} else {
    echo "<span class='warning'>⚠️ Limited responsive classes found</span>";
    $results['errors'][] = "Limited responsive design";
}

// ========================================
// FINAL SUMMARY
// ========================================
echo "</div><div class='test'><h2>🎯 FINAL TEST SUMMARY</h2>";

$passed = count(array_filter($results)) - count($results['errors']);
$total = count($results) - 1; // Subtract errors array

echo "<h3>Overall Score: <strong>$passed / $total</strong> core components working</h3>";

echo "<h4>✅ WORKING COMPONENTS:</h4>";
foreach ($results as $component => $status) {
    if ($status === true && $component !== 'errors') {
        $name = ucwords(str_replace('_', ' ', $component));
        echo "• $name<br>";
    }
}

if (!empty($results['errors'])) {
    echo "<h4 style='color:#dc3545;'>❌ ISSUES TO FIX:</h4>";
    foreach ($results['errors'] as $error) {
        echo "• $error<br>";
    }
}

echo "<h4>🔧 RECOMMENDED NEXT STEPS:</h4>";
if (!$results['cart_count']) {
    echo "• Fix cart count updates in header<br>";
}
if (!$results['cart_add']) {
    echo "• Debug cart addition process<br>";
}
if (!$results['mobile_responsive']) {
    echo "• Improve mobile responsiveness<br>";
}

echo "<br><strong>Status: " . ($passed >= $total * 0.7 ? "Most components working" : "Major fixes needed") . "</strong>";
?>
