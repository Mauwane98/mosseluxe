<?php
$pageTitle = "Forgot Password - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';

// If user is already logged in, redirect to their account
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: my_account.php");
    exit;
}

require_once 'includes/header.php';

$error = '';
$success = '';
$csrf_token = generate_csrf_token();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email']);

        if (empty($email)) {
            $error = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $conn = get_db_connection();

            // Check if email exists
            $sql = "SELECT id FROM users WHERE email = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    // Insert or update reset token
                    $reset_sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)
                                  ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
                    if ($reset_stmt = $conn->prepare($reset_sql)) {
                        $reset_stmt->bind_param("sss", $email, $token, $expires_at);
                        if ($reset_stmt->execute()) {
                            // Send reset email
                            $reset_link = SITE_URL . "reset_password.php?token=" . $token;
                            $email_sent = sendPasswordResetEmail($email, $reset_link);

                            if ($email_sent) {
                                $success = 'If an account with that email exists, we have sent you a password reset link.';
                            } else {
                                $error = 'There was an error sending the reset email. Please try again later.';
                            }
                        } else {
                            $error = 'There was an error processing your request. Please try again.';
                        }
                        $reset_stmt->close();
                    }
                } else {
                    // Send success message even if email doesn't exist for security
                    $success = 'If an account with that email exists, we have sent you a password reset link.';
                }
                $stmt->close();
            }
            $conn->close();
        }
    }

    // Clear token on POST to prevent reuse
    regenerate_csrf_token();
}
?>

<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <div class="max-w-md mx-auto">
            <div class="text-center mb-8">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Forgot Password</h1>
                </div>
                <p class="text-lg text-black/70">Enter your email address and we'll send you a link to reset your password.</p>
            </div>

            <div class="bg-white p-8 rounded-lg shadow-md">
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($success)): // Only show form if success message not displayed ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-black/80 mb-1">Email Address</label>
                            <input type="email" id="email" name="email" required
                                   class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                            <p class="text-xs text-black/50 mt-1">Enter the email address associated with your account.</p>
                        </div>

                        <button type="submit" class="w-full bg-black text-white py-3 px-4 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">
                            Send Reset Link
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
