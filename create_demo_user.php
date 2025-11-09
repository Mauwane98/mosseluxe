<?php
require_once 'includes/db_connect.php';

$conn = get_db_connection();

// Create a demo user
$name = "Demo User";
$email = "demo@mosseluxe.com";
$password = password_hash("password123", PASSWORD_DEFAULT);

$sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("sss", $name, $email, $password);
    if ($stmt->execute()) {
        echo "Demo user created successfully!\n";
        echo "Email: demo@mosseluxe.com\n";
        echo "Password: password123\n";
    } else {
        echo "Error creating demo user: " . $stmt->error . "\n";
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error . "\n";
}

$conn->close();
?>
