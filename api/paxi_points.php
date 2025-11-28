<?php
/**
 * Paxi Points API
 * Returns nearby Paxi pickup points based on location
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/paxi_service.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $city = $_GET['city'] ?? null;
    $postal_code = $_GET['postal_code'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    try {
        $points = PaxiService::getNearbyPoints($postal_code, $city, $limit);
        
        echo json_encode([
            'success' => true,
            'points' => $points,
            'count' => count($points)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch Paxi points: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>
