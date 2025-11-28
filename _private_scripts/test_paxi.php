<?php
/**
 * Test Paxi Integration
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/paxi_service.php';

echo "===========================================\n";
echo "TESTING PAXI INTEGRATION\n";
echo "===========================================\n\n";

// Test 1: Get Paxi Points for Pretoria
echo "📍 TEST 1: Get Paxi Points for Pretoria\n";
echo "-------------------------------------------\n";
$pretoria_points = PaxiService::getNearbyPoints(null, 'Pretoria', 5);
echo "Found " . count($pretoria_points) . " Paxi Points in Pretoria:\n\n";
foreach ($pretoria_points as $point) {
    echo "  • {$point['name']}\n";
    echo "    Address: {$point['address']}\n";
    echo "    Hours: {$point['hours']}\n";
    echo "    Distance: {$point['distance']}\n\n";
}

// Test 2: Calculate Paxi costs
echo "\n💰 TEST 2: Calculate Paxi Delivery Costs\n";
echo "-------------------------------------------\n";
$test_amounts = [200, 350, 550, 900, 1000];
foreach ($test_amounts as $amount) {
    $cost = PaxiService::calculateCost($amount);
    echo "  Cart Total: R" . number_format($amount, 2) . " → Paxi Cost: R" . number_format($cost, 2);
    if ($cost == 0) echo " (FREE!)";
    echo "\n";
}

// Test 3: Get delivery estimate
echo "\n⏱️  TEST 3: Delivery Estimate\n";
echo "-------------------------------------------\n";
echo "  Estimated Delivery: " . PaxiService::getDeliveryEstimate() . "\n";

// Test 4: Create mock shipment
echo "\n📦 TEST 4: Create Mock Shipment\n";
echo "-------------------------------------------\n";
$mock_order = [
    'order_id' => 12345,
    'paxi_point_id' => 'PAXI_PTA_001',
    'customer_name' => 'John Doe',
    'customer_phone' => '0821234567'
];
$shipment = PaxiService::createShipment($mock_order);
echo "  Tracking Number: {$shipment['tracking_number']}\n";
echo "  Paxi Point: {$shipment['paxi_point_id']}\n";
echo "  Estimated Delivery: {$shipment['estimated_delivery']}\n";
echo "  Status: {$shipment['status']}\n";

// Test 5: API Endpoint
echo "\n🔗 TEST 5: API Endpoint\n";
echo "-------------------------------------------\n";
$api_url = SITE_URL . "api/paxi_points.php?city=Johannesburg";
echo "  API URL: $api_url\n";

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "  ✅ API Working! Found {$data['count']} points\n";
    } else {
        echo "  ❌ API Error: {$data['message']}\n";
    }
} else {
    echo "  ❌ HTTP Error: $http_code\n";
}

echo "\n===========================================\n";
echo "✅ PAXI INTEGRATION TEST COMPLETE\n";
echo "===========================================\n\n";

echo "🎯 NEXT STEPS:\n";
echo "1. Add Paxi option to checkout page\n";
echo "2. Let customers select Paxi Point\n";
echo "3. Calculate shipping based on selection\n";
echo "4. Store Paxi Point info with order\n";
echo "5. Generate tracking number on order completion\n\n";

echo "💡 PRICING:\n";
echo "  • Under R300: R65 (Paxi) vs R100 (Standard)\n";
echo "  • R300-R899: R45 (Paxi) vs R100 (Standard)\n";
echo "  • Over R900: FREE (both options)\n\n";
?>
