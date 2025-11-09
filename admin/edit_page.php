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

$error = '';
$csrf_token = generate_csrf_token();

$page_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) : 0;

$page = null;
if ($page_id > 0) {
    $sql = "SELECT id, title, slug, content FROM pages WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $page_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $page = $result->fetch_assoc();
        $stmt->close();
    }
}

if (!$page) {
    header("Location: pages.php?error=not_found");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Invalid CSRF token.';
    } else {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);

        if (empty($title) || empty($content)) {
            $error = 'Title and content cannot be empty.';
        } else {
            if ($page_id > 0) {
                $sql = "UPDATE pages SET title = ?, content = ? WHERE id = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param('ssi', $title, $content, $page_id);
                    if ($stmt->execute()) {
                        header('Location: pages.php?success=updated');
                        exit;
                    } else {
                        $error = 'Failed to update page.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$pageTitle = 'Edit Page: ' . htmlspecialchars($page['title']);
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Page</h2>
        <a href="pages.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Back to Pages</a>
    </div>

    <?php if(!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="edit_page.php?id=<?php echo $page_id; ?>" method="post" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Title -->
            <div class="md:col-span-2">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Page Title</label>
                <input type="text" id="title" name="title" required
                       value="<?php echo htmlspecialchars($page['title']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Slug (Read-only) -->
            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Page Slug</label>
                <input type="text" id="slug" name="slug" readonly
                       value="<?php echo htmlspecialchars($page['slug']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
                <p class="text-xs text-gray-500 mt-1">Slug cannot be changed</p>
            </div>

            <!-- Preview Link -->
            <div class="flex items-end">
                <a href="../page.php?slug=<?php echo urlencode($page['slug']); ?>" target="_blank"
                   class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-center">
                    Preview Page
                </a>
            </div>
        </div>

        <!-- Content -->
        <div>
            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Page Content</label>
            <textarea id="content" name="content" rows="15" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black font-mono text-sm"><?php echo htmlspecialchars($page['content']); ?></textarea>
            <p class="text-xs text-gray-500 mt-1">You can use HTML tags for formatting</p>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-4">
            <a href="pages.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors">Save Changes</button>
        </div>
    </form>
</div>

<!-- Content Preview Modal -->
<div id="previewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-4/5 max-w-4xl shadow-lg rounded-md bg-white max-h-screen overflow-y-auto">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Content Preview</h3>
                <button onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="previewContent" class="prose max-w-none"></div>
        </div>
    </div>
</div>

<script>
// Simple content preview functionality
function previewContent() {
    const content = document.getElementById('content').value;
    const previewContent = document.getElementById('previewContent');
    previewContent.innerHTML = content;
    document.getElementById('previewModal').classList.remove('hidden');
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
}

// Auto-save draft (optional enhancement)
let autoSaveTimeout;
function autoSave() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
        // Could implement auto-save to localStorage or server
        console.log('Auto-saving draft...');
    }, 30000); // 30 seconds
}

// Add auto-save on content change
document.getElementById('content').addEventListener('input', autoSave);
document.getElementById('title').addEventListener('input', autoSave);
</script>

<?php include 'footer.php'; ?>
