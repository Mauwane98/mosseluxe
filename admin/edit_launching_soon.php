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
$edit_item_error = '';
$item_id = null;
$item_data = null;

// Check if item ID is provided in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $item_id = filter_var(trim($_GET['id']), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));

    // Fetch item details from the database
    $sql_fetch_item = "SELECT id, name, image, status FROM launching_soon WHERE id = ?";
    if ($stmt_fetch = $conn->prepare($sql_fetch_item)) {
        $stmt_fetch->bind_param("i", $param_id);
        $param_id = $item_id;

        if ($stmt_fetch->execute()) {
            $result_fetch = $stmt_fetch->get_result();
            if ($row_fetch = $result_fetch->fetch_assoc()) {
                $item_data = $row_fetch;
            } else {
                header("Location: launching_soon.php?error=item_not_found");
                exit();
            }
        } else {
            header("Location: launching_soon.php?error=fetch_failed");
            exit();
        }
        $stmt_fetch->close();
    } else {
        header("Location: launching_soon.php?error=prepare_failed");
        exit();
    }
} else {
    header("Location: launching_soon.php?error=no_id");
    exit();
}

// Handle form submission for updating item
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $edit_item_error = 'Invalid CSRF token. Please try again.';
    } else {
        // Sanitize and validate inputs
        $name = trim($_POST["name"]);
        $status = filter_var(trim($_POST["status"]), FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 1)));
        
        // New Image upload handling using ImageService
        $image_path = $item_data['image']; // Keep old image if new one is not uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $new_image_path = ImageService::processUpload($_FILES['image'], PRODUCT_IMAGE_WIDTH, PRODUCT_IMAGE_HEIGHT, $edit_item_error);
            if ($new_image_path) {
                // Delete old image if a new one was successfully uploaded and it's not the same as the old one
                if (!empty($item_data['image']) && $item_data['image'] !== $new_image_path && file_exists('../' . $item_data['image'])) {
                    unlink('../' . $item_data['image']);
                }
                $image_path = $new_image_path;
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            $edit_item_error = 'There was an error with the new image upload.';
        }

        if (empty($name) || !isset($status)) {
            $edit_item_error = 'Please fill out all required fields.';
        } elseif (!preg_match("/^[a-zA-Z0-9\s\-'’]+$/", $name)) {
            $edit_item_error = "Item name can only contain letters, numbers, spaces, hyphens, and apostrophes.";
        } else {
            // Prepare an update statement
            $sql_update_item = "UPDATE launching_soon SET name = ?, image = ?, status = ? WHERE id = ?";
            
            if ($stmt_update = $conn->prepare($sql_update_item)) {
                $stmt_update->bind_param("ssii", $param_name, $param_image, $param_status, $param_id);

                // Set parameters
                $param_name = $name;
                $param_image = $image_path;
                $param_status = $status;
                $param_id = $item_id;

                if ($stmt_update->execute()) {
                    header("Location: launching_soon.php?success=updated");
                    exit();
                } else {
                    $edit_item_error = 'Something went wrong. Please try again later.';
                }
                $stmt_update->close();
            } else {
                $edit_item_error = 'Error preparing statement. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Edit "Launching Soon" Item';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit "Launching Soon" Item</h2>
        <a href="launching_soon.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Back to Launching Soon</a>
    </div>

    <?php if (!empty($edit_item_error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $edit_item_error; ?>
        </div>
    <?php endif; ?>

    <?php if ($item_data): ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $item_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Item Name -->
            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Item Name</label>
                <input type="text" id="name" name="name" required
                       value="<?php echo htmlspecialchars($item_data['name']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                <p class="text-xs text-gray-500 mt-1">Only letters, numbers, spaces, hyphens, and apostrophes allowed</p>
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" name="status" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="1" <?php echo ($item_data['status'] == 1) ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo ($item_data['status'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <!-- Item ID (Read-only) -->
            <div>
                <label for="item_id" class="block text-sm font-medium text-gray-700 mb-2">Item ID</label>
                <input type="text" id="item_id" readonly
                       value="#<?php echo htmlspecialchars($item_data['id']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
            </div>
        </div>

        <!-- Image Upload -->
        <div>
            <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Item Image</label>
            <input type="file" id="image" name="image" accept="image/*"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            <p class="text-sm text-gray-500 mt-1">Upload a new image to replace the current one (JPG, PNG, WebP)</p>

            <?php if (!empty($item_data['image'])): ?>
                <div class="mt-3">
                    <p class="text-sm font-medium text-gray-700 mb-2">Current Image:</p>
                    <img src="../<?php echo htmlspecialchars($item_data['image']); ?>" alt="Current Item Image"
                         class="h-32 w-32 object-cover rounded-md border border-gray-300">
                </div>
            <?php endif; ?>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-4">
            <a href="launching_soon.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors">Update Item</button>
        </div>
    </form>
    <?php else: ?>
        <div class="text-center py-8">
            <p class="text-gray-500">Item data could not be loaded.</p>
            <a href="launching_soon.php" class="text-blue-600 hover:text-blue-800">Back to Launching Soon</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
