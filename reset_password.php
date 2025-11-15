<?php
$pageTitle = "Reset Password - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';

// If user is already logged in, redirect to their account
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: my_account.php");
    exit;
}

require_once 'includes/header.php';

$message = '';
$message_type = ''; // success or error
$show_form = false;
$email = '';

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    $message = 'Invalid password reset link.';
    $message_type = 'error';
} else {
    // Validate token
    $conn = get_db_connection();
    $sql = "SELECT email, expires_at FROM password_resets WHERE token = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $email = $row['email'];
            $expires_at = strtotime($row['expires_at']);
            $current_time = time();

            if ($current_time > $expires_at) {
                $message = 'This password reset link has expired. Please request a new one.';
                $message_type = 'error';

                // Clean up expired token
                $cleanup_sql = "DELETE FROM password_resets WHERE token = ?";
                if ($cleanup_stmt = $conn->prepare($cleanup_sql)) {
                    $cleanup_stmt->bind_param("s", $token);
                    $cleanup_stmt->execute();
                    $cleanup_stmt->close();
                }
            } else {
                $show_form = true;
            }
        } else {
            $message = 'Invalid password reset link.';
            $message_type = 'error';
        }
        $stmt->close();
    }
    $conn->close();
}

// Handle password reset form
if ($show_form && $_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid security token. Please try again.';
        $message_type = 'error';
        $show_form = true;
    } else {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($new_password) || empty($confirm_password)) {
            $message = 'Please fill in all password fields.';
            $message_type = 'error';
            $show_form = true;
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match.';
            $message_type = 'error';
            $show_form = true;
        } elseif (strlen($new_password) < 8) {
            $message = 'Password must be at least 8 characters long.';
            $message_type = 'error';
            $show_form = true;
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
            $message = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
            $message_type = 'error';
            $show_form = true;
        } else {
            // Update password
            $conn = get_db_connection();
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_sql = "UPDATE users SET password = ? WHERE email = ?";
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("ss", $hashed_password, $email);
                if ($update_stmt->execute()) {
                    // Clean up the used token
                    $cleanup_sql = "DELETE FROM password_resets WHERE token = ?";
                    if ($cleanup_stmt = $conn->prepare($cleanup_sql)) {
                        $cleanup_stmt->bind_param("s", $token);
                        $cleanup_stmt->execute();
                        $cleanup_stmt->close();
                    }

                    $message = 'Your password has been successfully reset! You can now log in with your new password.';
                    $message_type = 'success';
                    $show_form = false;
                } else {
                    $message = 'There was an error updating your password. Please try again.';
                    $message_type = 'error';
                    $show_form = true;
                }
                $update_stmt->close();
            }
            $conn->close();
        }

        regenerate_csrf_token();
    }
}

$csrf_token = generate_csrf_token();
?>

<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <div class="max-w-md mx-auto">
            <div class="text-center mb-8">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Reset Password</h1>
                </div>
                <p class="text-lg text-black/70">Enter your new password below.</p>
            </div>

            <div class="bg-white p-8 rounded-lg shadow-md">
                <?php if (!empty($message)): ?>
                    <div class="mb-4 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($show_form): ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . urlencode($token); ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="mb-4">
                            <label for="new_password" class="block text-sm font-medium text-black/80 mb-1">New Password</label>
                            <input type="password" id="new_password" name="new_password" required
                                   class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                            <p class="text-xs text-black/50 mt-1">Must be at least 8 characters with uppercase, lowercase, and numbers.</p>
                        </div>

                        <div class="mb-6">
                            <label for="confirm_password" class="block text-sm font-medium text-black/80 mb-1">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                        </div>

                        <button type="submit" class="w-full bg-black text-white py-3 px-4 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">
                            Reset Password
                        </button>
                    </form>
                <?php endif; ?>

                <div class="mt-6 text-center">
                    <p class="text-sm text-black/60">
                        Remember your password?
                        <a href="login.php" class="font-medium text-black hover:text-black/80">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
