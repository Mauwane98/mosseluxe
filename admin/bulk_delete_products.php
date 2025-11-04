<?php
// Start session and include necessary files
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
$conn = get_db_connection();

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: products.php?error=invalid_csrf");
        exit();
    }

    // Check if the bulk action is 'delete' and product IDs are provided
    if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'delete' && isset($_POST['product_ids']) && is_array($_POST['product_ids'])) {
        $product_ids = $_POST['product_ids'];

        if (empty($product_ids)) {
            header("Location: products.php?error=no_selection");
            exit();
        }

        // Sanitize all product IDs to ensure they are integers
        $sanitized_ids = array_map('intval', $product_ids);

        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
        $types = str_repeat('i', count($sanitized_ids));

        // Prepare a delete statement
        $sql_delete = "DELETE FROM products WHERE id IN ($placeholders)";
        
        if ($stmt_delete = $conn->prepare($sql_delete)) {
            $stmt_delete->bind_param($types, ...$sanitized_ids);

            if ($stmt_delete->execute()) {
                $deleted_count = $stmt_delete->affected_rows;
                header("Location: products.php?success=bulk_deleted&count=" . $deleted_count);
            } else {
                header("Location: products.php?error=bulk_delete_failed");
            }
            $stmt_delete->close();
        } else {
            header("Location: products.php?error=prepare_failed");
        }
    } else {
        header("Location: products.php?error=invalid_action");
    }
} else {
    header("Location: products.php");
}
$conn->close();
exit();