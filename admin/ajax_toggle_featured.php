<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $response['message'] = 'Invalid security token.';
    echo json_encode($response);
    exit;
}

$product_id = isset($_POST['product_id']) ? filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT) : 0;

if (!$product_id) {
    $response['message'] = 'Invalid product ID.';
    echo json_encode($response);
    exit;
}

$conn = get_db_connection();

// Toggle the is_featured status
$sql = "UPDATE products SET is_featured = NOT is_featured WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    // Fetch the new status to return to the client
    $result = $conn->query("SELECT is_featured FROM products WHERE id = $product_id");
    $new_status = $result->fetch_assoc()['is_featured'];
    $response = ['success' => true, 'is_featured' => (bool)$new_status];
} else {
    $response['message'] = 'Failed to update product status.';
}

$stmt->close();
$conn->close();

echo json_encode($response);