<?php
/**
 * Reviews API Endpoint
 * Handles review submissions and retrieval
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/review_functions.php';

header('Content-Type: application/json');

$conn = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Fetch reviews for a product
if ($method === 'GET' && isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $reviews = getProductReviews($conn, $product_id, $limit, $offset);
    $stats = getReviewStats($conn, $product_id);
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'stats' => $stats
    ]);
    exit;
}

// POST - Submit a new review
if ($method === 'POST') {
    // Verify CSRF token
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['csrf_token']) || !verify_csrf_token($input['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    
    $result = addProductReview($conn, $input);
    
    // Handle photo uploads if present
    if ($result['success'] && !empty($_FILES['photos']['name'][0])) {
        $photos = uploadReviewPhotos($conn, $result['review_id'], $_FILES['photos']);
        $result['photos_uploaded'] = count($photos);
    }
    
    http_response_code($result['success'] ? 201 : 400);
    echo json_encode($result);
    exit;
}

// PUT - Mark review as helpful
if ($method === 'PUT' && isset($_GET['review_id']) && isset($_GET['action']) && $_GET['action'] === 'helpful') {
    $review_id = (int)$_GET['review_id'];
    $success = markReviewHelpful($conn, $review_id);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Marked as helpful' : 'Failed to update'
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
