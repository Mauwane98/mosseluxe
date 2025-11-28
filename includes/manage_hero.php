<?php
require_once 'bootstrap.php';
define('ABSPATH', dirname(__DIR__));
$conn = get_db_connection();
require_once '../includes/image_service.php';

$pageTitle = 'Manage Hero Carousel';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $_SESSION['error_message'] = 'Invalid CSRF token.';
        header('Location: manage_hero.php');
        exit;
    }

    // Add or Update a slide
    if (isset($_POST['save_slide'])) {
        $slide_id = isset($_POST['slide_id']) ? (int)$_POST['slide_id'] : 0;
        $title = trim($_POST['title']);
        $subtitle = trim($_POST['subtitle']);
        $button_text = trim($_POST['button_text']);
        $button_url = trim($_POST['button_url']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = (int)$_POST['sort_order'];
        $current_image = $_POST['current_image'] ?? '';

        // Handle image upload
        $image_path = $current_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_error = '';
            $new_image_path = ImageService::processUpload($_FILES['image'], 'assets/images/hero/', 1920, 1080, $upload_error);
            if ($new_image_path) {
                $image_path = $new_image_path;
                // Optionally delete old image if it exists and is different
                if (!empty($current_image) && $current_image !== $image_path && file_exists(ABSPATH . DIRECTORY_SEPARATOR . $current_image)) {
                    unlink(ABSPATH . DIRECTORY_SEPARATOR . $current_image);
                }
            } else {
                // On failure, store form data in session to repopulate the form
                $_SESSION['error_message'] = 'Image upload failed: ' . $upload_error;
                $_SESSION['form_data'] = $_POST;
                header('Location: manage_hero.php');
                exit;
            }
        }

        if ($slide_id > 0) { // Update existing slide
            $sql = "UPDATE hero_slides SET title=?, subtitle=?, button_text=?, button_url=?, image_url=?, is_active=?, sort_order=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssssiii', $title, $subtitle, $button_text, $button_url, $image_path, $is_active, $sort_order, $slide_id);
        } else { // Insert new slide
            $sql = "INSERT INTO hero_slides (title, subtitle, button_text, button_url, image_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssssii', $title, $subtitle, $button_text, $button_url, $image_path, $is_active, $sort_order);
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Hero slide saved successfully.';
        } else {
            $_SESSION['error_message'] = 'Error saving slide: ' . $stmt->error;
        }
        $stmt->close();
        header('Location: manage_hero.php');
        exit;
    }

    // Delete a slide
    if (isset($_POST['delete_slide'])) {
        $slide_id = (int)$_POST['slide_id'];
        // First, get the image path to delete the file
        $stmt_get = $conn->prepare("SELECT image_url FROM hero_slides WHERE id = ?");
        $stmt_get->bind_param('i', $slide_id);
        $stmt_get->execute();
        $result = $stmt_get->get_result();
        if ($slide = $result->fetch_assoc()) {
            if (!empty($slide['image_url']) && file_exists(ABSPATH . DIRECTORY_SEPARATOR . $slide['image_url'])) {
                unlink(ABSPATH . DIRECTORY_SEPARATOR . $slide['image_url']);
            }
        }
        $stmt_get->close();

        // Then delete the record
        $stmt_del = $conn->prepare("DELETE FROM hero_slides WHERE id = ?");
        $stmt_del->bind_param('i', $slide_id);
        if ($stmt_del->execute()) {
            $_SESSION['success_message'] = 'Slide deleted successfully.';
        } else {
            $_SESSION['error_message'] = 'Error deleting slide.';
        }
        $stmt_del->close();
        header('Location: manage_hero.php');
        exit;
    }
}

// Fetch all hero slides
$slides = [];
$sql = "SELECT * FROM hero_slides ORDER BY sort_order ASC";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $slides[] = $row;
    }
}

// Check for form data from a failed submission to repopulate the 'add' form
$form_data = $_SESSION['form_data'] ?? [];
if (!empty($form_data)) {
    // Clear it so it doesn't persist on subsequent page loads
    unset($_SESSION['form_data']);
}

