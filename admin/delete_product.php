<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Invalid CSRF token.';
        header('Location: products.php');
        exit;
    }

    $product_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($product_id) {
        $conn->begin_transaction();

        try {
            // Check if product is associated with any orders before deleting
            $sql_check_orders = "SELECT COUNT(*) as order_count FROM order_items WHERE product_id = ?";
            $stmt_check = $conn->prepare($sql_check_orders);
            $stmt_check->bind_param("i", $product_id);
            $stmt_check->execute();
            $order_count = $stmt_check->get_result()->fetch_assoc()['order_count'];
            $stmt_check->close();

            if ($order_count > 0) {
                $_SESSION['error_message'] = 'Cannot delete product: It is associated with existing orders.';
                $conn->rollback();
                header('Location: products.php');
                exit;
            }

            // Get image path before deleting product
            $sql_get_image = "SELECT image FROM products WHERE id = ?";
            $stmt_get_image = $conn->prepare($sql_get_image);
            $stmt_get_image->bind_param("i", $product_id);
            $stmt_get_image->execute();
            $image_path_row = $stmt_get_image->get_result()->fetch_assoc();
            $stmt_get_image->close();

            $image_to_delete = $image_path_row['image'] ?? null;

            // Delete product from database
            $stmt_delete_product = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt_delete_product->bind_param('i', $product_id);

            if (!$stmt_delete_product->execute()) {
                throw new Exception('Failed to delete product from database.');
            }
            $stmt_delete_product->close();

            // Delete image file
            if ($image_to_delete) {
                $full_image_path = ABSPATH . '/' . $image_to_delete;
                if (file_exists($full_image_path)) {
                    if (!unlink($full_image_path)) {
                        error_log("Failed to delete image file: " . $full_image_path);
                        // Optionally, you might want to set an error message for the admin here,
                        // but the product deletion itself is still successful.
                    }
                }
            }

            $conn->commit();
            $_SESSION['success_message'] = 'Product deleted successfully.';

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = 'Failed to delete product: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = 'Invalid product ID.';
    }
} else {
    $_SESSION['error_message'] = 'Invalid request method.';
}

header('Location: products.php');
exit;
?>
