<?php
require_once 'includes/bootstrap.php';

// Connect to database
$conn = get_db_connection();

// Test user credentials
$email = 'test@example.com';
$password = 'password';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "Test user already exists.\n";
} else {
    // Insert test user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, status) VALUES ('Test User', ?, ?, 1)");
    $stmt->bind_param("ss", $email, $hashed_password);
    if ($stmt->execute()) {
        echo "Test user created successfully.\n";
    } else {
        echo "Failed to create test user.\n";
    }
}

$stmt->close();
$conn->close();
?>
