<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

// Admin user data
$name = 'Administrator';
$email = 'admin@example.com';
$plain_password = 'password123';
$role = 'admin';

// Hash the password
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

// Insert into database
$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

if ($stmt->execute()) {
    echo "Admin user created successfully.\n";
    echo "Email: $email\n";
    echo "Password: $plain_password\n";
} else {
    echo "Error creating admin user: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();
?>
