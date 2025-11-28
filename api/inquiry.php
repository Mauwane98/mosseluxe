<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$conn = get_db_connection();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST; // Fallback for form data
    }

    $type = $input['type'] ?? 'inquiry';
    if ($type === 'cart' || $type === 'checkout') {
        handle_cart_inquiry($conn, $input);
    } elseif ($type === 'chat') {
        handle_chat_message($conn, $input);
    } else {
        handle_general_inquiry($conn, $input);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handle_general_inquiry($conn, $input) {
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $message = trim($input['message'] ?? '');
    $product_id = isset($input['product_id']) ? (int)$input['product_id'] : null;

    // Validation
    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name, email, and message are required']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please provide a valid email address']);
        return;
    }

    // Rate limiting - basic check (in production, use proper rate limiting)
    $ip = $_SERVER['REMOTE_ADDR'];
    $time_window = time() - 300; // 5 minutes
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders_or_inquiries WHERE JSON_EXTRACT(contact_info, '$.ip') = ? AND created_at > ? AND type = 'inquiry'");
    $stmt->bind_param("si", $ip, date('Y-m-d H:i:s', $time_window));
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();

    if ($count >= 3) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many inquiries. Please wait before submitting another request.']);
        return;
    }

    // Prepare data
    $cart_snapshot = null;
    $contact_info = json_encode([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'ip' => $ip,
        'product_id' => $product_id
    ]);

    // Insert inquiry
    $stmt = $conn->prepare("INSERT INTO orders_or_inquiries (cart_snapshot, contact_info, type, status) VALUES (?, ?, 'inquiry', 'pending')");
    $stmt->bind_param("ss", $cart_snapshot, $contact_info);
    $stmt->execute();
    $inquiry_id = $conn->insert_id;
    $stmt->close();

    // Send email notification (if configured)
    send_inquiry_notification($name, $email, $phone, $message, $product_id, $inquiry_id);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your inquiry. We will get back to you soon!',
        'inquiry_id' => $inquiry_id
    ]);
}

function handle_cart_inquiry($conn, $input) {
    // For cart inquiries, include cart data
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $message = trim($input['message'] ?? '');

    if (empty($name) || empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name and email are required']);
        return;
    }

    // Get cart data from session
    ensure_cart_session();
    $cart_data = $_SESSION['cart'] ?? [];

    if (empty($cart_data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        return;
    }

    $cart_snapshot = json_encode($cart_data);
    $contact_info = json_encode([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);

    // Insert inquiry
    $stmt = $conn->prepare("INSERT INTO orders_or_inquiries (cart_snapshot, contact_info, type, status) VALUES (?, ?, 'inquiry', 'pending')");
    $stmt->bind_param("ss", $cart_snapshot, $contact_info);
    $stmt->execute();
    $inquiry_id = $conn->insert_id;
    $stmt->close();

    // Send cart inquiry notification
    send_cart_inquiry_notification($name, $email, $phone, $message, $cart_data, $inquiry_id);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your inquiry about these items. We will get back to you soon!',
        'inquiry_id' => $inquiry_id
    ]);
}

function handle_chat_message($conn, $input) {
    $message = trim($input['message'] ?? '');
    $name = trim($input['name'] ?? 'Anonymous');

    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        return;
    }

    $cart_snapshot = null;
    $contact_info = json_encode([
        'name' => $name,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    // Insert chat message
    $stmt = $conn->prepare("INSERT INTO orders_or_inquiries (cart_snapshot, contact_info, type, status) VALUES (?, ?, 'chat', 'pending')");
    $stmt->bind_param("ss", $cart_snapshot, $contact_info);
    $stmt->execute();
    $chat_id = $conn->insert_id;
    $stmt->close();

    // For chat, we might want to store the actual message in the cart_snapshot
    $chat_data = json_encode(['message' => $message, 'timestamp' => time()]);
    $update_stmt = $conn->prepare("UPDATE orders_or_inquiries SET cart_snapshot = ? WHERE id = ?");
    $update_stmt->bind_param("si", $chat_data, $chat_id);
    $update_stmt->execute();
    $update_stmt->close();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully',
        'chat_id' => $chat_id
    ]);
}

function send_inquiry_notification($name, $email, $phone, $message, $product_id, $inquiry_id) {
    // Check if email is configured
    if (!defined('SMTP_USERNAME') || empty(SMTP_USERNAME)) {
        return; // Skip if not configured
    }

    $subject = "New Product Inquiry - Inquiry ID: $inquiry_id";

    $body = "You have received a new product inquiry:\n\n";
    $body .= "Inquiry ID: $inquiry_id\n";
    $body .= "Name: $name\n";
    $body .= "Email: $email\n";
    $body .= "Phone: $phone\n";

    if ($product_id) {
        $body .= "Product ID: $product_id\n";
    }

    $body .= "\nMessage:\n$message\n";

    // In a real application, you would use proper email sending here
    // For now, just log it
    error_log("INQUIRY EMAIL: $subject\n$body");
}

function send_cart_inquiry_notification($name, $email, $phone, $message, $cart_data, $inquiry_id) {
    if (!defined('SMTP_USERNAME') || empty(SMTP_USERNAME)) {
        return;
    }

    $subject = "New Cart Inquiry - Inquiry ID: $inquiry_id";

    $body = "You have received a new cart inquiry:\n\n";
    $body .= "Inquiry ID: $inquiry_id\n";
    $body .= "Name: $name\n";
    $body .= "Email: $email\n";
    $body .= "Phone: $phone\n\n";

    $body .= "Cart Items:\n";
    $total = 0;
    foreach ($cart_data as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $total += $item_total;
        $body .= "- {$item['name']} (Qty: {$item['quantity']}) - R" . number_format($item_total, 2) . "\n";
    }
    $body .= "\nTotal: R" . number_format($total, 2) . "\n\n";

    if (!empty($message)) {
        $body .= "Additional Message:\n$message\n";
    }

    error_log("CART INQUIRY EMAIL: $subject\n$body");
}

function ensure_cart_session() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

$conn->close();
?>
