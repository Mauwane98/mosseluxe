<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$conn = get_db_connection();

// If an admin is already logged in, redirect them to the dashboard
if (isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

$pageTitle = "Admin Login - Mossé Luxe";
$error = '';
$csrf_token = generate_csrf_token();

// Process login form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Prepare a select statement
            $sql = "SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'admin'";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $param_email);
                $param_email = $email;
                
                if ($stmt->execute()) {
                    $stmt->store_result();
                    
                    // Check if admin user exists
                    if ($stmt->num_rows == 1) {
                        $stmt->bind_result($id, $name, $email, $hashed_password, $role);
                        if ($stmt->fetch()) {
                            if (password_verify($password, $hashed_password)) {
                                // Password is correct, so start a new session
                                session_regenerate_id();
                                
                                // Store data in session variables
                                $_SESSION["admin_loggedin"] = true;
                                $_SESSION["admin_id"] = $id;
                                $_SESSION["admin_name"] = $name;
                                $_SESSION["admin_role"] = $role;                            
                                
                                // Redirect to admin dashboard
                                header("location: dashboard.php");
                                exit;
                            } else {
                                $error = 'Invalid email or password.';
                            }
                        }
                    } else {
                        $error = 'Invalid email or password.';
                    }
                } else {
                    $error = 'Oops! Something went wrong. Please try again later.';
                }
                $stmt->close();
            }
        }
    }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-md">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="bg-white shadow-lg rounded-lg px-8 pt-6 pb-8 mb-4">
            <div class="mb-8 text-center">
                <a href="../index.php"><img src="../assets/images/logo-dark.png" alt="Mossé Luxe" class="h-32 w-auto mx-auto"></a>
                <h1 class="text-2xl font-black uppercase tracking-tighter mt-4">Admin Login</h1>
            </div>
            <?php if(!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label><input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" name="email" placeholder="Email"></div>
            <div class="mb-6"><label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label><input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="password" type="password" name="password" placeholder="******************"></div>
            <div class="flex items-center justify-between"><button class="bg-black hover:bg-black/80 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" type="submit">Sign In</button></div>
        </form>
        <p class="text-center text-gray-500 text-xs">&copy;<?php echo date("Y"); ?> Mossé Luxe. All rights reserved.</p>
    </div>
</body>
</html>