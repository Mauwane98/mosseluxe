<?php
// Include the admin bootstrap for automatic setup
require_once 'bootstrap.php';

// --- Data Fetching ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT o.id, o.created_at, o.total_price, o.status, u.name as customer_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id";
$count_sql = "SELECT COUNT(o.id) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id";

$where_clauses = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_clauses[] = "u.name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= 's';
}
if (!empty($status_filter)) {
    $where_clauses[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
    $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Get total order count for pagination
$total_orders = 0;
if ($stmt_count = $conn->prepare($count_sql)) {
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_orders = $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();
}
$total_pages = ceil($total_orders / $limit);

// Fetch orders for the current page
$sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$orders = [];
if ($stmt = $conn->prepare($sql)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
}
$conn->close();

$status_classes = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'processing' => 'bg-blue-100 text-blue-800',
    'shipped' => 'bg-indigo-100 text-indigo-800',
    'completed' => 'bg-green-100 text-green-800',
    'cancelled' => 'bg-red-100 text-red-800',
];
$all_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

$pageTitle = "Manage Orders";
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">All Orders</h2>

    <!-- Filters -->
    <form action="orders.php" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="md:col-span-1">
            <input type="text" name="search" placeholder="Search by customer name..." value="<?php echo htmlspecialchars($search); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
        </div>
        <div class="md:col-span-1">
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                <option value="">All Statuses</option>
                <?php foreach ($all_statuses as $stat): ?>
                    <option value="<?php echo $stat; ?>" <?php echo ($status_filter == $stat) ? 'selected' : ''; ?>>
                        <?php echo ucfirst($stat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="w-full bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Filter</button>
        </div>
    </form>

    <!-- Bulk Actions -->
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center space-x-4">
            <label class="flex items-center">
                <input type="checkbox" id="selectAllOrders" class="rounded border-gray-300 text-black focus:ring-black">
                <span class="ml-2 text-sm text-gray-700">Select All</span>
            </label>
            <div id="bulkOrderActions" class="hidden space-x-2">
                <select id="bulkStatusSelect" class="px-3 py-1 border border-gray-300 rounded text-sm">
                    <option value="">Change Status To:</option>
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Shipped">Shipped</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <button id="applyBulkStatus" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">Apply</button>
            </div>
        </div>
        <div class="text-sm text-gray-600">
            Showing <?php echo count($orders); ?> of <?php echo $total_orders; ?> orders
        </div>
    </div>

    <!-- Orders Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        <input type="checkbox" class="rounded border-gray-300 text-black focus:ring-black">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr class="order-row">
                            <td class="px-6 py-4">
                                <input type="checkbox" class="order-checkbox rounded border-gray-300 text-black focus:ring-black" value="<?php echo $order['id']; ?>">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">#ML-<?php echo htmlspecialchars($order['id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">R<?php echo number_format($order['total_price'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select class="status-select px-2 py-1 text-xs rounded-full border-0 text-white <?php echo $status_classes[strtolower($order['status'])] ?? 'bg-gray-100 text-gray-800'; ?>" data-order-id="<?php echo $order['id']; ?>">
                                    <option value="Pending" <?php echo ($order['status'] == 'Pending') ? 'selected' : ''; ?> class="bg-yellow-100 text-yellow-800">Pending</option>
                                    <option value="Processing" <?php echo ($order['status'] == 'Processing') ? 'selected' : ''; ?> class="bg-blue-100 text-blue-800">Processing</option>
                                    <option value="Shipped" <?php echo ($order['status'] == 'Shipped') ? 'selected' : ''; ?> class="bg-indigo-100 text-indigo-800">Shipped</option>
                                    <option value="Completed" <?php echo ($order['status'] == 'Completed') ? 'selected' : ''; ?> class="bg-green-100 text-green-800">Completed</option>
                                    <option value="Cancelled" <?php echo ($order['status'] == 'Cancelled') ? 'selected' : ''; ?> class="bg-red-100 text-red-800">Cancelled</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="view_order.php?id=<?php echo $order['id']; ?>" class="text-indigo-600 hover:text-indigo-900">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-gray-500 py-6">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"
                       class="<?php echo $page == $i ? 'z-10 bg-black text-white' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bulk Actions Functionality for Orders
    const selectAllOrdersCheckbox = document.getElementById('selectAllOrders');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const bulkOrderActions = document.getElementById('bulkOrderActions');

    // Handle select all checkbox
    selectAllOrdersCheckbox.addEventListener('change', function() {
        orderCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkOrderActionsVisibility();
    });

    // Handle individual checkboxes
    orderCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
            selectAllOrdersCheckbox.checked = checkedBoxes.length === orderCheckboxes.length;
            selectAllOrdersCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < orderCheckboxes.length;
            updateBulkOrderActionsVisibility();
        });
    });

    function updateBulkOrderActionsVisibility() {
        const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
        if (checkedBoxes.length > 0) {
            bulkOrderActions.classList.remove('hidden');
        } else {
            bulkOrderActions.classList.add('hidden');
        }
    }

    // Bulk Status Update
    document.getElementById('applyBulkStatus').addEventListener('click', function() {
        const selectedIds = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
        const newStatus = document.getElementById('bulkStatusSelect').value;

        if (selectedIds.length === 0 || !newStatus) {
            alert('Please select orders and a status to apply.');
            return;
        }

        if (confirm(`Are you sure you want to change the status of ${selectedIds.length} selected orders to "${newStatus}"?`)) {
            bulkUpdateOrderStatus(selectedIds, newStatus);
        }
    });

    function bulkUpdateOrderStatus(orderIds, status) {
        fetch('ajax_update_order_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ids=${orderIds.join(',')}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully updated ${data.updated_count} orders.`);
                location.reload();
            } else {
                alert('Failed to update orders. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // Inline Status Update
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;

            fetch('ajax_update_order_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ids=${orderId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the select styling based on new status
                    const statusClasses = {
                        'Pending': 'bg-yellow-100 text-yellow-800',
                        'Processing': 'bg-blue-100 text-blue-800',
                        'Shipped': 'bg-indigo-100 text-indigo-800',
                        'Completed': 'bg-green-100 text-green-800',
                        'Cancelled': 'bg-red-100 text-red-800'
                    };

                    this.className = `status-select px-2 py-1 text-xs rounded-full border-0 text-white ${statusClasses[newStatus] || 'bg-gray-100 text-gray-800'}`;
                } else {
                    alert('Failed to update order status.');
                    location.reload(); // Reload to revert changes
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                location.reload();
            });
        });
    });
});
</script>

<?php include 'footer.php'; ?>
