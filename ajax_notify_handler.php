<?php
/**
 * AJAX Handler for Notify Me (Back in Stock) Alerts
 * Supports both logged-in users and guest users
 */

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit();
}

// Validate CSRF token
if (!validate_csrf_token()) {
    $response = ['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.'];
    echo json_encode($response);
    exit();
}

$action = $_POST['action'] ?? '';
$product_id = filter_var($_POST['product_id'] ?? 0, FILTER_VALIDATE_INT);

if ($action !== 'set_back_in_stock_alert' || !$product_id) {
    $response['message'] = 'Invalid request parameters.';
    echo json_encode($response);
    exit();
}

$conn = get_db_connection();

// Verify product exists and is out of stock
$product_sql = "SELECT id, name, stock FROM products WHERE id = ? AND status = 1";
$product_stmt = $conn->prepare($product_sql);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();
$product_stmt->close();

if (!$product) {
    $response['message'] = 'Product not found.';
    echo json_encode($response);
    exit();
}

// Get variant preferences
$size_variant = !empty($_POST['size_variant']) ? trim($_POST['size_variant']) : null;
$color_variant = !empty($_POST['color_variant']) ? trim($_POST['color_variant']) : null;

// Determine user ID and email
$user_id = null;
$email = null;

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Logged-in user
    $user_id = $_SESSION['user_id'];
    
    // Get user email from database
    $email_sql = "SELECT email FROM users WHERE id = ?";
    $email_stmt = $conn->prepare($email_sql);
    $email_stmt->bind_param("i", $user_id);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();
    $user_data = $email_result->fetch_assoc();
    $email_stmt->close();
    
    if ($user_data) {
        $email = $user_data['email'];
    }
} else {
    // Guest user - email is required
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit();
    }
}

if (!$email) {
    $response['message'] = 'Email address is required.';
    echo json_encode($response);
    exit();
}

// Check if alert already exists for this email/product/variant combination
$check_sql = "SELECT id FROM back_in_stock_alerts 
              WHERE email = ? AND product_id = ? 
              AND (size_variant <=> ?) AND (color_variant <=> ?)
              AND is_notified = 0";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("siss", $email, $product_id, $size_variant, $color_variant);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $check_stmt->close();
    $response = [
        'success' => true,
        'message' => 'You\'re already subscribed to notifications for this product!'
    ];
    echo json_encode($response);
    exit();
}
$check_stmt->close();

// Create the alert
$insert_sql = "INSERT INTO back_in_stock_alerts (user_id, product_id, email, size_variant, color_variant, created_at) 
               VALUES (?, ?, ?, ?, ?, NOW())";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("iisss", $user_id, $product_id, $email, $size_variant, $color_variant);

if ($insert_stmt->execute()) {
    $variant_text = [];
    if ($size_variant) $variant_text[] = "Size: $size_variant";
    if ($color_variant) $variant_text[] = "Color: $color_variant";
    $variant_display = !empty($variant_text) ? ' (' . implode(', ', $variant_text) . ')' : '';
    
    $response = [
        'success' => true,
        'message' => "We'll notify you at $email when \"{$product['name']}\"$variant_display is back in stock!"
    ];
} else {
    error_log("Failed to create back-in-stock alert: " . $conn->error);
    $response['message'] = 'Failed to set notification. Please try again.';
}

$insert_stmt->close();
echo json_encode($response);
