<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

require_once '../includes/image_service.php';

$csrf_token = generate_csrf_token();

$item_id = isset($_GET['id']) ? filter_var(trim($_GET['id']), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;

if (!$item_id) {
    $_SESSION['toast_message'] = ['message' => 'No item ID provided.', 'type' => 'error'];
    header("Location: launching_soon.php");
    exit();
}

// Fetch item details from the database
$stmt_fetch = $conn->prepare("SELECT id, name, image, status FROM launching_soon WHERE id = ?");
$stmt_fetch->bind_param("i", $item_id);
if (!$stmt_fetch->execute()) {
    $_SESSION['toast_message'] = ['message' => 'Failed to fetch item details.', 'type' => 'error'];
    header("Location: launching_soon.php");
    exit();
}
$result_fetch = $stmt_fetch->get_result();
$item_data = $result_fetch->fetch_assoc();
$stmt_fetch->close();

if (!$item_data) {
    $_SESSION['toast_message'] = ['message' => 'Item not found.', 'type' => 'error'];
    header("Location: launching_soon.php");
    exit();
}

// Handle form submission for updating item
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token. Please try again.', 'type' => 'error'];
        header("Location: edit_launching_soon.php?id=" . $item_id);
        exit();
    }

    $name = trim($_POST["name"]);
    $status = filter_var(trim($_POST["status"]), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);

    if (empty($name) || !isset($status) || !preg_match("/^[a-zA-Z0-9\s\-'’]+$/", $name)) {
        $_SESSION['toast_message'] = ['message' => 'Please fill all fields with valid data.', 'type' => 'error'];
        header("Location: edit_launching_soon.php?id=" . $item_id);
        exit();
    }

    $image_path = $item_data['image']; // Keep old image by default
    $upload_error = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        // Correctly call the ImageService
        $new_image_path = ImageService::processUpload($_FILES['image'], PRODUCT_IMAGE_DIR, PRODUCT_IMAGE_WIDTH, PRODUCT_IMAGE_HEIGHT, $upload_error);
        
        if ($new_image_path) {
            // Delete old image if a new one was successfully uploaded
            if (!empty($item_data['image']) && file_exists(ABSPATH . '/' . $item_data['image'])) {
                unlink(ABSPATH . '/' . $item_data['image']);
            }
            $image_path = $new_image_path;
        } else {
            // If upload fails, stay on the page and show the error
            $_SESSION['toast_message'] = ['message' => $upload_error ?: 'Failed to process new image.', 'type' => 'error'];
            header("Location: edit_launching_soon.php?id=" . $item_id);
            exit();
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
        $_SESSION['toast_message'] = ['message' => 'There was an error with the new image upload.', 'type' => 'error'];
        header("Location: edit_launching_soon.php?id=" . $item_id);
        exit();
    }

    // Prepare and execute the update statement
    $sql_update_item = "UPDATE launching_soon SET name = ?, image = ?, status = ? WHERE id = ?";
    if ($stmt_update = $conn->prepare($sql_update_item)) {
        $stmt_update->bind_param("ssii", $name, $image_path, $status, $item_id);

        if ($stmt_update->execute()) {
            $_SESSION['toast_message'] = ['message' => 'Item updated successfully!', 'type' => 'success'];
            regenerate_csrf_token();
            header("Location: launching_soon.php");
            exit();
        } else {
            $_SESSION['toast_message'] = ['message' => 'Database error: Failed to update item.', 'type' => 'error'];
        }
        $stmt_update->close();
    } else {
        $_SESSION['toast_message'] = ['message' => 'Error preparing database statement.', 'type' => 'error'];
    }
    
    header("Location: edit_launching_soon.php?id=" . $item_id);
    exit();
}

$pageTitle = 'Edit "Launching Soon" Item';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit "Launching Soon" Item</h2>
        <a href="launching_soon.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Back to Launching Soon</a>
    </div>



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
