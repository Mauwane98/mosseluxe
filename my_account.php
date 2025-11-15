<?php
$pageTitle = "My Account - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';

// If user is not logged in, redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$conn = get_db_connection();
require_once 'includes/header.php'; // Now include header after all PHP logic

$user_id = $_SESSION['user_id'];
$profile_error = '';
$profile_success = '';
$success_messages = [];
$error_messages = [];


// Determine which view to show: 'orders' or 'profile'
$view = isset($_GET['view']) ? $_GET['view'] : 'orders';

// Handle profile update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_messages[] = 'Invalid CSRF token. Please try again.';
    } else {
        // Update Name
        $name = trim($_POST['name']);
        $sql_update_name = "UPDATE users SET name = ? WHERE id = ?";
        if ($stmt_name = $conn->prepare($sql_update_name)) {
            $stmt_name->bind_param("si", $name, $user_id);
            if ($stmt_name->execute()) {
                $_SESSION['name'] = $name; // Update session name
                $success_messages[] = 'Your name has been updated successfully.';
            } else {
                $profile_error = 'Error updating name. Please try again.';
            }
            $stmt_name->close();
        }

        // Update Password (if fields are filled)
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];

        // If any password field is filled, validate all of them
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_new_password)) {
            if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
                $error_messages[] = 'To change your password, please fill in the current, new, and confirm password fields.';
            }
            if ($new_password !== $confirm_new_password) {
                $error_messages[] = 'New passwords do not match.';
            } elseif (strlen($new_password) < 8) {
                $error_messages[] = 'New password must be at least 8 characters long.';
            } else {
                // Fetch current password to verify
                $sql_user = "SELECT password FROM users WHERE id = ?";
                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param("i", $user_id);
                $stmt_user->execute();
                $result_user = $stmt_user->get_result();
                $user_data = $result_user->fetch_assoc();

                if ($user_data && password_verify($current_password, $user_data['password'])) {
                    // Current password is correct, hash and update new password
                    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_update_pass = "UPDATE users SET password = ? WHERE id = ?";
                    $stmt_pass = $conn->prepare($sql_update_pass);
                    $stmt_pass->bind_param("si", $hashed_new_password, $user_id);
                    if ($stmt_pass->execute()) {
                        $success_messages[] = 'Your password has been updated successfully.';
                    } else {
                        $error_messages[] = 'Error updating password. Please try again.';
                    }
                    $stmt_pass->close();
                } else {
                    $error_messages[] = 'Incorrect current password.';
                }
                $stmt_user->close();
            }
        }
    }

    // Consolidate messages
    if (!empty($success_messages)) {
        $profile_success = implode(' ', $success_messages);
    }
    if (!empty($error_messages)) {
        $profile_error = implode(' ', $error_messages);
    }
}

// Fetch user details for the profile form
$user = null;
$sql_user_details = "SELECT name, email FROM users WHERE id = ?";
if ($stmt_user_details = $conn->prepare($sql_user_details)) {
    $stmt_user_details->bind_param("i", $user_id);
    $stmt_user_details->execute();
    $result_user_details = $stmt_user_details->get_result();
    $user = $result_user_details->fetch_assoc();
    $stmt_user_details->close();
}

$orders = [];
// Fetch user's orders
$sql_orders = "SELECT id, created_at, total_price, status FROM orders WHERE user_id = ? ORDER BY created_at DESC";

if ($stmt = $conn->prepare($sql_orders)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    } else {
        error_log("Error executing user orders query: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Error preparing user orders query: " . $conn->error);
}

