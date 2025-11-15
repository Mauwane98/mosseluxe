<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_ids']) && is_array($_POST['product_ids'])) {
    $product_ids = array_map('intval', $_POST['product_ids']);
    
    if (!empty($product_ids)) {
        $conn = get_db_connection();
        
        $conn->begin_transaction();

        try {
            // Check if any products are associated with orders before deleting
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $sql_check = "SELECT COUNT(*) as order_count FROM order_items WHERE product_id IN ($placeholders)";
            $stmt_check = $conn->prepare($sql_check);
            $types = str_repeat('i', count($product_ids));
            $stmt_check->bind_param($types, ...$product_ids);
            $stmt_check->execute();
            $order_count = $stmt_check->get_result()->fetch_assoc()['order_count'];
            $stmt_check->close();

            if ($order_count > 0) {
                $_SESSION['error_message'] = 'Cannot delete products that are associated with existing orders.';
                $conn->rollback();
                $conn->close();
                header('Location: products.php');
                exit();
            }
            
            // We also need to delete the images associated with the products
            $sql_get_images = "SELECT image FROM products WHERE id IN ($placeholders)";
            $stmt_get_images = $conn->prepare($sql_get_images);
            $stmt_get_images->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
            $stmt_get_images->execute();
            $result = $stmt_get_images->get_result();
            $images_to_delete = [];
            while ($row = $result->fetch_assoc()) {
                $images_to_delete[] = $row['image'];
            }
            $stmt_get_images->close();

            // Now delete the products from the database
            $sql_delete = "DELETE FROM products WHERE id IN ($placeholders)";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
            
            if (!$stmt_delete->execute()) {
                throw new Exception('Failed to delete products from database.');
            }
            $stmt_delete->close();

            // Now delete the image files
            foreach ($images_to_delete as $image) {
                $image_path = ABSPATH . '/' . $image;
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            $conn->commit();
            $_SESSION['success_message'] = 'Selected products have been deleted.';

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = 'Failed to delete selected products: ' . $e->getMessage();
        }
        $conn->close();
    }
}

header('Location: products.php');
exit();
?>