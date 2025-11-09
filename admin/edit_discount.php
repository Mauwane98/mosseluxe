<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
$conn = get_db_connection();

$pageTitle = 'Edit Discount';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Discount Code</h2>
        <a href="manage_discounts.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Back to Discounts</a>
    </div>

    <?php if(!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="edit_discount.php?id=<?php echo $discount_id; ?>" method="post" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Discount Code (Read-only) -->
            <div class="md:col-span-2">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Discount Code</label>
                <input type="text" id="code" name="code" readonly
                       value="<?php echo htmlspecialchars($discount['code']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
                <p class="text-xs text-gray-500 mt-1">Code cannot be changed</p>
            </div>

            <!-- Type -->
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Discount Type</label>
                <select id="type" name="type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="percentage" <?php echo ($discount['type'] === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                    <option value="fixed" <?php echo ($discount['type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount (R)</option>
                </select>
            </div>

            <!-- Value -->
            <div>
                <label for="value" class="block text-sm font-medium text-gray-700 mb-2">Discount Value</label>
                <input type="number" id="value" name="value" step="0.01" min="0" required
                       value="<?php echo htmlspecialchars($discount['value']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Usage Limit -->
            <div>
                <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-2">Usage Limit</label>
                <input type="number" id="usage_limit" name="usage_limit" min="1" required
                       value="<?php echo htmlspecialchars($discount['usage_limit']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Current Usage -->
            <div>
                <label for="current_usage" class="block text-sm font-medium text-gray-700 mb-2">Current Usage</label>
                <input type="text" id="current_usage" readonly
                       value="<?php echo htmlspecialchars($discount['usage_count']); ?> / <?php echo htmlspecialchars($discount['usage_limit']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
            </div>

            <!-- Expiry Date -->
            <div class="md:col-span-2">
                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">Expiry Date (Optional)</label>
                <input type="datetime-local" id="expires_at" name="expires_at"
                       value="<?php echo $discount['expires_at'] ? date('Y-m-d\TH:i', strtotime($discount['expires_at'])) : ''; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                <p class="text-xs text-gray-500 mt-1">Leave empty for no expiry</p>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-4">
            <a href="manage_discounts.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" name="update_discount" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors">Update Discount</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
