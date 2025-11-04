<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and CSRF protection
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
require_once '../includes/auth_service.php';
require_once '../includes/config.php'; // For SITE_URL
$conn = get_db_connection();

$login_error = '';
$csrf_token = generate_csrf_token(); // Generate token for the form

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $login_error = 'Invalid CSRF token. Please try again.';
    } else {
        // Sanitize inputs
        $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
        $password = trim($_POST["password"]);

        // Prepare a select statement to find the admin user
        // NOTE: In a real application, you would have a dedicated 'admins' table
        // or a role system in the 'users' table. For this example, we'll assume
        // a specific user can be promoted to admin or a separate table exists.
        // For now, we'll use a placeholder query and assume a user with a specific ID is admin.
        // A more robust solution would involve checking a role or a separate admin table.
        
        // Placeholder: Assuming admin credentials are checked against a specific user or table.
        // For demonstration, let's assume a user with email 'admin@mosseluxe.com' is the admin.
        // In a real scenario, you'd query an 'admins' table or check user roles.
        
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'admin'";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement
            $stmt->bind_param("s", $param_email);
            $param_email = $email;

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();

                // Check if email exists and is an admin
                if ($stmt->num_rows == 1) {
                    // Bind result variables
                    $stmt->bind_result($id, $name, $email, $hashed_password, $role);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, use the Auth service to log in
                            Auth::loginAdmin(['id' => $id, 'name' => $name, 'role' => $role]);

                            if (isset($_POST['remember_me'])) {
                                Auth::rememberAdmin($id);
                            }
                            // Redirect to admin dashboard
                            header("location: dashboard.php");
                            exit();
                        } else {
                            // Password is not valid, display a generic error message
                            $login_error = 'Invalid email or password.';
                        }
                    }
                } else {
                    // Email doesn't exist or is not an admin, display a generic error message
                    $login_error = 'Invalid email or password.';
                }
            } else {
                $login_error = 'Oops! Something went wrong. Please try again later.';
            }

            // Close statement
            $stmt->close();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mossé Luxe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="container vh-100 d-flex justify-content-center align-items-center">
    <div class="col-md-6 col-lg-4">
        <div class="login-card p-4">
            <div class="text-center mb-4">
                <a href="../index.php">
                    <img src="<?php echo SITE_URL; ?>assets/images/logo.png" alt="Mossé Luxe Logo" style="height: 80px;">
                </a>
                <p class="text-muted mt-2">Administrator Login</p>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe">
                        <label class="form-check-label text-muted" for="rememberMe">
                            Remember Me
                        </label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary-dark">Sign In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
