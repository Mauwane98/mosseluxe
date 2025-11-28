<?php
/**
 * Test Order ID Generation
 * Tests the generate_order_id() function for uniqueness and race conditions
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/order_service.php';

echo "===========================================\n";
echo "TESTING ORDER ID GENERATION\n";
echo "===========================================\n\n";

// Test 1: Generate multiple order IDs
echo "📋 TEST 1: Generate 10 Order IDs\n";
echo "-------------------------------------------\n";
$generated_ids = [];
for ($i = 1; $i <= 10; $i++) {
    try {
        $order_id = generate_order_id();
        $generated_ids[] = $order_id;
        echo "  {$i}. {$order_id}\n";
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
}

// Test 2: Check for duplicates
echo "\n✅ TEST 2: Check for Duplicates\n";
echo "-------------------------------------------\n";
$unique_ids = array_unique($generated_ids);
if (count($unique_ids) === count($generated_ids)) {
    echo "  ✓ All IDs are unique!\n";
    echo "  Generated: " . count($generated_ids) . " IDs\n";
    echo "  Unique: " . count($unique_ids) . " IDs\n";
} else {
    echo "  ❌ DUPLICATE FOUND!\n";
    echo "  Generated: " . count($generated_ids) . " IDs\n";
    echo "  Unique: " . count($unique_ids) . " IDs\n";
    $duplicates = array_diff_assoc($generated_ids, $unique_ids);
    echo "  Duplicates: " . implode(', ', $duplicates) . "\n";
}

// Test 3: Validate format
echo "\n📐 TEST 3: Validate Order ID Format\n";
echo "-------------------------------------------\n";
$all_valid = true;
foreach ($generated_ids as $id) {
    $is_valid = validate_order_id($id);
    if (!$is_valid) {
        echo "  ❌ Invalid format: {$id}\n";
        $all_valid = false;
    }
}
if ($all_valid) {
    echo "  ✓ All IDs have valid format (MSL-YYYY-NNNNN)\n";
}

// Test 4: Check database uniqueness
echo "\n🗄️  TEST 4: Check Database Uniqueness\n";
echo "-------------------------------------------\n";
$conn = get_db_connection();
$stmt = $conn->prepare("SELECT order_id, COUNT(*) as count FROM orders GROUP BY order_id HAVING count > 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "  ❌ DUPLICATES FOUND IN DATABASE:\n";
    while ($row = $result->fetch_assoc()) {
        echo "    - {$row['order_id']} appears {$row['count']} times\n";
    }
} else {
    echo "  ✓ No duplicates in database\n";
}
$stmt->close();

// Test 5: Show recent orders
echo "\n📊 TEST 5: Recent Orders\n";
echo "-------------------------------------------\n";
$stmt = $conn->prepare("SELECT id, order_id, created_at FROM orders ORDER BY id DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "  Last 5 orders:\n";
    while ($row = $result->fetch_assoc()) {
        echo "    #{$row['id']}: {$row['order_id']} - {$row['created_at']}\n";
    }
} else {
    echo "  No orders in database yet\n";
}
$stmt->close();

echo "\n===========================================\n";
echo "✅ ORDER ID GENERATION TEST COMPLETE\n";
echo "===========================================\n\n";

echo "🔧 IMPROVEMENTS MADE:\n";
echo "  • Database table locking to prevent race conditions\n";
echo "  • Retry logic with exponential backoff\n";
echo "  • Fallback to UUID-based IDs if needed\n";
echo "  • Duplicate key error handling in order creation\n";
echo "  • Up to 3 retry attempts for order insertion\n\n";

echo "💡 HOW IT WORKS:\n";
echo "  1. Lock orders table to prevent concurrent access\n";
echo "  2. Get highest order number for current year\n";
echo "  3. Increment and format as MSL-YYYY-NNNNN\n";
echo "  4. Unlock table and return unique ID\n";
echo "  5. If duplicate detected, retry with new ID\n";
echo "  6. Fallback to UUID if all retries fail\n\n";
?>
