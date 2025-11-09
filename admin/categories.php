<?php
// Include the admin bootstrap for automatic setup
require_once 'bootstrap.php';

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();
$message = '';
$error = '';

// Handle form submission for adding a new category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            // Validate category name: alphanumeric, spaces, hyphens, apostrophes
            if (!preg_match("/^[a-zA-Z0-9\s\-'’]+$/", $name)) {
                $error = "Category name can only contain letters, numbers, spaces, hyphens, and apostrophes.";
            } else {
                $sql = "INSERT INTO categories (name) VALUES (?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $name);
                if ($stmt->execute()) {
                    header("Location: categories.php?success=added");
                    exit();
                } else {
                    $error = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = "Category name cannot be empty.";
        }
    }
}

// Handle POST request for deleting a category (after confirmation modal)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_category'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $id_to_delete = filter_var(trim($_POST['category_id']), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        if ($id_to_delete) {
            // Check if category is associated with any products before deleting
            $sql_check_products = "SELECT COUNT(*) FROM products WHERE category = ?";
            $stmt_check = $conn->prepare($sql_check_products);
            $stmt_check->bind_param("i", $id_to_delete);
            $stmt_check->execute();
            $stmt_check->bind_result($product_count);
            $stmt_check->fetch();
            $stmt_check->close();

            if ($product_count > 0) {
                header("Location: categories.php?error=category_has_products");
                exit();
            }

            $sql = "DELETE FROM categories WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_to_delete);
            if ($stmt->execute()) {
                header("Location: categories.php?success=deleted");
                exit();
            } else { // Fallback error if deletion fails for other reasons
                header("Location: categories.php?error=deletion_failed");
                exit();
            }
        }
    }
}

// Fetch all categories
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($result = $conn->query($sql_categories)) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Check for success/error messages from redirects
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'added') {
        $message = "New category added successfully!";
    } elseif ($_GET['success'] == 'deleted') {
        $message = "Category deleted successfully!";
    }
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] == 'category_has_products') {
        $error = "Cannot delete category: It is associated with existing products.";
    } elseif ($_GET['error'] == 'deletion_failed') {
        $error = "Error deleting category. Please try again.";
    }
}

$pageTitle = 'Manage Categories';
include 'header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Add New Category -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Add New Category</h3>

        <?php if(!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="categories.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                <input type="text" id="name" name="name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <button type="submit" name="add_category" class="w-full bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Add Category</button>
        </form>
    </div>

    <!-- Categories List -->
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold text-gray-800 mb-4">All Categories</h3>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $category['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($category['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="edit_category.php?id=<?php echo $category['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                    <button onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center text-gray-500 py-6">No categories found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
                <form id="deleteForm" action="categories.php" method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="category_id" id="deleteCategoryId">
                    <button type="submit" name="delete_category" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(categoryId, categoryName) {
    document.getElementById('deleteMessage').textContent = `Are you sure you want to delete the category "${categoryName}"?`;
    document.getElementById('deleteCategoryId').value = categoryId;
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
