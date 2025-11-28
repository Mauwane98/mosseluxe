<?php
require_once 'bootstrap.php';
require_once '../includes/image_service.php';
$conn = get_db_connection();

// Get all pages for dropdown
$pages = get_pages_for_dropdown();

// Handle Delete Slide
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['delete'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
    } else {
        $id = (int)$_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM hero_slides WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = ['message' => 'Hero slide deleted successfully!', 'type' => 'success'];
        } else {
            $_SESSION['toast_message'] = ['message' => 'Failed to delete slide.', 'type' => 'error'];
        }
        $stmt->close();
    }
    regenerate_csrf_token();
    header("Location: manage_homepage.php");
    exit();
}

// Handle Update Section
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_section'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
    } else {
        $section_key = trim($_POST['section_key']);
        $subtitle = trim($_POST['subtitle']);
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $button_text = trim($_POST['button_text']);
        $button_url = trim($_POST['button_url']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = (int)$_POST['sort_order'];

        $stmt = $conn->prepare("UPDATE homepage_sections SET subtitle = ?, title = ?, content = ?, button_text = ?, button_url = ?, is_active = ?, sort_order = ? WHERE section_key = ?");
        $stmt->bind_param("sssssiss", $subtitle, $title, $content, $button_text, $button_url, $is_active, $sort_order, $section_key);

        if ($stmt->execute()) {
            $_SESSION['toast_message'] = ['message' => 'Section updated successfully!', 'type' => 'success'];
        } else {
            $_SESSION['toast_message'] = ['message' => 'Failed to update section.', 'type' => 'error'];
        }
        $stmt->close();
    }
    regenerate_csrf_token();
    header("Location: manage_homepage.php");
    exit();
}

// Handle Add New Slide Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_slide'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
    } else {
        $title = trim($_POST['title']);
        $subtitle = trim($_POST['subtitle']);
        $button_text = trim($_POST['button_text']);
        $button_url = trim($_POST['button_url']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = (int)$_POST['sort_order'];
        
        $image_path = '';
        $upload_error = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $image_path = ImageService::processUpload($_FILES['image'], PRODUCT_IMAGE_DIR . 'hero/', 1920, 1080, $upload_error);
        } else {
            $upload_error = 'Image upload is required.';
        }

        if (!$upload_error && $image_path) {
            $sql = "INSERT INTO hero_slides (title, subtitle, button_text, button_url, image_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssssii', $title, $subtitle, $button_text, $button_url, $image_path, $is_active, $sort_order);

            if ($stmt->execute()) {
                $_SESSION['toast_message'] = ['message' => 'New hero slide added successfully!', 'type' => 'success'];
            } else {
                $_SESSION['toast_message'] = ['message' => 'Failed to add new slide.', 'type' => 'error'];
            }
            $stmt->close();
        } else {
            $_SESSION['toast_message'] = ['message' => $upload_error, 'type' => 'error'];
        }
    }
    regenerate_csrf_token();
    header("Location: manage_homepage.php");
    exit();
}

// Handle Add New Section
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_section'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
    } else {
        $section_key = trim($_POST['new_section_key']);
        $section_name = trim($_POST['new_section_name']);
        $subtitle = trim($_POST['new_subtitle'] ?? '');
        $title = trim($_POST['new_title']);
        $content = trim($_POST['new_content']);
        $button_text = trim($_POST['new_button_text']);
        $button_url = trim($_POST['new_button_url']);
        $is_active = isset($_POST['new_is_active']) ? 1 : 0;
        $sort_order = (int)$_POST['new_sort_order'];

        $stmt = $conn->prepare("INSERT INTO homepage_sections (section_key, section_name, subtitle, title, content, button_text, button_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssii", $section_key, $section_name, $subtitle, $title, $content, $button_text, $button_url, $is_active, $sort_order);

        if ($stmt->execute()) {
            $_SESSION['toast_message'] = ['message' => 'New homepage section added successfully!', 'type' => 'success'];
        } else {
            $_SESSION['toast_message'] = ['message' => 'Failed to add new section.', 'type' => 'error'];
        }
        $stmt->close();
    }
    regenerate_csrf_token();
    header("Location: manage_homepage.php");
    exit();
}

// --- Data Fetching ---
$pageTitle = 'Manage Hero Carousel';
include 'header.php';

