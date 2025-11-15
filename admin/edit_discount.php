<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

$csrf_token = generate_csrf_token();
$discount_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;

if (!$discount_id) {
    $_SESSION['toast_message'] = ['message' => 'Invalid discount ID provided.', 'type' => 'error'];
    header("Location: manage_discounts.php");
    exit();
}

// Fetch discount details
$stmt = $conn->prepare("SELECT * FROM discount_codes WHERE id = ?");
$stmt->bind_param("i", $discount_id);
$stmt->execute();
$result = $stmt->get_result();
$discount = $result->fetch_assoc();
$stmt->close();

if (!$discount) {
    $_SESSION['toast_message'] = ['message' => 'Discount code not found.', 'type' => 'error'];
    header("Location: manage_discounts.php");
    exit();
}

// Handle form submission for updating the discount
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_discount'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
        header("Location: edit_discount.php?id=" . $discount_id);
        exit();
    }

    // Repopulate discount array with POST data to retain values on error
    $discount['type'] = trim($_POST['type']);
    $discount['value'] = trim($_POST['value']);
    $discount['usage_limit'] = trim($_POST['usage_limit']);
    $discount['expires_at'] = !empty($_POST['expires_at']) ? trim($_POST['expires_at']) : null;

    $type = $discount['type'];
    $value = filter_var($discount['value'], FILTER_VALIDATE_FLOAT);
    $usage_limit = filter_var($discount['usage_limit'], FILTER_VALIDATE_INT);
    $expires_at = $discount['expires_at'];

    // Input validation
    if (!in_array($type, ['percentage', 'fixed'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid discount type.', 'type' => 'error'];
    } elseif ($value === false || $value <= 0) {
        $_SESSION['toast_message'] = ['message' => 'Discount value must be a positive number.', 'type' => 'error'];
    } elseif ($usage_limit === false || $usage_limit < 0) { // 0 could mean unlimited, but let's stick to positive for now.
        $_SESSION['toast_message'] = ['message' => 'Usage limit must be a non-negative integer.', 'type' => 'error'];
    } else {
        // Update the discount code
        $sql_update = "UPDATE discount_codes SET type = ?, value = ?, usage_limit = ?, expires_at = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sdisi", $type, $value, $usage_limit, $expires_at, $discount_id);

        if ($stmt_update->execute()) {
            $_SESSION['toast_message'] = ['message' => 'Discount code updated successfully!', 'type' => 'success'];
            regenerate_csrf_token();
            header("Location: manage_discounts.php");
            exit();
        } else {
            $_SESSION['toast_message'] = ['message' => 'Failed to update discount code: ' . $conn->error, 'type' => 'error'];
        }
        $stmt_update->close();
    }
    // Redirect back to the edit page on error to show the message
    header("Location: edit_discount.php?id=" . $discount_id);
    exit();
}

$pageTitle = 'Edit Discount Code';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Discount: <?php echo htmlspecialchars($discount['code']); ?></h2>
        <a href="manage_discounts.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Back to Discounts</a>
    </div>

    <form action="edit_discount.php?id=<?php echo $discount_id; ?>" method="post" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Discount Code (Read-only) -->
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Discount Code</label>
                <input type="text" id="code" name="code" readonly
                       value="<?php echo htmlspecialchars($discount['code']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
            </div>

            <!-- Type -->
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select id="type" name="type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="percentage" <?php echo ($discount['type'] === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                    <option value="fixed" <?php echo ($discount['type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount (R)</option>
                </select>
            </div>

            <!-- Value -->
            <div>
                <label for="value" class="block text-sm font-medium text-gray-700 mb-2">Value</label>
                <input type="number" id="value" name="value" step="0.01" min="0" required
                       value="<?php echo htmlspecialchars($discount['value']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Usage Limit -->
            <div>
                <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-2">Usage Limit</label>
                <input type="number" id="usage_limit" name="usage_limit" min="0" required
                       value="<?php echo htmlspecialchars($discount['usage_limit']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Expiry Date -->
            <div class="md:col-span-2">
                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">Expiry Date (Optional)</label>
                <input type="datetime-local" id="expires_at" name="expires_at"
                       value="<?php echo !empty($discount['expires_at']) ? date('Y-m-d\TH:i', strtotime($discount['expires_at'])) : ''; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-4 border-t pt-6">
            <a href="manage_discounts.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" name="update_discount" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors">Update Discount</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>