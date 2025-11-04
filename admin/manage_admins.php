<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
$conn = get_db_connection();

$csrf_token = generate_csrf_token();
$message = '';
$error = '';

// Handle form submission for adding a new admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_admin'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name']);
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password']);

        if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            $error = "Please fill all fields with valid data.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            // Check if email already exists
            $sql_check = "SELECT id FROM users WHERE email = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $error = "An account with this email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'admin';
                $sql_insert = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("ssss", $name, $email, $hashed_password, $role);
                if ($stmt_insert->execute()) {
                    header("Location: manage_admins.php?success=added");
                    exit();
                } else {
                    $error = "Failed to create admin account.";
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
    }
}

// Handle POST request for deleting an admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_admin'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $id_to_delete = filter_var(trim($_POST['admin_id']), FILTER_SANITIZE_NUMBER_INT);
        if ($id_to_delete == $_SESSION['admin_id']) {
            header("Location: manage_admins.php?error=self_delete");
            exit();
        }

        // Check if this is the last admin
        $sql_count = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
        $result_count = $conn->query($sql_count);
        $admin_count = $result_count->fetch_assoc()['admin_count'];

        if ($admin_count <= 1) {
            header("Location: manage_admins.php?error=last_admin");
            exit();
        }

        $sql_delete = "DELETE FROM users WHERE id = ? AND role = 'admin'";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id_to_delete);
        if ($stmt_delete->execute()) {
            header("Location: manage_admins.php?success=deleted");
            exit();
        } else {
            header("Location: manage_admins.php?error=delete_failed");
            exit();
        }
    }
}

// Fetch all admin users
$admins = [];
$sql_admins = "SELECT id, name, email FROM users WHERE role = 'admin' ORDER BY name ASC";
if ($result = $conn->query($sql_admins)) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

// Check for success/error messages from redirects
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'added') $message = "Admin user added successfully!";
    if ($_GET['success'] == 'deleted') $message = "Admin user deleted successfully!";
    if ($_GET['success'] == 'updated') $message = "Admin user updated successfully!";
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] == 'self_delete') $error = "You cannot delete your own account.";
    if ($_GET['error'] == 'last_admin') $error = "Cannot delete the last remaining admin account.";
    if ($_GET['error'] == 'delete_failed') $error = "Failed to delete admin user.";
}
$active_page = 'manage_admins';
$page_title = 'Manage Administrators';
?>
<?php include '../includes/admin_page_header.php'; ?>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php
    include '../includes/admin_header.php'; 
    ?>

    <?php if(!empty($message)): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if(!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card p-4">
                <h5 class="gold-text">Add New Admin</h5>
                <form action="manage_admins.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3"><label for="name" class="form-label">Full Name</label><input type="text" class="form-control" id="name" name="name" required></div>
                    <div class="mb-3"><label for="email" class="form-label">Email Address</label><input type="email" class="form-control" id="email" name="email" required></div>
                    <div class="mb-3"><label for="password" class="form-label">Password</label><input type="password" class="form-control" id="password" name="password" required></div>
                    <button type="submit" name="add_admin" class="btn btn-primary-dark w-100">Add Admin</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (!empty($admins)): ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo $admin['id']; ?></td>
                                    <td><?php echo htmlspecialchars($admin['name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <a href='edit_admin.php?id=<?php echo $admin['id']; ?>' class='btn btn-sm btn-outline-dark'><i class="bi bi-pencil-square"></i></a>
                                        <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAdminModal<?php echo $admin['id']; ?>"><i class="bi bi-trash-fill"></i></button>
                                        <?php endif; ?>

                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteAdminModal<?php echo $admin['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header"><h5 class="modal-title">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <div class="modal-body">Are you sure you want to delete the admin "<?php echo htmlspecialchars($admin['name']); ?>"? This action cannot be undone.</div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="manage_admins.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                            <button type="submit" name="delete_admin" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan='4' class='text-center'>No admin users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>