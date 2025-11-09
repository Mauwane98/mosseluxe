<?php
// Include the admin bootstrap for automatic setup
require_once 'bootstrap.php';

$settings = [];
$sql = "SELECT * FROM settings";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        die('CSRF token validation failed.');
    }

    $store_name = $_POST['store_name'];
    $store_email = $_POST['store_email'];
    $store_phone = $_POST['store_phone'];
    $store_address = $_POST['store_address'];

    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?";
    $stmt = $conn->prepare($sql);

    $stmt->bind_param('sss', $key, $value, $value);

    $key = 'store_name';
    $value = $store_name;
    $stmt->execute();

    $key = 'store_email';
    $value = $store_email;
    $stmt->execute();

    $key = 'store_phone';
    $value = $store_phone;
    $stmt->execute();

    $key = 'store_address';
    $value = $store_address;
    $stmt->execute();

    $stmt->close();

    header('Location: settings.php?success=1');
    exit;
}

$pageTitle = 'Settings';
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Store Settings</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            Settings saved successfully.
        </div>
    <?php endif; ?>

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

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800 transition-colors">Save Settings</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
