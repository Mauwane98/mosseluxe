<?php
require_once __DIR__ . '/../includes/bootstrap.php'; // Includes db_connect.php, config.php, csrf.php, and starts session (if not already started)
require_once __DIR__ . '/../includes/auth_service.php'; // Auth class

// Ensure admin is logged in
Auth::checkAdmin(); // Redirects to login if not authenticated

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) { // Validate CSRF token
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

$ids = isset($_POST['ids']) ? $_POST['ids'] : '';
$status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

if (empty($ids) || !in_array($status, [0, 1])) {
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
$sql = "UPDATE products SET status = ? WHERE id IN ($placeholders)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$params = array_merge([$status], $idArray);
$types = str_repeat('i', count($params));
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $updated_count = $stmt->affected_rows;
    echo json_encode([
        'success' => true,
        'updated_count' => $updated_count,
        'message' => "Successfully updated $updated_count products"
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update products']);
}

$stmt->close();
$conn->close();
?>
