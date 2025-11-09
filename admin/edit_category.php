<?php
// Start session and include admin authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
$conn = get_db_connection();

$csrf_token = generate_csrf_token();
$category_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) : 0;
$error = '';

if (!$category_id) {
    header("Location: categories.php");
    exit();
}

// Fetch category details
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();
if (!$category) {
    header("Location: categories.php?error=not_found");
    exit();
}

// Handle form submission for updating the category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_category'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            // Validate category name: alphanumeric, spaces, hyphens, apostrophes
            if (!preg_match("/^[a-zA-Z0-9\s\-'’]+$/", $name)) {
                $error = "Category name can only contain letters, numbers, spaces, hyphens, and apostrophes.";
            } else {
                $sql = "UPDATE categories SET name=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $name, $category_id);

                if ($stmt->execute()) {
                    header("Location: categories.php?success=updated");
                    exit();
                } else {
                    $error = "Error updating record: " . $conn->error;
                }
                $stmt->close();
            }
        } else {
            $error = "Category name cannot be empty.";
        }
    }
}

$pageTitle = 'Edit Category';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Category</h2>
        <a href="categories.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Back to Categories</a>
    </div>

    <?php if(!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="edit_category.php?id=<?php echo $category_id; ?>" method="post" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Category Name -->
            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                <input type="text" id="name" name="name" required
                       value="<?php echo htmlspecialchars($category['name']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                <p class="text-xs text-gray-500 mt-1">Only letters, numbers, spaces, hyphens, and apostrophes allowed</p>
            </div>

            <!-- Category ID (Read-only) -->
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category ID</label>
                <input type="text" id="category_id" readonly
                       value="#<?php echo htmlspecialchars($category['id']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
            </div>

            <!-- Created Date (Read-only) -->
            <div>
                <label for="created_date" class="block text-sm font-medium text-gray-700 mb-2">Created Date</label>
                <input type="text" id="created_date" readonly
                       value="<?php echo date('d M Y', strtotime($category['created_at'])); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-4">
            <a href="categories.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" name="update_category" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors">Update Category</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
