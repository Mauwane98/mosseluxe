<?php
require_once __DIR__ . '/../includes/bootstrap.php'; // Includes db_connect.php, config.php, csrf.php, and starts session (if not already started)
require_once __DIR__ . '/../includes/auth_service.php'; // Auth class

// Ensure admin is logged in
Auth::checkAdmin(); // Redirects to login if not authenticated

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) { // Validate CSRF token
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }
    
    if (!isset($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'Product ID is missing.']);
        exit;
    }
    
    $product_id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

    $conn = get_db_connection();
    
    // First, get the current is_featured status
    $sql_get_featured = "SELECT is_featured FROM products WHERE id = ?";
    $stmt_get = $conn->prepare($sql_get_featured);
    $stmt_get->bind_param('i', $product_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    if ($row = $result->fetch_assoc()) {
        $new_status = $row['is_featured'] ? 0 : 1;
        
        // Now, update the is_featured status
        $sql_update = "UPDATE products SET is_featured = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('ii', $new_status, $product_id);
        
        if ($stmt_update->execute()) {
            $status_text = $new_status ? 'featured' : 'unfeatured';
            echo json_encode(['success' => true, 'new_status' => $new_status, 'message' => "Product successfully {$status_text}."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update featured status.']);
        }
        $stmt_update->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
    }
    $stmt_get->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
exit();
?>