<?php
require_once 'includes/bootstrap.php';

echo "<h1>Mossé Luxe - Production Readiness Test</h1>";

// Test database connection
echo "<h2>1. Database Connection</h2>";
$conn = get_db_connection();
echo $conn->connect_error ? "❌ FAILED: " . $conn->connect_error : "✅ SUCCESS";

// Test products
echo "<h2>2. Products Check</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 1");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "✅ Found $count active products";
    $result->close();
} else {
    echo "❌ FAILED: " . $conn->error;
}

// Test categories
echo "<h2>3. Categories Check</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM categories");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "✅ Found $count categories";
    $result->close();
} else {
    echo "❌ FAILED: " . $conn->error;
}

// Test users
echo "<h2>4. Users Check</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'demo'");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "✅ Found $count real users (excludes demo accounts)" ;
    $result->close();
} else {
    echo "❌ FAILED: " . $conn->error;
}

// Test configuration
echo "<h2>5. Configuration Check</h2>";
echo "APP_ENV: " . (defined('APP_ENV') ? APP_ENV : 'undefined') . "<br>";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'undefined') . "<br>";
echo "SMTP_HOST: " . (defined('SMTP_HOST') ? SMTP_HOST : 'undefined') . "<br>";
echo "SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'undefined') . "<br>";

// Test demo data removal
echo "<h2>6. Demo Data Removal Check</h2>";
$demo_checks = [
    'users' => "SELECT COUNT(*) as count FROM users WHERE email IN ('john.doe@example.com', 'admin@mosse-luxe.com')",
    'discount_codes' => "SELECT COUNT(*) as count FROM discount_codes WHERE code IN ('WELCOME10', 'WINTER50')",
    'products' => "SELECT COUNT(*) as count FROM products WHERE image LIKE '%placehold.co%'"
];

foreach ($demo_checks as $table => $query) {
    $result = $conn->query($query);
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        $status = $count == 0 ? "✅ CLEAN" : "❌ FOUND $count items";
        echo "$table: $status<br>";
        $result->close();
    }
}

// Check for potential issues
echo "<h2>7. Potential Issues Check</h2>";
$issues = [];

// Check if .env has placeholder values
if (getenv('YOCO_PUBLIC_KEY') === 'pk_live_YOUR_YOCO_PUBLIC_KEY_HERE') {
    $issues[] = "Yoco payment keys not configured";
}
if (getenv('PAYFAST_MERCHANT_ID') === 'YOUR_PAYFAST_MERCHANT_ID') {
    $issues[] = "PayFast payment keys not configured";
}

echo "Issues found:<br>";
if (empty($issues)) {
    echo "✅ No critical issues detected";
} else {
    echo "⚠️ Issues found:<br>";
    foreach ($issues as $issue) {
        echo "- $issue<br>";
    }
}

$conn->close();

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "1. ✅ Browse the site manually for any visual errors<br>";
echo "2. ✅ Test cart functionality (add/remove products)<br>";
echo "3. ✅ Test checkout process<br>";
echo "4. ✅ Test WhatsApp integration<br>";
echo "5. ✅ Test admin panel access<br>";
?>
