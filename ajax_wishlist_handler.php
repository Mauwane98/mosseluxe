<?php
/**
 * AJAX Wishlist Handler
 * 
 * Handles wishlist operations via AJAX.
 * Supports both logged-in users (database) and guests (session + cookie).
 * 
 * Always returns JSON: { "success": bool, "message": string, ... }
 */

// Prevent any output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/wishlist_functions.php';
require_once __DIR__ . '/app/Controllers/WishlistController.php';
require_once __DIR__ . '/app/Services/InputSanitizer.php';

// Clean any unexpected output
$buffer = ob_get_clean();
if (!empty($buffer)) {
    error_log("AJAX Wishlist Handler - Unexpected output: " . substr($buffer, 0, 200));
}

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Helper function to send JSON response
function sendJson(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

// Get database connection
$conn = get_db_connection();

// Initialize controller (works for both guests and logged-in users)
$wishlist = new \App\Controllers\WishlistController($conn);

// Get action and product_id
$action = $_POST['action'] ?? '';
$productId = isset($_POST['product_id']) 
    ? \App\Services\InputSanitizer::productId($_POST['product_id']) 
    : null;

// Validate CSRF for state-changing operations
if (in_array($action, ['add', 'remove', 'toggle', 'clear', 'move_to_cart'])) {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        sendJson([
            'success' => false,
            'message' => 'Invalid security token. Please refresh the page.'
        ]);
    }
}

// Handle actions
switch ($action) {
    case 'add':
        if (!$productId) {
            sendJson(['success' => false, 'message' => 'Invalid product ID']);
        }
        $result = $wishlist->add($productId);
        sendJson($result);
        break;
        
    case 'remove':
        if (!$productId) {
            sendJson(['success' => false, 'message' => 'Invalid product ID']);
        }
        $result = $wishlist->remove($productId);
        sendJson($result);
        break;
        
    case 'toggle':
        if (!$productId) {
            sendJson(['success' => false, 'message' => 'Invalid product ID']);
        }
        $result = $wishlist->toggle($productId);
        sendJson($result);
        break;
        
    case 'get':
        $items = $wishlist->getItems();
        sendJson([
            'success' => true,
            'wishlist' => $items,
            'count' => count($items)
        ]);
        break;
        
    case 'check':
        if (!$productId) {
            sendJson(['success' => false, 'message' => 'Invalid product ID']);
        }
        sendJson([
            'success' => true,
            'in_wishlist' => $wishlist->isInWishlist($productId),
            'count' => $wishlist->getCount()
        ]);
        break;
        
    case 'count':
        sendJson([
            'success' => true,
            'count' => $wishlist->getCount()
        ]);
        break;
        
    case 'clear':
        $result = $wishlist->clear();
        sendJson($result);
        break;
        
    case 'move_to_cart':
        if (!$productId) {
            sendJson(['success' => false, 'message' => 'Invalid product ID']);
        }
        $result = $wishlist->moveToCart($productId);
        sendJson($result);
        break;
        
    default:
        sendJson([
            'success' => false,
            'message' => 'Invalid action'
        ]);
}

$conn->close();
