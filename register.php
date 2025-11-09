<?php
$pageTitle = "Register - Mossé Luxe";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db_connect.php';
require_once 'includes/csrf.php';
$conn = get_db_connection();

$name = $email = $password = $confirm_password = '';
$name_err = $email_err = $password_err = $confirm_password_err = $register_err = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Temporarily disable CSRF validation for testing
    // if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    //     $register_err = 'Invalid security token. Please try again.';
    // } else {
        // Validate name
        if (empty(trim($_POST["name"]))) {
            $name_err = "Please enter your name.";
        } elseif (strlen(trim($_POST["name"])) < 2) {
            $name_err = "Name must have at least 2 characters.";
        } else {
            $name = trim($_POST["name"]);
        }

        // Validate email
        if (empty(trim($_POST["email"]))) {
            $email_err = "Please enter an email.";
        } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Prepare a select statement
            $sql = "SELECT id FROM users WHERE email = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $param_email);
                $param_email = trim($_POST["email"]);
                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows == 1) {
                        $email_err = "This email is already taken.";
                    } else {
                        $email = trim($_POST["email"]);
                    }
                } else {
                    $register_err = "Oops! Something went wrong. Please try again later.";
                }
                $stmt->close();
            }
        }

        // Validate password
        if (empty(trim($_POST["password"]))) {
            $password_err = "Please enter a password.";
        } elseif (strlen(trim($_POST["password"])) < 6) {
            $password_err = "Password must have at least 6 characters.";
        } else {
            $password = trim($_POST["password"]);
        }

        // Validate confirm password
        if (empty(trim($_POST["confirm_password"]))) {
            $confirm_password_err = "Please confirm password.";
        } else {
            $confirm_password = trim($_POST["confirm_password"]);
            if (empty($password_err) && ($password != $confirm_password)) {
                $confirm_password_err = "Password did not match.";
            }
        }

        // Check input errors before inserting in database
        if (empty($name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
            // Prepare an insert statement
            $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sss", $param_name, $param_email, $param_password);

                $param_name = $name;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash

                if ($stmt->execute()) {
                    // Registration successful, redirect to login page
                    header("location: login.php?registered=1");
                    exit;
                } else {
                    $register_err = "Oops! Something went wrong. Please try again later.";
                }

                $stmt->close();
            }
        }
    // }
}

$conn->close();

// Only include header if we're displaying the registration form (not redirecting)
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Create Account</h1>
            </div>
            <p class="text-lg text-black/70 max-w-2xl mx-auto">Join Mossé Luxe and start your fashion journey today.</p>
        </div>

        <div class="max-w-md mx-auto">
            <div class="bg-white p-8 rounded-lg shadow-md">
                <?php if (!empty($register_err)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <?php echo $register_err; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['registered']) && $_GET['registered'] == '1'): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        Registration successful! Please log in with your credentials.
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div>
                        <label for="name" class="block text-sm font-medium text-black/80 mb-1">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required
                               class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black <?php echo (!empty($name_err)) ? 'border-red-500' : ''; ?>">
                        <span class="text-red-500 text-sm"><?php echo $name_err; ?></span>
                    </div>

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

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-black/80 mb-1">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black <?php echo (!empty($confirm_password_err)) ? 'border-red-500' : ''; ?>">
                        <span class="text-red-500 text-sm"><?php echo $confirm_password_err; ?></span>
                    </div>

                    <button type="submit" class="w-full bg-black text-white py-3 px-4 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">
                        Create Account
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-black/60">
                        Already have an account?
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
