<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';

// Ensure admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once '../includes/csrf.php';

$conn = get_db_connection();

$csrf_token = generate_csrf_token();
$message = '';
$error = '';

// Handle subscription deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_subscription'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $subscription_id = filter_var($_POST['subscription_id'], FILTER_SANITIZE_NUMBER_INT);
        if ($subscription_id) {
            $sql = "DELETE FROM stock_notifications WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $subscription_id);
                if ($stmt->execute()) {
                    header("Location: manage_subscriptions.php?success=deleted");
                    exit();
                } else {
                    $error = "Failed to delete subscription.";
                }
                $stmt->close();
            }
        }
    }
}

// Fetch all pending stock notification subscriptions
$subscriptions = [];
$sql_subscriptions = "SELECT sn.id, sn.email, sn.created_at, p.name as product_name
                      FROM stock_notifications sn
                      JOIN products p ON sn.product_id = p.id
                      WHERE sn.notified_at IS NULL
                      ORDER BY sn.created_at DESC";
if ($result = $conn->query($sql_subscriptions)) {
    while ($row = $result->fetch_assoc()) {
        $subscriptions[] = $row;
    }
}

if (isset($_GET['success']) && $_GET['success'] == 'deleted') {
    $message = "Subscription removed successfully.";
}

$pageTitle = 'Stock Notification Subscriptions';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Pending Stock Notification Subscriptions</h2>

    <?php if(!empty($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subscriber Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date Subscribed</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($subscriptions)): ?>
                    <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sub['product_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($sub['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo date('d M Y, H:i', strtotime($sub['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="confirmDelete(<?php echo $sub['id']; ?>, '<?php echo htmlspecialchars($sub['email']); ?>')" class="text-red-600 hover:text-red-900" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-gray-500 py-6">No pending stock notification subscriptions.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Deletion</h3>
            <p class="text-sm text-gray-500 mb-4" id="deleteMessage"></p>
            <div class="flex justify-end space-x-4">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">Cancel</button>
                <form id="deleteForm" action="manage_subscriptions.php" method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="subscription_id" id="deleteSubscriptionId">
                    <button type="submit" name="delete_subscription" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(subscriptionId, email) {
    document.getElementById('deleteMessage').textContent = `Are you sure you want to delete the subscription for ${email}?`;
    document.getElementById('deleteSubscriptionId').value = subscriptionId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php include 'footer.php'; ?>
