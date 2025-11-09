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
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

$valid_statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];

if (empty($ids) || !in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$idArray = explode(',', $ids);
$idArray = array_map('intval', array_filter($idArray));

if (empty($idArray)) {
    echo json_encode(['success' => false, 'message' => 'No valid IDs provided']);
    exit;
}

$conn = get_db_connection();

// Prepare the update statement
$placeholders = str_repeat('?,', count($idArray) - 1) . '?';
$sql = "UPDATE orders SET status = ? WHERE id IN ($placeholders)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$params = array_merge([$status], $idArray);
$types = str_repeat('i', count($params));
$types[0] = 's'; // First parameter is status (string)

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $updated_count = $stmt->affected_rows;
    echo json_encode([
        'success' => true,
        'updated_count' => $updated_count,
        'message' => "Successfully updated $updated_count orders to $status"
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update orders']);
}

$stmt->close();
$conn->close();
?>
