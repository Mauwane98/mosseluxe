<?php
// Initialize AJAX handler with clean JSON output
require_once __DIR__ . '/includes/ajax_init.php';

$response = ['success' => false, 'message' => 'Invalid request.'];

// Ensure user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'You must be logged in to manage your wishlist.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle GET requests for checking wishlist status
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check' && isset($_GET['product_id'])) {
    $product_id = filter_var($_GET['product_id'], FILTER_VALIDATE_INT);
    
    if ($product_id <= 0) {
        $response['message'] = 'Invalid product ID.';
        echo json_encode($response);
        exit();
    }
    
    $conn = get_db_connection();
    $stmt_check = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
    $stmt_check->bind_param("ii", $user_id, $product_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    $response = ['success' => true, 'in_wishlist' => $stmt_check->num_rows > 0];
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['product_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $response = ['success' => false, 'message' => 'Invalid security token.'];
        echo json_encode($response);
        exit;
    }
    $action = $_POST['action'];
    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);

    if ($product_id <= 0) {
        $response['message'] = 'Invalid product ID.';
        echo json_encode($response);
        exit();
    }

    $conn = get_db_connection();

    switch ($action) {
        case 'add':
            // Check if already in wishlist
            $stmt_check = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
            $stmt_check->bind_param("ii", $user_id, $product_id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $response['message'] = 'Product is already in your wishlist.';
            } else {
                $stmt_add = $conn->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
                $stmt_add->bind_param("ii", $user_id, $product_id);
                if ($stmt_add->execute()) {
                    $response = ['success' => true, 'message' => 'Product added to wishlist.'];
                } else {
                    $response['message'] = 'Error adding product to wishlist.';
                }
                $stmt_add->close();
            }
            $stmt_check->close();
            break;

        case 'remove':
            $stmt_remove = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
            $stmt_remove->bind_param("ii", $user_id, $product_id);
            if ($stmt_remove->execute()) {
                if ($stmt_remove->affected_rows > 0) {
                    $response = ['success' => true, 'message' => 'Product removed from wishlist.'];
                } else {
                    $response['message'] = 'Product not found in your wishlist.';
                }
            } else {
                $response['message'] = 'Error removing product from wishlist.';
            }
            $stmt_remove->close();
            break;

        case 'check':
            // Check if product is in wishlist
            $stmt_check = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
            $stmt_check->bind_param("ii", $user_id, $product_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            $response = ['success' => true, 'in_wishlist' => $stmt_check->num_rows > 0];
            $stmt_check->close();
            break;

        case 'toggle':
            // Add or remove from wishlist (toggle functionality)
            $stmt_check = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
            $stmt_check->bind_param("ii", $user_id, $product_id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                // Remove from wishlist
                $stmt_remove = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
                $stmt_remove->bind_param("ii", $user_id, $product_id);
                $stmt_remove->execute();
                $response = ['success' => true, 'message' => 'Product removed from wishlist.', 'action' => 'removed'];
                $stmt_remove->close();
            } else {
                // Add to wishlist
                $stmt_add = $conn->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
                $stmt_add->bind_param("ii", $user_id, $product_id);
                $stmt_add->execute();
                $response = ['success' => true, 'message' => 'Product added to wishlist.', 'action' => 'added'];
                $stmt_add->close();
            }
            $stmt_check->close();
            break;

        default:
            $response['message'] = 'Unknown action.';
            break;
    }

    $conn->close();
}

echo json_encode($response);
// No closing PHP tag - prevents accidental whitespace output
