<?php

namespace App\Controllers;

use Twig\Environment;

class RegisterController
{
    private $conn;
    private $twig;

    public function __construct($conn, Environment $twig)
    {
        $this->conn = $conn;
        $this->twig = $twig;
    }

    public function index()
    {
        $registration_error = '';
        $csrf_token = generate_csrf_token();

        echo $this->twig->render('auth/register.html', [
            'registration_error' => $registration_error,
            'csrf_token' => $csrf_token
        ]);
    }

    public function register()
    {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['registration_error'] = 'Invalid CSRF token. Please try again.';
            header("Location: /register");
            exit();
        }

        $name = trim($_POST['name']);
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            $_SESSION['registration_error'] = 'Please fill in all fields.';
            header("Location: /register");
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['registration_error'] = 'Invalid email format.';
            header("Location: /register");
            exit();
        }

        if ($password !== $confirm_password) {
            $_SESSION['registration_error'] = 'Passwords do not match.';
            header("Location: /register");
            exit();
        }

        // Check if email already exists
        $sql_check_email = "SELECT id FROM users WHERE email = ?";
        if ($stmt_check = $this->conn->prepare($sql_check_email)) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $_SESSION['registration_error'] = 'This email is already registered.';
                header("Location: /register");
                exit();
            }
            $stmt_check->close();
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into database
        $sql_insert_user = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        if ($stmt_insert = $this->conn->prepare($sql_insert_user)) {
            $stmt_insert->bind_param("sss", $name, $email, $hashed_password);
            if ($stmt_insert->execute()) {
                $_SESSION['registration_success'] = 'Registration successful! Please log in.';
                header("Location: /login");
                exit();
            } else {
                $_SESSION['registration_error'] = 'Registration failed. Please try again.';
                header("Location: /register");
                exit();
            }
            $stmt_insert->close();
        }
    }
}
