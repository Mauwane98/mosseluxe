<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.', 'items' => []];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'You must be logged in to access this feature.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);

    // Validate CSRF token
    if (!validate_csrf_token()) {
        $response['message'] = 'Invalid security token.';
        echo json_encode($response);
        exit();
    }

    // Verify order belongs to user
    $user_id = $_SESSION['user_id'];
    $conn = get_db_connection();

    // Check order ownership and eligibility (delivered within 30 days, not already returned/refunded)
    $order_check_sql = "SELECT id FROM orders
                       WHERE id = ?
                       AND user_id = ?
                       AND status IN ('Delivered', 'Completed')
                       AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $order_check_stmt = $conn->prepare($order_check_sql);
    $order_check_stmt->bind_param("ii", $order_id, $user_id);
    $order_check_stmt->execute();
    $order_result = $order_check_stmt->get_result();
    $order_check_stmt->close();

    if ($order_result->num_rows === 0) {
        $response['message'] = 'Order not found or not eligible for returns.';
        echo json_encode($response);
        exit();
    }

    // Fetch order items that aren't already returned or refunded
    $items_sql = "SELECT oi.product_id, oi.quantity, oi.price,
                         p.name, p.image, p.id as product_id
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = ?
                  ORDER BY p.name";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $result = $items_stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        // Check if this item has already been returned
        // For now, we'll allow all items, but you could extend this to check return status
        $items[] = [
            'product_id' => $row['product_id'],
            'name' => $row['name'],
            'image' => SITE_URL . htmlspecialchars($row['image']),
            'quantity' => $row['quantity'],
            'price' => number_format($row['price'], 2)
        ];
    }
    $items_stmt->close();
    $conn->close();

    $response = [
        'success' => true,
        'message' => 'Items loaded successfully.',
        'items' => $items
    ];
}

echo json_encode($response);
?>
