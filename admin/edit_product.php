<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

require_once '../includes/image_service.php';


$csrf_token = generate_csrf_token();

$product_id = null;
$product_data = null;

// Fetch categories for the dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($stmt_categories = $conn->prepare($sql_categories)) {
    if ($stmt_categories->execute()) {
        $result_categories = $stmt_categories->get_result();
        while ($row_category = $result_categories->fetch_assoc()) {
            $categories[] = $row_category;
        }
    } else {
        error_log("Error executing categories query in edit_product.php: " . $stmt_categories->error);
    }
    $stmt_categories->close();
} else {
    error_log("Error preparing categories query in edit_product.php: " . $conn->error);
}

// Check if product ID is provided in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = filter_var(trim($_GET['id']), FILTER_SANITIZE_NUMBER_INT);

    // Fetch product details from the database
    $sql_fetch_product = "SELECT id, name, description, price, sale_price, category, image, status, stock, is_featured, is_coming_soon, is_bestseller, is_new FROM products WHERE id = ?";
    if ($stmt_fetch = $conn->prepare($sql_fetch_product)) {
        $stmt_fetch->bind_param("i", $param_id);
        $param_id = $product_id;

        if ($stmt_fetch->execute()) {
            $result_fetch = $stmt_fetch->get_result();
            if ($row_fetch = $result_fetch->fetch_assoc()) {
                $product_data = $row_fetch;
            } else {
                $_SESSION['error_message'] = "Product not found.";
                header("Location: products.php");
                exit();
            }
        } else {
            error_log("Error executing product fetch query in edit_product.php: " . $stmt_fetch->error);
            $_SESSION['error_message'] = "Failed to fetch product details.";
            header("Location: products.php");
            exit();
        }
        $stmt_fetch->close();
    } else {
        error_log("Error preparing product fetch query in edit_product.php: " . $conn->error);
        $_SESSION['error_message'] = "Failed to prepare statement for fetching product details.";
        header("Location: products.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "No product ID provided.";
    header("Location: products.php");
    exit();
}

// Handle form submission for updating product
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Invalid CSRF token. Please try again.';
        header("Location: edit_product.php?id=" . $product_id);
        exit();
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
        $product_data['is_coming_soon'] = isset($_POST['is_coming_soon']) ? 1 : 0;
        $product_data['is_bestseller'] = isset($_POST['is_bestseller']) ? 1 : 0;
        $product_data['is_new'] = isset($_POST['is_new']) ? 1 : 0;

        // Sanitize and validate inputs
        $name = trim($_POST["name"]);
        $description = trim($_POST["description"]);
        $price = filter_var(trim($_POST["price"]), FILTER_VALIDATE_FLOAT);
        $sale_price = !empty($_POST['sale_price']) ? filter_var(trim($_POST['sale_price']), FILTER_VALIDATE_FLOAT) : null;
        $category_id = filter_var(trim($_POST["category"]), FILTER_VALIDATE_INT);
        $stock = filter_var(trim($_POST["stock"]), FILTER_VALIDATE_INT);
        $status = filter_var(trim($_POST["status"]), FILTER_SANITIZE_NUMBER_INT);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_coming_soon = isset($_POST['is_coming_soon']) ? 1 : 0;
        $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
        $is_new = isset($_POST['is_new']) ? 1 : 0;
        
        // New Image upload handling using ImageService
        $image_path = $product_data['image']; // Keep old image if new one is not uploaded
        $remove_image = isset($_POST['remove_image']) ? true : false;
        $edit_product_error = ''; // Initialize error variable for ImageService

        if ($remove_image && empty($_FILES['image']['name'])) {
            // If remove image is checked and no new image is uploaded
            // Check if there will be no image after this operation
            if (empty($product_data['image']) || (!empty($product_data['image']) && $remove_image && empty($_FILES['image']['name']))) {
                $edit_product_error = 'A product must always have an image. Please upload a new image or uncheck "Remove current image".';
            }
            if (!empty($product_data['image']) && file_exists(ABSPATH . DIRECTORY_SEPARATOR . $product_data['image'])) {
                unlink(ABSPATH . DIRECTORY_SEPARATOR . $product_data['image']);
            }
            $image_path = ''; // Set image path to empty in DB
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $new_image_path = ImageService::processUpload($_FILES['image'], PRODUCT_IMAGE_DIR, PRODUCT_IMAGE_WIDTH, PRODUCT_IMAGE_HEIGHT, $edit_product_error);
            if ($new_image_path) {
                // Delete old image if a new one was successfully uploaded and it's not the same as the old one
                // Ensure the old image path is not empty and the file exists before attempting to delete
                if (!empty($product_data['image']) && $product_data['image'] !== $new_image_path && file_exists(ABSPATH . DIRECTORY_SEPARATOR . $product_data['image'])) {
                    unlink(ABSPATH . DIRECTORY_SEPARATOR . $product_data['image']);
                }
                $image_path = $new_image_path;
            } else {
                $edit_product_error = 'Image upload failed: ' . $edit_product_error;
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            $edit_product_error = 'There was an error with the new image upload.';
        }

        if (!empty($edit_product_error)) { // If image processing already failed, don't proceed with other validations
            // error message is already set by ImageService::processUpload or image specific logic
        } elseif ($price === false || $stock === false || filter_var($status, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 1))) === false) {
            $edit_product_error = 'Please fill out all required fields with valid data.';
        } elseif ($sale_price !== null && $sale_price >= $price) {
            $edit_product_error = 'Sale price must be less than the regular price.';
        } else {
            $old_stock = $product_data['stock']; // Get stock before update
            // Prepare an update statement
            $sql_update_product = "UPDATE products SET name = ?, description = ?, price = ?, sale_price = ?, category = ?, image = ?, status = ?, stock = ?, is_featured = ?, is_coming_soon = ?, is_bestseller = ?, is_new = ? WHERE id = ?";

            if ($stmt_update = $conn->prepare($sql_update_product)) {
                $stmt_update->bind_param("ssddisiiiiiii", $param_name, $param_description, $param_price, $param_sale_price, $param_category, $param_image, $param_status, $param_stock, $param_is_featured, $param_is_coming_soon, $param_is_bestseller, $param_is_new, $param_id);

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
                $param_is_coming_soon = $is_coming_soon;
                $param_is_bestseller = $is_bestseller;
                $param_is_new = $is_new;
                $param_id = $product_id;

                if ($stmt_update->execute()) {
                    // Check if stock was replenished and send notifications
                    if ($old_stock <= 0 && $stock > 0) {
                        // Include NotificationService only when needed
                        require_once '../includes/notification_service.php';
                        $sql_get_notifs = "SELECT id, email FROM stock_notifications WHERE product_id = ? AND notified_at IS NULL";
                        if ($stmt_notifs = $conn->prepare($sql_get_notifs)) {
                            $stmt_notifs->bind_param("i", $product_id);
                            if ($stmt_notifs->execute()) {
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
                                    if (!$stmt_update_notifs->execute()) {
                                        error_log("Error updating stock notifications: " . $stmt_update_notifs->error);
                                    }
                                    $stmt_update_notifs->close();
                                }
                            } else {
                                error_log("Error executing stock notifications query: " . $stmt_notifs->error);
                            }
                        } else {
                            error_log("Error preparing stock notifications query: " . $conn->error);
                        }
                    }
                    $_SESSION['success_message'] = "Product updated successfully!";
                    header("Location: products.php");
                    exit();
                } else {
                    error_log("Error executing product update query: " . $stmt_update->error);
                    $edit_product_error = 'Something went wrong. Please try again later.';
                }
                $stmt_update->close();
            } else {
                error_log("Error preparing product update query: " . $conn->error);
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



    <?php if ($product_data): ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $product_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Product Name -->
            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Product Name <span class="text-gray-500 text-xs">(optional)</span></label>
                <input type="text" id="name" name="name"
                       value="<?php echo htmlspecialchars($product_data['name']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"
                       placeholder="Optional product name">
            </div>

            <!-- Price -->
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Price (R) <span class="text-gray-500 text-xs">(required)</span></label>
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
                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category <span class="text-gray-500 text-xs">(optional)</span></label>
                <select id="category" name="category"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="">No category (optional)</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($product_data['category'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Stock -->
            <div>
                <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity <span class="text-gray-500 text-xs">(required)</span></label>
                <input type="number" id="stock" name="stock" min="0" required
                       value="<?php echo htmlspecialchars($product_data['stock']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status <span class="text-gray-500 text-xs">(required)</span></label>
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

            <!-- Coming Soon Toggle -->
            <div class="flex items-center">
                <label class="flex items-center">
                    <input type="checkbox" id="is_coming_soon" name="is_coming_soon" value="1" <?php echo ($product_data['is_coming_soon'] == 1) ? 'checked' : ''; ?>
                           class="rounded border-gray-300 text-black focus:ring-black">
                    <span class="ml-2 text-sm font-medium text-gray-700">Coming Soon</span>
                </label>
            </div>

            <!-- Bestseller Toggle -->
            <div class="flex items-center">
                <label class="flex items-center">
                    <input type="checkbox" id="is_bestseller" name="is_bestseller" value="1" <?php echo ($product_data['is_bestseller'] == 1) ? 'checked' : ''; ?>
                           class="rounded border-gray-300 text-black focus:ring-black">
                    <span class="ml-2 text-sm font-medium text-gray-700">Bestseller</span>
                </label>
            </div>

            <!-- New Toggle -->
            <div class="flex items-center">
                <label class="flex items-center">
                    <input type="checkbox" id="is_new" name="is_new" value="1" <?php echo ($product_data['is_new'] == 1) ? 'checked' : ''; ?>
                           class="rounded border-gray-300 text-black focus:ring-black">
                    <span class="ml-2 text-sm font-medium text-gray-700">New</span>
                </label>
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description <span class="text-gray-500 text-xs">(optional)</span></label>
            <textarea id="description" name="description" rows="4"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"
                      placeholder="Optional product description"><?php echo htmlspecialchars($product_data['description']); ?></textarea>
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
                                         <img src="<?php echo SITE_URL . htmlspecialchars($product_data['image']); ?>" alt="Current Product Image"                         class="h-32 w-32 object-cover rounded-md border border-gray-300">
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
