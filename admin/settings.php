<?php
// Include the admin bootstrap for automatic setup
require_once 'bootstrap.php';
$conn = get_db_connection();

$settings = [];
$sql = "SELECT setting_key, setting_value FROM settings";
if ($stmt = $conn->prepare($sql)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
        header('Location: settings.php');
        exit;
    }

    $store_name = trim($_POST['store_name']);
    $store_email = trim($_POST['store_email']);
    $store_phone = trim($_POST['store_phone']);
    $store_address = trim($_POST['store_address']);
    $hero_buttons_enabled = isset($_POST['hero_buttons_enabled']) ? 1 : 0;

    // Input Validation
    if (empty($store_name) || empty($store_phone) || empty($store_address) || !filter_var($store_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['toast_message'] = ['message' => 'Please fill all required fields with valid data.', 'type' => 'error'];
        header('Location: settings.php');
        exit;
    }

    $settings_to_update = [
        'store_name' => $store_name,
        'store_email' => $store_email,
        'store_phone' => $store_phone,
        'store_address' => $store_address,
        'hero_buttons_enabled' => $hero_buttons_enabled,
    ];

    foreach ($settings_to_update as $key => $value) {
        $insert_sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param('sss', $key, $value, $value);

        if (!$stmt->execute()) {
            $_SESSION['toast_message'] = ['message' => 'Error saving settings.', 'type' => 'error'];
            $stmt->close();
            header('Location: settings.php');
            exit;
        }
        $stmt->close();
    }
    
    header('Location: settings.php');
    exit;
}

$pageTitle = 'Settings';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Store Settings</h2>
    <form action="settings.php" method="post" class="space-y-6">
        <?php generate_csrf_token_input(); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Store Name -->
            <div>
                <label for="store_name" class="block text-sm font-medium text-gray-700 mb-2">Store Name</label>
                <input type="text" id="store_name" name="store_name" value="<?php echo htmlspecialchars($settings['store_name'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Store Email -->
            <div>
                <label for="store_email" class="block text-sm font-medium text-gray-700 mb-2">Store Email</label>
                <input type="email" id="store_email" name="store_email" value="<?php echo htmlspecialchars($settings['store_email'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Store Phone -->
            <div>
                <label for="store_phone" class="block text-sm font-medium text-gray-700 mb-2">Store Phone</label>
                <input type="text" id="store_phone" name="store_phone" value="<?php echo htmlspecialchars($settings['store_phone'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>
        </div>

        <!-- Store Address -->
        <div>
            <label for="store_address" class="block text-sm font-medium text-gray-700 mb-2">Store Address</label>
            <textarea id="store_address" name="store_address" rows="4" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"><?php echo htmlspecialchars($settings['store_address'] ?? ''); ?></textarea>
        </div>

        <!-- Hero Button Settings -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Hero Slider Buttons</h3>

            <!-- Hero Button Enabled Checkbox -->
            <div class="flex items-center">
                <input type="checkbox" id="hero_buttons_enabled" name="hero_buttons_enabled" value="1"
                       <?php echo isset($settings['hero_buttons_enabled']) && $settings['hero_buttons_enabled'] == '1' ? 'checked' : ''; ?>
                       class="rounded border-gray-300 text-black focus:ring-black h-4 w-4">
                <div class="ml-3">
                    <label for="hero_buttons_enabled" class="text-sm font-medium text-gray-700">Enable Hero Slider Buttons</label>
                    <p class="text-sm text-gray-500">Allow hero slide buttons to appear at the bottom of the hero carousel</p>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800 transition-colors">Save All Settings</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
