<?php
$pageTitle = "Search Results - Mossé Luxe";
require_once 'includes/bootstrap.php'; // Changed from db_connect.php
$conn = get_db_connection();

// Get search parameters
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$price_min = isset($_GET['price_min']) && is_numeric($_GET['price_min']) ? (float)$_GET['price_min'] : '';
$price_max = isset($_GET['price_max']) && is_numeric($_GET['price_max']) ? (float)$_GET['price_max'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';

$results = [];
$total_results = 0;
$categories = [];
$suggestions = [];

// Fetch categories for filter dropdown
$categories_sql = "SELECT id, name FROM categories ORDER BY name ASC";
if ($categories_result = $conn->query($categories_sql)) {
    while ($cat_row = $categories_result->fetch_assoc()) {
        $categories[] = $cat_row;
    }
    $categories_result->close();
}

// Generate live search suggestions
if (!empty($query) && strlen($query) >= 1) {
    $suggestion_sql = "SELECT DISTINCT name, description
                      FROM products
                      WHERE status = 1
                      AND (name LIKE ? OR description LIKE ?)
                      ORDER BY name ASC
                      LIMIT 10";

    $suggestion_term = "%{$query}%";
    if ($suggestion_stmt = $conn->prepare($suggestion_sql)) {
        $suggestion_stmt->bind_param("ss", $suggestion_term, $suggestion_term);
        $suggestion_stmt->execute();
        $suggestion_result = $suggestion_stmt->get_result();

        while ($sugg_row = $suggestion_result->fetch_assoc()) {
            // Extract keywords from name and description
            $name_words = explode(' ', strtolower($sugg_row['name']));
            $desc_words = explode(' ', strtolower($sugg_row['description']));
            $all_words = array_unique(array_merge($name_words, $desc_words));

            foreach ($all_words as $word) {
                if (strlen($word) > 2 && strpos($word, strtolower($query)) !== false) {
                    $suggestions[] = $word;
                }
            }
        }
        $suggestions = array_unique(array_slice($suggestions, 0, 8));
        $suggestion_stmt->close();
    }
}

// Handle AJAX request for suggestions
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode(['suggestions' => $suggestions]);
    exit;
}

// Advanced search functionality
if (!empty($query) && strlen($query) >= 2) {
    $base_sql = "SELECT id, name, description, price, sale_price, image, category, is_featured, is_new, is_bestseller
                 FROM products
                 WHERE status = 1";

    $conditions = [];
    $params = [];
    $types = '';

    // Text search
    $conditions[] = "(name LIKE ? OR description LIKE ?)";
    $search_term = "%{$query}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';

    // Category filter
    if ($category_filter > 0) {
        $conditions[] = "category = ?";
        $params[] = $category_filter;
        $types .= 'i';
    }

    // Price filters
    if (!empty($price_min)) {
        $conditions[] = "price >= ?";
        $params[] = $price_min;
        $types .= 'd';
    }

    if (!empty($price_max)) {
        $conditions[] = "price <= ?";
        $params[] = $price_max;
        $types .= 'd';
    }

    $sql = $base_sql . " AND " . implode(" AND ", $conditions);

    // Sorting
    switch ($sort_by) {
        case 'price_low':
            $sql .= " ORDER BY price ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY price DESC";
            break;
        case 'name':
            $sql .= " ORDER BY name ASC";
            break;
        case 'newest':
            $sql .= " ORDER BY created_at DESC";
            break;
        case 'relevance':
        default:
            $sql .= " ORDER BY
                CASE
                    WHEN name LIKE ? THEN 1
                    WHEN description LIKE ? THEN 2
                    ELSE 3
                END,
                price ASC";
            $exact_match = "{$query}%";
            $params[] = $exact_match;
            $params[] = $exact_match;
            $types .= 'ss';
            break;
    }

    $sql .= " LIMIT 50";

    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }

        $total_results = count($results);
        $stmt->close();
    }
}

require_once 'includes/header.php';
?>

