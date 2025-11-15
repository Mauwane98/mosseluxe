<?php
require_once 'bootstrap.php';
require_once '../includes/notification_service.php';
$conn = get_db_connection();

$messages = [];
$sql = "SELECT id, name, email, subject, received_at, is_read FROM messages ORDER BY received_at DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}

$action = $_GET['action'] ?? 'list';
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message_content = null;
$reply_success = false;
$reply_error = '';

if ($action === 'view' && $message_id > 0) {
    // Mark as read
    $stmt_update = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $stmt_update->bind_param('i', $message_id);
    $stmt_update->execute();
    $stmt_update->close();
    // Fetch message content
    $stmt = $conn->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->bind_param('i', $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $message_content = $result->fetch_assoc();
    $stmt->close();
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $reply_error = 'Invalid security token.';
    } else {
        $reply_to_id = (int)$_POST['message_id'];
        $reply_subject = trim($_POST['reply_subject']);
        $reply_message = trim($_POST['reply_message']);
        $recipient_email = trim($_POST['recipient_email']);
        $recipient_name = trim($_POST['recipient_name']);

        if (empty($reply_subject) || empty($reply_message) || empty($recipient_email)) {
            $reply_error = 'Please fill in all required fields.';
        } else {
            // Send email reply using NotificationService
            $original_message = isset($message_content['message']) ? $message_content['message'] : '';
            $mail_result = NotificationService::sendMessageReply($recipient_email, $recipient_name, $reply_subject, $reply_message, $original_message);

            if ($mail_result) {
                $reply_success = true;
                error_log("Message reply sent successfully from admin to " . $recipient_email);
            } else {
                $reply_error = 'Failed to send email reply. Please try again.';
            }
        }
    }
}

$pageTitle = "Customer Messages";
include 'header.php';
?>

<?php if ($action === 'view' && $message_content): ?>
    <!-- View Message Detail -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Message Details</h2>
            <div class="flex space-x-2">
                <button onclick="openReplyModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">Reply</button>
                <button onclick="confirmDelete(<?php echo $message_content['id']; ?>, 'message from <?php echo htmlspecialchars(addslashes($message_content['name'])); ?>', 'message')" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors">Delete</button>
                <a href="messages.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Back to Messages</a>
            </div>
        </div>

        <?php if ($reply_success): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Reply Sent Successfully!</h3>
                        <div class="mt-1 text-sm text-green-700">
                            <p>Email sent to: <strong><?php echo htmlspecialchars($_POST['recipient_name'] ?? 'N/A'); ?> <<?php echo htmlspecialchars($_POST['recipient_email'] ?? 'N/A'); ?>></strong></p>
                            <p>Subject: <strong><?php echo htmlspecialchars($_POST['reply_subject'] ?? 'N/A'); ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($reply_error)): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Failed to Send Reply</h3>
                        <div class="mt-1 text-sm text-red-700">
                            <p><?php echo htmlspecialchars($reply_error); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">From</label>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($message_content['name']); ?> <<?php echo htmlspecialchars($message_content['email']); ?>></p>
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
                                    <a href="messages.php?action=view&id=<?php echo $message['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                    <button onclick="confirmDelete(<?php echo $message['id']; ?>, 'message from <?php echo htmlspecialchars(addslashes($message['name'])); ?>', 'message')" class="text-red-600 hover:text-red-900">Delete</button>
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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Deletion</h3>
            <p class="text-sm text-gray-500 mb-4" id="deleteMessage"></p>
            <div class="flex justify-end space-x-4">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">Cancel</button>
                <form id="deleteForm" action="delete_message.php" method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="id" id="deleteId">
                    <button name="delete_message" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div id="replyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white max-h-90vh overflow-y-auto">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Reply to Message</h3>

            <form action="messages.php?action=view&id=<?php echo $message_id; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="send_reply" value="1">
                <input type="hidden" name="message_id" value="<?php echo $message_id; ?>">
                <input type="hidden" name="recipient_email" value="<?php echo htmlspecialchars($message_content['email'] ?? ''); ?>">
                <input type="hidden" name="recipient_name" value="<?php echo htmlspecialchars($message_content['name'] ?? ''); ?>">

                <div class="mb-4">
                    <label for="recipient" class="block text-sm font-medium text-gray-700 mb-2">To</label>
                    <input type="text" readonly class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md"
                           value="<?php echo htmlspecialchars(($message_content['name'] ?? '') . ' <' . ($message_content['email'] ?? '') . '>'); ?>">
                </div>

                <div class="mb-4">
                    <label for="reply_subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                    <input type="text" id="reply_subject" name="reply_subject" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="Re: <?php echo htmlspecialchars($message_content['subject'] ?? ''); ?>">
                </div>

                <div class="mb-4">
                    <label for="reply_message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                    <textarea id="reply_message" name="reply_message" rows="8" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Compose your reply..."></textarea>
                </div>

                <!-- Original Message Reference -->
                <div class="mb-4 p-3 bg-gray-100 rounded-md">
                    <p class="text-sm text-gray-600 mb-2"><strong>Original Message:</strong></p>
                    <div class="text-sm text-gray-500 max-h-32 overflow-y-auto">
                        <p><strong>Subject:</strong> <?php echo htmlspecialchars($message_content['subject'] ?? ''); ?></p>
                        <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars(substr($message_content['message'] ?? '', 0, 200))); ?><?php echo strlen($message_content['message'] ?? '') > 200 ? '...' : ''; ?></p>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeReplyModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">Send Reply</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
function confirmDelete(id, name, type) {
    const message = `Are you sure you want to delete the ${type} "${name}"?`;
    document.getElementById('deleteMessage').textContent = message;
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function openReplyModal() {
    document.getElementById('replyModal').style.display = 'block';
}

function closeReplyModal() {
    document.getElementById('replyModal').style.display = 'none';
}

// Close modals when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
document.getElementById('replyModal').addEventListener('click', function(e) {
    if (e.target === this) closeReplyModal();
});
</script>
