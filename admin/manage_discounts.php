<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

$csrf_token = generate_csrf_token();
$message = '';
$error = '';

// Handle form submission for adding a new discount
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_discount'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $code = strtoupper(trim($_POST['code']));
        $type = $_POST['type'];
        $value = filter_var($_POST['value'], FILTER_VALIDATE_FLOAT);
        $usage_limit = filter_var($_POST['usage_limit'], FILTER_VALIDATE_INT);
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

        if (empty($code) || empty($type) || $value === false || $usage_limit === false) {
            $_SESSION['toast_message'] = ['message' => 'Please fill all fields with valid data.', 'type' => 'error'];
            header("Location: manage_discounts.php");
            exit();
        } elseif (!preg_match('/^[A-Z0-9]{4,}$/', $code)) { // Example: minimum 4 alphanumeric characters
            $_SESSION['toast_message'] = ['message' => 'Discount code must be at least 4 alphanumeric characters.', 'type' => 'error'];
            header("Location: manage_discounts.php");
            exit();
        } elseif (!in_array($type, ['percentage', 'fixed'])) {
            $_SESSION['toast_message'] = ['message' => 'Invalid discount type selected.', 'type' => 'error'];
            header("Location: manage_discounts.php");
            exit();
        } elseif ($value <= 0) {
            $_SESSION['toast_message'] = ['message' => 'Discount value must be a positive number.', 'type' => 'error'];
            header("Location: manage_discounts.php");
            exit();
        } elseif ($type === 'percentage' && ($value > 100 || $value < 0)) {
            $_SESSION['toast_message'] = ['message' => 'Percentage discount must be between 0 and 100.', 'type' => 'error'];
            header("Location: manage_discounts.php");
            exit();
        } elseif ($expires_at && !strtotime($expires_at)) {
            $_SESSION['toast_message'] = ['message' => 'Invalid expiry date provided.', 'type' => 'error'];
            header("Location: manage_discounts.php");
            exit();
        } else {
            $sql = "INSERT INTO discount_codes (code, type, value, usage_limit, expires_at) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssdis", $code, $type, $value, $usage_limit, $expires_at);
                if ($stmt->execute()) {
                    $_SESSION['toast_message'] = ['message' => 'Discount code created successfully!', 'type' => 'success'];
                    regenerate_csrf_token();
                    header("Location: manage_discounts.php");
                    exit();
                } else {
                    $_SESSION['toast_message'] = ['message' => 'Failed to create discount code. It might already exist.', 'type' => 'error'];
                    header("Location: manage_discounts.php");
                    exit();
                }
                $stmt->close();
            }
        }
    }
}

// Handle toggling active status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
        header("Location: manage_discounts.php");
        exit();
    } else {
        $discount_id = filter_var($_POST['discount_id'], FILTER_SANITIZE_NUMBER_INT);
        $current_status = filter_var($_POST['current_status'], FILTER_SANITIZE_NUMBER_INT);
        $new_status = $current_status == 1 ? 0 : 1;

        $sql_toggle = "UPDATE discount_codes SET is_active = ? WHERE id = ?";
        if ($stmt_toggle = $conn->prepare($sql_toggle)) {
            $stmt_toggle->bind_param("ii", $new_status, $discount_id);
            if ($stmt_toggle->execute()) {
                $_SESSION['toast_message'] = ['message' => 'Discount status updated successfully!', 'type' => 'success'];
                regenerate_csrf_token();
                header("Location: manage_discounts.php");
                exit();
            }
        }
        $_SESSION['toast_message'] = ['message' => 'Failed to update status.', 'type' => 'error'];
        header("Location: manage_discounts.php");
        exit();
    }
}

// Fetch all discount codes
$discounts = [];
$sql_discounts = "SELECT * FROM discount_codes ORDER BY id DESC";
if ($result = $conn->query($sql_discounts)) {
    while ($row = $result->fetch_assoc()) {
        $discounts[] = $row;
    }
}



$pageTitle = 'Manage Discount Codes';
include 'header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Create New Discount -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Create New Discount</h3>



        <form action="manage_discounts.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Discount Code</label>
                <input type="text" id="code" name="code" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black uppercase">
            </div>
            <div class="mb-4">
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select id="type" name="type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount (R)</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="value" class="block text-sm font-medium text-gray-700 mb-2">Value</label>
                <input type="number" id="value" name="value" step="0.01" min="0" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <div class="mb-4">
                <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-2">Usage Limit</label>
                <input type="number" id="usage_limit" name="usage_limit" min="1" required value="1"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <div class="mb-4">
                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">Expiry Date (Optional)</label>
                <input type="datetime-local" id="expires_at" name="expires_at"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <button type="submit" name="add_discount" class="w-full bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Create Discount</button>
        </form>
    </div>

    <!-- Discount Codes List -->
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold text-gray-800 mb-4">All Discount Codes</h3>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($discounts)): ?>
                        <?php foreach ($discounts as $discount): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"><?php echo htmlspecialchars($discount['code']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo ucfirst($discount['type']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php
                                    if ($discount['type'] === 'percentage') {
                                        echo htmlspecialchars($discount['value']) . '%';
                                    } else {
                                        echo 'R ' . htmlspecialchars(number_format($discount['value'], 2));
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $discount['usage_count'] . ' / ' . $discount['usage_limit']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $discount['expires_at'] ? date('d M Y', strtotime($discount['expires_at'])) : 'Never'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $is_expired = $discount['expires_at'] && new DateTime() > new DateTime($discount['expires_at']);
                                    $is_used_up = $discount['usage_count'] >= $discount['usage_limit'];
                                    $is_active = $discount['is_active'] && !$is_expired && !$is_used_up;
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="edit_discount.php?id=<?php echo $discount['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                    <form action="manage_discounts.php" method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="discount_id" value="<?php echo $discount['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $discount['is_active']; ?>">
                                        <button type="submit" name="toggle_status" class="text-sm <?php echo $discount['is_active'] ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900'; ?>" title="<?php echo $discount['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas <?php echo $discount['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-gray-500 py-6">No discount codes found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
