<?php
/**
 * Admin - Abandoned Carts Management
 * View and manage abandoned shopping carts
 */

$pageTitle = "Abandoned Carts - Admin";
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/abandoned_cart_functions.php';

$conn = get_db_connection();

// Get statistics
$stats = getAbandonedCartStats($conn);

// Get abandoned carts
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$carts = getAbandonedCarts($conn, $limit, $offset);

// Use regular header if admin header doesn't exist
if (file_exists(__DIR__ . '/../includes/admin_header.php')) {
    require_once __DIR__ . '/../includes/admin_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Abandoned Carts</h1>
        <p class="text-gray-600">Track and recover abandoned shopping carts</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Abandoned</p>
                    <p class="text-3xl font-bold"><?php echo $stats['total_abandoned']; ?></p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Value</p>
                    <p class="text-3xl font-bold">R <?php echo number_format($stats['total_value'], 2); ?></p>
                </div>
                <div class="bg-orange-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Recovered</p>
                    <p class="text-3xl font-bold"><?php echo $stats['recovered_count']; ?></p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Recovery Rate</p>
                    <p class="text-3xl font-bold"><?php echo number_format($stats['recovery_rate'], 1); ?>%</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Abandoned Carts Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold">Recent Abandoned Carts</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($carts)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                No abandoned carts found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($carts as $cart): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d, Y H:i', strtotime($cart['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $cart['email'] ? htmlspecialchars($cart['email']) : '<span class="text-gray-400">No email</span>'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo count($cart['cart_data']); ?> items
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    R <?php echo number_format($cart['total_amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($cart['recovery_email_sent']): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            Email Sent
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button onclick="copyRecoveryLink('<?php echo SITE_URL; ?>recover-cart.php?token=<?php echo $cart['recovery_token']; ?>')" 
                                            class="text-blue-600 hover:text-blue-800 font-medium">
                                        Copy Link
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function copyRecoveryLink(url) {
    navigator.clipboard.writeText(url).then(() => {
        alert('Recovery link copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        prompt('Copy this recovery link:', url);
    });
}
</script>

<?php
// Use regular footer if admin footer doesn't exist
if (file_exists(__DIR__ . '/../includes/admin_footer.php')) {
    require_once __DIR__ . '/../includes/admin_footer.php';
} else {
    require_once __DIR__ . '/../includes/footer.php';
}
?>
