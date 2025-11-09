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

// Handle review actions (approve, unapprove, delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $review_id = filter_var($_POST['review_id'], FILTER_SANITIZE_NUMBER_INT);
        $action = $_POST['action'] ?? '';

        if ($review_id) {
            if ($action === 'approve') {
                $sql = "UPDATE product_reviews SET is_approved = 1 WHERE id = ?";
            } elseif ($action === 'unapprove') {
                $sql = "UPDATE product_reviews SET is_approved = 0 WHERE id = ?";
            } elseif ($action === 'delete') {
                $sql = "DELETE FROM product_reviews WHERE id = ?";
            }

            if (isset($sql) && ($stmt = $conn->prepare($sql))) {
                $stmt->bind_param("i", $review_id);
                if ($stmt->execute()) {
                    header("Location: manage_reviews.php?success=true");
                    exit();
                } else {
                    $error = "Action failed. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}

// Fetch all reviews
$reviews = [];
$sql_reviews = "SELECT r.id, r.rating, r.review_text, r.is_approved, r.created_at, p.name as product_name, u.name as user_name
                FROM product_reviews r
                JOIN products p ON r.product_id = p.id
                JOIN users u ON r.user_id = u.id
                ORDER BY r.created_at DESC";
if ($result = $conn->query($sql_reviews)) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

if (isset($_GET['success']) && $_GET['success'] == 'true') {
    $message = "Review status updated successfully.";
}

$pageTitle = 'Manage Product Reviews';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">All Product Reviews</h2>

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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Review</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($review['product_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($review['user_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <div class="flex items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ml-2"><?php echo $review['rating']; ?>/5</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($review['review_text']); ?>">
                                <?php echo htmlspecialchars($review['review_text']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo date('d M Y', strtotime($review['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $review['is_approved'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo $review['is_approved'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form action="manage_reviews.php" method="POST" class="inline mr-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <?php if ($review['is_approved']): ?>
                                        <input type="hidden" name="action" value="unapprove">
                                        <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="Unapprove">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="text-green-600 hover:text-green-900" title="Approve">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </form>
                                <button onclick="confirmDelete(<?php echo $review['id']; ?>, '<?php echo htmlspecialchars($review['user_name']); ?>')" class="text-red-600 hover:text-red-900" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-gray-500 py-6">No reviews found.</td>
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
                <form id="deleteForm" action="manage_reviews.php" method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="review_id" id="deleteReviewId">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(reviewId, userName) {
    document.getElementById('deleteMessage').textContent = `Are you sure you want to permanently delete the review by ${userName}?`;
    document.getElementById('deleteReviewId').value = reviewId;
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