?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 md:px-6 py-16 md:py-24">
        <!-- Page Header -->
        <div class="text-center mb-16">
            <div class="flex items-center justify-center gap-3 mb-4">
                <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">My Account</h1>
            </div>
            <p class="text-lg text-black/70 max-w-2xl mx-auto">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! Manage your orders, profile, and preferences.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <!-- Sidebar Navigation -->
            <div class="md:col-span-3">
                <div class="bg-neutral-50 rounded-lg p-6">
                    <h3 class="text-lg font-bold uppercase tracking-wider mb-4">Account Menu</h3>
                    <nav class="flex flex-col space-y-2">
                        <a href="my_account.php?view=orders" class="flex items-center gap-3 px-4 py-3 rounded-md text-sm font-semibold transition-colors <?php echo ($view === 'orders') ? 'bg-black text-white' : 'hover:bg-black/5'; ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Order History
                        </a>
                        <a href="wishlist.php" class="flex items-center gap-3 px-4 py-3 rounded-md text-sm font-semibold hover:bg-black/5 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            My Wishlist
                        </a>
                        <a href="my_account.php?view=profile" class="flex items-center gap-3 px-4 py-3 rounded-md text-sm font-semibold transition-colors <?php echo ($view === 'profile') ? 'bg-black text-white' : 'hover:bg-black/5'; ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Profile Details
                        </a>
                        <hr class="border-black/10 my-2">
                        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-md text-sm font-semibold hover:bg-red-50 hover:text-red-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="md:col-span-9">
                <?php if ($view === 'orders'): ?>
                    <h2 class="text-2xl font-bold uppercase tracking-wider mb-6">My Order History</h2>
                    <div class="bg-neutral-50 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-black/5 text-xs uppercase">
                                    <tr>
                                        <th class="px-6 py-3">Order ID</th>
                                        <th class="px-6 py-3">Date</th>
                                        <th class="px-6 py-3">Total</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($orders)): ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr class="border-b border-black/5">
                                                <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars(get_order_id_from_numeric_id($order['id'])); ?></td>
                                                <td class="px-6 py-4"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></td>
                                                <td class="px-6 py-4">R <?php echo number_format($order['total_price'], 2); ?></td>
                                                <td class="px-6 py-4">
                                                    <?php 
                                                        $status_class = '';
                                                        switch (strtolower($order['status'])) {
                                                            case 'pending': $status_class = 'bg-yellow-100 text-yellow-800'; break;
                                                            case 'paid': $status_class = 'bg-green-100 text-green-800'; break;
                                                            case 'processing': $status_class = 'bg-blue-100 text-blue-800'; break;
                                                            case 'shipped': $status_class = 'bg-cyan-100 text-cyan-800'; break;
                                                            case 'delivered': $status_class = 'bg-gray-200 text-gray-800'; break;
                                                            case 'cancelled': case 'failed': $status_class = 'bg-red-100 text-red-800'; break;
                                                            default: $status_class = 'bg-gray-200 text-gray-800';
                                                        }
                                                    ?>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <a href="view_user_order.php?id=<?php echo $order['id']; ?>" class="font-medium text-black hover:underline">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-black/60 py-8">You have not placed any orders yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php elseif ($view === 'profile' && $user): ?>
                    <h2 class="text-2xl font-bold uppercase tracking-wider mb-6">Profile Details</h2>

                    <?php if (!empty($profile_error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><?php echo $profile_error; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($profile_success)): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><?php echo $profile_success; ?></div>
                    <?php endif; ?>

                    <div class="bg-neutral-50 p-8 rounded-lg">
                        <form action="my_account.php?view=profile" method="POST" class="space-y-8">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div>
                                <h3 class="text-lg font-bold uppercase tracking-wider mb-4">Personal Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-black/80 mb-1">Full Name</label>
                                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                    </div>
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-black/80 mb-1">Email Address</label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly class="w-full p-3 bg-white border border-black/20 rounded-md text-black/60 cursor-not-allowed">
                                        <p class="mt-1 text-xs text-black/50">Email address cannot be changed.</p>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-black/10">

                            <div>
                                <h3 class="text-lg font-bold uppercase tracking-wider mb-4">Change Password</h3>
                                <p class="text-sm text-black/60 mb-4">Leave these fields blank if you do not wish to change your password.</p>
                                <div class="space-y-6">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-black/80 mb-1">Current Password</label>
                                        <input type="password" id="current_password" name="current_password" class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="new_password" class="block text-sm font-medium text-black/80 mb-1">New Password</label>
                                            <input type="password" id="new_password" name="new_password" minlength="8" class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                        </div>
                                        <div>
                                            <label for="confirm_new_password" class="block text-sm font-medium text-black/80 mb-1">Confirm New Password</label>
                                            <input type="password" id="confirm_new_password" minlength="8" name="confirm_new_password" class="w-full p-3 bg-white border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <button type="submit" name="update_profile" class="bg-black text-white py-3 px-8 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
