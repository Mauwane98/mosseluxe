<?php
require_once 'includes/db_connect.php';
require_once 'includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = get_db_connection();
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email address.";
        header("Location: index.php#newsletter-signup");
        exit();
    }

    // Check if email already subscribed
    $sql_check = "SELECT id FROM subscribers WHERE email = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $_SESSION['error_message'] = "This email is already subscribed.";
            header("Location: index.php#newsletter-signup");
            exit();
        }
        $stmt_check->close();
    }

    // Insert new subscriber
    $sql_insert = "INSERT INTO subscribers (email) VALUES (?)";
    if ($stmt_insert = $conn->prepare($sql_insert)) {
        $stmt_insert->bind_param("s", $email);
        if ($stmt_insert->execute()) {
            $_SESSION['success_message'] = "Thank you for subscribing!";
        } else {
            $_SESSION['error_message'] = "Something went wrong. Please try again.";
        }
        $stmt_insert->close();
    } else {
        $_SESSION['error_message'] = "Database error. Please try again.";
    }
    $conn->close();
    header("Location: index.php#newsletter-signup");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>