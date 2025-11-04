<?php

namespace App\Controllers;

use Twig\Environment;

class ResetPasswordController
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
        $error = '';
        $message = '';
        $show_form = true;
        $csrf_token = generate_csrf_token();
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $error = 'Invalid password reset token.';
            $show_form = false;
        } else {
            // Validate token against database
            $sql = "SELECT id, reset_token_expires_at FROM users WHERE reset_token = ?";
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $user = $result->fetch_assoc();
                    $expires_at = new \DateTime($user['reset_token_expires_at']);
                    $now = new \DateTime();

                    if ($now > $expires_at) {
                        $error = 'Password reset token has expired.';
                        $show_form = false;
                    }
                } else {
                    $error = 'Invalid password reset token.';
                    $show_form = false;
                }
                $stmt->close();
            }
        }

        echo $this->twig->render('auth/reset_password.html', [
            'error' => $error,
            'message' => $message,
            'show_form' => $show_form,
            'csrf_token' => $csrf_token,
            'token' => $token
        ]);
    }

    public function resetPassword()
    {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
            header("Location: /reset-password?token=" . $_POST['token']);
            exit();
        }

        $token = trim($_POST['token']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        if (empty($token) || empty($password) || empty($confirm_password)) {
            $_SESSION['error'] = 'Please fill in all fields.';
            header("Location: /reset-password?token=" . $token);
            exit();
        }

        if ($password !== $confirm_password) {
            $_SESSION['error'] = 'Passwords do not match.';
            header("Location: /reset-password?token=" . $token);
            exit();
        }

        if (strlen($password) < 6) {
            $_SESSION['error'] = 'Password must be at least 6 characters long.';
            header("Location: /reset-password?token=" . $token);
            exit();
        }

        // Validate token against database
        $sql = "SELECT id, reset_token_expires_at FROM users WHERE reset_token = ?";
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                $expires_at = new \DateTime($user['reset_token_expires_at']);
                $now = new \DateTime();

                if ($now > $expires_at) {
                    $_SESSION['error'] = 'Password reset token has expired.';
                    header("Location: /forgot-password");
                    exit();
                }

                // Update password and clear token
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?";
                if ($stmt_update = $this->conn->prepare($sql_update)) {
                    $stmt_update->bind_param("si", $hashed_password, $user['id']);
                    if ($stmt_update->execute()) {
                        $_SESSION['success_message'] = 'Your password has been reset successfully. Please log in.';
                        header("Location: /login");
                        exit();
                    } else {
                        $_SESSION['error'] = 'Error updating password. Please try again.';
                    }
                    $stmt_update->close();
                }
            } else {
                $_SESSION['error'] = 'Invalid password reset token.';
            }
            $stmt->close();
        }
        header("Location: /reset-password?token=" . $token);
        exit();
    }
}
