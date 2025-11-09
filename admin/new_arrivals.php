<?php
// Include the admin bootstrap for automatic setup
require_once 'bootstrap.php';

// Include CSRF protection
require_once '../includes/csrf.php';

$pageTitle = "Manage New Arrivals";
include 'header.php';

// --- Data Fetching ---
$new_arrivals_settings = [];
$all_products = [];
$featured_new_arrivals = [];

// Simple database queries without complex error handling
$sql = "SELECT * FROM settings WHERE setting_key IN ('new_arrivals_message', 'new_arrivals_display_count')";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $new_arrivals_settings[$row['setting_key']] = $row['setting_value'];
    }
}

$sql = "SELECT id, name, price, sale_price, image, status, is_featured, created_at FROM products ORDER BY name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_products[] = $row;
    }
}

$sql = "SELECT p.id, p.name, p.price, p.sale_price, p.image, p.status, p.is_featured, p.created_at, na.display_order, na.release_date
        FROM products p
        JOIN new_arrivals na ON p.id = na.product_id
        ORDER BY na.display_order ASC, na.release_date DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $featured_new_arrivals[] = $row;
    }
}

$conn->close();

// Create a fresh database connection for form handling
function get_fresh_db_connection() {
    require_once '../includes/config.php';
    return new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
}

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug form submission
    echo "<!-- FORM SUBMISSION DEBUG -->";
    echo "<!-- POST data: " . print_r($_POST, true) . " -->";

    // CSRF validation
    if (!verify_csrf_token()) {
        $error = 'Invalid CSRF token.';
        echo "<!-- CSRF validation failed -->";
    } else {
        echo "<!-- CSRF validation passed -->";

        // Handle settings update
        if (isset($_POST['update_settings'])) {
            $new_arrivals_message = trim($_POST['new_arrivals_message']);
            $display_count = (int)$_POST['display_count'];

            // Update settings
            $db_conn = get_fresh_db_connection();
            if (!$db_conn || $db_conn->connect_error) {
                $error = "Database connection failed.";
            } else {
                $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?";
                $stmt = $db_conn->prepare($sql);

                if (!$stmt) {
                    $error = "Failed to prepare statement: " . $db_conn->error;
                } else {
                    $stmt->bind_param('sss', $key, $value, $value);

                    $key = 'new_arrivals_message';
                    $value = $new_arrivals_message;
                    if (!$stmt->execute()) {
                        $error = "Failed to update message: " . $stmt->error;
                    }

                    $key = 'new_arrivals_display_count';
                    $value = $display_count;
                    if (!$stmt->execute()) {
                        $error = "Failed to update display count: " . $stmt->error;
                    }

                    $stmt->close();
                }
                $db_conn->close();

                if (!isset($error)) {
                    $message = "Settings updated successfully!";
                    header("Location: new_arrivals.php?success=settings_updated");
                    exit();
                }
            }
        }

        // Handle adding product to new arrivals
        if (isset($_POST['add_product'])) {
            $product_id = (int)$_POST['product_id'];
            $display_order = (int)$_POST['display_order'];
            $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : date('Y-m-d H:i:s');

            $add_conn = get_fresh_db_connection();
            if (!$add_conn || $add_conn->connect_error) {
                $error = "Database connection failed.";
            } else {
                $sql = "INSERT INTO new_arrivals (product_id, display_order, release_date) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE display_order = ?, release_date = ?";
                $stmt = $add_conn->prepare($sql);
                $stmt->bind_param('iisss', $product_id, $display_order, $release_date, $display_order, $release_date);

                if ($stmt->execute()) {
                    $message = "Product added to new arrivals successfully!";
                    // Temporarily disable redirect to debug
                    // header("Location: new_arrivals.php?success=product_added");
                    // exit();
                } else {
                    $error = "Error adding product: " . $stmt->error;
                }
                $stmt->close();
                $add_conn->close();
            }
        }

        // Handle removing product from new arrivals
        if (isset($_POST['remove_product'])) {
            $product_id = (int)$_POST['product_id'];

            $remove_conn = get_fresh_db_connection();
            if (!$remove_conn || $remove_conn->connect_error) {
                $error = "Database connection failed.";
            } else {
                $sql = "DELETE FROM new_arrivals WHERE product_id = ?";
                $stmt = $remove_conn->prepare($sql);
                $stmt->bind_param('i', $product_id);

                if ($stmt->execute()) {
                    $message = "Product removed from new arrivals successfully!";
                    header("Location: new_arrivals.php?success=product_removed");
                    exit();
                } else {
                    $error = "Error removing product: " . $stmt->error;
                }
                $stmt->close();
                $remove_conn->close();
            }
        }
    }
}

// Check for success/error messages from redirects
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'settings_updated') {
        $message = "Settings updated successfully!";
    } elseif ($_GET['success'] == 'product_added') {
        $message = "Product added to new arrivals successfully!";
    } elseif ($_GET['success'] == 'product_removed') {
        $message = "Product removed from new arrivals successfully!";
    }
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] == 'settings_failed') {
        $error = "Error updating settings. Please try again.";
    }
}

