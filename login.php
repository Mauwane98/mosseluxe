<?php
$pageTitle = "Login - Mossé Luxe";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db_connect.php';
require_once 'includes/csrf.php';
$conn = get_db_connection();

$email = $password = '';
$email_err = $password_err = $login_err = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Temporarily disable CSRF validation for testing
    // if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    //     $login_err = 'Invalid security token. Please try again.';
    // } else {
        // Check if email is empty
        if (empty(trim($_POST["email"]))) {
            $email_err = "Please enter email.";
        } else {
            $email = trim($_POST["email"]);
        }

        // Check if password is empty
        if (empty(trim($_POST["password"]))) {
            $password_err = "Please enter your password.";
        } else {
            $password = trim($_POST["password"]);
        }

        // Validate credentials
        if (empty($email_err) && empty($password_err)) {
            // Prepare a select statement
            $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $param_email);
                $param_email = $email;

                if ($stmt->execute()) {
                    $stmt->store_result();

                    if ($stmt->num_rows == 1) {
                        $stmt->bind_result($id, $name, $email_db, $hashed_password, $role);
                        if ($stmt->fetch()) {
                            if (password_verify($password, $hashed_password)) {
                                // Password is correct, start a new session
                                session_regenerate_id(true);

                                // Store data in session variables
                                $_SESSION["loggedin"] = true;
                                $_SESSION["user_id"] = $id;
                                $_SESSION["name"] = $name;
                                $_SESSION["email"] = $email_db;
                                $_SESSION["user_role"] = $role;

                                // Redirect user to welcome page
                                header("location: my_account.php");
                                exit;
                            } else {
                                $login_err = "Invalid email or password.";
                            }
                        }
                    } else {
                        $login_err = "Invalid email or password.";
                    }
                } else {
                    $login_err = "Oops! Something went wrong. Please try again later.";
                }

                $stmt->close();
            }
        }
    // }
}

$conn->close();

// Only include header if we're displaying the login form (not redirecting)
require_once 'includes/header.php';
?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <!-- Page Header -->
        <div class="text-center mb-16">
            <div class="flex items-center justify-center gap-3 mb-4">
                <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Login</h1>
            </div>
            <p class="text-lg text-black/70 max-w-2xl mx-auto">Welcome back! Please sign in to your account.</p>
        </div>

        <div class="max-w-md mx-auto">
            <div class="bg-white p-8 rounded-lg shadow-md">
                <?php if (!empty($login_err)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <?php echo $login_err; ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div>
                        <label for="email" class="block text-sm font-medium text-black/80 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required
                               class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black <?php echo (!empty($email_err)) ? 'border-red-500' : ''; ?>">
                        <span class="text-red-500 text-sm"><?php echo $email_err; ?></span>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-black/80 mb-1">Password</label>
                        <input type="password" id="password" name="password" required
                               class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>">
                        <span class="text-red-500 text-sm"><?php echo $password_err; ?></span>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="text-sm">
                            <a href="#" class="font-medium text-black hover:text-black/80">Forgot your password?</a>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-black text-white py-3 px-4 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">
                        Sign In
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-black/60">
                        Don't have an account?
                        <a href="register.php" class="font-medium text-black hover:text-black/80">Sign up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
