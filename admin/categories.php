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
$message = '';
$error = '';

// Handle form submission for adding a new category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name']);
        if (!empty($name)) {
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
        $id_to_delete = filter_var(trim($_POST['category_id']), FILTER_SANITIZE_NUMBER_INT);
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
$active_page = 'categories';
$page_title = 'Manage Categories';
?>
<?php include '../includes/admin_page_header.php'; ?>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php
    include '../includes/admin_header.php'; 
    ?>

    <?php if(!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-4">
            <div class="card p-4">
                <h5 class="gold-text">Add New Category</h5>
                <form action="categories.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary-dark w-100">Add Category</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td>
                                        <a href='edit_category.php?id=<?php echo $category['id']; ?>' class='btn btn-sm btn-outline-dark'><i class="bi bi-pencil-square"></i></a>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal<?php echo $category['id']; ?>"><i class="bi bi-trash-fill"></i></button>

                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteCategoryModal<?php echo $category['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Deletion</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete the category "<?php echo htmlspecialchars($category['name']); ?>"?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="categories.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                            <button type="submit" name="delete_category" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan='3' class='text-center'>No categories found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
