<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

// All POST logic should be at the top of the file, before any data is fetched for display.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
        header("Location: new_arrivals.php");
        exit();
    }

    // Handle settings update
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        $new_arrivals_title = trim($_POST['new_arrivals_title'] ?? 'New Arrivals');
        $new_arrivals_enabled = $_POST['new_arrivals_enabled'] ?? '1';
        $new_arrivals_limit = (int)($_POST['new_arrivals_limit'] ?? 8);
        $new_arrivals_message = trim($_POST['new_arrivals_message'] ?? '');
        $display_count = (int)($_POST['display_count'] ?? 4);

        // Update all settings
        $settings_to_update = [
            'new_arrivals_title' => $new_arrivals_title,
            'new_arrivals_enabled' => $new_arrivals_enabled,
            'new_arrivals_limit' => $new_arrivals_limit,
            'new_arrivals_message' => $new_arrivals_message,
            'new_arrivals_display_count' => $display_count
        ];

        $success = true;
        foreach ($settings_to_update as $key => $value) {
            $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $key, $value);
            if (!$stmt->execute()) {
                $success = false;
            }
            $stmt->close();
        }

        if ($success) {
            $_SESSION['toast_message'] = ['message' => 'Settings updated successfully!', 'type' => 'success'];
        } else {
            $_SESSION['toast_message'] = ['message' => 'Failed to update settings.', 'type' => 'error'];
        }
    }

    // Handle adding product to new arrivals
    if (isset($_POST['add_product'])) {
        $product_id = (int)$_POST['product_id'];
        $display_order = (int)$_POST['display_order'];
        $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : date('Y-m-d H:i:s');

        $sql = "INSERT INTO new_arrivals (product_id, display_order, release_date) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE display_order = VALUES(display_order), release_date = VALUES(release_date)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $product_id, $display_order, $release_date);

        if ($stmt->execute()) {
            $_SESSION['toast_message'] = ['message' => 'Product added to new arrivals!', 'type' => 'success'];
        } else {
            $_SESSION['toast_message'] = ['message' => 'Error adding product: ' . $stmt->error, 'type' => 'error'];
        }
        $stmt->close();
    }

    // Handle removing product from new arrivals
    if (isset($_POST['remove_product'])) {
        $product_id = (int)$_POST['product_id'];

        $sql = "DELETE FROM new_arrivals WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $product_id);

        if ($stmt->execute()) {
            $_SESSION['toast_message'] = ['message' => 'Product removed from new arrivals.', 'type' => 'success'];
        } else {
            $_SESSION['toast_message'] = ['message' => 'Error removing product: ' . $stmt->error, 'type' => 'error'];
        }
        $stmt->close();
    }

    // Regenerate token and redirect to prevent re-submission
    regenerate_csrf_token();
    header("Location: new_arrivals.php");
    exit();
}


// --- Data Fetching ---
$pageTitle = "Manage New Arrivals";
include 'header.php';

$new_arrivals_settings = [];
$all_products = [];
$featured_new_arrivals = [];

// Fetch settings
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('new_arrivals_message', 'new_arrivals_display_count')";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $new_arrivals_settings[$row['setting_key']] = $row['setting_value'];
}

// Fetch all products
$sql = "SELECT id, name, price, sale_price, image, status FROM products ORDER BY name ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $all_products[] = $row;
}

// Fetch currently featured new arrivals
$sql = "SELECT p.id, p.name, p.price, p.sale_price, p.image, na.display_order
        FROM products p
        JOIN new_arrivals na ON p.id = na.product_id
        ORDER BY na.display_order ASC, na.release_date DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $featured_new_arrivals[] = $row;
}

$featured_ids = array_column($featured_new_arrivals, 'id');
?>

