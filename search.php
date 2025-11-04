<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/csrf.php';
$conn = get_db_connection();

$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$csrf_token = generate_csrf_token();
$products = [];
$total_items = 0;

// --- Pagination Logic ---
$items_per_page = 12;
$current_page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_HIGH) : 1;
$offset = ($current_page - 1) * $items_per_page;

if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";

    // --- Count total items for pagination ---
    $sql_count = "SELECT COUNT(id) FROM products WHERE (name LIKE ? OR description LIKE ?) AND status = 1";
    if ($stmt_count = $conn->prepare($sql_count)) {
        $stmt_count->bind_param("ss", $search_param, $search_param);
        $stmt_count->execute();
        $stmt_count->bind_result($total_items);
        $stmt_count->fetch();
        $stmt_count->close();
    }
    $total_pages = ceil($total_items / $items_per_page);

    // --- Fetch products for the current page ---
    $sql_search = "SELECT id, name, price, sale_price, image FROM products WHERE (name LIKE ? OR description LIKE ?) AND status = 1 LIMIT ? OFFSET ?";
    if ($stmt_search = $conn->prepare($sql_search)) {
        $stmt_search->bind_param("ssii", $search_param, $search_param, $items_per_page, $offset);
        $stmt_search->execute();
        $result = $stmt_search->get_result();
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt_search->close();
    }
}

?>

<div class="container my-5 bg-white-section rounded shadow-sm">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                Search Results for: <span class="gold-text">"<?php echo htmlspecialchars($search_query); ?>"</span>
            </h1>
            <p class="text-muted">
                <?php echo $total_items; ?> product(s) found.
            </p>

            <hr class="my-4">

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col">
                            <div class="card h-100 product-card text-white">
                                <?php if (isset($product['sale_price']) && $product['sale_price'] > 0): ?>
                                    <span class="badge bg-danger position-absolute top-0 end-0 m-2">SALE</span>
                                <?php endif; ?>
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </a>
                                <div class="card-body text-center">
                                    <h5 class="card-title" style="min-height: 48px;"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <div class="card-text gold-text mb-3">
                                        <?php if (isset($product['sale_price']) && $product['sale_price'] > 0): ?>
                                            <span class="text-muted text-decoration-line-through me-2">R <?php echo number_format($product['price'], 2); ?></span>
                                            <span>R <?php echo number_format($product['sale_price'], 2); ?></span>
                                        <?php else: ?>
                                            <span>R <?php echo number_format($product['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex justify-content-center align-items-center">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary-dark">View Details</a>
                                        <form action="cart.php" method="POST" class="d-inline ms-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <input type="hidden" name="action" value="add">
                                            <button type="submit" class="btn btn-sm btn-outline-dark" title="Add to Cart"><i class="bi bi-bag-plus"></i></button>
                                        </form>
                                        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                                            <form action="wishlist_actions.php" method="POST" class="d-inline ms-2">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="action" value="toggle">
                                                <button type="submit" class="btn btn-sm btn-outline-dark" title="Add to Wishlist"><i class="bi bi-heart"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">No products matched your search. Please try different keywords.</p>
                        <a href="shop.php" class="btn btn-outline-light mt-3">Back to Shop</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination Controls -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?query=<?php echo urlencode($search_query); ?>&page=<?php echo $current_page - 1; ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?query=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?query=<?php echo urlencode($search_query); ?>&page=<?php echo $current_page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>