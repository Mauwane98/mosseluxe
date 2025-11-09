<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$ids = isset($_POST['ids']) ? $_POST['ids'] : '';

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No IDs provided']);
    exit;
}

$idArray = explode(',', $ids);
$idArray = array_map('intval', array_filter($idArray));

if (empty($idArray)) {
    echo json_encode(['success' => false, 'message' => 'No valid IDs provided']);
    exit;
}

$conn = get_db_connection();

// Check if any products are associated with orders before deleting
$placeholders = str_repeat('?,', count($idArray) - 1) . '?';
$sql_check = "SELECT COUNT(*) as order_count FROM order_items WHERE product_id IN ($placeholders)";
$stmt_check = $conn->prepare($sql_check);
$types = str_repeat('i', count($idArray));
$stmt_check->bind_param($types, ...$idArray);
$stmt_check->execute();
$order_count = $stmt_check->get_result()->fetch_assoc()['order_count'];
$stmt_check->close();

if ($order_count > 0) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete products that are associated with existing orders']);
    exit;
}

// Prepare the delete statement
$sql = "DELETE FROM products WHERE id IN ($placeholders)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param($types, ...$idArray);

if ($stmt->execute()) {
    $deleted_count = $stmt->affected_rows;
    echo json_encode([
        'success' => true,
        'deleted_count' => $deleted_count,
        'message' => "Successfully deleted $deleted_count products"
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete products']);
}

$stmt->close();
$conn->close();
?>