// Display any session messages
displaySuccessMessage();
displayErrorMessage();
?>

<!-- Content start -->
<div class="space-y-6">
    <!-- Settings Section -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">New Arrivals Settings</h2>
        <p class="text-sm text-gray-600 mb-4">Page loaded successfully at: <?php echo date('H:i:s'); ?></p>

        <?php if(!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="new_arrivals.php" method="post" class="space-y-6">
            <?php generate_csrf_token_input(); ?>

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
                    <p class="text-sm text-gray-500 mt-1">How many products to show in the new arrivals section (default: 4)</p>
                </div>
            </div>

            <!-- Custom Message -->
            <div>
                <label for="new_arrivals_message" class="block text-sm font-medium text-gray-700 mb-2">Custom Message (when no products available)</label>
                <textarea id="new_arrivals_message" name="new_arrivals_message" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black" placeholder="Enter a custom message to display when no new arrivals are available..."><?php echo htmlspecialchars($new_arrivals_settings['new_arrivals_message'] ?? 'New arrivals will be available soon. Please check back later.'); ?></textarea>
                <p class="text-sm text-gray-500 mt-1">This message appears when no products are set as new arrivals</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" name="update_settings" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800 transition-colors">
                    Update Settings
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
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="aspect-w-1 aspect-h-1 bg-neutral-100 rounded-md overflow-hidden mb-3">
                            <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-contain p-2">
                        </div>
                        <h4 class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p class="text-sm text-gray-600 mt-1">
                            <?php if ($product['sale_price'] > 0): ?>
                                <span class="text-red-600">R<?php echo number_format($product['sale_price'], 2); ?></span>
                                <span class="line-through text-gray-400">R<?php echo number_format($product['price'], 2); ?></span>
                            <?php else: ?>
                                R<?php echo number_format($product['price'], 2); ?>
                            <?php endif; ?>
                        </p>
                        <div class="flex items-center justify-between mt-3">
                            <span class="text-xs text-gray-500">Order: <?php echo $product['display_order']; ?></span>
                            <form action="new_arrivals.php" method="post" class="inline">
                                <?php generate_csrf_token_input(); ?>
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
            <div class="text-center py-12">
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
            <?php generate_csrf_token_input(); ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Product Selection -->
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-2">Select Product</label>
                    <select id="product_id" name="product_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                        <option value="">Choose a product...</option>
                        <?php
                        // Create array of featured product IDs for compatibility
                        $featured_ids = array();
                        foreach ($featured_new_arrivals as $featured) {
                            $featured_ids[] = $featured['id'];
                        }
                        ?>
                        <?php foreach ($all_products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo in_array($product['id'], $featured_ids) ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($product['name']); ?> (<?php echo $product['status'] ? 'Active' : 'Draft'; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Display Order -->
                <div>
                    <label for="display_order" class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                    <input type="number" id="display_order" name="display_order" min="1" max="99" value="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <p class="text-sm text-gray-500 mt-1">Lower numbers appear first</p>
                </div>

                <!-- Release Date -->
                <div>
                    <label for="release_date" class="block text-sm font-medium text-gray-700 mb-2">Release Date (Optional)</label>
                    <input type="datetime-local" id="release_date" name="release_date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                    <p class="text-sm text-gray-500 mt-1">Leave empty for immediate release</p>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" name="add_product" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800 transition-colors">
                    Add to New Arrivals
                </button>
            </div>
        </form>
    </div>

    <!-- Available Products -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Available Products</h2>
        <p class="text-sm text-gray-600 mb-4">Products not currently featured in new arrivals:</p>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    // Use the same featured_ids array we created earlier for compatibility
                    $available_products = array_filter($all_products, function($product) use ($featured_ids) {
                        return !in_array($product['id'], $featured_ids);
                    });
                    ?>

                    <?php if (!empty($available_products)): ?>
                        <?php foreach ($available_products as $product): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-12 w-12 object-cover rounded-md">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php if ($product['sale_price'] > 0): ?>
                                        <span class="text-red-600">R<?php echo number_format($product['sale_price'], 2); ?></span>
                                        <span class="line-through text-gray-400 ml-1">R<?php echo number_format($product['price'], 2); ?></span>
                                    <?php else: ?>
                                        R<?php echo number_format($product['price'], 2); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $product['status'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $product['status'] ? 'Active' : 'Draft'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <form action="new_arrivals.php" method="post" class="inline">
                                        <?php generate_csrf_token_input(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="display_order" value="<?php echo count($featured_new_arrivals) + 1; ?>">
                                        <button type="submit" name="add_product" class="text-indigo-600 hover:text-indigo-900">
                                            Add to New Arrivals
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 py-6">All products are already featured in new arrivals.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
echo "<!-- Footer include attempt -->";
include 'footer.php';
echo "<!-- Page end -->";
?>
