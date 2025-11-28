<?php
// Complete debug script to identify the blank page issue
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🧪 Complete Product Details Debug</h1>";
echo "<hr>";

// Test 1: Check basic PHP
echo "<h2>Test 1: PHP Basics</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Error Display: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "<br>";
echo "Error Reporting: " . ini_get('error_reporting') . "<br>";

// Test 2: Check includes directory
echo "<h2>Test 2: Files</h2>";
$files = ['includes/bootstrap.php', 'includes/header.php', 'includes/footer.php', 'includes/config.php'];
foreach ($files as $file) {
    echo "- $file: " . (file_exists($file) ? '✅ EXISTS' : '❌ MISSING') . "<br>";
}

// Test 3: Load config only
echo "<h2>Test 3: Config Load</h2>";
try {
    require_once 'includes/config.php';
    echo "✅ Config loaded<br>";
    echo "SITE_URL defined: " . (defined('SITE_URL') ? '✅ YES (' . SITE_URL . ')' : '❌ NO') . "<br>";
} catch (Exception $e) {
    echo "❌ ERROR loading config: " . $e->getMessage() . "<br>";
}

// Test 4: Load bootstrap step by step
echo "<h2>Test 4: Bootstrap Load</h2>";
try {
    // Test bootstrap includes individually
    echo "Testing autoload...<br>";
    require_once __DIR__ . '/vendor/autoload.php';
    echo "✅ Autoload loaded<br>";

    echo "Testing environment...<br>";
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/');
    $dotenv->load();
    echo "✅ Environment loaded<br>";

    require_once __DIR__ . '/includes/config.php';
    echo "✅ Config loaded second time<br>";

    require_once __DIR__ . '/includes/db_connect.php';
    echo "✅ DB Connect loaded<br>";

    $conn = get_db_connection();
    echo "✅ DB Connection established<br>";

    require_once __DIR__ . '/includes/csrf.php';
    echo "✅ CSRF loaded<br>";

    // Test helper functions
    if (function_exists('generate_csrf_token_input')) {
        echo "✅ generate_csrf_token_input() exists<br>";
    } else {
        echo "❌ generate_csrf_token_input() MISSING<br>";
    }

    global $conn;
    if (isset($conn)) {
        echo "✅ \$conn variable set<br>";
    } else {
        echo "❌ \$conn variable NOT set<br>";
    }

} catch (Exception $e) {
    echo "❌ ERROR in bootstrap step: " . $e->getMessage() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
} catch (Error $e) {
    echo "❌ FATAL ERROR in bootstrap step: " . $e->getMessage() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Test 5: Test database products
echo "<h2>Test 5: Database Products</h2>";
try {
    $conn = get_db_connection();
    $result = $conn->query("SELECT id, name, status, image FROM products LIMIT 5");
    echo "Products in database:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "- ID: {$row['id']}, Name: {$row['name']}, Status: {$row['status']}, Image: {$row['image']}<br>";
    }
} catch (Exception $e) {
    echo "❌ ERROR querying products: " . $e->getMessage() . "<br>";
}

// Test 6: Partial header load
echo "<h2>Test 6: Header Functions Test</h2>";
try {
    if (defined('SITE_URL')) {
        echo "SITE_URL available: " . SITE_URL . "<br>";
        if (function_exists('generate_csrf_token_input')) {
            $csrf = generate_csrf_token_input();
            echo "CSRF token generated: " . (strpos($csrf, 'name="') !== false ? '✅ YES' : '❌ MALFORMED') . "<br>";
        } else {
            echo "❌ CSRF function not available for header<br>";
        }
    } else {
        echo "❌ SITE_URL not defined for header<br>";
    }
} catch (Exception $e) {
    echo "❌ ERROR testing header functions: " . $e->getMessage() . "<br>";
}

// Test 7: Create minimal working template
echo "<h2>Test 7: Minimal Template Test</h2>";
echo "<style>body{font-family:Arial;margin:20px;} .test-box{border:1px solid #ccc;padding:10px;margin:10px 0;}</style>";

echo "<div class='test-box'>";
echo "<h3>🔷 If you can see this, HTML is working</h3>";
echo "<p>PHP is executing: Current timestamp: " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";

// Report summary
echo "<h2>Test Summary</h2>";
echo "<div style='background:#f0f0f0;padding:15px;border-radius:5px;margin-top:20px;'>";
echo "<p>If you can see all ✅ above and the box below, then PHP/MySQL/include files are all working.</p>";
echo "<p>Next step: Test the actual product-details.php page manually by visiting it directly.</p>";
echo "<p>Possible remaining issues:</p>";
echo "<ul>";
echo "<li>- URL rewrite rules not working</li>";
echo "<li>- Browser cache issues</li>";
echo "<li>- CSS/JavaScript loading but content appears 'blank' visually</li>";
echo "</ul>";
echo "</div>";
?>
