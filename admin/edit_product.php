<?php
// Start session and include admin authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Removed: require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
require_once '../includes/config.php';
require_once '../includes/image_service.php';
// Removed: require_once '../includes/notification_service.php'; // Not used in this file

$conn = get_db_connection();

$edit_product_error = '';
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
        // Repopulate product_data with POST data to retain values on error
        $product_data['name'] = trim($_POST["name"]);
        $product_data['description'] = trim($_POST["description"]);
        $product_data['price'] = trim($_POST["price"]);
        $product_data['sale_price'] = trim($_POST['sale_price']);
        $product_data['category'] = trim($_POST["category"]);
        $product_data['stock'] = trim($_POST["stock"]);
        $product_data['status'] = trim($_POST["status"]);
        $product_data['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;

        // Sanitize and validate inputs
        $name = trim($_POST["name"]);
        $description = trim($_POST["description"]);
        $price = filter_var(trim($_POST["price"]), FILTER_VALIDATE_FLOAT);
        $sale_price = !empty($_POST['sale_price']) ? filter_var(trim($_POST['sale_price']), FILTER_VALIDATE_FLOAT) : null;
        $category_id = filter_var(trim($_POST["category"]), FILTER_VALIDATE_INT);
        $stock = filter_var(trim($_POST["stock"]), FILTER_VALIDATE_INT);
        $status = filter_var(trim($_POST["status"]), FILTER_SANITIZE_NUMBER_INT);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        // New Image upload handling using ImageService
        $image_path = $product_data['image']; // Keep old image if new one is not uploaded
        $remove_image = isset($_POST['remove_image']) ? true : false;

        if ($remove_image && empty($_FILES['image']['name'])) {
            // If remove image is checked and no new image is uploaded
            if (!empty($product_data['image']) && file_exists('../' . $product_data['image'])) {
                unlink('../' . $product_data['image']);
            }
            $image_path = ''; // Set image path to empty in DB
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $new_image_path = ImageService::processUpload($_FILES['image'], PRODUCT_IMAGE_WIDTH, PRODUCT_IMAGE_HEIGHT, $edit_product_error);
            if ($new_image_path) {
                // Delete old image if a new one was successfully uploaded and it's not the same as the old one
                // Ensure the old image path is not empty and the file exists before attempting to delete
                if (!empty($product_data['image']) && $product_data['image'] !== $new_image_path && file_exists('../' . $product_data['image'])) {
                    unlink('../' . $product_data['image']);
                }
                $image_path = $new_image_path;
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            $edit_product_error = 'There was an error with the new image upload.';
        }

        if (empty($name)) {
            $edit_product_error = 'Product name cannot be empty.';
        } elseif (empty($description)) {
            $edit_product_error = 'Product description cannot be empty.';
        } elseif ($price === false || $category_id === false || $stock === false || filter_var($status, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 1))) === false) {
            $edit_product_error = 'Please fill out all required fields with valid data.';
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

$pageTitle = 'Edit Product';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Product</h2>
        <a href="products.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Back to Products</a>
    </div>

    <?php if (!empty($edit_product_error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $edit_product_error; ?>
        </div>
    <?php endif; ?>

    <?php if ($product_data): ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $product_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Product Name -->
            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Product Name</label>
                <input type="text" id="name" name="name" required
                       value="<?php echo htmlspecialchars($product_data['name']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Price -->
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Price (R)</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required
                       value="<?php echo htmlspecialchars($product_data['price']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Sale Price -->
            <div>
                <label for="sale_price" class="block text-sm font-medium text-gray-700 mb-2">Sale Price (R) <span class="text-gray-500">(optional)</span></label>
                <input type="number" id="sale_price" name="sale_price" step="0.01" min="0"
                       value="<?php echo htmlspecialchars($product_data['sale_price'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Category -->
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select id="category" name="category" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="">Choose category...</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($product_data['category'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Stock -->
            <div>
                <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity</label>
                <input type="number" id="stock" name="stock" min="0" required
                       value="<?php echo htmlspecialchars($product_data['stock']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" name="status" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="1" <?php echo ($product_data['status'] == 1) ? 'selected' : ''; ?>>Published</option>
                    <option value="0" <?php echo ($product_data['status'] == 0) ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>

            <!-- Featured Toggle -->
            <div class="flex items-center">
                <label class="flex items-center">
                    <input type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo ($product_data['is_featured'] == 1) ? 'checked' : ''; ?>
                           class="rounded border-gray-300 text-black focus:ring-black">
                    <span class="ml-2 text-sm font-medium text-gray-700">Featured Product</span>
                </label>
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea id="description" name="description" rows="4" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"><?php echo htmlspecialchars($product_data['description']); ?></textarea>
        </div>

        <!-- Image Upload -->
        <div>
            <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
            <input type="file" id="image" name="image" accept="image/*"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            <p class="text-sm text-gray-500 mt-1">Upload a new image to replace the current one (JPG, PNG, WebP)</p>

            <?php if (!empty($product_data['image'])): ?>
                <div class="mt-3">
                    <p class="text-sm font-medium text-gray-700 mb-2">Current Image:</p>
                    <img src="../<?php echo htmlspecialchars($product_data['image']); ?>" alt="Current Product Image"
                         class="h-32 w-32 object-cover rounded-md border border-gray-300">
                    <div class="mt-2">
                        <label class="flex items-center">
                            <input type="checkbox" id="remove_image" name="remove_image" value="1"
                                   class="rounded border-gray-300 text-black focus:ring-black">
                            <span class="ml-2 text-sm text-gray-700">Remove current image</span>
                        </label>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-4">
            <a href="products.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors">Update Product</button>
        </div>
    </form>
    <?php else: ?>
        <div class="text-center py-8">
            <p class="text-gray-500">Product data could not be loaded.</p>
            <a href="products.php" class="text-blue-600 hover:text-blue-800">Back to Products</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
