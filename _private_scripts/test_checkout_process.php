<?php
/**
 * Test Checkout Process
 * Simulates a checkout to identify any errors
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../yoco_process.php';

echo "===========================================\n";
echo "TESTING CHECKOUT PROCESS\n";
echo "===========================================\n\n";

// Test 1: Check required tables
echo "📋 TEST 1: Check Required Tables\n";
echo "-------------------------------------------\n";
$conn = get_db_connection();
$required_tables = ['orders', 'order_items', 'order_counters', 'products', 'discount_codes'];
$all_exist = true;

foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
    if ($result->num_rows > 0) {
        echo "  ✓ {$table} exists\n";
    } else {
        echo "  ✗ {$table} MISSING!\n";
        $all_exist = false;
    }
}

if (!$all_exist) {
    echo "\n❌ Missing required tables. Please run setup scripts.\n";
    exit(1);
}

// Test 2: Check order_counters
echo "\n🔢 TEST 2: Check Order Counter\n";
echo "-------------------------------------------\n";
$year = date('Y');
$stmt = $conn->prepare("SELECT * FROM order_counters WHERE year = ?");
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "  ✓ Counter exists for {$year}\n";
    echo "    Current counter: {$row['counter']}\n";
    echo "    Last updated: {$row['last_updated']}\n";
} else {
    echo "  ⚠ No counter for {$year}, will be created automatically\n";
}
$stmt->close();

// Test 3: Simulate checkout data
echo "\n🛒 TEST 3: Simulate Checkout\n";
echo "-------------------------------------------\n";

// Get a test product
$stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE status = 1 AND stock > 0 LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "  ✗ No products available for testing\n";
    exit(1);
}

$product = $result->fetch_assoc();
echo "  Using test product: {$product['name']} (ID: {$product['id']})\n";
echo "  Price: R{$product['price']}, Stock: {$product['stock']}\n";
$stmt->close();

// Create test checkout data
$test_data = [
    'user_id' => null,
    'cart_items' => [
        $product['id'] => [
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => 1,
            'image' => 'test.jpg'
        ]
    ],
    'subtotal' => $product['price'],
    'shipping_cost' => 100.00,
    'total' => $product['price'] + 100,
    'final_total' => $product['price'] + 100,
    'discount_data' => null,
    'shipping_info' => [
        'firstName' => 'Test',
        'lastName' => 'User',
        'address' => '123 Test Street',
        'city' => 'Pretoria',
        'zip' => '0001',
        'email' => 'test@example.com',
        'phone' => '0123456789'
    ]
];

echo "\n  Test Data:\n";
echo "    Subtotal: R" . number_format($test_data['subtotal'], 2) . "\n";
echo "    Shipping: R" . number_format($test_data['shipping_cost'], 2) . "\n";
echo "    Total: R" . number_format($test_data['final_total'], 2) . "\n";

// Test 4: Try to create order (with rollback)
echo "\n📦 TEST 4: Test Order Creation\n";
echo "-------------------------------------------\n";

try {
    $conn->begin_transaction();
    $result = create_order_for_yoco($conn, $test_data);
    $conn->rollback(); // Rollback to avoid creating test order
    
    echo "  ✓ Order creation successful!\n";
    echo "    Order ID: {$result['formatted_order_id']}\n";
    echo "    Amount: R" . number_format($result['amount'] / 100, 2) . "\n";
    echo "    Numeric ID: {$result['numeric_order_id']}\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "  ✗ Order creation FAILED!\n";
    echo "    Error: {$e->getMessage()}\n";
    echo "    File: {$e->getFile()}:{$e->getLine()}\n";
    
    // Show more details
    echo "\n  Stack Trace:\n";
    $trace = $e->getTrace();
    foreach (array_slice($trace, 0, 3) as $i => $t) {
        echo "    #{$i} ";
        if (isset($t['file'])) {
            echo basename($t['file']) . ":{$t['line']} ";
        }
        if (isset($t['function'])) {
            echo "{$t['function']}()\n";
        }
    }
}

// Test 5: Check YOCO configuration
echo "\n💳 TEST 5: Check Payment Configuration\n";
echo "-------------------------------------------\n";

if (defined('YOCO_PUBLIC_KEY') && !empty(YOCO_PUBLIC_KEY)) {
    $key_preview = substr(YOCO_PUBLIC_KEY, 0, 10) . '...';
    echo "  ✓ YOCO_PUBLIC_KEY configured: {$key_preview}\n";
} else {
    echo "  ✗ YOCO_PUBLIC_KEY not configured\n";
}

if (defined('YOCO_SECRET_KEY') && !empty(YOCO_SECRET_KEY)) {
    $key_preview = substr(YOCO_SECRET_KEY, 0, 10) . '...';
    echo "  ✓ YOCO_SECRET_KEY configured: {$key_preview}\n";
} else {
    echo "  ✗ YOCO_SECRET_KEY not configured\n";
}

// Test 6: Check shipping configuration
echo "\n🚚 TEST 6: Check Shipping Configuration\n";
echo "-------------------------------------------\n";
echo "  SHIPPING_COST: R" . number_format(SHIPPING_COST, 2) . "\n";
echo "  FREE_SHIPPING_THRESHOLD: R" . number_format(FREE_SHIPPING_THRESHOLD, 2) . "\n";
echo "  PAXI_COST: R" . number_format(PAXI_COST, 2) . "\n";

echo "\n===========================================\n";
echo "✅ CHECKOUT PROCESS TEST COMPLETE\n";
echo "===========================================\n\n";

echo "💡 TROUBLESHOOTING TIPS:\n";
echo "  1. Check browser console for JavaScript errors\n";
echo "  2. Check PHP error logs for server-side errors\n";
echo "  3. Verify all form fields are filled correctly\n";
echo "  4. Ensure products have sufficient stock\n";
echo "  5. Check CSRF token is being sent\n";
echo "  6. Verify database connection is working\n\n";

echo "📝 TO VIEW ERROR LOGS:\n";
echo "  Windows: C:\\xamppp\\apache\\logs\\error.log\n";
echo "  Or check: php.ini error_log setting\n\n";
?>
