<?php
/**
 * Product API Endpoint
 * Get product details for quick view and other features
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/image_service.php';

header('Content-Type: application/json');

$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Ensure image path is correct
        if (isset($product['image']) && !empty($product['image'])) {
            // If image doesn't start with http or assets/, prepend it
            if (!preg_match('/^(http|assets\/)/', $product['image'])) {
                $product['image'] = 'assets/images/' . $product['image'];
            }
        } else {
            $product['image'] = 'assets/images/placeholder.svg';
        }
        
        // Add slug for URL
        $product['slug'] = strtolower(str_replace(' ', '-', $product['name']));
        
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
    }
    
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}

$conn->close();
?>
