<?php
// Start session and check if user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If user is not logged in, redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/csrf.php';
$conn = get_db_connection();

$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();
$profile_error = '';
$profile_success = '';
$success_messages = [];
$error_messages = [];


// Determine which view to show: 'orders' or 'profile'
$view = isset($_GET['view']) ? $_GET['view'] : 'orders';

// Handle profile update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
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

        if (!empty($current_password) && !empty($new_password)) { // Only start password update if current and new password are provided
            if ($new_password !== $confirm_new_password) {
                $error_messages[] = 'New passwords do not match.';
            } elseif (strlen($new_password) < 6) {
                $error_messages[] = 'New password must be at least 6 characters long.';
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

<div class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    My Account
                </div>
                <div class="list-group list-group-flush">
                    <a href="my_account.php?view=orders" class="list-group-item list-group-item-action <?php echo ($view === 'orders') ? 'active' : ''; ?>">Order History</a>
                    <a href="wishlist.php" class="list-group-item list-group-item-action">My Wishlist</a>
                    <a href="my_account.php?view=profile" class="list-group-item list-group-item-action <?php echo ($view === 'profile') ? 'active' : ''; ?>">Profile Details</a>
                    <a href="logout.php" class="list-group-item list-group-item-action">Logout</a>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <?php if ($view === 'orders'): ?>
                <h1 class="mb-4" style="font-family: 'Playfair Display', serif;">My Order History</h1>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#ML-<?php echo htmlspecialchars($order['id']); ?></td>
                                        <td><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></td>
                                        <td>R <?php echo number_format($order['total_price'], 2); ?></td>
                                        <td>
                                            <?php 
                                                $status_class = '';
                                                switch (strtolower($order['status'])) {
                                                    case 'pending': $status_class = 'bg-warning text-dark'; break;
                                                    case 'paid': $status_class = 'bg-success'; break;
                                                    case 'processing': $status_class = 'bg-primary'; break;
                                                    case 'shipped': $status_class = 'bg-info text-dark'; break;
                                                    case 'delivered': $status_class = 'bg-secondary'; break;
                                                    case 'cancelled': case 'failed': $status_class = 'bg-danger'; break;
                                                    default: $status_class = 'bg-secondary';
                                                }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                                        </td>
                                        <td>
                                            <a href="view_user_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-eye-fill"></i> View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">You have not placed any orders yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($view === 'profile' && $user): ?>
                <h1 class="mb-4" style="font-family: 'Playfair Display', serif;">Profile Details</h1>

                <?php if (!empty($profile_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo $profile_error; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
                <?php endif; ?>
                <?php if (!empty($profile_success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $profile_success; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
                <?php endif; ?>

                <div class="card p-4">
                    <form action="my_account.php?view=profile" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <h5>Personal Information</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                <div class="form-text">Email address cannot be changed.</div>
                            </div>
                        </div>

                        <hr class="border-secondary">

                        <h5 class="mt-4">Change Password</h5>
                        <p class="text-muted small">Leave these fields blank if you do not wish to change your password.</p>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="update_profile" class="btn btn-primary-dark">Save Changes</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>o