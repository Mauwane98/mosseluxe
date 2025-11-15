<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
        header("Location: manage_homepage.php");
        exit();
    }

    $slide_id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;

    if (!$slide_id) {
        $_SESSION['toast_message'] = ['message' => 'Invalid slide ID.', 'type' => 'error'];
        header("Location: manage_homepage.php");
        exit();
    }

    // First, get the image path to delete the file
    $stmt_get = $conn->prepare("SELECT image_url FROM hero_slides WHERE id = ?");
    $stmt_get->bind_param('i', $slide_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    if ($slide = $result->fetch_assoc()) {
        if (!empty($slide['image_url']) && file_exists(ABSPATH . '/' . $slide['image_url'])) {
            unlink(ABSPATH . '/' . $slide['image_url']);
        }
    }
    $stmt_get->close();

    // Then delete the record
    $stmt_del = $conn->prepare("DELETE FROM hero_slides WHERE id = ?");
    $stmt_del->bind_param('i', $slide_id);
    if ($stmt_del->execute()) {
        $_SESSION['toast_message'] = ['message' => 'Slide deleted successfully.', 'type' => 'success'];
    } else {
        $_SESSION['toast_message'] = ['message' => 'Error deleting slide.', 'type' => 'error'];
    }
    $stmt_del->close();

    regenerate_csrf_token();
    header("Location: manage_homepage.php");
    exit();
} else {
    // Redirect if accessed directly
    header("Location: manage_homepage.php");
    exit();
}
?>
