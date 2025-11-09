<?php
require_once '../includes/db_connect.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_ids']) && is_array($_POST['product_ids'])) {
    $product_ids = array_map('intval', $_POST['product_ids']);
    
    if (!empty($product_ids)) {
        $conn = get_db_connection();
        
        // We also need to delete the images associated with the products
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
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
        
        if ($stmt_delete->execute()) {
            // Now delete the image files
            foreach ($images_to_delete as $image) {
                $image_path = '../assets/images/' . $image;
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            $_SESSION['success_message'] = 'Selected products have been deleted.';
        } else {
            $_SESSION['error_message'] = 'Failed to delete selected products.';
        }
        $stmt_delete->close();
        $conn->close();
    }
}

header('Location: products.php');
exit();
?>