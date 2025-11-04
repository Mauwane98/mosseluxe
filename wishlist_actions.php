<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db_connect.php';
require_once 'includes/csrf.php';
$conn = get_db_connection();

// User must be logged in to modify wishlist
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token.');
    }

    $user_id = $_SESSION['user_id'];
    $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
    $action = $_POST['action'] ?? '';
    $redirect_to = $_POST['redirect_to'] ?? 'product'; // Default redirect to product page

    if (!$product_id) {
        die('Invalid product ID.');
    }

    switch ($action) {
        case 'add':
            $sql = "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)";
            break;
        case 'remove':
            $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
            break;
        case 'toggle':
            // Check if the item is already in the wishlist
            $check_sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("ii", $user_id, $product_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                // Item exists, so remove it
                $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
            } else {
                // Item does not exist, so add it
                $sql = "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)";
            }
            $stmt_check->close();
            break;
        case 'notify_stock':
            // Add a request for a back-in-stock notification
            $sql = "INSERT INTO stock_notifications (user_id, product_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE notified = 0";
            break;
    }

    if (isset($sql)) {
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->close();

    // Redirect back to the appropriate page
    if ($redirect_to === 'wishlist') {
        header("Location: wishlist.php");
    } else {
        // For 'product' or 'shop' pages, redirecting back to the referrer is best
        $fallback_url = ($redirect_to === 'shop') ? 'shop.php' : 'product.php?id=' . $product_id;
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? $fallback_url));
    }
    exit();

} else {
    // If not a POST request, redirect to homepage
    header("Location: index.php");
    exit();
}
?>