<?php
/**
 * Test Quick View Modal API
 */

require_once __DIR__ . '/../includes/bootstrap.php';

echo "===========================================\n";
echo "TESTING QUICK VIEW MODAL API\n";
echo "===========================================\n\n";

$conn = get_db_connection();

// Get a sample product
$stmt = $conn->prepare("SELECT id, name FROM products WHERE status = 1 LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    $product_id = $product['id'];
    $product_name = $product['name'];
    
    echo "📦 Testing with Product:\n";
    echo "   ID: $product_id\n";
    echo "   Name: $product_name\n\n";
    
    // Test API endpoint
    $api_url = SITE_URL . "api/product.php?id=$product_id";
    echo "🔗 API URL: $api_url\n\n";
    
    // Make request
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "📊 Response Status: $http_code\n\n";
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        
        if ($data && isset($data['success']) && $data['success']) {
            echo "✅ API Response: SUCCESS\n\n";
            echo "📋 Product Data:\n";
            echo "   ID: " . $data['product']['id'] . "\n";
            echo "   Name: " . $data['product']['name'] . "\n";
            echo "   Price: R" . number_format($data['product']['price'], 2) . "\n";
            echo "   Stock: " . $data['product']['stock'] . "\n";
            echo "   Image: " . $data['product']['image'] . "\n";
            echo "   Slug: " . $data['product']['slug'] . "\n\n";
            
            echo "✅ QUICK VIEW MODAL IS WORKING!\n\n";
            echo "🎯 Test in browser:\n";
            echo "   1. Go to: " . SITE_URL . "\n";
            echo "   2. Hover over a product in 'New Arrivals'\n";
            echo "   3. Click the eye icon (Quick View)\n";
            echo "   4. Modal should open with product details\n\n";
            
        } else {
            echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ HTTP Error: $http_code\n";
        echo "Response: $response\n";
    }
    
} else {
    echo "❌ No products found in database\n";
    echo "   Please add products first\n";
}

$stmt->close();
$conn->close();

echo "\n===========================================\n";
echo "TEST COMPLETE\n";
echo "===========================================\n";
?>
