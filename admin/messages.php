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

$conn = get_db_connection();

$messages = [];
$sql = "SELECT id, name, email, subject, received_at, is_read FROM messages ORDER BY received_at DESC";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $result->free();
}

$action = $_GET['action'] ?? 'list';
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message_content = null;

if ($action === 'view' && $message_id > 0) {
    // Mark as read
    $conn->query("UPDATE messages SET is_read = 1 WHERE id = $message_id");
    // Fetch message content
    $stmt = $conn->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->bind_param('i', $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $message_content = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();

$pageTitle = "Customer Messages";
include 'header.php';
?>

<?php if ($action === 'view' && $message_content): ?>
    <!-- View Message Detail -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Message Details</h2>
            <a href="messages.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Back to Messages</a>
        </div>

        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">From</label>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($message_content['name']); ?> &lt;<?php echo htmlspecialchars($message_content['email']); ?>&gt;</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Received</label>
                    <p class="mt-1 text-sm text-gray-900"><?php echo date('d M Y, H:i', strtotime($message_content['received_at'])); ?></p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Subject</label>
                <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($message_content['subject']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Message</label>
                <div class="mt-1 p-4 bg-gray-50 rounded-md">
                    <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($message_content['message']); ?></p>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Messages List -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Customer Messages</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Received</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $message): ?>
                            <tr class="<?php echo !$message['is_read'] ? 'bg-blue-50' : ''; ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($message['name']); ?>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($message['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($message['subject']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo date('d M Y, H:i', strtotime($message['received_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $message['is_read'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $message['is_read'] ? 'Read' : 'New'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="messages.php?action=view&id=<?php echo $message['id']; ?>" class="text-indigo-600 hover:text-indigo-900">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 py-6">No messages found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