include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Manage Hero Carousel</h2>
    <p class="text-gray-600 mb-6">Add, edit, reorder, and delete the slides that appear in your homepage's main hero carousel.</p>

    <!-- Add/Edit Slide Form (collapsible) -->
    <details class="mb-6 border border-gray-200 rounded-lg" <?php echo !empty($form_data) ? 'open' : ''; ?>>
        <summary class="p-4 cursor-pointer font-bold text-lg">Add New Slide</summary>
        <div class="p-6 border-t">
            <form action="manage_hero.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?php generate_csrf_token_input(); ?>
                <input type="hidden" name="slide_id" value="0">
                <input type="text" name="title" placeholder="Title (e.g., The Art of Luxe)" class="w-full p-2 border rounded" value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>">
                <input type="text" name="subtitle" placeholder="Subtitle (optional)" class="w-full p-2 border rounded" value="<?php echo htmlspecialchars($form_data['subtitle'] ?? ''); ?>">
                <input type="text" name="button_text" placeholder="Button Text (e.g., Shop Now)" class="w-full p-2 border rounded" value="<?php echo htmlspecialchars($form_data['button_text'] ?? ''); ?>">
                <input type="text" name="button_url" placeholder="Button URL (e.g., /shop.php)" class="w-full p-2 border rounded" value="<?php echo htmlspecialchars($form_data['button_url'] ?? ''); ?>">
                <div>
                    <label for="image" class="block text-sm font-medium">Image</label>
                    <input type="file" name="image" id="image" required class="w-full p-2 border rounded">
                </div>
                <div class="flex items-center gap-4">
                    <label>Order: <input type="number" name="sort_order" value="<?php echo htmlspecialchars($form_data['sort_order'] ?? '100'); ?>" class="w-20 p-2 border rounded"></label>
                    <label class="flex items-center"><input type="checkbox" name="is_active" value="1" <?php echo !empty($form_data) ? (isset($form_data['is_active']) ? 'checked' : '') : 'checked'; ?> class="mr-2"> Active</label>
                </div>
                <button type="submit" name="save_slide" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800">Add Slide</button>
            </form>
        </div>
    </details>

    <!-- Existing Slides -->
    <div class="space-y-4">
        <?php foreach ($slides as $slide): ?>
        <details class="border border-gray-200 rounded-lg">
            <summary class="p-4 cursor-pointer font-bold flex justify-between items-center">
                <span><?php echo htmlspecialchars($slide['title'] ?: 'Slide ' . $slide['id']); ?></span>
                <span class="text-sm font-normal <?php echo $slide['is_active'] ? 'text-green-600' : 'text-gray-500'; ?>"><?php echo $slide['is_active'] ? 'Active' : 'Inactive'; ?></span>
            </summary>
            <div class="p-6 border-t">
                <form action="manage_hero.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <?php generate_csrf_token_input(); ?>
                    <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($slide['image_url']); ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="title" value="<?php echo htmlspecialchars($slide['title']); ?>" placeholder="Title" class="w-full p-2 border rounded">
                        <input type="text" name="subtitle" value="<?php echo htmlspecialchars($slide['subtitle']); ?>" placeholder="Subtitle" class="w-full p-2 border rounded">
                        <input type="text" name="button_text" value="<?php echo htmlspecialchars($slide['button_text']); ?>" placeholder="Button Text" class="w-full p-2 border rounded">
                        <input type="text" name="button_url" value="<?php echo htmlspecialchars($slide['button_url']); ?>" placeholder="Button URL" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Image</label>
                        <input type="file" name="image" class="w-full p-2 border rounded mt-1">
                        <img src="../<?php echo htmlspecialchars($slide['image_url']); ?>" class="h-20 w-auto mt-2 rounded">
                    </div>
                    <div class="flex items-center gap-4">
                        <label>Order: <input type="number" name="sort_order" value="<?php echo $slide['sort_order']; ?>" class="w-20 p-2 border rounded"></label>
                        <label class="flex items-center"><input type="checkbox" name="is_active" value="1" <?php echo $slide['is_active'] ? 'checked' : ''; ?> class="mr-2"> Active</label>
                    </div>
                    <div class="flex justify-between items-center">
                        <button type="submit" name="save_slide" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800">Save Changes</button>
                        <button type="submit" name="delete_slide" class="text-red-600 hover:underline" onclick="return confirm('Are you sure you want to delete this slide?');">Delete Slide</button>
                    </div>
                </form>
            </div>
        </details>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'footer.php'; // No closing PHP tag - prevents accidental whitespace output