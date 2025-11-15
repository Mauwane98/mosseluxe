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
        header('Location: shop_settings.php');
        exit;
    }

    $shop_title = trim($_POST['shop_title']);
    $shop_h1_title = trim($_POST['shop_h1_title']);
    $shop_sub_title = trim($_POST['shop_sub_title']);

    // Input Validation
    if (empty($shop_title) || empty($shop_h1_title)) {
        $_SESSION['toast_message'] = ['message' => 'Please fill all required fields.', 'type' => 'error'];
        header('Location: shop_settings.php');
        exit;
    }

    $settings_to_update = [
        'shop_title' => $shop_title,
        'shop_h1_title' => $shop_h1_title,
        'shop_sub_title' => $shop_sub_title,
    ];

    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?), (?, ?), (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $_SESSION['toast_message'] = ['message' => 'Failed to prepare statement for updating settings.', 'type' => 'error'];
    } else {
        $params = [];
        foreach ($settings_to_update as $key => $value) {
            $params[] = $key;
            $params[] = $value;
        }

        $stmt->bind_param('ssssss', ...$params);

        if ($stmt->execute()) {
            $_SESSION['toast_message'] = ['message' => 'Shop settings saved successfully.', 'type' => 'success'];
            regenerate_csrf_token();
        } else {
            $_SESSION['toast_message'] = ['message' => 'Error saving shop settings.', 'type' => 'error'];
        }
        $stmt->close();
    }

    header('Location: shop_settings.php');
    exit;
}

$pageTitle = 'Shop Settings';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Shop Page Settings</h2>

    <form action="shop_settings.php" method="post" class="space-y-6">
        <?php generate_csrf_token_input(); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Shop Page Title -->
            <div>
                <label for="shop_title" class="block text-sm font-medium text-gray-700 mb-2">Shop Page Title</label>
                <input type="text" id="shop_title" name="shop_title" value="<?php echo htmlspecialchars($settings['shop_title'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <!-- Shop H1 Title -->
            <div>
                <label for="shop_h1_title" class="block text-sm font-medium text-gray-700 mb-2">Shop Page H1 Title</label>
                <input type="text" id="shop_h1_title" name="shop_h1_title" value="<?php echo htmlspecialchars($settings['shop_h1_title'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            </div>
        </div>

        <!-- Shop Sub Title -->
        <div>
            <label for="shop_sub_title" class="block text-sm font-medium text-gray-700 mb-2">Shop Page Sub Title</label>
            <textarea id="shop_sub_title" name="shop_sub_title" rows="3" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"><?php echo htmlspecialchars($settings['shop_sub_title'] ?? ''); ?></textarea>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800 transition-colors">Save Shop Settings</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
