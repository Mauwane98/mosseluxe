<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['is_featured'])) {
    $product_id = (int)$_POST['product_id'];
    $is_featured = $_POST['is_featured'] === 'true' ? 1 : 0;
    
    $conn = get_db_connection();
    
    $sql_update = "UPDATE products SET is_featured = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param('ii', $is_featured, $product_id);
    
    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'is_featured' => $is_featured]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
    }
    $stmt_update->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
exit();
?>