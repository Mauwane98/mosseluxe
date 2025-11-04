<?php

namespace App\Controllers;

use Twig\Environment;

class ForgotPasswordController
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
        $email_error = '';
        $success_message = '';
        $csrf_token = generate_csrf_token();

        echo $this->twig->render('auth/forgot_password.html', [
            'email_error' => $email_error,
            'success_message' => $success_message,
            'csrf_token' => $csrf_token
        ]);
    }

    public function sendResetLink()
    {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['email_error'] = 'Invalid CSRF token. Please try again.';
            header("Location: /forgot-password");
            exit();
        }

        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

        if (empty($email)) {
            $_SESSION['email_error'] = 'Please enter your email address.';
            header("Location: /forgot-password");
            exit();
        }

        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                // Generate a unique reset token
                $reset_token = bin2hex(random_bytes(32));
                $reset_token_expires_at = date("Y-m-d H:i:s", strtotime('+1 hour'));

                // Store the token in the database
                $sql_update = "UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?";
                if ($stmt_update = $this->conn->prepare($sql_update)) {
                    $stmt_update->bind_param("sss", $reset_token, $reset_token_expires_at, $email);
                    if ($stmt_update->execute()) {
                        // Send email with reset link
                        $reset_link = SITE_URL . "reset-password?token=" . $reset_token;
                        // In a real application, you would use PHPMailer or similar to send the email
                        // For now, we'll just set a success message
                        $_SESSION['success_message'] = 'A password reset link has been sent to your email address.';
                    } else {
                        $_SESSION['email_error'] = 'Error updating reset token. Please try again.';
                    }
                    $stmt_update->close();
                }
            } else {
                $_SESSION['email_error'] = 'No account found with that email address.';
            }
            $stmt->close();
        }
        header("Location: /forgot-password");
        exit();
    }
}
