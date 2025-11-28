<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'You must be logged in to submit returns.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    // Validate CSRF token
    if (!validate_csrf_token()) {
        $response['message'] = 'Invalid security token.';
        echo json_encode($response);
        exit();
    }

    $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
    $return_reason = $_POST['return_reason'] ?? '';
    $additional_details = trim($_POST['additional_details'] ?? '');
    $resolution = $_POST['resolution'] ?? '';
    $contact_method = $_POST['contact_method'] ?? 'email';
    $return_items = $_POST['return_items'] ?? [];
    $return_quantity = (int) ($_POST['return_quantity'] ?? 1);

    // Validation
    if (!$order_id) {
        $response['message'] = 'Invalid order selected.';
        echo json_encode($response);
        exit();
    }

    if (empty($return_items) || !is_array($return_items)) {
        $response['message'] = 'Please select at least one item to return.';
        echo json_encode($response);
        exit();
    }

    if (empty($return_reason)) {
        $response['message'] = 'Please select a return reason.';
        echo json_encode($response);
        exit();
    }

    if (!in_array($resolution, ['refund', 'store_credit', 'exchange'])) {
        $response['message'] = 'Please select a valid resolution method.';
        echo json_encode($response);
        exit();
    }

    $conn = get_db_connection();

    // Verify order belongs to user and is eligible for return
    $order_check_sql = "SELECT id, status FROM orders
                       WHERE id = ?
                       AND user_id = ?
                       AND status IN ('Delivered', 'Completed')
                       AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $order_check_stmt = $conn->prepare($order_check_sql);
    $order_check_stmt->bind_param("ii", $order_id, $user_id);
    $order_check_stmt->execute();
    $order_result = $order_check_stmt->get_result();
    $order_data = $order_result->fetch_assoc();
    $order_check_stmt->close();

    if (!$order_data) {
        $response['message'] = 'Order not found or not eligible for returns.';
        echo json_encode($response);
        exit();
    }

    // Validate selected items exist in the order
    $placeholders = str_repeat('?,', count($return_items) - 1) . '?';
    $item_check_sql = "SELECT product_id FROM order_items WHERE order_id = ? AND product_id IN ($placeholders)";
    $item_check_stmt = $conn->prepare($item_check_sql);
    $item_check_params = array_merge([$order_id], $return_items);
    $types = str_repeat('i', count($item_check_params));
    $item_check_stmt->bind_param($types, ...$item_check_params);
    $item_check_stmt->execute();
    $item_result = $item_check_stmt->get_result();
    $valid_items = [];
    while ($row = $item_result->fetch_assoc()) {
        $valid_items[] = $row['product_id'];
    }
    $item_check_stmt->close();

    // Check if all selected items are valid
    $invalid_items = array_diff($return_items, $valid_items);
    if (!empty($invalid_items)) {
        $response['message'] = 'Some selected items are not valid or not in this order.';
        echo json_encode($response);
        exit();
    }

    // Prepare banking details if it's a refund
    $bank_details = null;
    if ($resolution === 'refund') {
        $bank_details = [
            'bank_name' => $_POST['bank_name'] ?? '',
            'account_holder' => $_POST['account_holder'] ?? '',
            'account_number' => $_POST['account_number'] ?? '',
            'branch_code' => $_POST['branch_code'] ?? ''
        ];

        // Validate required bank details
        if (empty($bank_details['bank_name']) || empty($bank_details['account_holder']) ||
            empty($bank_details['account_number']) || empty($bank_details['branch_code'])) {
            $response['message'] = 'Please provide complete banking details for refund processing.';
            echo json_encode($response);
            exit();
        }
        $bank_details = json_encode($bank_details);
    }

    // Create return request
    $items_json = json_encode($return_items);
    $return_id = 'RTN-' . time() . '-' . $order_id;

    $insert_sql = "INSERT INTO returns (
        return_id, order_id, user_id, return_items, return_reason,
        additional_details, resolution_type, banking_details, contact_method, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("siissssss",
        $return_id,
        $order_id,
        $user_id,
        $items_json,
        $return_reason,
        $additional_details,
        $resolution,
        $bank_details,
        $contact_method
    );

    if ($insert_stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Your return request has been submitted successfully! We will review your request and contact you within 2 business days.',
            'return_id' => $return_id
        ];

        // TODO: Send confirmation email/WhatsApp notification
        // You could integrate with notification service here

    } else {
        $response['message'] = 'Failed to submit return request. Please try again.';
    }

    $insert_stmt->close();
    $conn->close();
}

echo json_encode($response);
?>
