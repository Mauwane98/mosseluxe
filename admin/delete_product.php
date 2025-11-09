<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Invalid CSRF token.';
        header('Location: products.php');
        exit;
    }

    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if ($product_id) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param('i', $product_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Product deleted successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to delete product.';
        }
        $stmt->close();
        $conn->close();
    } else {
        $_SESSION['error_message'] = 'Invalid product ID.';
    }
} else {
    $_SESSION['error_message'] = 'Invalid request method.';
}

header('Location: products.php');
exit;
?>