$slides = [];
$sql = "SELECT * FROM hero_slides ORDER BY sort_order ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $slides[] = $row;
}
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Add New Slide Form -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Add New Hero Slide</h3>
        <form action="manage_homepage.php" method="post" enctype="multipart/form-data" class="space-y-4">
            <?php echo generate_csrf_token_input(); ?>

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-gray-500 text-xs">(optional)</span></label>
                <input type="text" id="title" name="title" placeholder="Enter slide title (optional)" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
            </div>
            <div>
                <label for="subtitle" class="block text-sm font-medium text-gray-700">Subtitle <span class="text-gray-500 text-xs">(optional)</span></label>
                <input type="text" id="subtitle" name="subtitle" placeholder="Enter slide subtitle (optional)" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
            </div>
            <div>
                <label for="button_text" class="block text-sm font-medium text-gray-700">Button Text</label>
                <input type="text" id="button_text" name="button_text" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
            </div>
            <div>
                <label for="button_url" class="block text-sm font-medium text-gray-700">Button URL</label>
                <?php
                $current_url = '';
                $input_id = 'button_url';
                $input_name = 'button_url';
                $default_mode = 'pages'; // Hero slides often link to pages
                include '../includes/page_selector_component.php';
                ?>
            </div>
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700">Image</label>
                <input type="file" id="image" name="image" required accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-black file:text-white hover:file:bg-gray-800">
            </div>
            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700">Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" value="<?php echo count($slides) + 1; ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
            </div>
            <div class="flex items-center">
                <input id="is_active" name="is_active" type="checkbox" checked class="h-4 w-4 text-black border-gray-300 rounded focus:ring-black">
                <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
            </div>
            
            <button type="submit" name="add_slide" class="w-full bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Add Slide</button>
        </form>
    </div>

    <!-- Existing Slides List -->
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Existing Hero Slides</h3>
        <div class="space-y-4">
            <?php if (!empty($slides)): ?>
                <?php foreach ($slides as $slide): ?>
                    <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                        <img src="../<?php echo htmlspecialchars($slide['image_url']); ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>" class="h-20 w-32 object-cover rounded-md mr-4">
                        <div class="flex-grow">
                            <h4 class="font-bold text-gray-900"><?php echo htmlspecialchars($slide['title']); ?></h4>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                            <span class="text-xs px-2 py-1 rounded-full <?php echo $slide['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo $slide['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <span class="text-xs text-gray-500 ml-2">Order: <?php echo $slide['sort_order']; ?></span>
                        </div>
                        <div class="flex-shrink-0 space-x-4">
                            <a href="edit_hero_slide.php?id=<?php echo $slide['id']; ?>" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</a>
                            <form action="manage_homepage.php?delete=<?php echo $slide['id']; ?>" method="post" class="inline">
                                <?php echo generate_csrf_token_input(); ?>
                                <button type="submit" onclick="return confirm('Are you sure you want to delete this slide?');" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-500">No hero slides found. Add one using the form.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow-md mt-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Edit Homepage Sections</h3>
    <div class="space-y-6">
        <?php
        $homepage_sections_sql = "SELECT * FROM homepage_sections WHERE section_key NOT IN ('hero_carousel') ORDER BY sort_order ASC";
        $homepage_sections_result = $conn->query($homepage_sections_sql);
        while ($section = $homepage_sections_result->fetch_assoc()) {
        ?>
        <div class="border-t pt-4 first:border-t-0 first:pt-0">
            <h4 class="font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($section['section_name']); ?> (<?php echo htmlspecialchars($section['section_key']); ?>)</h4>
            <form action="manage_homepage.php" method="post" class="space-y-4">
                <?php echo generate_csrf_token_input(); ?>
                <input type="hidden" name="section_key" value="<?php echo htmlspecialchars($section['section_key']); ?>">
                <div>
                    <label for="subtitle_<?php echo $section['section_key']; ?>" class="block text-sm font-medium text-gray-700">Subtitle</label>
                    <input type="text" id="subtitle_<?php echo $section['section_key']; ?>" name="subtitle" value="<?php echo htmlspecialchars($section['subtitle'] ?? ''); ?>" placeholder="Optional subtitle" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
                </div>
                <div>
                    <label for="title_<?php echo $section['section_key']; ?>" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" id="title_<?php echo $section['section_key']; ?>" name="title" value="<?php echo htmlspecialchars($section['title']); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
                </div>
                <div>
                    <label for="content_<?php echo $section['section_key']; ?>" class="block text-sm font-medium text-gray-700">Content</label>
                    <textarea id="content_<?php echo $section['section_key']; ?>" name="content" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black"><?php echo htmlspecialchars($section['content']); ?></textarea>
                </div>
                <div>
                    <label for="button_text_<?php echo $section['section_key']; ?>" class="block text-sm font-medium text-gray-700">Button Text</label>
                    <input type="text" id="button_text_<?php echo $section['section_key']; ?>" name="button_text" value="<?php echo htmlspecialchars($section['button_text']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
                </div>
                <div>
                    <label for="button_url_<?php echo $section['section_key']; ?>" class="block text-sm font-medium text-gray-700">Button URL</label>
                    <?php
                    $current_url = $section['button_url'];
                    $input_id = 'button_url_' . $section['section_key'];
                    $input_name = 'button_url';
                    $default_mode = 'pages'; // Homepage sections often link to pages
                    include '../includes/page_selector_component.php';
                    ?>
                </div>
                <div class="flex items-center">
                    <input id="is_active_<?php echo $section['section_key']; ?>" name="is_active" type="checkbox" <?php echo $section['is_active'] ? 'checked' : ''; ?> class="h-4 w-4 text-black border-gray-300 rounded focus:ring-black">
                    <label for="is_active_<?php echo $section['section_key']; ?>" class="ml-2 block text-sm text-gray-900">Active</label>
                </div>
                <div>
                    <label for="sort_order_<?php echo $section['section_key']; ?>" class="block text-sm font-medium text-gray-700">Sort Order</label>
                    <input type="number" id="sort_order_<?php echo $section['section_key']; ?>" name="sort_order" value="<?php echo htmlspecialchars($section['sort_order']); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
                </div>
                <button type="submit" name="update_section" class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Update <?php echo htmlspecialchars($section['section_name']); ?></button>
            </form>
        </div>
        <?php } ?>

        <!-- Add New Section Form -->
        <div class="border-t pt-4">
            <h4 class="font-semibold text-gray-900 mb-2">Add New Homepage Section</h4>
            <form action="manage_homepage.php" method="post" class="space-y-4">
                <?php echo generate_csrf_token_input(); ?>
                <div>
                    <label for="new_section_key" class="block text-sm font-medium text-gray-700">Section Key</label>
                    <input type="text" id="new_section_key" name="new_section_key" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black" placeholder="e.g., my_section">
                </div>
                <div>
                    <label for="new_section_name" class="block text-sm font-medium text-gray-700">Section Name</label>
                    <input type="text" id="new_section_name" name="new_section_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black" placeholder="e.g., My Custom Section">
                </div>
                <div>
                    <label for="new_subtitle" class="block text-sm font-medium text-gray-700">Subtitle (Optional)</label>
                    <input type="text" id="new_subtitle" name="new_subtitle" placeholder="Optional subtitle for the section" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
                </div>
                <div>
                    <label for="new_title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" id="new_title" name="new_title" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
                </div>
                <div>
                    <label for="new_content" class="block text-sm font-medium text-gray-700">Content</label>
                    <textarea id="new_content" name="new_content" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black"></textarea>
                </div>
                <div>
                    <label for="new_button_text" class="block text-sm font-medium text-gray-700">Button Text</label>
                    <input type="text" id="new_button_text" name="new_button_text" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
                </div>
                <div>
                    <label for="new_button_url" class="block text-sm font-medium text-gray-700">Button URL</label>
                    <?php
                    $current_url = '';
                    $input_id = 'new_button_url';
                    $input_name = 'new_button_url';
                    $default_mode = 'pages'; // New homepage sections often link to pages
                    include '../includes/page_selector_component.php';
                    ?>
                </div>
                <div>
                    <label for="new_sort_order" class="block text-sm font-medium text-gray-700">Sort Order</label>
                    <input type="number" id="new_sort_order" name="new_sort_order" value="50" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-black focus:border-black">
                </div>
                <div class="flex items-center">
                    <input id="new_is_active" name="new_is_active" type="checkbox" checked class="h-4 w-4 text-black border-gray-300 rounded focus:ring-black">
                    <label for="new_is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                </div>
                <button type="submit" name="add_section" class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Add New Section</button>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white max-w-md">
        <div class="mt-3 text-center">
            <div class="flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>

            <h3 class="text-lg font-medium text-gray-900 mb-3">Confirm Deletion</h3>
            <p class="text-sm text-gray-600 mb-5" id="deleteMessage"></p>

            <div class="flex justify-center space-x-4">
                <button onclick="closeDeleteModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>

                <form id="deleteForm" method="post" class="inline">
                    <input type="hidden" name="delete" id="deleteId">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                        Delete Slide
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
function confirmDelete(id, name, type) {
    const message = `Are you sure you want to delete the ${type} "${name}"?`;
    document.getElementById('deleteMessage').textContent = message;
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
</script>