<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Search Results</h1>
            <?php if (!empty($query)): ?>
                <p class="mt-4 text-lg text-black/70">Found <?php echo $total_results; ?> result(s) for "<?php echo htmlspecialchars($query); ?>"</p>
            <?php endif; ?>
        </div>

        <!-- Advanced Search and Filters -->
        <div class="max-w-4xl mx-auto mb-12">
            <!-- Main Search -->
            <div class="mb-6">
                <form action="search.php" method="GET" class="relative">
                    <div class="flex gap-4">
                        <div class="flex-1 relative">
                            <input
                                type="search"
                                id="search-input"
                                name="q"
                                value="<?php echo htmlspecialchars($query); ?>"
                                placeholder="Search for products..."
                                class="w-full px-4 py-3 bg-white border border-black/50 rounded-md text-black placeholder-black/50 focus:outline-none focus:ring-2 focus:ring-black pr-12"
                                autocomplete="off"
                            >
                            <button
                                type="submit"
                                class="absolute right-2 top-1/2 -translate-y-1/2 bg-black text-white p-2 rounded-md hover:bg-black/80 transition-colors"
                                title="Search"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Live Search Suggestions -->
                    <div id="search-suggestions" class="absolute top-full left-0 right-0 bg-white border border-black/20 rounded-md shadow-lg z-50 mt-1 hidden max-h-64 overflow-y-auto">
                        <div id="suggestions-list" class="py-2"></div>
                    </div>

                    <!-- Advanced Filters (Collapsible) -->
                    <div class="mt-4 bg-gray-50 rounded-lg p-4">
                        <button type="button" id="toggle-filters" class="flex items-center gap-2 text-black font-semibold hover:text-gray-700 transition-colors">
                            <svg class="w-5 h-5 transform transition-transform" id="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                            </svg>
                            Advanced Filters
                        </button>

                        <div id="filters-content" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4 hidden">
                            <!-- Category Filter -->
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <select name="category" id="category" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                    <option value="0">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Price Range -->
                            <div>
                                <label for="price_min" class="block text-sm font-medium text-gray-700 mb-2">Min Price</label>
                                <input
                                    type="number"
                                    name="price_min"
                                    id="price_min"
                                    value="<?php echo htmlspecialchars($price_min); ?>"
                                    placeholder="0"
                                    min="0"
                                    step="0.01"
                                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"
                                >
                            </div>

                            <div>
                                <label for="price_max" class="block text-sm font-medium text-gray-700 mb-2">Max Price</label>
                                <input
                                    type="number"
                                    name="price_max"
                                    id="price_max"
                                    value="<?php echo htmlspecialchars($price_max); ?>"
                                    placeholder="No limit"
                                    min="0"
                                    step="0.01"
                                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"
                                >
                            </div>

                            <!-- Sort By -->
                            <div>
                                <label for="sort" class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                                <select name="sort" id="sort" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                                    <option value="relevance" <?php echo ($sort_by == 'relevance') ? 'selected' : ''; ?>>Relevance</option>
                                    <option value="price_low" <?php echo ($sort_by == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_high" <?php echo ($sort_by == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="name" <?php echo ($sort_by == 'name') ? 'selected' : ''; ?>>Name</option>
                                    <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest</option>
                                </select>
                            </div>
                        </div>

                        <!-- Apply Filters Button -->
                        <div class="mt-4 flex justify-center">
                            <button
                                type="submit"
                                class="bg-black text-white px-6 py-2 rounded-md font-semibold hover:bg-black/80 transition-colors"
                            >
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($query) && strlen($query) < 2): ?>
            <div class="text-center">
                <p class="text-lg text-black/70">Please enter at least 2 characters to search.</p>
            </div>
        <?php elseif (!empty($query) && empty($results)): ?>
            <div class="text-center">
                <p class="text-lg text-black/70 mb-8">No products found matching your search.</p>
                <div class="space-y-4">
                    <p class="text-sm text-black/60">Try:</p>
                    <ul class="text-sm text-black/60 space-y-1">
                        <li>• Checking your spelling</li>
                        <li>• Using different keywords</li>
                        <li>• Using fewer words</li>
                    </ul>
                </div>
                <div class="mt-8">
                    <a href="shop.php" class="text-lg font-semibold text-black border-b-2 border-black hover:border-transparent transition-colors">
                        Browse All Products
                    </a>
                </div>
            </div>
        <?php elseif (!empty($results)): ?>
            <!-- Search Results -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-8">
                <?php foreach ($results as $product): ?>
                    <div class="group">
                        <a href="<?php echo SITE_URL; ?>product/<?php echo $product['id']; ?>/<?php echo urlencode(str_replace(' ', '-', strtolower($product['name']))); ?>">
                            <div class="relative aspect-w-1 aspect-h-1 bg-neutral-100 rounded-md overflow-hidden border border-transparent group-hover:border-black/10 transition-colors">
                                <img
                                    src="<?php echo htmlspecialchars($product['image']); ?>"
                                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                                    class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-300"
                                    onerror="this.src='https://placehold.co/600x600/f1f1f1/000000?text=Mossé+Luxe'"
                                    loading="eager"
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
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>

<script>
// Live search suggestions
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const suggestionsContainer = document.getElementById('search-suggestions');
    const suggestionsList = document.getElementById('suggestions-list');
    const toggleFiltersBtn = document.getElementById('toggle-filters');
    const filtersContent = document.getElementById('filters-content');
    const filterIcon = document.getElementById('filter-icon');

    let searchTimeout;

    // Live search suggestions
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();

        clearTimeout(searchTimeout);

        if (query.length >= 1) {
            searchTimeout = setTimeout(() => {
                fetchSuggestions(query);
            }, 300);
        } else {
            suggestionsContainer.classList.add('hidden');
        }
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.classList.add('hidden');
        }
    });

    // Toggle filters
    toggleFiltersBtn.addEventListener('click', function() {
        const isHidden = filtersContent.classList.contains('hidden');

        if (isHidden) {
            filtersContent.classList.remove('hidden');
            filterIcon.style.transform = 'rotate(180deg)';
        } else {
            filtersContent.classList.add('hidden');
            filterIcon.style.transform = 'rotate(0deg)';
        }
    });

    // Fetch live suggestions
    function fetchSuggestions(query) {
        fetch(`search.php?q=${encodeURIComponent(query)}&ajax=1`)
            .then(response => response.json())
            .then(data => {
                displaySuggestions(data.suggestions);
            })
            .catch(error => {
                console.error('Error fetching suggestions:', error);
            });
    }

    // Display suggestions
    function displaySuggestions(suggestions) {
        if (!suggestions || suggestions.length === 0) {
            suggestionsContainer.classList.add('hidden');
            return;
        }

        suggestionsList.innerHTML = '';

        suggestions.forEach(suggestion => {
            const suggestionItem = document.createElement('div');
            suggestionItem.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer transition-colors';
            suggestionItem.textContent = suggestion;

            suggestionItem.addEventListener('click', function() {
                searchInput.value = suggestion;
                suggestionsContainer.classList.add('hidden');

                // Auto-submit search
                searchInput.form.submit();
            });

            suggestionsList.appendChild(suggestionItem);
        });

        suggestionsContainer.classList.remove('hidden');
    }
});
</script>
