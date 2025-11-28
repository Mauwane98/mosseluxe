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
                        <a href="my_account.php?view=referrals" class="flex items-center gap-3 px-4 py-3 rounded-md text-sm font-semibold transition-colors <?php echo ($view === 'referrals') ? 'bg-black text-white' : 'hover:bg-black/5'; ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Referrals
                        </a>
                        <a href="my_account.php?view=loyalty" class="flex items-center gap-3 px-4 py-3 rounded-md text-sm font-semibold transition-colors <?php echo ($view === 'loyalty') ? 'bg-black text-white' : 'hover:bg-black/5'; ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            Loyalty Program
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
                <?php elseif ($view === 'loyalty'): ?>
                    <h2 class="text-2xl font-bold uppercase tracking-wider mb-6">Loyalty Program</h2>

                    <?php
                    $loyalty_info = get_user_loyalty_info($user_id);
                    if ($loyalty_info):
                    ?>
                    <div class="space-y-8">
                        <!-- Current Balance & Tier -->
                        <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white p-8 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-2xl font-bold mb-2">Welcome to Your Loyalty Dashboard</h3>
                                    <p class="text-purple-100">Earn points with every purchase and unlock exclusive rewards</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-4xl font-black"><?php echo number_format($loyalty_info['points']); ?></div>
                                    <div class="text-lg opacity-90">Points</div>
                                </div>
                            </div>

                            <!-- Tier Progress -->
                            <div class="mt-6">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium"><?php echo ucfirst($loyalty_info['tier_progress']['current_tier']); ?> Member</span>
                                    <span class="text-sm">
                                        <?php if ($loyalty_info['tier_progress']['next_tier']): ?>
                                            <?php echo number_format($loyalty_info['tier_progress']['points_to_next']); ?> to <?php echo ucfirst($loyalty_info['tier_progress']['next_tier']); ?>
                                        <?php else: ?>
                                            Top Tier ✓
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="w-full bg-white/20 rounded-full h-3">
                                    <div class="bg-white h-3 rounded-full transition-all duration-300" style="width: <?php echo $loyalty_info['tier_progress']['progress_percentage']; ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- How to Earn & Redeem -->
                        <div class="grid md:grid-cols-2 gap-8">
                            <!-- How to Earn -->
                            <div class="bg-neutral-50 p-6 rounded-lg">
                                <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Ways to Earn Points
                                </h3>
                                <ul class="space-y-3 text-sm">
                                    <li class="flex justify-between">
                                        <span>Shopping purchases</span>
                                        <span class="font-medium">10 pts per R100</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span>Writing product reviews</span>
                                        <span class="font-medium">50 pts</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span>Account signup bonus</span>
                                        <span class="font-medium">100 pts</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span>Social media shares</span>
                                        <span class="font-medium">25 pts</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Recent Transactions -->
                            <div class="bg-neutral-50 p-6 rounded-lg">
                                <h3 class="text-xl font-bold mb-4">Recent Activity</h3>
                                <?php if (!empty($loyalty_info['recent_transactions'])): ?>
                                    <div class="space-y-3">
                                        <?php foreach ($loyalty_info['recent_transactions'] as $transaction): ?>
                                            <div class="flex justify-between items-center text-sm">
                                                <div>
                                                    <span class="font-medium"><?php echo htmlspecialchars($transaction['description']); ?></span>
                                                    <div class="text-gray-500 text-xs"><?php echo date('d M Y', strtotime($transaction['created_at'])); ?></div>
                                                </div>
                                                <span class="font-bold <?php echo $transaction['transaction_type'] === 'earned' ? 'text-green-600' : 'text-red-600'; ?>">
                                                    <?php echo $transaction['transaction_type'] === 'earned' ? '+' : '-'; ?><?php echo $transaction['points']; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm italic">No recent activity</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tier Benefits -->
                        <div class="bg-neutral-50 p-6 rounded-lg">
                            <h3 class="text-xl font-bold mb-6">Your Membership Benefits</h3>
                            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <?php
                                $tiers = [
                                    ['name' => 'Bronze', 'min_points' => 0, 'benefits' => ['Standard Shipping', 'Newsletter Access']],
                                    ['name' => 'Silver', 'min_points' => 500, 'benefits' => ['Free Shipping', 'Early Access', 'Birthday Gift']],
                                    ['name' => 'Gold', 'min_points' => 1500, 'benefits' => ['Exclusive Discounts', 'VIP Events', 'Personal Shopping']],
                                    ['name' => 'Platinum', 'min_points' => 5000, 'benefits' => ['All Gold Benefits', 'Custom Products', '24/7 Concierge']]
                                ];

                                foreach ($tiers as $tier):
                                    $is_current = strtolower($tier['name']) === $loyalty_info['tier'];
                                ?>
                                    <div class="border rounded-lg p-4 <?php echo $is_current ? 'border-purple-300 bg-purple-50' : 'border-gray-200'; ?>">
                                        <div class="flex items-center gap-2 mb-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm
                                                <?php
                                                switch(strtolower($tier['name'])) {
                                                    case 'bronze': echo 'bg-amber-600'; break;
                                                    case 'silver': echo 'bg-gray-400'; break;
                                                    case 'gold': echo 'bg-yellow-500'; break;
                                                    case 'platinum': echo 'bg-purple-600'; break;
                                                }
                                                ?>">
                                                <?php echo substr($tier['name'], 0, 1); ?>
                                            </div>
                                            <h4 class="font-bold"><?php echo $tier['name']; ?></h4>
                                            <?php if ($is_current): ?>
                                                <span class="ml-auto text-xs bg-purple-600 text-white px-2 py-1 rounded-full">Current</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs text-gray-600 mb-2"><?php echo number_format($tier['min_points']); ?>+ points</div>
                                        <ul class="text-xs space-y-1">
                                            <?php foreach ($tier['benefits'] as $benefit): ?>
                                                <li class="flex items-center gap-1">
                                                    <svg class="w-3 h-3 <?php echo $is_current ? 'text-purple-600' : 'text-gray-400'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <?php echo $benefit; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="text-center py-12 bg-neutral-50 rounded-lg">
                            <div class="text-6xl mb-4">⭐</div>
                            <h3 class="text-xl font-bold mb-2">Join Our Loyalty Program</h3>
                            <p class="text-gray-600 mb-6">Start earning points with your next purchase!</p>
                            <a href="shop.php" class="bg-black text-white px-6 py-3 rounded-md hover:bg-black/80 transition-colors">
                                Start Shopping
                            </a>
                        </div>
                    <?php endif; ?>
                <?php elseif ($view === 'referrals'): ?>
                    <?php
                    // Generate unique referral code for user
                    $referral_code = 'ML' . strtoupper(substr(md5($user_id . 'mosseluxe'), 0, 6));
                    $referral_link = SITE_URL . '?ref=' . $referral_code;
                    
                    // Temporarily disabled - table needs to be created
                    $referralStats = [
                        'total_referrals' => 0,
                        'completed_referrals' => 0,
                        'total_earnings' => 0.00,
                        'pending_rewards' => 0.00,
                        'referral_code' => $referral_code,
                        'referral_link' => $referral_link
                    ];
                    // require_once 'includes/referral_service.php';
                    // $referralService = new ReferralService();
                    // $referralStats = $referralService->getReferralStats($user_id);
                    ?>
                    <h2 class="text-2xl font-bold uppercase tracking-wider mb-6">Referral Program</h2>

                    <div class="space-y-8">
                        <!-- Referral Stats -->
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-8 rounded-lg">
                            <div class="grid md:grid-cols-3 gap-6 text-center">
                                <div>
                                    <div class="text-3xl font-black mb-2"><?php echo number_format($referralStats['total_referrals']); ?></div>
                                    <div class="text-sm opacity-90">Total Referrals</div>
                                </div>
                                <div>
                                    <div class="text-3xl font-black mb-2"><?php echo number_format($referralStats['completed_referrals']); ?></div>
                                    <div class="text-sm opacity-90">Completed Referrals</div>
                                </div>
                                <div>
                                    <div class="text-3xl font-black mb-2"><?php echo number_format($referralStats['pending_rewards']); ?></div>
                                    <div class="text-sm opacity-90">Pending Rewards</div>
                                </div>
                            </div>
                        </div>

                        <!-- Referral Link -->
                        <div class="bg-neutral-50 p-6 rounded-lg">
                            <h3 class="text-xl font-bold mb-4">Your Referral Link</h3>
                            <p class="text-gray-600 mb-4">
                                Share this link with friends and family. When they sign up and make their first purchase,
                                you'll both get exclusive discounts!
                            </p>

                            <div class="flex flex-col md:flex-row gap-4">
                                <input type="text"
                                       id="referral-link"
                                       value="<?php echo htmlspecialchars($referralStats['referral_link']); ?>"
                                       readonly
                                       class="flex-1 p-3 bg-white border border-gray-300 rounded-md text-sm">
                                <button onclick="copyReferralLink()" class="bg-black text-white px-6 py-3 rounded-md hover:bg-black/80 transition-colors whitespace-nowrap">
                                    Copy Link
                                </button>
                            </div>

                            <!-- Social Share Buttons -->
                            <div class="mt-6">
                                <h4 class="text-sm font-bold mb-3">Share on Social Media</h4>
                                <div class="flex gap-3">
                            <button onclick="shareReferralOnWhatsApp()" class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors text-sm">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347
