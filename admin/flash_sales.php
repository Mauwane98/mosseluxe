<?php
/**
 * Admin - Flash Sales Manager
 * Create and manage flash sales
 */

$pageTitle = "Flash Sales Manager - Admin";
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/flash_sales_functions.php';

$conn = get_db_connection();

// Handle form submission
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $product_id = (int)$_POST['product_id'];
        $sale_price = (float)$_POST['sale_price'];
        $discount_percentage = (int)$_POST['discount_percentage'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $quantity_limit = !empty($_POST['quantity_limit']) ? (int)$_POST['quantity_limit'] : null;
        
        if (createFlashSale($conn, $product_id, $sale_price, $discount_percentage, $start_time, $end_time, $quantity_limit)) {
            $message = ['type' => 'success', 'text' => 'Flash sale created successfully!'];
        } else {
            $message = ['type' => 'error', 'text' => 'Failed to create flash sale.'];
        }
    } elseif ($_POST['action'] === 'end' && isset($_POST['sale_id'])) {
        endFlashSale($conn, (int)$_POST['sale_id']);
        $message = ['type' => 'success', 'text' => 'Flash sale ended.'];
    }
}

// Get statistics
$stats = getFlashSaleStats($conn);

// Get active and upcoming sales
$active_sales = getActiveFlashSales($conn, 50);
$upcoming_sales = getUpcomingFlashSales($conn, 50);

// Get all products for dropdown
$products = [];
$result = $conn->query("SELECT id, name, price FROM products WHERE status = 1 ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

if (file_exists(__DIR__ . '/../includes/admin_header.php')) {
    require_once __DIR__ . '/../includes/admin_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">⚡ Flash Sales Manager</h1>
        <p class="text-gray-600">Create and manage limited-time offers</p>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Active Sales</p>
            <p class="text-3xl font-bold text-red-600"><?php echo $stats['active_sales']; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Upcoming</p>
            <p class="text-3xl font-bold text-orange-600"><?php echo $stats['upcoming_sales']; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Items Sold (30d)</p>
            <p class="text-3xl font-bold text-green-600"><?php echo $stats['items_sold']; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Revenue (30d)</p>
            <p class="text-3xl font-bold text-blue-600">R <?php echo number_format($stats['total_revenue'], 2); ?></p>
        </div>
    </div>

    <!-- Create Flash Sale Form -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-2xl font-bold mb-6">Create New Flash Sale</h2>
        
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="hidden" name="action" value="create">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product *</label>
                <select name="product_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-black focus:border-transparent">
                    <option value="">Select a product</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?> (R <?php echo number_format($product['price'], 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sale Price *</label>
                <input type="number" name="sale_price" step="0.01" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-black focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Discount % *</label>
                <input type="number" name="discount_percentage" min="1" max="99" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-black focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quantity Limit (optional)</label>
                <input type="number" name="quantity_limit" min="1" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-black focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Start Time *</label>
                <input type="datetime-local" name="start_time" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-black focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">End Time *</label>
                <input type="datetime-local" name="end_time" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-black focus:border-transparent">
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="bg-black text-white px-8 py-3 rounded-md font-semibold hover:bg-black/80 transition-colors">
                    Create Flash Sale
                </button>
            </div>
        </form>
    </div>

    <!-- Active Flash Sales -->
    <?php if (!empty($active_sales)): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold">🔥 Active Flash Sales</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Original</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sold</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ends</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($active_sales as $sale): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <img src="<?php echo SITE_URL . htmlspecialchars($sale['image']); ?>" alt="" class="w-10 h-10 object-cover rounded mr-3">
                                    <span class="font-medium"><?php echo htmlspecialchars($sale['name']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">R <?php echo number_format($sale['original_price'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap font-bold text-red-600">R <?php echo number_format($sale['sale_price'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    -<?php echo $sale['discount_percentage']; ?>%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo $sale['quantity_sold']; ?><?php echo $sale['quantity_limit'] ? '/' . $sale['quantity_limit'] : ''; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php echo date('M d, H:i', strtotime($sale['end_time'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="inline" onsubmit="return confirm('End this flash sale?')">
                                    <input type="hidden" name="action" value="end">
                                    <input type="hidden" name="sale_id" value="<?php echo $sale['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">
                                        End Sale
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upcoming Flash Sales -->
    <?php if (!empty($upcoming_sales)): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold">📅 Upcoming Flash Sales</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Starts</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($upcoming_sales as $sale): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <img src="<?php echo SITE_URL . htmlspecialchars($sale['image']); ?>" alt="" class="w-10 h-10 object-cover rounded mr-3">
                                    <span class="font-medium"><?php echo htmlspecialchars($sale['name']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-bold">R <?php echo number_format($sale['sale_price'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                    -<?php echo $sale['discount_percentage']; ?>%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php echo date('M d, Y H:i', strtotime($sale['start_time'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="inline" onsubmit="return confirm('Cancel this flash sale?')">
                                    <input type="hidden" name="action" value="end">
                                    <input type="hidden" name="sale_id" value="<?php echo $sale['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">
                                        Cancel
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
if (file_exists(__DIR__ . '/../includes/admin_footer.php')) {
    require_once __DIR__ . '/../includes/admin_footer.php';
} else {
    require_once __DIR__ . '/../includes/footer.php';
}
?>
