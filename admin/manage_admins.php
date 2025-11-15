<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

$csrf_token = generate_csrf_token();


$add_admin_error = '';
$delete_admin_error = '';
$new_admin_name = '';
$new_admin_email = '';

// Handle form submission for adding a new admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_admin'])) {
    $new_admin_name = trim($_POST['name']);
    $new_admin_email = trim($_POST['email']);

    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $add_admin_error = 'Invalid CSRF token.';
    } else {
        $name = $new_admin_name;
        $email = filter_var($new_admin_email, FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password']);
        $role = trim($_POST['role']);

        $valid_roles = ['admin', 'super_admin'];
        if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password) || !in_array($role, $valid_roles)) {
            $add_admin_error = "Please fill all fields with valid data.";
        } elseif (strlen($password) < 8) {
            $add_admin_error = "Password must be at least 8 characters long.";
        } else {
            // Check if email already exists
            $sql_check = "SELECT id FROM users WHERE email = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $add_admin_error = "An account with this email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_insert = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("ssss", $name, $email, $hashed_password, $role);
                if ($stmt_insert->execute()) {
                    $_SESSION['success_message'] = "Admin user added successfully!";
                    regenerate_csrf_token();
                    header("Location: manage_admins.php");
                    exit();
                } else {
                    error_log("Error executing add admin query: " . $stmt_insert->error);
                    $add_admin_error = "Failed to create admin account.";
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
    }
}

// Handle POST request for deleting an admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_admin'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $delete_admin_error = 'Invalid CSRF token.';
    } else {
        $id_to_delete = filter_var(trim($_POST['id']), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))); // Changed from admin_id to id
        if ($id_to_delete == $_SESSION['admin_id']) {
            $delete_admin_error = "You cannot delete your own account.";
        }

        if (empty($delete_admin_error)) {
            // Check if this is the last admin
            $sql_count = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
            $result_count = $conn->query($sql_count);
            $admin_count = $result_count->fetch_assoc()['admin_count'];

            if ($admin_count <= 1) {
                $delete_admin_error = "Cannot delete the last remaining admin account.";
            }
        }

        if (empty($delete_admin_error)) {
            $sql_delete = "DELETE FROM users WHERE id = ? AND role = 'admin'";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $id_to_delete);
            if ($stmt_delete->execute()) {
                $_SESSION['success_message'] = "Admin user deleted successfully!";
                regenerate_csrf_token();
                header("Location: manage_admins.php");
                exit();
            } else {
                error_log("Error executing delete admin query: " . $stmt_delete->error);
                $delete_admin_error = "Failed to delete admin user.";
            }
            $stmt_delete->close();
        }
    }
}

// Fetch all admin users
$admins = [];
$sql_admins = "SELECT id, name, email, role FROM users WHERE role IN ('admin', 'super_admin') ORDER BY name ASC";
if ($result = $conn->query($sql_admins)) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}



$pageTitle = 'Manage Administrators';
include 'header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Add New Admin -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Add New Admin</h3>

        <?php if(!empty($add_admin_error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $add_admin_error; ?>
            </div>
        <?php endif; ?>

        <form action="manage_admins.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($new_admin_name); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($new_admin_email); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <div class="mb-4">
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select id="role" name="role" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="admin">Administrator</option>
                    <option value="super_admin">Super Administrator</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <button type="submit" name="add_admin" class="w-full bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Add Admin</button>
        </form>
    </div>

    <!-- Admins List -->
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold text-gray-800 mb-4">All Administrators</h3>

        <?php if(!empty($delete_admin_error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $delete_admin_error; ?>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($admins)): ?>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $admin['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($admin['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $admin['role']))); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="edit_admin.php?id=<?php echo $admin['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                        <button onclick="confirmDelete(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['name']); ?>', 'admin')" class="text-red-600 hover:text-red-900">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 py-6">No admin users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Deletion</h3>
            <p class="text-sm text-gray-500 mb-4" id="deleteMessage"></p>
            <div class="flex justify-end space-x-4">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">Cancel</button>
                <form id="deleteForm" action="manage_admins.php" method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" name="delete_admin" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>



<?php include 'footer.php'; ?>