<!-- Content start -->
<div class="space-y-6">
    <!-- Settings Section -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">New Arrivals Settings</h2>
        
        <form action="new_arrivals.php" method="post" class="space-y-6">
            <?php echo generate_csrf_token_input(); ?>
            <input type="hidden" name="action" value="update_settings">
            
            <div>
                <label for="new_arrivals_title" class="block text-sm font-medium text-gray-700 mb-2">
                    Section Title
                </label>
                <input type="text" name="new_arrivals_title" id="new_arrivals_title" value="<?php echo htmlspecialchars($new_arrivals_settings['new_arrivals_title'] ?? 'New Arrivals'); ?>" placeholder="New Arrivals" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
                <p class="mt-1 text-sm text-gray-500">This title appears on the homepage above the new arrivals products</p>
            </div>
            
            <div>
                <label for="new_arrivals_enabled" class="block text-sm font-medium text-gray-700 mb-2">
                    Enable New Arrivals Section
                </label>
                <select name="new_arrivals_enabled" id="new_arrivals_enabled" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
                    <option value="1" <?php echo (($new_arrivals_settings['new_arrivals_enabled'] ?? '1') === '1') ? 'selected' : ''; ?>>Enabled</option>
                    <option value="0" <?php echo (($new_arrivals_settings['new_arrivals_enabled'] ?? '1') === '0') ? 'selected' : ''; ?>>Disabled</option>
                </select>
            </div>
            
            <div>
                <label for="new_arrivals_limit" class="block text-sm font-medium text-gray-700 mb-2">
                    Number of Products to Display
                </label>
                <input type="number" name="new_arrivals_limit" id="new_arrivals_limit" value="<?php echo htmlspecialchars($new_arrivals_settings['new_arrivals_limit'] ?? '8'); ?>" min="1" max="20" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
            </div>
            
            <div>
                <label for="new_arrivals_message" class="block text-sm font-medium text-gray-700 mb-2">Custom Message (when no products available)</label>
                <textarea id="new_arrivals_message" name="new_arrivals_message" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black" placeholder="Enter a custom message..."><?php echo htmlspecialchars($new_arrivals_settings['new_arrivals_message'] ?? 'New arrivals will be available soon.'); ?></textarea>
                <p class="text-sm text-gray-500 mt-1">This message appears when no products are set as new arrivals.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Display Count -->
                <div>
                    <label for="display_count" class="block text-sm font-medium text-gray-700 mb-2">Number of Products to Display</label>
                    <select id="display_count" name="display_count" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (($new_arrivals_settings['new_arrivals_display_count'] ?? 4) == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?> product<?php echo $i > 1 ? 's' : ''; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">How many products to show in the new arrivals section on the homepage.</p>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Current New Arrivals -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Current New Arrivals</h2>
            <span class="text-sm text-gray-500"><?php echo count($featured_new_arrivals); ?> products featured</span>
        </div>

        <?php if (!empty($featured_new_arrivals)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-6">
                <?php foreach ($featured_new_arrivals as $product): ?>
                    <div class="border border-gray-200 rounded-lg p-4 flex flex-col">
                        <div class="aspect-w-1 aspect-h-1 bg-neutral-100 rounded-md overflow-hidden mb-3">
                            <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-contain p-2">
                        </div>
                        <h4 class="text-sm font-medium text-gray-900 truncate flex-grow"><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p class="text-sm text-gray-600 mt-1">
                            <?php if ($product['sale_price'] > 0): ?>
                                <span class="text-red-600">R<?php echo htmlspecialchars(number_format($product['sale_price'], 2)); ?></span>
                                <span class="line-through text-gray-400">R<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></span>
                            <?php else: ?>
                                R<?php echo htmlspecialchars(number_format($product['price'], 2)); ?>
                            <?php endif; ?>
                        </p>
                        <div class="flex items-center justify-between mt-3">
                            <span class="text-xs text-gray-500">Order: <?php echo $product['display_order']; ?></span>
                            <form action="new_arrivals.php" method="post" class="inline">
                                <?php echo generate_csrf_token_input(); ?>
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="remove_product" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Remove this product from new arrivals?');">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12 border-2 border-dashed border-gray-300 rounded-lg">
                <i class="fas fa-star text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No New Arrivals Set</h3>
                <p class="text-gray-500">Add products below to feature them in the new arrivals section.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Product to New Arrivals -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Add Product to New Arrivals</h2>

        <form action="new_arrivals.php" method="post" class="space-y-6">
            <?php echo generate_csrf_token_input(); ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Product Selection -->
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-2">Select Product</label>
                    <select id="product_id" name="product_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                        <option value="">Choose a product...</option>
                        <?php foreach ($all_products as $product): ?>
                            <?php if (!in_array($product['id'], $featured_ids)): ?>
                                <option value="<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> (<?php echo $product['status'] ? 'Active' : 'Draft'; ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Display Order -->
                <div>
                    <label for="display_order" class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                    <input type="number" id="display_order" name="display_order" min="1" max="99" value="<?php echo count($featured_new_arrivals) + 1; ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <p class="text-sm text-gray-500 mt-1">Lower numbers appear first.</p>
                </div>

                <!-- Release Date -->
                <div>
                    <label for="release_date" class="block text-sm font-medium text-gray-700 mb-2">Release Date (Optional)</label>
                    <input type="datetime-local" id="release_date" name="release_date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <p class="text-sm text-gray-500 mt-1">Leave empty for immediate release.</p>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" name="add_product" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800 transition-colors">
                    Add to New Arrivals
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>