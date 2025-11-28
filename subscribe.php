<?php
// Prevent any output before JSON
ob_start();

require_once 'includes/bootstrap.php'; // This includes db_connect.php, config.php, and csrf.php

// Clear any accidental output
ob_end_clean();

header('Content-Type: application/json'); // Change to JSON response for AJAX form

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $response = ['success' => false, 'message' => 'Invalid security token.'];
        echo json_encode($response);
        exit;
    }

    $conn = get_db_connection();
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ['success' => false, 'message' => "Invalid email address."];
        echo json_encode($response);
        exit();
    }

    // Check if email already subscribed
    $sql_check = "SELECT id FROM subscribers WHERE email = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $response = ['success' => false, 'message' => "This email is already subscribed."];
            echo json_encode($response);
            exit();
        }
        $stmt_check->close();
    }

    // Insert new subscriber
    $sql_insert = "INSERT INTO subscribers (email) VALUES (?)";
    if ($stmt_insert = $conn->prepare($sql_insert)) {
        $stmt_insert->bind_param("s", $email);
        if ($stmt_insert->execute()) {
            $response = ['success' => true, 'message' => "Thank you for subscribing!"];
        } else {
            $response = ['success' => false, 'message' => "Something went wrong. Please try again."];
        }
        $stmt_insert->close();
    } else {
        $response = ['success' => false, 'message' => "Database error. Please try again."];
    }
    $conn->close();
} else {
    $response = ['success' => false, 'message' => 'Invalid request method.'];
}

echo json_encode($response);