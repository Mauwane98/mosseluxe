<?php

namespace App\Controllers;

use Twig\Environment;

class LoginController
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
        $login_error = '';
        $csrf_token = generate_csrf_token();

        echo $this->twig->render('auth/login.html', [
            'login_error' => $login_error,
            'csrf_token' => $csrf_token
        ]);
    }

    public function authenticate()
    {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['login_error'] = 'Invalid CSRF token. Please try again.';
            header("Location: /login");
            exit();
        }

        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password']);

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Please enter both email and password.';
            header("Location: /login");
            exit();
        }

        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Password is correct, start a new session
                    session_regenerate_id(true);
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user['id'];
                    $_SESSION["user_id"] = $user['id']; // Consistent naming
                    $_SESSION["name"] = $user['name'];
                    $_SESSION["email"] = $user['email'];
                    $_SESSION["role"] = $user['role'];

                    // Redirect user to previous page or dashboard
                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect_url = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header("Location: " . $redirect_url);
                    } else {
                        header("Location: /my-account");
                    }
                    exit();
                } else {
                    $_SESSION['login_error'] = 'Invalid email or password.';
                }
            } else {
                $_SESSION['login_error'] = 'Invalid email or password.';
            }
            $stmt->close();
        }
        header("Location: /login");
        exit();
    }

    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        header("Location: /login");
        exit();
    }
}
