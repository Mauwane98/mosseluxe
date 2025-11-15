<?php
$pageTitle = "Login - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';
$conn = get_db_connection();
 
$email = $password = '';
$email_err = $password_err = $login_err = '';
 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assume login will fail until proven otherwise
    $login_err = "Invalid email or password.";
 
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $login_err = 'Invalid security token. Please try again.';
    } else {
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

        // Proceed only if form fields were filled
        if (empty($email_err) && empty($password_err)) {
            $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $param_email);
                $param_email = $email;
 
                if ($stmt->execute()) {
                    $stmt->store_result();
 
                    // Mitigate timing attacks: always fetch and verify password
                    // even if the user does not exist.
                    if ($stmt->num_rows == 1) {
                        $stmt->bind_result($id, $name, $email_db, $hashed_password, $role);
                        $stmt->fetch();
                    } else {
                        // User not found, but we'll still run password_verify on a dummy hash
                        // to ensure consistent execution time.
                        $hashed_password = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy'; // "password"
                    }
 
                    if (password_verify($password, $hashed_password)) {
                        // Clear any login error if password is correct
                        $login_err = '';

                        // Password is correct, regenerate session ID to prevent session fixation
                        session_regenerate_id(true);

                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $id;
                        $_SESSION["name"] = $name;
                        $_SESSION["email"] = $email_db;
                        $_SESSION["user_role"] = $role;

                        // Handle cart persistence - load user cart and merge with guest cart
                        // Load user's saved cart from database
                        $user_cart = [];
                        if (isset($_SESSION['user_id'])) {
                            try {
                                $stmt = $conn->prepare("
                                    SELECT uc.product_id, uc.quantity, p.name, p.price, p.sale_price, p.image
                                    FROM user_carts uc
                                    JOIN products p ON uc.product_id = p.id
                                    WHERE uc.user_id = ? AND p.status = 1
                                ");
                                if ($stmt) {
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    while ($row = $result->fetch_assoc()) {
                                        $user_cart[$row['product_id']] = [
                                            'name' => $row['name'],
                                            'price' => $row['sale_price'] > 0 ? $row['sale_price'] : $row['price'],
                                            'image' => $row['image'],
                                            'quantity' => $row['quantity']
                                        ];
                                    }
                                    $stmt->close();
                                } else {
                                    // user_carts table doesn't exist yet, skip cart loading
                                    error_log("User carts table not found, skipping cart persistence");
                                }
                            } catch (Exception $e) {
                                // Handle case where user_carts table doesn't exist
                                error_log("Error loading user cart: " . $e->getMessage());
                            }
                        }

                        // Merge with existing guest cart if any
                        $guest_cart = $_SESSION['cart'] ?? [];
                        if (!empty($guest_cart)) {
                            // Combine guest cart with user cart, adding quantities for duplicate items
                            foreach ($guest_cart as $product_id => $guest_item) {
                                if (isset($user_cart[$product_id])) {
                                    $user_cart[$product_id]['quantity'] += $guest_item['quantity'];
                                } else {
                                    $user_cart[$product_id] = $guest_item;
                                }
                            }
                        }

                        // Set the merged cart in session and save to database
                        $_SESSION['cart'] = $user_cart;
                        if (!empty($user_cart) && isset($_SESSION['user_id'])) {
                            try {
                                // Save the merged cart to database
                                $stmt_clear = $conn->prepare("DELETE FROM user_carts WHERE user_id = ?");
                                if ($stmt_clear) {
                                    $stmt_clear->bind_param("i", $_SESSION['user_id']);
                                    $stmt_clear->execute();
                                    $stmt_clear->close();

                                    $stmt_insert = $conn->prepare("INSERT INTO user_carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
                                    if ($stmt_insert) {
                                        foreach ($user_cart as $product_id => $item) {
                                            $stmt_insert->bind_param("iii", $_SESSION['user_id'], $product_id, $item['quantity']);
                                            $stmt_insert->execute();
                                        }
                                        $stmt_insert->close();
                                    }
                                }
                            } catch (Exception $e) {
                                // Handle case where user_carts table doesn't exist
                                error_log("Error saving user cart: " . $e->getMessage());
                                // Continue with login process - cart persistence is not critical
                            }
                        }

                        // Redirect user to their account page or redirect URL and exit script
                        $redirect_url = $_GET['redirect'] ?? 'my_account.php';
                        header("location: " . $redirect_url);
                        exit;
                    }
                } else {
                    $login_err = "Oops! Something went wrong. Please try again later.";
                }
                $stmt->close();
            }
        }
        }
}


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

                <?php if (isset($_GET['registered']) && $_GET['registered'] == '1'): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        Registration successful! Please log in with your credentials.
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
                            <a href="forgot_password.php" class="font-medium text-black hover:text-black/80">Forgot your password?</a>
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
