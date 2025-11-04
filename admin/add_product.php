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

$add_product_error = '';
$success_message = '';
$csrf_token = generate_csrf_token();

// Fetch categories for the dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($result_categories = $conn->query($sql_categories)) {
    while ($row_category = $result_categories->fetch_assoc()) {
        $categories[] = $row_category;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $add_product_error = 'Invalid CSRF token. Please try again.';
    } else {
        // Sanitize and validate inputs
        $name = trim($_POST["name"]);
        $description = trim($_POST["description"]);
        $price = filter_var(trim($_POST["price"]), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $sale_price = !empty($_POST['sale_price']) ? filter_var(trim($_POST['sale_price']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
        $category_id = filter_var(trim($_POST["category"]), FILTER_SANITIZE_NUMBER_INT);
        $stock = filter_var(trim($_POST["stock"]), FILTER_SANITIZE_NUMBER_INT);
        $status = filter_var(trim($_POST["status"]), FILTER_SANITIZE_NUMBER_INT);
        
        // New Image upload handling using ImageService
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $image_path = ImageService::processUpload($_FILES['image'], '../assets/images/', PRODUCT_IMAGE_WIDTH, PRODUCT_IMAGE_HEIGHT, $add_product_error);
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            $add_product_error = 'There was an error with the image upload.';
        } else {
            $add_product_error = 'Please select an image to upload.';
        }

        // Basic validation
        if (empty($name) || empty($description) || empty($price) || empty($category_id) || empty($stock) || !isset($status) || empty($image_path)) {
            if(empty($image_path)) {
                // Do nothing, error is already set
            } else {
                $add_product_error = 'Please fill out all required fields.';
            }
        } elseif ($price <= 0) {
            $add_product_error = 'Price must be a positive number.';
        } elseif ($sale_price !== null && $sale_price >= $price) {
            $add_product_error = 'Sale price must be less than the regular price.';
        } else {
            // Prepare an insert statement
            $sql_insert_product = "INSERT INTO products (name, description, price, sale_price, category, stock, image, status, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)"; // Default is_featured to 0
            
            if ($stmt_insert = $conn->prepare($sql_insert_product)) {
                // Bind variables
                $stmt_insert->bind_param("ssddiisi", $param_name, $param_description, $param_price, $param_sale_price, $param_category, $param_stock, $param_image, $param_status);

                // Set parameters
                $param_name = $name;
                $param_description = $description;
                $param_price = $price;
                $param_sale_price = $sale_price;
                $param_category = $category_id;
                $param_stock = $stock;
                $param_image = $image_path;
                $param_status = $status;

                // Attempt to execute
                if ($stmt_insert->execute()) {
                    $success_message = 'Product added successfully!';
                    header("Location: products.php?success=added");
                    exit();
                } else {
                    $add_product_error = 'Something went wrong. Please try again later.';
                }
                $stmt_insert->close();
            } else {
                $add_product_error = 'Error preparing statement. Please try again later.';
            }
        }
    }
}

$active_page = 'products';
$page_title = 'Add New Product';
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
            <?php if (!empty($add_product_error)): ?>
                <div class="alert alert-danger"><?php echo $add_product_error; ?></div>
            <?php elseif (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="price" class="form-label">Price (R)</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label for="sale_price" class="form-label">Sale Price (R) <span class="text-muted small">(Optional)</span></label>
                        <input type="number" class="form-control" id="sale_price" name="sale_price" step="0.01">
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Choose...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" required>
                    </div>
                    <div class="col-md-6">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image" required>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="1">Published</option>
                            <option value="0">Draft</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary-dark">Add Product</button>
                    <a href="products.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