m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884
m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"
                                          />
                                </svg>
                                WhatsApp
                            </button>
                                    <button onclick="shareReferralOnFacebook()" class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                        Facebook
                                    </button>
                                    <button onclick="shareReferralOnTwitter()" class="flex items-center gap-2 bg-blue-400 text-white px-4 py-2 rounded-md hover:bg-blue-500 transition-colors text-sm">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                        </svg>
                                        Twitter
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- How Referral Program Works -->
                        <div class="bg-neutral-50 p-6 rounded-lg">
                            <h3 class="text-xl font-bold mb-6">How Referral Program Works</h3>
                            <div class="space-y-4">
                                <div class="flex items-start gap-4">
                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">1</div>
                                    <div>
                                        <h4 class="font-bold mb-1">Share Your Link</h4>
                                        <p class="text-gray-600 text-sm">Send your referral link to friends and family who might be interested in Mossé Luxe products.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">2</div>
                                    <div>
                                        <h4 class="font-bold mb-1">They Sign Up</h4>
                                        <p class="text-gray-600 text-sm">When your referral signs up for an account using your link, the referral is recorded.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">3</div>
                                    <div>
                                        <h4 class="font-bold mb-1">They Make First Purchase</h4>
                                        <p class="text-gray-600 text-sm">Once your referral completes their first purchase, the referral is considered successful.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">4</div>
                                    <div>
                                        <h4 class="font-bold mb-1">Both Get Rewards</h4>
                                        <p class="text-gray-600 text-sm">You both receive discount codes automatically - 15% off for you, 10% for your friend!</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Available Discounts -->
                        <div class="bg-neutral-50 p-6 rounded-lg">
                            <h3 class="text-xl font-bold mb-4">Your Referral Rewards</h3>
                            <?php
                            // Temporarily disabled - table doesn't exist
                            $available_discounts = [];
                            // $stmt = $conn->prepare("SELECT * FROM referral_discount_codes WHERE user_id = ? AND used = 0 AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY created_at DESC");
                            // $stmt->bind_param("i", $user_id);
                            // $stmt->execute();
                            // $available_discounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            // $stmt->close();
                            ?>

                            <?php if (!empty($available_discounts)): ?>
                                <div class="space-y-3">
                                    <?php foreach ($available_discounts as $discount): ?>
                                        <div class="flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200">
                                            <div>
                                                <div class="font-bold"><?php echo htmlspecialchars($discount['discount_code']); ?></div>
                                                <div class="text-sm text-gray-600">
                                                    <?php echo $discount['type'] === 'percentage' ? $discount['value'] . '% off' : 'R ' . $discount['value'] . ' off'; ?>
                                                    <?php if ($discount['expires_at']): ?>
                                                        (expires <?php echo date('d M Y', strtotime($discount['expires_at'])); ?>)
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <button onclick="copyToClipboard('<?php echo htmlspecialchars($discount['discount_code']); ?>')" class="bg-black text-white px-4 py-2 rounded-md text-sm hover:bg-black/80 transition-colors">
                                                Copy Code
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic">No referral rewards available yet. Share your link to earn discounts!</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <script>
                    function copyReferralLink() {
                        const linkInput = document.getElementById('referral-link');
                        linkInput.select();
                        document.execCommand('copy');

                        // Show feedback
                        const button = event.target;
                        const originalText = button.textContent;
                        button.textContent = 'Copied!';
                        button.classList.add('bg-green-600', 'hover:bg-green-700');
                        button.classList.remove('bg-black', 'hover:bg-black/80');

                        setTimeout(() => {
                            button.textContent = originalText;
                            button.classList.remove('bg-green-600', 'hover:bg-green-700');
                            button.classList.add('bg-black', 'hover:bg-black/80');
                        }, 2000);
                    }

                    function shareReferralOnWhatsApp() {
                        const url = '<?php echo htmlspecialchars($referralStats['referral_link']); ?>';
                        const message = `Hey! Check out Mossé Luxe - they've got amazing fashion items! Join using my referral link and we both get discounts: ${url}`;
                        const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
                        window.open(whatsappUrl, '_blank');
                    }

                    function shareReferralOnFacebook() {
                        const url = '<?php echo htmlspecialchars($referralStats['referral_link']); ?>';
                        const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=Check out Mossé Luxe fashion! Use my referral link for discounts.`;
                        window.open(facebookUrl, '_blank');
                    }

                    function shareReferralOnTwitter() {
                        const url = '<?php echo htmlspecialchars($referralStats['referral_link']); ?>';
                        const message = `Check out @MosseLuxe for amazing fashion! Use my referral link for discounts: ${url}`;
                        const twitterUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(message)}`;
                        window.open(twitterUrl, '_blank');
                    }

                    function copyToClipboard(text) {
                        navigator.clipboard.writeText(text).then(() => {
                            // Show feedback
                            const button = event.target;
                            const originalText = button.textContent;
                            button.textContent = 'Copied!';
                            button.classList.add('bg-green-600', 'hover:bg-green-700');
                            button.classList.remove('bg-black', 'hover:bg-black/80');

                            setTimeout(() => {
                                button.textContent = originalText;
                                button.classList.remove('bg-green-600', 'hover:bg-green-700');
                                button.classList.add('bg-black', 'hover:bg-black/80');
                            }, 2000);
                        });
                    }
                    </script>
            <?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
