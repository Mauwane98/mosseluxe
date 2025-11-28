<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$email = 'admin@mosse-luxe.com';
$password = 'admin123'; // Default password for the user to use
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$name = 'Admin User';
$role = 'admin';

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing admin
    $stmt = $conn->prepare("UPDATE users SET password = ?, role = ? WHERE email = ?");
    $stmt->bind_param("sss", $hashed_password, $role, $email);
    if ($stmt->execute()) {
        echo "Admin user updated successfully.\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
    } else {
        echo "Error updating admin user: " . $conn->error . "\n";
    }
} else {
    // Create new admin
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
    if ($stmt->execute()) {
        echo "Admin user created successfully.\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
    } else {
        echo "Error creating admin user: " . $conn->error . "\n";
    }
}

$conn->close();
?>
