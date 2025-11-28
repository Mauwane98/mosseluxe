<?php
// Include the admin bootstrap for automatic setup
require_once 'bootstrap.php';

$conn = get_db_connection();

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();


$add_category_error = '';
$delete_category_error = '';
$new_category_name = ''; // To retain value in add category form

// Handle form submission for adding a new category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $new_category_name = trim($_POST['name']); // Retain value

    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $add_category_error = 'Invalid CSRF token.';
    } else {
        $name = $new_category_name;
        if (!empty($name)) {
            // Validate category name: alphanumeric, spaces, hyphens, apostrophes
            if (!preg_match("/^[a-zA-Z0-9\s\-'’]+$/", $name)) {
                $add_category_error = "Category name can only contain letters, numbers, spaces, hyphens, and apostrophes.";
            } else {
                $sql = "INSERT INTO categories (name) VALUES (?)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("s", $name);
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "New category added successfully!";
                        header("Location: categories.php");
                        exit();
                    } else {
                        error_log("Error executing add category query: " . $stmt->error);
                        $add_category_error = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    error_log("Error preparing add category query: " . $conn->error);
                    $add_category_error = "Error preparing statement. Please try again later.";
                }
            }
        } else {
            $add_category_error = "Category name cannot be empty.";
        }
    }
}

// Handle POST request for deleting a category (after confirmation modal)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_category'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $delete_category_error = 'Invalid CSRF token.';
    } else {
        $id_to_delete = filter_var(trim($_POST['category_id']), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        if ($id_to_delete) {
            // Check if category is associated with any products before deleting
            $sql_check_products = "SELECT COUNT(*) FROM products WHERE category = ?";
            if ($stmt_check = $conn->prepare($sql_check_products)) {
                $stmt_check->bind_param("i", $id_to_delete);
                if ($stmt_check->execute()) {
                    $stmt_check->bind_result($product_count);
                    $stmt_check->fetch();
                } else {
                    error_log("Error executing check products query for category deletion: " . $stmt_check->error);
                    $delete_category_error = "Error checking product associations. Please try again.";
                }
                $stmt_check->close();
            } else {
                error_log("Error preparing check products query for category deletion: " . $conn->error);
                $delete_category_error = "Error preparing statement. Please try again later.";
            }

            if (empty($delete_category_error) && $product_count > 0) { // Only proceed if no prior error
                $delete_category_error = "Cannot delete category: It is associated with existing products.";
            }

            if (empty($delete_category_error)) { // Only proceed if no prior error
                $sql = "DELETE FROM categories WHERE id = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("i", $id_to_delete);
                    if ($stmt->execute()) {
                        $_SESSION['toast_message'] = ['message' => 'Category deleted successfully!', 'type' => 'success'];
                        header("Location: categories.php");
                        exit();
                    } else { // Fallback error if deletion fails for other reasons
                        error_log("Error executing delete category query: " . $stmt->error);
                        $delete_category_error = "Error deleting category. Please try again.";
                    }
                    $stmt->close();
                } else {
                    error_log("Error preparing delete category query: " . $conn->error);
                    $delete_category_error = "Error preparing statement. Please try again later.";
                }
            }
        } else {
            $delete_category_error = "Invalid category ID.";
        }
    }
}

// Fetch all categories
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($stmt = $conn->prepare($sql_categories)) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    } else {
        error_log("Error executing fetch categories query: " . $stmt->error);
        $_SESSION['error_message'] = "Error fetching categories. Please try again later.";
    }
    $stmt->close();
} else {
    error_log("Error preparing fetch categories query: " . $conn->error);
    $_SESSION['error_message'] = "Error preparing statement. Please try again later.";
}



$pageTitle = 'Manage Categories';
include 'header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Add New Category -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Add New Category</h3>

        <?php if(!empty($add_category_error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $add_category_error; ?>
            </div>
        <?php endif; ?>

        <form action="categories.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($new_category_name); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <button type="submit" name="add_category" class="w-full bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Add Category</button>
        </form>
    </div>

    <!-- Categories List -->
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold text-gray-800 mb-4">All Categories</h3>

        <?php if(!empty($delete_category_error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $delete_category_error; ?>
            </div>
        <?php endif; ?>

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
                                    <button onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', 'category')" class="text-red-600 hover:text-red-900">Delete</button>
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
                    <input type="hidden" name="category_id" id="deleteId">
                    <button type="submit" name="delete_category" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
function confirmDelete(id, name, type) {
    const message = `Are you sure you want to delete the ${type} "${name}"?`;
    document.getElementById('deleteMessage').textContent = message;
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').style.display = 'none';
}

// Prevent form from submitting multiple times
document.getElementById('deleteForm')?.addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn.disabled) {
        e.preventDefault();
        return false;
    }
    submitBtn.disabled = true;
    submitBtn.textContent = 'Deleting...';
});
</script>
