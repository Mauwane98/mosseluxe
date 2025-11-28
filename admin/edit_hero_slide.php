<?php
require_once 'bootstrap.php';
require_once '../includes/image_service.php';
$conn = get_db_connection();

// Get all pages for dropdown
$pages = get_pages_for_dropdown();

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['toast_message'] = ['message' => 'Invalid slide ID.', 'type' => 'error'];
    header("Location: manage_homepage.php");
    exit();
}

$id = (int)$_GET['id'];

// Fetch slide data
$sql = "SELECT * FROM hero_slides WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $slide = $result->fetch_assoc();
        } else {
            $_SESSION['toast_message'] = ['message' => 'Slide not found.', 'type' => 'error'];
            header("Location: manage_homepage.php");
            exit();
        }
    } else {
        $_SESSION['toast_message'] = ['message' => 'Database error.', 'type' => 'error'];
        header("Location: manage_homepage.php");
        exit();
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_slide'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
    } else {
        $title = trim($_POST['title']);
        $subtitle = trim($_POST['subtitle']);
        $button_text = trim($_POST['button_text']);
        $button_url = trim($_POST['button_url']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = (int)$_POST['sort_order'];
        $button_style = trim($_POST['button_style']);
        $button_visibility = isset($_POST['button_visibility']) ? 1 : 0;

        $image_path = $slide['image_url']; // Keep existing if no new image
        $upload_error = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $image_path = ImageService::processUpload($_FILES['image'], PRODUCT_IMAGE_DIR . 'hero/', 1920, 1080, $upload_error);
        }

        if (!$upload_error) {
            $sql = "UPDATE hero_slides SET title = ?, subtitle = ?, button_text = ?, button_url = ?, image_url = ?, is_active = ?, sort_order = ?, button_style = ?, button_visibility = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssssiissi', $title, $subtitle, $button_text, $button_url, $image_path, $is_active, $sort_order, $button_style, $button_visibility, $id);

            if ($stmt->execute()) {
                $_SESSION['toast_message'] = ['message' => 'Hero slide updated successfully!', 'type' => 'success'];
            } else {
                $_SESSION['toast_message'] = ['message' => 'Failed to update slide.', 'type' => 'error'];
            }
            $stmt->close();
        } else {
            $_SESSION['toast_message'] = ['message' => $upload_error, 'type' => 'error'];
        }
    }
    regenerate_csrf_token();
    header("Location: edit_hero_slide.php?id=$id");
    exit();
}

$pageTitle = 'Edit Hero Slide';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Hero Slide</h2>
        <a href="manage_homepage.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors">Back to Manage Homepage</a>
    </div>

    <form action="edit_hero_slide.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data" class="space-y-4">
        <?php echo generate_csrf_token_input(); ?>

        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-gray-500 text-xs">(optional)</span></label>
            <input type="text" id="title" name="title" placeholder="Enter slide title (optional)" value="<?php echo htmlspecialchars($slide['title']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
        </div>
        <div>
            <label for="subtitle" class="block text-sm font-medium text-gray-700">Subtitle <span class="text-gray-500 text-xs">(optional)</span></label>
            <input type="text" id="subtitle" name="subtitle" placeholder="Enter slide subtitle (optional)" value="<?php echo htmlspecialchars($slide['subtitle']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
        </div>
        <div>
            <label for="button_text" class="block text-sm font-medium text-gray-700">Button Text</label>
            <input type="text" id="button_text" name="button_text" value="<?php echo htmlspecialchars($slide['button_text']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
        </div>
        <div>
            <label for="button_url" class="block text-sm font-medium text-gray-700">Button URL</label>
            <?php
            $current_url = $slide['button_url'];
            $input_id = 'button_url';
            $input_name = 'button_url';
            $default_mode = 'pages'; // Hero slides often link to pages
            include '../includes/page_selector_component.php';
            ?>
        </div>
        <div>
            <label for="image" class="block text-sm font-medium text-gray-700">Update Image (leave empty to keep current)</label>
            <input type="file" id="image" name="image" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-black file:text-white hover:file:bg-gray-800">
        </div>
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($slide['sort_order']); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
        </div>
        <div>
            <label for="button_style" class="block text-sm font-medium text-gray-700">Button Style</label>
            <select id="button_style" name="button_style" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
                <option value="wide" <?php echo ($slide['button_style'] ?? 'wide') == 'wide' ? 'selected' : ''; ?>>Modern Long Horizontal</option>
                <option value="wider" <?php echo ($slide['button_style'] ?? 'wide') == 'wider' ? 'selected' : ''; ?>>Bold Pill Shape</option>
                <option value="widest" <?php echo ($slide['button_style'] ?? 'wide') == 'widest' ? 'selected' : ''; ?>>Rounded Rectangular</option>
                <option value="largest" <?php echo ($slide['button_style'] ?? 'wide') == 'largest' ? 'selected' : ''; ?>>Maximal Prominent</option>
            </select>
        </div>
        <div class="flex items-center">
            <input id="button_visibility" name="button_visibility" type="checkbox" <?php echo isset($slide['button_visibility']) && $slide['button_visibility'] ? 'checked' : ''; ?> class="h-4 w-4 text-black border-gray-300 rounded focus:ring-black">
            <label for="button_visibility" class="ml-2 block text-sm text-gray-900">Button Visible</label>
        </div>
        <div class="flex items-center">
            <input id="is_active" name="is_active" type="checkbox" <?php echo $slide['is_active'] ? 'checked' : ''; ?> class="h-4 w-4 text-black border-gray-300 rounded focus:ring-black">
            <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
        </div>

        <button type="submit" name="update_slide" class="w-full bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Update Slide</button>
    </form>
</div>



<?php include 'footer.php'; ?>
