<?php
header('Content-Type: application/json');

// Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once 'includes/bootstrap.php';

$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) : 0;

if (!$id) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$conn = get_db_connection();

// Fetch product details
$stmt = $conn->prepare("SELECT id, name, description, price, sale_price, image, stock FROM products WHERE id = ? AND status = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Product not found']);
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Create slug-like URL for linking
$slug = urlencode(str_replace(' ', '-', strtolower($product['name'])));

// Return product data
echo json_encode([
    'id' => $product['id'],
    'name' => $product['name'],
    'description' => $product['description'],
    'price' => $product['price'],
    'sale_price' => $product['sale_price'] ?: null,
    'image' => htmlspecialchars($product['image']),
    'stock' => (int)$product['stock'],
    'slug' => $slug
]);
?>
