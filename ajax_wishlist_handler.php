<?php
/**
 * AJAX Wishlist Handler
 * Handle wishlist operations
 */

// Suppress any errors/warnings from being displayed
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any errors/warnings
ob_start();

require_once 'includes/bootstrap.php';
require_once 'includes/wishlist_functions.php';

// Clean any output that might have occurred
$buffer = ob_get_clean();

// Log any unexpected output for debugging
if (!empty($buffer)) {
    error_log("AJAX Wishlist Handler - Unexpected output before JSON: " . substr($buffer, 0, 200));
}

header('Content-Type: application/json');

$conn = get_db_connection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to use wishlist'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $product_id = (int)$_POST['product_id'];
        $result = addToWishlist($conn, $user_id, $product_id);
        $result['count'] = getWishlistCount($conn, $user_id);
        echo json_encode($result);
        break;
        
    case 'remove':
        $product_id = (int)$_POST['product_id'];
        $result = removeFromWishlist($conn, $user_id, $product_id);
        $result['count'] = getWishlistCount($conn, $user_id);
        echo json_encode($result);
        break;
        
    case 'get':
        $wishlist = getWishlist($conn, $user_id);
        echo json_encode([
            'success' => true,
            'wishlist' => $wishlist,
            'count' => count($wishlist)
        ]);
        break;
        
    case 'check':
        $product_id = (int)$_POST['product_id'];
        $inWishlist = isInWishlist($conn, $user_id, $product_id);
        echo json_encode([
            'success' => true,
            'inWishlist' => $inWishlist
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
}

$conn->close();
// No closing PHP tag - prevents accidental whitespace output
