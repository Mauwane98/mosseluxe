<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';

// Ensure admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("location: users.php");
    exit;
}

$user_id_to_delete = trim($_GET['id']);
$current_admin_id = $_SESSION['admin_id']; // Assuming admin_id is stored in session on login

// Prevent admin from deleting themselves
if ($user_id_to_delete == $current_admin_id) {
    header("location: users.php");
    exit;
}

$conn = get_db_connection();

// Prepare a delete statement
$sql = "DELETE FROM users WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_id);
    $param_id = $user_id_to_delete;

    if ($stmt->execute()) {
        // User deleted successfully
    } else {
        // Error occurred
    }
    $stmt->close();
} else {
    // Error preparing statement
}

$conn->close();

header("location: users.php");
exit;
?>
