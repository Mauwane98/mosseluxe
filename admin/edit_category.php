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
$category_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : 0;
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
        } else {
            $error = "Category name cannot be empty.";
        }
    }
}

$active_page = 'categories';
$page_title = 'Edit Category';
?>
<?php include '../includes/admin_page_header.php'; ?>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../includes/admin_header.php'; ?>

    <div class="card p-4">
        <div class="card-body">
            <?php if(!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <form action="edit_category.php?id=<?php echo $category_id; ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <label for="name" class="form-label">Category Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                <button type="submit" name="update_category" class="btn btn-primary-dark mt-3">Update Category</button>
                <a href="categories.php" class="btn btn-outline-secondary mt-3 ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
