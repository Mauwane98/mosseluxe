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
require_once '../includes/notification_service.php';
$conn = get_db_connection();

$edit_product_error = '';
$success_message = '';
$csrf_token = generate_csrf_token();

$product_id = null;
$product_data = null;

// Fetch categories for the dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($result_categories = $conn->query($sql_categories)) {
    while ($row_category = $result_categories->fetch_assoc()) {
        $categories[] = $row_category;
    }
}

// Check if product ID is provided in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = filter_var(trim($_GET['id']), FILTER_SANITIZE_NUMBER_INT);

    // Fetch product details from the database
    $sql_fetch_product = "SELECT id, name, description, price, sale_price, category, image, status, stock, is_featured FROM products WHERE id = ?";
    if ($stmt_fetch = $conn->prepare($sql_fetch_product)) {
        $stmt_fetch->bind_param("i", $param_id);
        $param_id = $product_id;

        if ($stmt_fetch->execute()) {
            $result_fetch = $stmt_fetch->get_result();
            if ($row_fetch = $result_fetch->fetch_assoc()) {
                $product_data = $row_fetch;
            } else {
                header("Location: products.php?error=product_not_found");
                exit();
            }
        } else {
            header("Location: products.php?error=fetch_failed");
            exit();
        }
        $stmt_fetch->close();
    } else {
        header("Location: products.php?error=prepare_failed");
        exit();
    }
} else {
    header("Location: products.php?error=no_id");
    exit();
}

// Handle form submission for updating product
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $edit_product_error = 'Invalid CSRF token. Please try again.';
    } else {
        // Sanitize and validate inputs
        $name = trim($_POST["name"]);
        $description = trim($_POST["description"]);
        $price = filter_var(trim($_POST["price"]), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $sale_price = !empty($_POST['sale_price']) ? filter_var(trim($_POST['sale_price']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
        $category_id = filter_var(trim($_POST["category"]), FILTER_SANITIZE_NUMBER_INT);
        $stock = filter_var(trim($_POST["stock"]), FILTER_SANITIZE_NUMBER_INT);
        $status = filter_var(trim($_POST["status"]), FILTER_SANITIZE_NUMBER_INT);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        // New Image upload handling using ImageService
        $image_path = $product_data['image']; // Keep old image if new one is not uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $new_image_path = ImageService::processUpload($_FILES['image'], '../assets/images/', PRODUCT_IMAGE_WIDTH, PRODUCT_IMAGE_HEIGHT, $edit_product_error);
            if ($new_image_path) {
                $image_path = $new_image_path;
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            $edit_product_error = 'There was an error with the new image upload.';
        }

        if (empty($name) || empty($description) || $price === false || empty($category_id) || $stock === false || !isset($status)) {
            $edit_product_error = 'Please fill out all required fields.';
        } elseif ($sale_price !== null && $sale_price >= $price) {
            $edit_product_error = 'Sale price must be less than the regular price.';
        } else {
            $old_stock = $product_data['stock']; // Get stock before update
            // Prepare an update statement
            $sql_update_product = "UPDATE products SET name = ?, description = ?, price = ?, sale_price = ?, category = ?, image = ?, status = ?, stock = ?, is_featured = ? WHERE id = ?";
            
            if ($stmt_update = $conn->prepare($sql_update_product)) {
                $stmt_update->bind_param("ssddisiiii", $param_name, $param_description, $param_price, $param_sale_price, $param_category, $param_image, $param_status, $param_stock, $param_is_featured, $param_id);

                // Set parameters
                $param_name = $name;
                $param_description = $description;
                $param_price = $price;
                $param_sale_price = $sale_price;
                $param_category = $category_id;
                $param_image = $image_path;
                $param_status = $status;
                $param_stock = $stock;
                $param_is_featured = $is_featured;
                $param_id = $product_id;

                if ($stmt_update->execute()) {
                    // Check if stock was replenished and send notifications
                    if ($old_stock <= 0 && $stock > 0) {
                        $sql_get_notifs = "SELECT id, email FROM stock_notifications WHERE product_id = ? AND notified_at IS NULL";
                        if ($stmt_notifs = $conn->prepare($sql_get_notifs)) {
                            $stmt_notifs->bind_param("i", $product_id);
                            $stmt_notifs->execute();
                            $result_notifs = $stmt_notifs->get_result();
                            $notification_ids = [];
                            while ($notif = $result_notifs->fetch_assoc()) {
                                NotificationService::sendBackInStockAlert(['id' => $product_id, 'name' => $name], $notif['email']);
                                $notification_ids[] = $notif['id'];
                            }
                            $stmt_notifs->close();

                            // Mark notifications as sent
                            if (!empty($notification_ids)) {
                                $placeholders = implode(',', array_fill(0, count($notification_ids), '?'));
                                $sql_update_notifs = "UPDATE stock_notifications SET notified_at = NOW() WHERE id IN ($placeholders)";
                                $stmt_update_notifs = $conn->prepare($sql_update_notifs);
                                $types = str_repeat('i', count($notification_ids));
                                $stmt_update_notifs->bind_param($types, ...$notification_ids);
                                $stmt_update_notifs->execute();
                                $stmt_update_notifs->close();
                            }
                        }
                    }
                    header("Location: products.php?success=updated");
                    exit();
                } else {
                    $edit_product_error = 'Something went wrong. Please try again later.';
                }
                $stmt_update->close();
            } else {
                $edit_product_error = 'Error preparing statement. Please try again later.';
            }
        }
    }
}

$active_page = 'products';
$page_title = 'Edit Product';
?>
<?php include '../includes/admin_page_header.php'; ?>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php
    include '../includes/admin_header.php'; 
    ?>

    <div class="card p-4">
        <div class="card-body">
            <?php if (!empty($edit_product_error)): ?>
                <div class="alert alert-danger"><?php echo $edit_product_error; ?></div>
            <?php elseif (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($product_data): ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $product_id; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($product_data['name']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="price" class="form-label">Price (R)</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" required value="<?php echo htmlspecialchars($product_data['price']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="sale_price" class="form-label">Sale Price (R) <span class="text-muted small">(Optional)</span></label>
                        <input type="number" class="form-control" id="sale_price" name="sale_price" step="0.01" value="<?php echo htmlspecialchars($product_data['sale_price'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($product_data['description']); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Choose...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($product_data['category'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="stock" class="form-label">Stock Quantity</label>
                        <input type="number" class="form-control" id="stock" name="stock" required value="<?php echo htmlspecialchars($product_data['stock']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image">
                        <img src="../<?php echo htmlspecialchars($product_data['image']); ?>" alt="Product Preview" class="product-image-preview">
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="1" <?php echo ($product_data['status'] == 1) ? 'selected' : ''; ?>>Published</option>
                            <option value="0" <?php echo ($product_data['status'] == 0) ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_featured" name="is_featured" value="1" <?php echo ($product_data['is_featured'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">Featured Product</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary-dark">Update Product</button>
                    <a href="products.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
            <?php else: ?>
                <p class="text-center text-muted">Product data could not be loaded.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
