<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

// Check if ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    header("Location: pages.php");
    exit();
}

$id = (int)$_POST['id'];

// Handle DELETE request for deleting a page
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_page'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Invalid CSRF token.';
    } else {
        $stmt = $conn->prepare("DELETE FROM pages WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Page deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete page.";
        }
        $stmt->close();
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

header("Location: pages.php");
exit();
?>
