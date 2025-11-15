<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

$csrf_token = generate_csrf_token();

$user_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) : 0;

if (!$user_id) {
    $_SESSION['error_message'] = "No user ID provided.";
    header("Location: users.php");
    exit();
}

// Fetch user details
$stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: users.php");
    exit();
}

$edit_user_error = ''; // Initialize error variable

// Handle form submission for updating the user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    // Repopulate $user with POST data to retain values on error
    $user['name'] = trim($_POST['name']);
    $user['email'] = trim($_POST['email']);
    $user['role'] = trim($_POST['role']);

    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $edit_user_error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name']);
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $role = trim($_POST['role']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']); // Get confirm password here

        // Validate inputs
        if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($role)) {
            $edit_user_error = "Name, valid email, and role are required.";
        } elseif (!in_array($role, ['admin', 'user'])) { // Changed 'customer' to 'user' for consistency
            $edit_user_error = "Invalid user role selected.";
        } else {
            // Check if email is being changed and if the new one is already taken
            if ($email !== $user['email']) {
                $sql_check = "SELECT id FROM users WHERE email = ? AND id != ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("si", $email, $user_id);
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $edit_user_error = "This email address is already in use by another account.";
                }
                $stmt_check->close();
            }

            if (empty($edit_user_error)) { // Only proceed if no prior error
                if (!empty($password)) {
                    if ($password !== $confirm_password) {
                        $edit_user_error = "New password and confirm password do not match.";
                    } elseif (strlen($password) < 8) {
                        $edit_user_error = "New password must be at least 8 characters long.";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql_update = "UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        $stmt_update->bind_param("ssssi", $name, $email, $role, $hashed_password, $user_id);
                    }
                } else {
                    // Update without changing password
                    $sql_update = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("sssi", $name, $email, $role, $user_id);
                }

                if (empty($edit_user_error)) { // Only proceed if no prior error
                    if (isset($stmt_update) && $stmt_update->execute()) {
                        $_SESSION['success_message'] = "User updated successfully!";
                        regenerate_csrf_token();
                        header("Location: users.php");
                        exit();
                    } else {
                        error_log("Error executing user update query: " . $stmt_update->error);
                        $edit_user_error = "Failed to update user.";
                    }
                }
                if (isset($stmt_update)) $stmt_update->close();
            }
        }
    }
}

$pageTitle = 'Edit User';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit User</h2>
        <a href="users.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Back to Users</a>
    </div>

    <?php if(!empty($edit_user_error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $edit_user_error; ?>
        </div>
    <?php endif; ?>

    <form action="edit_user.php?id=<?php echo $user_id; ?>" method="post" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Full Name -->
            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <input type="text" id="name" name="name" required
                       value="<?php echo htmlspecialchars($user['name']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Email Address -->
            <div class="md:col-span-2">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($user['email']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select id="role" name="role" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>Customer</option>
                    <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <!-- User ID (Read-only) -->
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">User ID</label>
                <input type="text" id="user_id" readonly
                       value="#<?php echo htmlspecialchars($user['id']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
            </div>
        </div>

        <!-- Password Change Section -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-800 mb-3">Change Password</h3>
            <p class="text-sm text-gray-600 mb-4">Leave blank to keep the current password.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" id="password" name="password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-4">
            <a href="users.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" name="update_user" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors">Update User</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
