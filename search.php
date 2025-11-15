<?php
$pageTitle = "Search Results - Mossé Luxe";
require_once 'includes/bootstrap.php'; // Changed from db_connect.php
$conn = get_db_connection();
require_once 'includes/header.php';

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];
$total_results = 0;

// Search functionality
if (!empty($query) && strlen($query) >= 2) {
    // Search in products table
    $sql = "SELECT id, name, description, price, sale_price, image, category
            FROM products
            WHERE status = 1
            AND (name LIKE ? OR description LIKE ?)
            ORDER BY
                CASE
                    WHEN name LIKE ? THEN 1
                    WHEN description LIKE ? THEN 2
                    ELSE 3
                END,
                name ASC
            LIMIT 50";

    $search_term = "%{$query}%";
    $exact_match = "%{$query}%";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssss", $search_term, $search_term, $exact_match, $exact_match);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }

        $total_results = count($results);
        $stmt->close();
    }
}
}
?>

<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Search Results</h1>
            <?php if (!empty($query)): ?>
                <p class="mt-4 text-lg text-black/70">Found <?php echo $total_results; ?> result(s) for "<?php echo htmlspecialchars($query); ?>"</p>
            <?php endif; ?>
        </div>

        <!-- Search Form -->
        <div class="max-w-2xl mx-auto mb-12">
            <form action="search.php" method="GET" class="flex gap-4">
                <input
                    type="search"
                    name="q"
                    value="<?php echo htmlspecialchars($query); ?>"
                    placeholder="Search for products..."
                    required
                    minlength="2"
                    class="flex-1 px-4 py-3 bg-white border border-black/50 rounded-md text-black placeholder-black/50 focus:outline-none focus:ring-2 focus:ring-black"
                >
                <button
                    type="submit"
                    class="bg-black text-white py-3 px-8 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider"
                >
                    Search
                </button>
            </form>
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
