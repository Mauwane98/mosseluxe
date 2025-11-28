<?php
/**
 * API Router (Front Controller)
 *
 * This file is the single entry point for all API requests.
 * It parses the URL and delegates the request to the appropriate controller.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/CartController.php';
require_once __DIR__ . '/PageController.php';

// Set common API headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

/**
 * A simple utility function to send a standardized JSON response and exit.
 * @param int $statusCode HTTP status code (e.g., 200, 404, 500)
 * @param array $data The data to be JSON encoded.
 */
function send_json_response($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// --- Basic Routing Logic ---
$method = $_SERVER['REQUEST_METHOD'];
$route = $_GET['_route'] ?? '';
$parts = explode('/', rtrim($route, '/'));

$resource = $parts[0] ?? null;
$action = $parts[1] ?? null;
$id = $parts[2] ?? null;

$conn = get_db_connection();
$cartController = new CartController($conn);

try {
    // --- Route Definitions ---
    switch ($resource) {
        case 'cart':
            if ($method === 'GET' && $action === null) {
                // Corresponds to: GET /api/cart
                $cartController->getCart();
            } elseif ($method === 'POST' && $action === 'items') {
                // Corresponds to: POST /api/cart/items
                $cartController->addItem();
            } elseif ($method === 'PUT' && $action === 'items' && $id) {
                // Corresponds to: PUT /api/cart/items/{id}
                $cartController->updateItem($id);
            } elseif ($method === 'DELETE' && $action === 'items' && $id) {
                // Corresponds to: DELETE /api/cart/items/{id}
                $cartController->removeItem($id);
            } else {
                send_json_response(404, ['success' => false, 'message' => 'Cart endpoint not found.']);
            }
            break;

        case 'pages':
            if ($method === 'GET' && $action) {
                $pageController = new PageController($conn);
                $pageController->getPage($action);
            } else {
                send_json_response(404, ['success' => false, 'message' => 'Page endpoint not found.']);
            }
            break;

        case 'products':
            // Delegate to products.php handler - it manages its own connection
            include __DIR__ . '/products.php';
            exit; // Exit after include to prevent finally block from closing connection

        case 'product':
            // Delegate to product.php handler for single product
            include __DIR__ . '/product.php';
            exit; // Exit after include to prevent finally block from closing connection

        default:
            send_json_response(404, ['success' => false, 'message' => 'API endpoint not found.']);
            break;
    }
    $conn->close();
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    if (isset($conn)) $conn->close();
    send_json_response(500, ['success' => false, 'message' => 'An internal server error occurred.']);
}