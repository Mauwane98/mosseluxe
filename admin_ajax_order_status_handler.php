<?php
require_once '../includes/bootstrap.php';
require_once '../includes/order_notification_service.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'Authentication required.';
    echo json_encode($response);
    exit();
}

if (!is_admin()) {
    $response['message'] = 'Admin access required.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_ids = $_POST['order_ids'] ?? [];
    $new_status = $_POST['status'] ?? '';

    if (empty($order_ids) || empty($new_status)) {
        $response['message'] = 'Order IDs and status are required.';
        echo json_encode($response);
        exit();
    }

    // Validate CSRF token
    if (!validate_csrf_token()) {
        $response['message'] = 'Invalid security token.';
        echo json_encode($response);
        exit();
    }

    $conn = get_db_connection();

    $updated_count = 0;
    $notification_count = 0;

    foreach ($order_ids as $order_id) {
        $order_id = (int) $order_id;

        // Check if order exists and get current status
        $check_sql = "SELECT status FROM orders WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $order_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $order = $result->fetch_assoc();
        $check_stmt->close();

        if (!$order) continue;

        // Skip if status is already the same
        if ($order['status'] === $new_status) continue;

        // Update order status
        $update_sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_status, $order_id);

        if ($update_stmt->execute()) {
            $updated_count++;

            // Send notifications if the status change should trigger notifications
            if (hook_order_status_update($order_id, $new_status)) {
                $notification_count++;
            }
        }

        $update_stmt->close();
    }

    $conn->close();

    $response = [
        'success' => true,
        'message' => "Updated $updated_count order(s) to '" . htmlspecialchars($new_status, ENT_QUOTES, 'UTF-8') . "'. Sent $notification_count notification(s).",
        'updated_count' => $updated_count,
        'notification_count' => $notification_count
    ];
}

echo json_encode($response);
?>
