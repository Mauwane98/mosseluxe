<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

$csrf_token = generate_csrf_token();

$admin_id = $_SESSION['admin_id'];

$edit_profile_error = ''; // Initialize error variable

// Fetch current admin details
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'admin'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    $_SESSION['toast_message'] = ['message' => 'Profile not found.', 'type' => 'error'];
    header("Location: dashboard.php");
    exit();
}

// Handle form submission for updating the profile
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Repopulate $admin with POST data to retain values on error
    $admin['name'] = trim($_POST['name']);
    $admin['email'] = trim($_POST['email']);

    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $edit_profile_error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name']);
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $current_password = trim($_POST['current_password']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Validate inputs
        if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $edit_profile_error = "Name and a valid email are required.";
        } else {
            // Check if email is being changed and if the new one is already taken
            if ($email !== $admin['email']) {
                $sql_check = "SELECT id FROM users WHERE email = ? AND id != ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("si", $email, $admin_id);
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $edit_profile_error = "This email address is already in use by another account.";
                }
                $stmt_check->close();
            }

            if (empty($edit_profile_error)) { // Only proceed if no prior error
                // Verify current password if changing password
                if (!empty($password)) {
                    if (empty($current_password)) {
                        $edit_profile_error = "Current password is required to change password.";
                    } else {
                        $stmt_verify = $conn->prepare("SELECT password FROM users WHERE id = ?");
                        $stmt_verify->bind_param("i", $admin_id);
                        $stmt_verify->execute();
                        $result_verify = $stmt_verify->get_result();
                        $current_hashed = $result_verify->fetch_assoc()['password'];
                        $stmt_verify->close();

                        if (!password_verify($current_password, $current_hashed)) {
                            $edit_profile_error = "Current password is incorrect.";
                        } elseif ($password !== $confirm_password) {
                            $edit_profile_error = "New password and confirm password do not match.";
                        } elseif (strlen($password) < 8) {
                            $edit_profile_error = "New password must be at least 8 characters long.";
                        } else {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $sql_update = "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?";
                            $stmt_update = $conn->prepare($sql_update);
                            $stmt_update->bind_param("sssi", $name, $email, $hashed_password, $admin_id);
                        }
                    }
                } else {
                    // Update without changing password
                    $sql_update = "UPDATE users SET name = ?, email = ? WHERE id = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("ssi", $name, $email, $admin_id);
                }

                if (empty($edit_profile_error)) { // Only proceed if no prior error
                    if (isset($stmt_update) && $stmt_update->execute()) {
                        // Update session name if changed
                        $_SESSION['admin_name'] = $name;
                        $_SESSION['toast_message'] = ['message' => 'Profile updated successfully!', 'type' => 'success'];
                        regenerate_csrf_token();
                        header("Location: profile.php");
                        exit();
                    } else {
                        error_log("Error executing profile update query: " . $stmt_update->error);
                        $edit_profile_error = "Failed to update profile.";
                    }
                }
                if (isset($stmt_update)) $stmt_update->close();
            }
        }
    }
}

$pageTitle = 'My Profile';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">My Profile</h2>

    <?php if(!empty($edit_profile_error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $edit_profile_error; ?>
        </div>
    <?php endif; ?>

    <form action="profile.php" method="post" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Full Name -->
            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <input type="text" id="name" name="name" required
                       value="<?php echo htmlspecialchars($admin['name']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Email Address -->
            <div class="md:col-span-2">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($admin['email']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Admin ID (Read-only) -->
            <div>
                <label for="admin_id" class="block text-sm font-medium text-gray-700 mb-2">Admin ID</label>
                <input type="text" id="admin_id" readonly
                       value="#<?php echo htmlspecialchars($admin['id']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
            </div>

            <!-- Role (Read-only) -->
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <input type="text" id="role" readonly
                       value="Administrator"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
            </div>
        </div>

        <!-- Password Change Section -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-800 mb-3">Change Password</h3>
            <p class="text-sm text-gray-600 mb-4">Enter your current password and new password to change it.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                    <input type="password" id="current_password" name="current_password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                </div>

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
        <div class="flex justify-end">
            <button type="submit" name="update_profile" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors">Update Profile</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
