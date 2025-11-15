<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

// Check if user ID is provided
if (!isset($_POST['id']) || empty(trim($_POST['id']))) {
    $_SESSION['error_message'] = "No user ID provided for deletion.";
    header("location: users.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['error_message'] = 'Invalid security token.';
    header("location: users.php");
    exit;
}

$user_id_to_delete = trim($_POST['id']);
$current_admin_id = $_SESSION['admin_id']; // Assuming admin_id is stored in session on login

// Prevent admin from deleting themselves
if ($user_id_to_delete == $current_admin_id) {
    $_SESSION['error_message'] = "You cannot delete your own admin account.";
    header("location: users.php");
    exit;
}

// Check if the user has any associated orders
$sql_check_orders = "SELECT COUNT(*) FROM orders WHERE user_id = ?";
$stmt_check = $conn->prepare($sql_check_orders);
$stmt_check->bind_param("i", $user_id_to_delete);
$stmt_check->execute();
$stmt_check->bind_result($order_count);
$stmt_check->fetch();
$stmt_check->close();

if ($order_count > 0) {
    $_SESSION['error_message'] = "Cannot delete user: This user has existing orders.";
    header("location: users.php");
    exit;
}

// Prepare a delete statement
$sql = "DELETE FROM users WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_id);
    $param_id = $user_id_to_delete;

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User deleted successfully.";
        regenerate_csrf_token();
    } else {
        $_SESSION['error_message'] = "Failed to delete user due to a database error.";
    }
    $stmt->close();
} else {
    $_SESSION['error_message'] = "Failed to prepare the delete statement.";
}



header("location: users.php");
exit;
?>
