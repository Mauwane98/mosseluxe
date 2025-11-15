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

// Retrieve image paths before deleting products
$image_paths_to_delete = [];
$sql_get_images = "SELECT image FROM products WHERE id IN ($placeholders)";
$stmt_get_images = $conn->prepare($sql_get_images);
$stmt_get_images->bind_param($types, ...$idArray);
$stmt_get_images->execute();
$result_images = $stmt_get_images->get_result();
while ($row = $result_images->fetch_assoc()) {
    if (!empty($row['image'])) {
        $image_paths_to_delete[] = $row['image'];
    }
}
$stmt_get_images->close();

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

    // Delete image files from the server
    foreach ($image_paths_to_delete as $image_path) {
        $full_image_path = ABSPATH . '/' . $image_path;
        if (file_exists($full_image_path)) {
            if (!unlink($full_image_path)) {
                error_log("Failed to delete image file during bulk deletion: " . $full_image_path);
            }
        }
    }

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
