<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    
    $conn = get_db_connection();
    
    // First, get the current status
    $sql_get_status = "SELECT status FROM products WHERE id = ?";
    $stmt_get = $conn->prepare($sql_get_status);
    $stmt_get->bind_param('i', $product_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    if ($row = $result->fetch_assoc()) {
        $new_status = $row['status'] ? 0 : 1;
        
        // Now, update the status
        $sql_update = "UPDATE products SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('ii', $new_status, $product_id);
        
        if ($stmt_update->execute()) {
            echo json_encode(['success' => true, 'status' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
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