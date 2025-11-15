<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

// Generate CSRF token
$csrf_token = generate_csrf_token();

$error = '';
$success = '';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: categories.php");
    exit();
}

$id = (int)$_GET['id'];

// Fetch category data
$category = [];
$sql = "SELECT id, name FROM categories WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $category = $result->fetch_assoc();
        } else {
            header("Location: categories.php");
            exit();
        }
    } else {
        $error = "Database error.";
    }
    $stmt->close();
} else {
    $error = "Failed to prepare statement.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_category'])) {
    $name = trim($_POST['name']);

    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } elseif (empty($name)) {
        $error = "Category name cannot be empty.";
    } elseif (!preg_match("/^[a-zA-Z0-9\s\-'’]+$/", $name)) {
        $error = "Category name can only contain letters, numbers, spaces, hyphens, and apostrophes.";
    } else {
        $sql_update = "UPDATE categories SET name = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql_update)) {
            $stmt->bind_param("si", $name, $id);
            if ($stmt->execute()) {
                $success = "Category updated successfully!";
                $category['name'] = $name; // Update for form display
            } else {
                $error = "Error updating category.";
            }
            $stmt->close();
        } else {
            $error = "Failed to prepare statement.";
        }
    }
}

$pageTitle = 'Edit Category';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md max-w-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Category</h2>
        <a href="categories.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors">Back to Categories</a>
    </div>

    <?php if(!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <form action="edit_category.php?id=<?php echo $id; ?>" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
        </div>
        <button type="submit" name="update_category" class="w-full bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Update Category</button>
    </form>
</div>

<?php include 'footer.php'; ?>
