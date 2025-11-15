<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

// Get announcement settings
$settings = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('announcement_text', 'announcement_url', 'announcement_enabled')";
if ($stmt = $conn->prepare($sql)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
}

// Get all pages for dropdown
$pages = get_pages_for_dropdown();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
        header('Location: announcement.php');
        exit;
    }

    $announcement_text = trim($_POST['announcement_text']);
    $announcement_url = trim($_POST['announcement_url']);
    $announcement_enabled = isset($_POST['announcement_enabled']) ? 1 : 0;

    $settings_to_update = [
        'announcement_text' => $announcement_text,
        'announcement_url' => $announcement_url,
        'announcement_enabled' => $announcement_enabled,
    ];

    foreach ($settings_to_update as $key => $value) {
        $insert_sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param('sss', $key, $value, $value);

        if (!$stmt->execute()) {
            $_SESSION['toast_message'] = ['message' => 'Error saving announcement settings.', 'type' => 'error'];
            $stmt->close();
            header('Location: announcement.php');
            exit;
        }
        $stmt->close();
    }

    $_SESSION['toast_message'] = ['message' => 'Announcement bar settings saved successfully!', 'type' => 'success'];
    header('Location: announcement.php');
    exit;
}

$pageTitle = 'Announcement Bar';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Announcement Bar Management</h2>
            <p class="text-sm text-gray-600 mt-1">Control the announcement bar that appears at the top of your website</p>
        </div>
        <a href="../index.php" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
            View Website
        </a>
    </div>

    <form action="announcement.php" method="post" class="space-y-6">
        <?php echo generate_csrf_token_input(); ?>

        <!-- Announcement Preview -->
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900 mb-3">Live Preview</h3>
            <div class="bg-black text-white text-center p-2.5 text-sm font-semibold tracking-wider uppercase rounded">
                <?php echo htmlspecialchars($settings['announcement_text'] ?? 'JOIN THE LIST & RECEIVE 10% OFF YOUR FIRST ORDER.'); ?>
            </div>
            <p class="text-xs text-gray-500 mt-2">This is how the announcement bar will appear on your website</p>
        </div>

        <!-- Announcement Enabled Checkbox -->
        <div class="flex items-center bg-white border border-gray-200 rounded-lg p-4">
            <input type="checkbox" id="announcement_enabled" name="announcement_enabled" value="1" class="rounded border-gray-300 text-black focus:ring-black h-4 w-4"
                   <?php echo isset($settings['announcement_enabled']) && $settings['announcement_enabled'] == '1' ? 'checked' : ''; ?>>
            <div class="ml-3">
                <label for="announcement_enabled" class="text-sm font-medium text-gray-700">Enable Announcement Bar</label>
                <p class="text-sm text-gray-500">Uncheck to hide the announcement bar from your website</p>
            </div>
        </div>

        <!-- Announcement Settings -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 space-y-6">
            <h3 class="text-lg font-medium text-gray-900">Announcement Settings</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Announcement Text -->
                <div class="md:col-span-2">
                    <label for="announcement_text" class="block text-sm font-medium text-gray-700 mb-2">Announcement Text</label>
                    <textarea id="announcement_text" name="announcement_text" rows="3" placeholder="Enter your announcement message here..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black resize-vertical"><?php echo htmlspecialchars($settings['announcement_text'] ?? 'Join The List & Receive 10% Off Your First Order.'); ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">The text that will be displayed in the announcement bar</p>
                </div>

                <!-- Announcement URL (using reusable component) -->
                <div class="md:col-span-2">
                    <label for="announcement_url" class="block text-sm font-medium text-gray-700 mb-2">Link Destination</label>
                    <?php
                    $current_url = $settings['announcement_url'] ?? '';
                    $input_id = 'announcement_url';
                    $input_name = 'announcement_url';
                    $default_mode = 'custom'; // Announcements often use anchor links
                    include '../includes/page_selector_component.php';
                    ?>
                </div>

                <!-- Current Status -->
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Status</label>
                    <div class="p-3 border border-gray-300 rounded-md bg-gray-50">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo (isset($settings['announcement_enabled']) && $settings['announcement_enabled'] == '1') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo (isset($settings['announcement_enabled']) && $settings['announcement_enabled'] == '1') ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4 pt-6 border-t">
            <a href="announcement.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                Reset
            </a>
            <button type="submit" class="px-6 py-2 bg-black text-white rounded-md hover:bg-black/80 transition-colors font-semibold">
                Save Announcement Settings
            </button>
        </div>
    </form>
</div>



<?php include 'footer.php'; ?>
