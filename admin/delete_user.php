<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/notification_service.php';

// Ensure admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    set_notification("error", "Invalid request. User ID not provided.");
    header("location: users.php");
    exit;
}

$user_id_to_delete = trim($_GET['id']);
$current_admin_id = $_SESSION['admin_id']; // Assuming admin_id is stored in session on login

// Prevent admin from deleting themselves
if ($user_id_to_delete == $current_admin_id) {
    set_notification("error", "You cannot delete your own account.");
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
        set_notification("success", "User has been deleted successfully.");
    } else {
        set_notification("error", "Oops! Something went wrong. Please try again later.");
    }
    $stmt->close();
} else {
    set_notification("error", "Error preparing the delete statement.");
}

$conn->close();

header("location: users.php");
exit;
?>
