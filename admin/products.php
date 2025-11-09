<?php
// Include the admin bootstrap for automatic setup
require_once 'bootstrap.php';

$pageTitle = "Manage Products";
include 'header.php';

// --- Data Fetching ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT p.id, p.name, p.price, p.sale_price, p.stock, p.status, p.image, p.is_featured, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category = c.id";
$count_sql = "SELECT COUNT(p.id) as total FROM products p";

$where_clauses = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_clauses[] = "p.name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= 's';
}
if (!empty($category_filter)) {
    $where_clauses[] = "p.category = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
    $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Get total product count for pagination
$total_products = 0;
if ($stmt_count = $conn->prepare($count_sql)) {
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_products = $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();
}
$total_pages = ceil($total_products / $limit);

// Fetch products for the current page
$sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$products = [];
if ($stmt = $conn->prepare($sql)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
}

// Fetch categories for the filter dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($result_categories = $conn->query($sql_categories)) {
    while ($row_category = $result_categories->fetch_assoc()) {
        $categories[] = $row_category;
    }
}

$conn->close();

$pageTitle = "Manage Products";

// Display any session messages
displaySuccessMessage();
displayErrorMessage();
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <!-- Header and Add Product Button -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">All Products</h2>
        <a href="add_product.php" class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Add New Product</a>
    </div>

    <!-- Filters -->
    <form action="products.php" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="md:col-span-1">
            <input type="text" name="search" placeholder="Search by product name..." value="<?php echo htmlspecialchars($search); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
        </div>
        <div class="md:col-span-1">
            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="w-full bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Filter</button>
        </div>
    </form>

    <!-- Bulk Actions -->
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center space-x-4">
            <label class="flex items-center">
                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-black focus:ring-black">
                <span class="ml-2 text-sm text-gray-700">Select All</span>
            </label>
            <div id="bulkActions" class="hidden space-x-2">
                <button id="bulkPublish" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">Publish Selected</button>
                <button id="bulkDraft" class="px-3 py-1 bg-gray-600 text-white text-sm rounded hover:bg-gray-700">Move to Draft</button>
                <button id="bulkDelete" class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">Delete Selected</button>
            </div>
        </div>
        <div class="text-sm text-gray-600">
            Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products
        </div>
    </div>

    <!-- Products Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        <input type="checkbox" class="rounded border-gray-300 text-black focus:ring-black">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Featured</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <tr class="product-row">
                            <td class="px-6 py-4">
                                <input type="checkbox" class="product-checkbox rounded border-gray-300 text-black focus:ring-black" value="<?php echo $product['id']; ?>">
                            </td>
                            <td class="px-6 py-4">
                                <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-12 w-12 object-cover rounded-md">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($product['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                R<?php echo number_format($product['sale_price'] > 0 ? $product['sale_price'] : $product['price'], 2); ?>
                                <?php if ($product['sale_price'] > 0): ?>
                                    <span class="text-xs text-red-600 line-through">R<?php echo number_format($product['price'], 2); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <span class="<?php echo $product['stock'] <= 5 ? 'text-red-600 font-bold' : ''; ?>">
                                    <?php echo htmlspecialchars($product['stock']); ?>
                                </span>
                                <?php if ($product['stock'] <= 5): ?>
                                    <span class="text-xs text-red-500">Low Stock!</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <!-- Status Toggle -->
                                <button data-id="<?php echo $product['id']; ?>" class="status-toggle <?php echo $product['status'] ? 'bg-green-500' : 'bg-gray-300'; ?> relative inline-flex items-center h-6 rounded-full w-11 transition-colors">
                                    <span class="<?php echo $product['status'] ? 'translate-x-6' : 'translate-x-1'; ?> inline-block w-4 h-4 transform bg-white rounded-full transition-transform"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <!-- Featured Toggle -->
                                <button data-id="<?php echo $product['id']; ?>" class="featured-toggle text-2xl <?php echo $product['is_featured'] ? 'text-yellow-400' : 'text-gray-300'; ?>">
                                    <i class="fa-solid fa-star"></i>
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-gray-500 py-6">No products found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>" 
                       class="<?php echo $page == $i ? 'z-10 bg-black text-white' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Status Toggle
    document.querySelectorAll('.status-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;
            const span = this.querySelector('span');

            fetch('ajax_toggle_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.new_status === 1) {
                        this.classList.remove('bg-gray-300');
                        this.classList.add('bg-green-500');
                        span.classList.remove('translate-x-1');
                        span.classList.add('translate-x-6');
                    } else {
                        this.classList.remove('bg-green-500');
                        this.classList.add('bg-gray-300');
                        span.classList.remove('translate-x-6');
                        span.classList.add('translate-x-1');
                    }
                } else {
                    alert('Failed to update status.');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Handle Featured Toggle
    document.querySelectorAll('.featured-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;

            fetch('ajax_toggle_featured.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.new_status === 1) {
                        this.classList.remove('text-gray-300');
                        this.classList.add('text-yellow-400');
                    } else {
                        this.classList.remove('text-yellow-400');
                        this.classList.add('text-gray-300');
                    }
                } else {
                    alert('Failed to update featured status.');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Bulk Actions Functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkActions = document.getElementById('bulkActions');

    // Handle select all checkbox
    selectAllCheckbox.addEventListener('change', function() {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActionsVisibility();
    });

    // Handle individual checkboxes
    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
            selectAllCheckbox.checked = checkedBoxes.length === productCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < productCheckboxes.length;
            updateBulkActionsVisibility();
        });
    });

    function updateBulkActionsVisibility() {
        const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
        if (checkedBoxes.length > 0) {
            bulkActions.classList.remove('hidden');
        } else {
            bulkActions.classList.add('hidden');
        }
    }

    // Bulk Publish
    document.getElementById('bulkPublish').addEventListener('click', function() {
        const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
        if (selectedIds.length === 0) return;

        if (confirm(`Are you sure you want to publish ${selectedIds.length} selected products?`)) {
            bulkUpdateStatus(selectedIds, 1);
        }
    });

    // Bulk Draft
    document.getElementById('bulkDraft').addEventListener('click', function() {
        const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
        if (selectedIds.length === 0) return;

        if (confirm(`Are you sure you want to move ${selectedIds.length} selected products to draft?`)) {
            bulkUpdateStatus(selectedIds, 0);
        }
    });

    // Bulk Delete
    document.getElementById('bulkDelete').addEventListener('click', function() {
        const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
        if (selectedIds.length === 0) return;

        if (confirm(`Are you sure you want to delete ${selectedIds.length} selected products? This action cannot be undone.`)) {
            bulkDeleteProducts(selectedIds);
        }
    });

    function bulkUpdateStatus(productIds, status) {
        fetch('ajax_bulk_update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ids=${productIds.join(',')}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully updated ${data.updated_count} products.`);
                location.reload();
            } else {
                alert('Failed to update products. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    function bulkDeleteProducts(productIds) {
        fetch('ajax_bulk_delete_products.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ids=${productIds.join(',')}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully deleted ${data.deleted_count} products.`);
                location.reload();
            } else {
                alert('Failed to delete products. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
});
</script>

<?php include 'footer.php'; ?>
