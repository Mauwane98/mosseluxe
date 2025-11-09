<?php
$pageTitle = "Shop - Mossé Luxe";
require_once 'includes/db_connect.php';
$conn = get_db_connection();
require_once 'includes/header.php'; // Now include header after all PHP logic

$products = [];
$categories = [];

// Fetch categories for the filter dropdown
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($result_categories = $conn->query($sql_categories)) {
    while ($row_category = $result_categories->fetch_assoc()) {
        $categories[] = $row_category;
    }
    $result_categories->free();
}

// Get filter and sort parameters
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest'; // Default sort

// Pagination
$limit = 8; // Number of products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build SQL query for counting total products
$count_sql = "SELECT COUNT(id) as total FROM products WHERE status = 1";
$sql = "SELECT id, name, price, sale_price, image FROM products WHERE status = 1";
$params = [];
$types = '';

if ($selected_category > 0) {
    $count_sql .= " AND category = ?";
    $sql .= " AND category = ?";
    $params[] = $selected_category;
    $types .= 'i';
}

// Get total number of products
$total_products = 0;
if ($stmt_count = $conn->prepare($count_sql)) {
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_products = $result_count->fetch_assoc()['total'];
    $stmt_count->close();
}

$total_pages = ceil($total_products / $limit);

switch ($sort_by) {
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY name DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY created_at DESC";
        break;
}

$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';


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
$conn->close();
?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">All Products</h1>
            <p class="mt-4 text-lg text-black/70 max-w-2xl mx-auto">Discover our curated collection of luxury streetwear, crafted with precision and passion.</p>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <form action="shop.php" method="GET" class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                <select name="category" id="category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block p-2.5">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $category_item): ?>
                        <option value="<?php echo $category_item['id']; ?>" <?php echo ($selected_category == $category_item['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category_item['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="sort_by" id="sort_by" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block p-2.5">
                    <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest Arrivals</option>
                    <option value="price_asc" <?php echo ($sort_by == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo ($sort_by == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="name_asc" <?php echo ($sort_by == 'name_asc') ? 'selected' : ''; ?>>Name: A-Z</option>
                    <option value="name_desc" <?php echo ($sort_by == 'name_desc') ? 'selected' : ''; ?>>Name: Z-A</option>
                </select>

                <button type="submit" class="bg-black text-white py-2.5 px-4 rounded-md text-sm font-semibold hover:bg-black/80 transition-colors">Apply Filters</button>
            </form>
        </div>

        <?php if (!empty($products)): ?>
            <!-- Product Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-8">
                <?php foreach ($products as $product): ?>
                    <div class="group">
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <div class="relative aspect-w-1 aspect-h-1 bg-neutral-100 rounded-md overflow-hidden border border-transparent group-hover:border-black/10 transition-colors">
                                <img 
                                    src="<?php echo htmlspecialchars($product['image']); ?>" 
                                    alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                    class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-300"
                                    onerror="this.src='https://placehold.co/600x600/f1f1f1/000000?text=Mossé+Luxe'"
                                >
                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 w-11/12">
                                    <button class="w-full bg-white/90 text-black text-sm font-bold uppercase py-2.5 rounded-md opacity-0 group-hover:opacity-100 transition-all duration-300 backdrop-blur-sm hover:bg-white quick-add-btn" data-product-id="<?php echo $product['id']; ?>">
                                        Quick Add
                                    </button>
                                </div>
                            </div>
                            <div class="mt-4 text-left">
                                <h4 class="text-sm text-black/60">Mossé Luxe</h4>
                                <h3 class="text-base md:text-lg font-bold truncate"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <?php if ($product['sale_price'] > 0): ?>
                                    <p class="text-md font-semibold mt-1"><span class="text-red-600">R <?php echo number_format($product['sale_price'], 2); ?></span> <span class="line-through text-black/50">R <?php echo number_format($product['price'], 2); ?></span></p>
                                <?php else: ?>
                                    <p class="text-md font-semibold mt-1">R <?php echo number_format($product['price'], 2); ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-12">
                    <nav aria-label="Page navigation">
                        <ul class="inline-flex items-center -space-x-px">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li>
                                    <a href="?page=<?php echo $i; ?>&category=<?php echo $selected_category; ?>&sort_by=<?php echo $sort_by; ?>" 
                                       class="px-3 py-2 leading-tight <?php echo $page == $i ? 'text-black bg-gray-200' : 'text-gray-500 bg-white'; ?> border border-gray-300 hover:bg-gray-100 hover:text-gray-700">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-center text-lg text-black/60">No products are available at the moment. Please check back soon.</p>
        <?php endif; ?>
    </div>
</main>
<?php
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.quick-add-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default button action
            const productId = this.dataset.productId;
            const quantity = 1; // Quick add always adds 1 item

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            fetch('ajax_cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    updateCartCountDisplay(); // Update cart count in header
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding to cart.');
            });
        });
    });
});
</script>
<?php
require_once 'includes/footer.php';
?>