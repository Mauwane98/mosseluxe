<?php
// Start session and include admin authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
require_once '../includes/config.php';
require_once '../includes/image_service.php';
$conn = get_db_connection();

$csrf_token = generate_csrf_token();
$message = '';
$error = '';

// Handle form submission for adding a new item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name']);
        
        // New Image upload handling using ImageService
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $image_path = ImageService::processUpload($_FILES['image'], '../assets/images/', PRODUCT_IMAGE_WIDTH, PRODUCT_IMAGE_HEIGHT, $error);
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            $error = 'There was an error with the image upload.';
        } else {
            $error = 'Please select an image to upload.';
        }

        if (empty($name) || empty($image_path)) {
            if(empty($image_path)) {
                // Do nothing, error is already set
            } else {
                $error = 'Please fill out all required fields.';
            }
        } else {
            $sql = "INSERT INTO launching_soon (name, image) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $name, $image_path);
            if ($stmt->execute()) {
                header("Location: launching_soon.php?success=added");
                exit();
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle POST request for deleting an item (after confirmation modal)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_item'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $id_to_delete = filter_var(trim($_POST['item_id']), FILTER_SANITIZE_NUMBER_INT);
        $sql = "DELETE FROM launching_soon WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $id_to_delete);
            if ($stmt->execute()) {
                header("Location: launching_soon.php?success=deleted");
                exit();
            } else {
                header("Location: launching_soon.php?error=deletion_failed");
                exit();
            }
        }
    }
}

// Fetch all items
$items = [];
$sql_items = "SELECT id, name, image, status FROM launching_soon";
if ($result = $conn->query($sql_items)) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Check for success/error messages from redirects
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'added') {
        $message = "New item added successfully!";
    } elseif ($_GET['success'] == 'deleted') {
        $message = "Item deleted successfully!";
    } elseif ($_GET['success'] == 'updated') {
        $message = "Item updated successfully!";
    }
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] == 'deletion_failed') {
        $error = "Error deleting item. Please try again.";
    }
}
$active_page = 'launching_soon';
$page_title = 'Manage "Launching Soon"';
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
                <h5 class="gold-text">Add New Item</h5>
                <?php if(!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <form action="launching_soon.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="image" name="image" required>
                    </div>
                    <button type="submit" name="add_item" class="btn btn-primary-dark w-100">Add Item</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><img src="
                                    <span class="badge <?php echo $item['status'] == 1 ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $item['status'] == 1 ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_launching_soon.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-pencil-square"></i></a>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteItemModal<?php echo $item['id']; ?>"><i class="bi bi-trash-fill"></i></button>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteItemModal<?php echo $item['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Deletion</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete "<?php echo htmlspecialchars($item['name']); ?>"?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form action="launching_soon.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" name="delete_item" class="btn btn-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
