<?php
require_once __DIR__ . '/../includes/bootstrap.php'; // Includes db_connect.php, config.php, csrf.php, and starts session (if not already started)
require_once __DIR__ . '/../includes/auth_service.php'; // Auth class
require_once __DIR__ . '/../includes/notification_service.php'; // For email notifications

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

    // Send shipping status emails for significant status changes
    if (in_array($status, ['Processing', 'Shipped', 'Completed', 'Cancelled'])) {
        foreach ($idArray as $order_id) {
            // Get order and customer details
            $order_sql = "SELECT o.*, u.name as customer_name, u.email as customer_email
                         FROM orders o
                         LEFT JOIN users u ON o.user_id = u.id
                         WHERE o.id = ?";
            if ($order_stmt = $conn->prepare($order_sql)) {
                $order_stmt->bind_param("i", $order_id);
                $order_stmt->execute();
                $order_result = $order_stmt->get_result();

                if ($order_row = $order_result->fetch_assoc()) {
                    // Get order items
                    $items_sql = "SELECT oi.quantity, oi.price, p.name
                                 FROM order_items oi
                                 JOIN products p ON oi.product_id = p.id
                                 WHERE oi.order_id = ?";
                    if ($items_stmt = $conn->prepare($items_sql)) {
                        $items_stmt->bind_param("i", $order_id);
                        $items_stmt->execute();
                        $items_result = $items_stmt->get_result();

                        $order_items = [];
                        while ($item_row = $items_result->fetch_assoc()) {
                            $order_items[] = [
                                'name' => $item_row['name'],
                                'quantity' => $item_row['quantity'],
                                'price' => $item_row['price']
                            ];
                        }
                        $items_stmt->close();

                        // Prepare order data for email
                        $order_data = [
                            'order_id' => $order_id,
                            'items' => $order_items,
                            'subtotal' => $order_row['total_price'] - SHIPPING_COST, // Approximate
                            'shipping_cost' => SHIPPING_COST,
                            'total' => $order_row['total_price'],
                            'status' => $status
                        ];

                        // Get customer name from shipping info if user is null (guest checkout)
                        $customer_name = $order_row['customer_name'];
                        if (!$customer_name) {
                            // Parse shipping info JSON
                            $shipping_info = json_decode($order_row['shipping_address_json'], true);
                            if ($shipping_info) {
                                $customer_name = $order_row['shipping_info']['firstName'] . ' ' . $order_row['shipping_info']['lastName'];
                                $customer_email = $order_row['shipping_info']['email'];
                            }
                        } else {
                            $customer_email = $order_row['customer_email'];
                        }

                        // Send shipping status email
                        if ($customer_email && $customer_name) {
                            NotificationService::sendShippingStatusUpdate($order_data, $customer_email, $customer_name, $status);
                        }
                    }
                }
                $order_stmt->close();
            }
        }
    }

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
