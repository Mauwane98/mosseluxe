<?php
require_once 'bootstrap.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = get_db_connection();

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['toast_message'] = ['message' => 'Invalid security token.', 'type' => 'error'];
    header("Location: categories.php");
    exit();
}

// Get category ID
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

if ($category_id <= 0) {
    $_SESSION['toast_message'] = ['message' => 'Invalid category ID.', 'type' => 'error'];
    header("Location: categories.php");
    exit();
}

// Check if category has products
$check_sql = "SELECT COUNT(*) as product_count FROM products WHERE category_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $category_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$row = $result->fetch_assoc();
$product_count = $row['product_count'];
$check_stmt->close();

if ($product_count > 0) {
    $_SESSION['toast_message'] = [
        'message' => "Cannot delete category. It has {$product_count} product(s) assigned to it. Please reassign or delete the products first.",
        'type' => 'error'
    ];
    header("Location: categories.php");
    exit();
}

// Delete the category
$delete_sql = "DELETE FROM categories WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $category_id);

if ($delete_stmt->execute()) {
    $_SESSION['toast_message'] = ['message' => 'Category deleted successfully!', 'type' => 'success'];
} else {
    $_SESSION['toast_message'] = ['message' => 'Error deleting category: ' . $delete_stmt->error, 'type' => 'error'];
}

$delete_stmt->close();
$conn->close();

header("Location: categories.php");
exit();
?>
