<?php
// Include the admin bootstrap for automatic setup
require_once 'bootstrap.php';

// Ensure admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once '../includes/image_service.php';

$conn = get_db_connection();

$add_product_error = '';
$csrf_token = generate_csrf_token();

// Form field values
$name = '';
$description = '';
$price = '';
$sale_price = '';
$category_id = '';
$stock = '';
$status = '1'; // Default to 'Published'

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
        $price = filter_var(trim($_POST["price"]), FILTER_VALIDATE_FLOAT);
        $sale_price = !empty($_POST['sale_price']) ? filter_var(trim($_POST['sale_price']), FILTER_VALIDATE_FLOAT) : null;
        $category_id = filter_var(trim($_POST["category"]), FILTER_VALIDATE_INT);
        $stock = filter_var(trim($_POST["stock"]), FILTER_VALIDATE_INT);
        $status = filter_var(trim($_POST["status"]), FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 1)));

        // New Image upload handling using ImageService
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $image_path = ImageService::processUpload($_FILES['image'], PRODUCT_IMAGE_WIDTH, PRODUCT_IMAGE_HEIGHT, $add_product_error);
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            $add_product_error = 'There was an error with the image upload.';
        } else {
            $add_product_error = 'Please select an image to upload.';
        }

        // Basic validation
        if (empty($name) || empty($description) || $price === false || $category_id === false || $stock === false || $status === false || empty($image_path)) {
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

$pageTitle = 'Add New Product';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Product</h2>

    <?php if(!empty($add_product_error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $add_product_error; ?>
        </div>
    <?php endif; ?>

    <form action="add_product.php" method="post" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Product Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Category -->
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                <select id="category" name="category" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Price -->
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Price (R) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($price); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Sale Price -->
            <div>
                <label for="sale_price" class="block text-sm font-medium text-gray-700 mb-2">Sale Price (R) <span class="text-sm text-gray-500">(optional)</span></label>
                <input type="number" id="sale_price" name="sale_price" step="0.01" min="0" value="<?php echo htmlspecialchars($sale_price); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Stock -->
            <div>
                <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity *</label>
                <input type="number" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($stock); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                <select id="status" name="status" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="1" <?php echo ($status == '1') ? 'selected' : ''; ?>>Published</option>
                    <option value="0" <?php echo ($status == '0') ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
            <textarea id="description" name="description" rows="4" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <!-- Image Upload -->
        <div>
            <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Product Image *</label>
            <input type="file" id="image" name="image" accept="image/*" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            <p class="text-sm text-gray-500 mt-1">Upload a high-quality image (JPG, PNG, WebP). Recommended size: 800x800px.</p>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-4">
            <a href="products.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors">Add Product</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
