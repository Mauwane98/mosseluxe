<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discount_code'])) {
    $discount_code = trim(strtoupper($_POST['discount_code']));
    $csrf_token = $_POST['csrf_token'] ?? '';

    // CSRF validation
    if (!verify_csrf_token($csrf_token)) {
        $response = ['success' => false, 'message' => 'Invalid security token.'];
        echo json_encode($response);
        exit;
    }

    if (empty($discount_code)) {
        $response = ['success' => false, 'message' => 'Please enter a discount code.'];
        echo json_encode($response);
        exit;
    }

    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM discount_codes WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) AND usage_limit > usage_count");
    $stmt->bind_param("s", $discount_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $discount_data = $result->fetch_assoc();

        $message = '';
        if ($discount_data['type'] === 'percentage') {
            $message = "Discount applied: {$discount_data['value']}% off";
        } else {
            $message = "Discount applied: R {$discount_data['value']} off";
        }

        $response = [
            'success' => true,
            'message' => $message,
            'discount_data' => $discount_data
        ];
    } else {
        $response = ['success' => false, 'message' => 'Invalid or expired discount code.'];
    }

    $stmt->close();
    $conn->close();
}

echo json_encode($response);
?>
