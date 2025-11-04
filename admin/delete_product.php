<?php
// Start session and include admin authentication
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
        // CSRF token is invalid, redirect back with an error
        header("Location: products.php?error=invalid_csrf_token");
        exit();
    }

    // Get product ID from POST data and sanitize it
    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
        $product_id = filter_var(trim($_POST['product_id']), FILTER_SANITIZE_NUMBER_INT);

        // Prepare a delete statement
        $sql_delete_product = "DELETE FROM products WHERE id = ?";
        
        if ($stmt_delete = $conn->prepare($sql_delete_product)) {
            // Bind the product ID to the prepared statement
            $stmt_delete->bind_param("i", $param_id);
            $param_id = $product_id;

            // Attempt to execute the prepared statement
            if ($stmt_delete->execute()) {
                // Check if any row was affected (i.e., if the product existed and was deleted)
                if ($stmt_delete->affected_rows > 0) {
                    // Product deleted successfully
                    header("Location: products.php?success=product_deleted");
                } else {
                    // Product not found or already deleted
                    header("Location: products.php?error=product_not_found_or_already_deleted");
                }
            } else {
                // Error executing the delete statement
                error_log("Error executing delete product query: " . $stmt_delete->error);
                header("Location: products.php?error=delete_failed");
            }
            $stmt_delete->close();
        } else {
            // Error preparing the delete statement
            error_log("Error preparing delete product query: " . $conn->error);
            header("Location: products.php?error=prepare_failed");
        }
    } else {
        // No product ID provided in the POST data
        header("Location: products.php?error=no_product_id_provided");
    }
} else {
    // If the request method is not POST, redirect to products page
    header("Location: products.php");
}

// Close the database connection
$conn->close();
?>
