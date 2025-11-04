<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
$conn = get_db_connection();

$csrf_token = generate_csrf_token();
$error = '';
$admin_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : 0;

if (!$admin_id) {
    header("Location: manage_admins.php");
    exit();
}

// Fetch admin details
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'admin'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    header("Location: manage_admins.php?error=not_found");
    exit();
}

// Handle form submission for updating the admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_admin'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name']);
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password']);

        // Validate inputs
        if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Name and a valid email are required.";
        } else {
            // Check if email is being changed and if the new one is already taken
            if ($email !== $admin['email']) {
                $sql_check = "SELECT id FROM users WHERE email = ? AND id != ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("si", $email, $admin_id);
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $error = "This email address is already in use by another account.";
                }
                $stmt_check->close();
            }

            if (empty($error)) {
                if (!empty($password)) {
                    // Update with new password
                    if (strlen($password) < 8) {
                        $error = "New password must be at least 8 characters long.";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql_update = "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        $stmt_update->bind_param("sssi", $name, $email, $hashed_password, $admin_id);
                    }
                } else {
                    // Update without changing password
                    $sql_update = "UPDATE users SET name = ?, email = ? WHERE id = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("ssi", $name, $email, $admin_id);
                }

                if (empty($error)) {
                    if (isset($stmt_update) && $stmt_update->execute()) {
                        header("Location: manage_admins.php?success=updated");
                        exit();
                    } else {
                        $error = "Failed to update admin user.";
                    }
                }
                if (isset($stmt_update)) $stmt_update->close();
            }
        }
    }
}

$active_page = 'manage_admins';
$page_title = 'Edit Administrator';
?>
<?php include '../includes/admin_page_header.php'; ?>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../includes/admin_header.php'; ?>

    <div class="card p-4">
        <div class="card-body">
            <?php if(!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <form action="edit_admin.php?id=<?php echo $admin_id; ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="mb-3"><label for="name" class="form-label">Full Name</label><input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required></div>
                <div class="mb-3"><label for="email" class="form-label">Email Address</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required></div>
                <hr class="my-4">
                <h5 class="gold-text">Change Password</h5>
                <p class="text-muted small">Leave blank to keep the current password.</p>
                <div class="mb-3"><label for="password" class="form-label">New Password</label><input type="password" class="form-control" id="password" name="password"></div>
                <button type="submit" name="update_admin" class="btn btn-primary-dark mt-3">Update Admin</button>
                <a href="manage_admins.php" class="btn btn-outline-secondary mt-3 ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>