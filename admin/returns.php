<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

$csrf_token = generate_csrf_token();

// Handle return request processing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_return'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
        header("Location: returns.php");
        exit();
    }

    $return_id = filter_var($_POST['return_id'], FILTER_VALIDATE_INT);
    $action = $_POST['action'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');

    if (!$return_id || !in_array($action, ['approve', 'reject', 'refund'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid request parameters.', 'type' => 'error'];
        header("Location: returns.php");
        exit();
    }

    $status = '';
    $message = '';

    switch ($action) {
        case 'approve':
            $status = 'Approved';
            $message = 'Return request has been approved.';
            break;
        case 'reject':
            $status = 'Rejected';
            $message = 'Return request has been rejected.';
            break;
        case 'refund':
            $status = 'Refunded';
            $message = 'Refund has been processed successfully.';
            break;
    }

    $update_sql = "UPDATE returns SET status = ?, admin_notes = ?, processed_at = NOW() WHERE id = ?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("ssi", $status, $admin_notes, $return_id);
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = ['message' => $message, 'type' => 'success'];

            // If refund, update order status
            if ($action === 'refund') {
                $order_sql = "UPDATE orders SET status = 'Refunded' WHERE id = (SELECT order_id FROM returns WHERE id = ?)";
                if ($order_stmt = $conn->prepare($order_sql)) {
                    $order_stmt->bind_param("i", $return_id);
                    $order_stmt->execute();
                    $order_stmt->close();
                }
            }
        } else {
            $_SESSION['toast_message'] = ['message' => 'Failed to process return.', 'type' => 'error'];
        }
        $stmt->close();
    }

    header("Location: returns.php");
    exit();
}

// Fetch returns
$returns = [];
$sql = "SELECT r.*, o.id as order_id, o.total_price, o.shipping_address_json, u.name as customer_name, u.email as customer_email
        FROM returns r
        JOIN orders o ON r.order_id = o.id
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY r.created_at DESC";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        // Parse shipping info for guest orders
        if (!$row['customer_name']) {
            $shipping_info = json_decode($row['shipping_address_json'], true);
            if ($shipping_info) {
                $row['customer_name'] = $shipping_info['firstName'] . ' ' . $shipping_info['lastName'];
                $row['customer_email'] = $shipping_info['email'];
            }
        }
        $returns[] = $row;
    }
}

$pageTitle = 'Manage Returns & Refunds';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Returns & Refunds Management</h2>

    <?php if (empty($returns)): ?>
        <div class="text-center py-8">
            <div class="text-gray-400 text-6xl mb-4">📦</div>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Return Requests</h3>
            <p class="text-gray-500">There are currently no return or refund requests to process.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($returns as $return): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #ML-<?php echo htmlspecialchars($return['order_id']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <div><?php echo htmlspecialchars($return['customer_name'] ?? 'Guest'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($return['customer_email'] ?? ''); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                <div class="font-medium"><?php echo htmlspecialchars($return['return_reason']); ?></div>
                                <?php if ($return['customer_notes']): ?>
                                    <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(substr($return['customer_notes'], 0, 50)); ?><?php echo strlen($return['customer_notes']) > 50 ? '...' : ''; ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                R <?php echo htmlspecialchars(number_format($return['refund_amount'], 2)); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    <?php
                                    switch ($return['status']) {
                                        case 'Pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'Approved': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'Rejected': echo 'bg-red-100 text-red-800'; break;
                                        case 'Refunded': echo 'bg-green-100 text-green-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo htmlspecialchars($return['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php echo date('d M Y', strtotime($return['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php if ($return['status'] === 'Pending'): ?>
                                    <button onclick="openProcessModal(<?php echo $return['id']; ?>, '<?php echo htmlspecialchars($return['return_reason']); ?>', <?php echo $return['refund_amount']; ?>, '<?php echo htmlspecialchars($return['customer_name'] ?? 'Guest'); ?>')"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">Process</button>
                                <?php else: ?>
                                    <button onclick="viewReturnDetails(<?php echo $return['id']; ?>, '<?php echo htmlspecialchars($return['return_reason']); ?>', '<?php echo htmlspecialchars($return['customer_notes'] ?? ''); ?>', '<?php echo htmlspecialchars($return['admin_notes'] ?? ''); ?>', '<?php echo htmlspecialchars($return['status']); ?>')"
                                            class="text-gray-600 hover:text-gray-900 mr-3">View</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Process Return Modal -->
<div id="processModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Process Return Request</h3>
            <div class="mb-4" id="returnDetails"></div>

            <form action="returns.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="return_id" id="returnIdInput">
                <input type="hidden" name="process_return" value="1">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                    <select name="action" id="actionSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                        <option value="approve">Approve Return</option>
                        <option value="reject">Reject Return</option>
                        <option value="refund">Process Refund</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                    <textarea name="admin_notes" id="admin_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black" placeholder="Add notes about this return..."></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeProcessModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-black text-white rounded-md hover:bg-black/80">Process Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Return Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Return Details</h3>
            <div id="viewReturnDetails"></div>
            <div class="flex justify-end mt-4">
                <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openProcessModal(returnId, reason, amount, customerName) {
    document.getElementById('modalTitle').textContent = 'Process Return Request - ' + customerName;
    document.getElementById('returnIdInput').value = returnId;
    document.getElementById('returnDetails').innerHTML = `
        <div class="bg-gray-50 p-3 rounded">
            <p><strong>Reason:</strong> ${reason}</p>
            <p><strong>Refund Amount:</strong> R ${amount}</p>
        </div>
    `;
    document.getElementById('processModal').classList.remove('hidden');
}

function closeProcessModal() {
    document.getElementById('processModal').classList.add('hidden');
    document.getElementById('admin_notes').value = '';
}

function viewReturnDetails(returnId, reason, customerNotes, adminNotes, status) {
    document.getElementById('viewReturnDetails').innerHTML = `
        <div class="space-y-3">
            <div><strong>Status:</strong> <span class="px-2 py-1 text-xs rounded-full ${
                status === 'Approved' ? 'bg-blue-100 text-blue-800' :
                status === 'Rejected' ? 'bg-red-100 text-red-800' :
                status === 'Refunded' ? 'bg-green-100 text-green-800' :
                'bg-yellow-100 text-yellow-800'
            }">${status}</span></div>
            <div><strong>Reason:</strong> ${reason}</div>
            ${customerNotes ? `<div><strong>Customer Notes:</strong> ${customerNotes}</div>` : ''}
            ${adminNotes ? `<div><strong>Admin Notes:</strong> ${adminNotes}</div>` : ''}
        </div>
    `;
    document.getElementById('viewModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

// Close modals when clicking outside
document.getElementById('processModal').addEventListener('click', function(e) {
    if (e.target === this) closeProcessModal();
});
document.getElementById('viewModal').addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});
</script>

<?php include 'footer.php'; ?>
