<?php
$pageTitle = "Stock Notifications";
require_once 'bootstrap.php';
$conn = get_db_connection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        $_SESSION['error'] = 'Invalid security token.';
        header('Location: stock_notifications.php');
        exit();
    }
    
    $action = $_POST['action'];
    
    if ($action === 'delete' && isset($_POST['alert_id'])) {
        $alert_id = (int) $_POST['alert_id'];
        $delete_sql = "DELETE FROM back_in_stock_alerts WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $alert_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Notification deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete notification.';
        }
        $stmt->close();
        header('Location: stock_notifications.php');
        exit();
    }
    
    if ($action === 'mark_notified' && isset($_POST['alert_id'])) {
        $alert_id = (int) $_POST['alert_id'];
        $update_sql = "UPDATE back_in_stock_alerts SET is_notified = 1, notified_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $alert_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Marked as notified.';
        } else {
            $_SESSION['error'] = 'Failed to update notification.';
        }
        $stmt->close();
        header('Location: stock_notifications.php');
        exit();
    }
    
    if ($action === 'send_notification' && isset($_POST['alert_id'])) {
        $alert_id = (int) $_POST['alert_id'];
        
        // Get alert details
        $alert_sql = "SELECT a.*, p.name as product_name, p.image as product_image, p.stock 
                      FROM back_in_stock_alerts a 
                      JOIN products p ON a.product_id = p.id 
                      WHERE a.id = ?";
        $stmt = $conn->prepare($alert_sql);
        $stmt->bind_param("i", $alert_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $alert = $result->fetch_assoc();
        $stmt->close();
        
        if ($alert && $alert['stock'] > 0) {
            // Send notification email
            require_once __DIR__ . '/../includes/notification_service.php';
            
            $product = [
                'id' => $alert['product_id'],
                'name' => $alert['product_name']
            ];
            
            if (NotificationService::sendBackInStockAlert($product, $alert['email'])) {
                // Mark as notified
                $update_sql = "UPDATE back_in_stock_alerts SET is_notified = 1, notified_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $alert_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                $_SESSION['success'] = 'Notification email sent to ' . $alert['email'];
            } else {
                $_SESSION['error'] = 'Failed to send notification email.';
            }
        } else {
            $_SESSION['error'] = 'Product is still out of stock.';
        }
        
        header('Location: stock_notifications.php');
        exit();
    }
}

// Filters
$filter_status = $_GET['status'] ?? 'pending';
$filter_product = $_GET['product_id'] ?? '';

// Build query
$where_clauses = [];
$params = [];
$types = '';

if ($filter_status === 'pending') {
    $where_clauses[] = "a.is_notified = 0";
} elseif ($filter_status === 'notified') {
    $where_clauses[] = "a.is_notified = 1";
}

if ($filter_product) {
    $where_clauses[] = "a.product_id = ?";
    $params[] = (int) $filter_product;
    $types .= 'i';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get notifications with product info
$sql = "SELECT a.*, p.name as product_name, p.image as product_image, p.stock, u.name as user_name
        FROM back_in_stock_alerts a
        JOIN products p ON a.product_id = p.id
        LEFT JOIN users u ON a.user_id = u.id
        $where_sql
        ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get stats
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_notified = 0 THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN is_notified = 1 THEN 1 ELSE 0 END) as notified
    FROM back_in_stock_alerts";
$stats = $conn->query($stats_sql)->fetch_assoc();

// Get products with pending notifications for filter dropdown
$products_sql = "SELECT DISTINCT p.id, p.name 
                 FROM back_in_stock_alerts a 
                 JOIN products p ON a.product_id = p.id 
                 ORDER BY p.name";
$products_result = $conn->query($products_sql);
$products_with_alerts = $products_result->fetch_all(MYSQLI_ASSOC);

require_once 'header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Stock Notifications</h1>
            <p class="text-gray-600 mt-1">Manage "Notify Me" requests from customers</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Requests</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['total'] ?? 0); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Pending</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['pending'] ?? 0); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Notified</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['notified'] ?? 0); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="notified" <?php echo $filter_status === 'notified' ? 'selected' : ''; ?>>Notified</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                <select name="product_id" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Products</option>
                    <?php foreach ($products_with_alerts as $prod): ?>
                        <option value="<?php echo $prod['id']; ?>" <?php echo $filter_product == $prod['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prod['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md text-sm hover:bg-gray-700">
                Filter
            </button>
            <a href="stock_notifications.php" class="text-gray-600 hover:text-gray-800 text-sm py-2">Reset</a>
        </form>
    </div>

    <!-- Notifications Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (empty($notifications)): ?>
            <div class="p-8 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <p>No stock notifications found.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($notifications as $notif): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($notif['product_image']): ?>
                                            <img src="<?php echo SITE_URL . htmlspecialchars($notif['product_image']); ?>" 
                                                 alt="" class="w-10 h-10 rounded object-cover mr-3">
                                        <?php endif; ?>
                                        <div>
                                            <a href="edit_product.php?id=<?php echo $notif['product_id']; ?>" 
                                               class="text-sm font-medium text-gray-900 hover:text-blue-600">
                                                <?php echo htmlspecialchars($notif['product_name']); ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($notif['email']); ?></div>
                                    <?php if ($notif['user_name']): ?>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($notif['user_name']); ?></div>
                                    <?php else: ?>
                                        <div class="text-xs text-gray-400">Guest</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                    $variants = [];
                                    if ($notif['size_variant']) $variants[] = "Size: " . $notif['size_variant'];
                                    if ($notif['color_variant']) $variants[] = "Color: " . $notif['color_variant'];
                                    echo !empty($variants) ? htmlspecialchars(implode(', ', $variants)) : '<span class="text-gray-400">Any</span>';
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($notif['stock'] > 0): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            In Stock (<?php echo $notif['stock']; ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Out of Stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($notif['is_notified']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Notified
                                        </span>
                                        <?php if ($notif['notified_at']): ?>
                                            <div class="text-xs text-gray-400 mt-1">
                                                <?php echo date('M j, Y', strtotime($notif['notified_at'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <?php if (!$notif['is_notified'] && $notif['stock'] > 0): ?>
                                            <form method="POST" class="inline">
                                                <?php echo generate_csrf_token_input(); ?>
                                                <input type="hidden" name="action" value="send_notification">
                                                <input type="hidden" name="alert_id" value="<?php echo $notif['id']; ?>">
                                                <button type="submit" class="text-blue-600 hover:text-blue-900" title="Send Notification">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if (!$notif['is_notified']): ?>
                                            <form method="POST" class="inline">
                                                <?php echo generate_csrf_token_input(); ?>
                                                <input type="hidden" name="action" value="mark_notified">
                                                <input type="hidden" name="alert_id" value="<?php echo $notif['id']; ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-900" title="Mark as Notified">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this notification request?');">
                                            <?php echo generate_csrf_token_input(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="alert_id" value="<?php echo $notif['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
