<?php
// Start session and include admin authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
$conn = get_db_connection();

// --- Filtering & Searching Logic ---
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

// Pagination variables
$items_per_page = 10;
$current_page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT) : 1;
$offset = ($current_page - 1) * $items_per_page;

$orders = [];
$where_clauses = [];
$params = [];
$types = '';

if (!empty($search_query)) {
    // Search by order ID (numeric) or customer name (string)
    if (is_numeric($search_query)) {
        $where_clauses[] = "o.id = ?";
        $params[] = $search_query;
        $types .= 'i';
    } else {
        $where_clauses[] = "u.name LIKE ?";
        $params[] = "%" . $search_query . "%";
        $types .= 's';
    }
}
if (!empty($filter_status)) {
    $where_clauses[] = "o.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$where_sql = count($where_clauses) > 0 ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Count total orders for pagination
$sql_count = "SELECT COUNT(o.id) AS total_orders FROM orders o LEFT JOIN users u ON o.user_id = u.id" . $where_sql;
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_orders = $stmt_count->get_result()->fetch_assoc()['total_orders'];
$stmt_count->close();
$total_pages = ceil($total_orders / $items_per_page);

// Build the main query for fetching orders
$sql_orders = "SELECT o.id, u.name AS customer_name, o.created_at, o.total_price, o.status 
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id" . $where_sql . " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$types .= 'i';
$params[] = $offset;
$types .= 'i';

if ($stmt = $conn->prepare($sql_orders)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    } else {
        // Handle error
    }
    $stmt->close();
} else {
    error_log("Error preparing orders query: " . $conn->error);
}

$active_page = 'orders';
$page_title = 'Manage Orders';
?>
<?php include '../includes/admin_page_header.php'; ?>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php
    include '../includes/admin_header.php'; 
    ?>

    <!-- Filter and Search Form -->
    <div class="card p-3 mb-4">
        <form action="orders.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="search_query" class="form-label">Search by Order ID or Customer Name</label>
                <input type="text" class="form-control" id="search_query" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <div class="col-md-4">
                <label for="filter_status" class="form-label">Filter by Status</label>
                <select class="form-select" id="filter_status" name="filter_status">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php if ($filter_status === 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Paid" <?php if ($filter_status === 'Paid') echo 'selected'; ?>>Paid</option>
                    <option value="Processing" <?php if ($filter_status === 'Processing') echo 'selected'; ?>>Processing</option>
                    <option value="Shipped" <?php if ($filter_status === 'Shipped') echo 'selected'; ?>>Shipped</option>
                    <option value="Delivered" <?php if ($filter_status === 'Delivered') echo 'selected'; ?>>Delivered</option>
                    <option value="Cancelled" <?php if ($filter_status === 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                    <option value="Failed" <?php if ($filter_status === 'Failed') echo 'selected'; ?>>Failed</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary-dark">Filter</button>
                <a href="orders.php" class="btn btn-outline-secondary ms-2">Clear</a>
                <a href="export_orders.php?search_query=<?php echo urlencode($search_query); ?>&filter_status=<?php echo urlencode($filter_status); ?>" class="btn btn-outline-success ms-2" target="_blank"><i class="bi bi-file-earmark-spreadsheet"></i> Export</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle text-dark">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#ML-<?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                            <td><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></td>
                            <td>R <?php echo number_format($order['total_price'], 2); ?></td>
                            <td>
                                <?php 
                                    $status_class = '';
                                    switch (strtolower($order['status'])) {
                                        case 'pending': $status_class = 'bg-warning text-dark'; break;
                                        case 'paid': $status_class = 'bg-success'; break;
                                        case 'processing': $status_class = 'bg-primary'; break;
                                        case 'shipped': $status_class = 'bg-info text-dark'; break;
                                        case 'delivered': $status_class = 'bg-secondary'; break;
                                        case 'cancelled':
                                        case 'failed':
                                            $status_class = 'bg-danger'; break;
                                        default: $status_class = 'bg-light text-dark';
                                    }
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                            </td>
                            <td>
                                <!-- View Order Button -->
                                <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-dark me-1"><i class="bi bi-eye-fill"></i> View</a>
                                <!-- Add other actions like Update Status, Delete etc. here -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&search_query=<?php echo htmlspecialchars($search_query); ?>&filter_status=<?php echo htmlspecialchars($filter_status); ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search_query=<?php echo htmlspecialchars($search_query); ?>&filter_status=<?php echo htmlspecialchars($filter_status); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&search_query=<?php echo htmlspecialchars($search_query); ?>&filter_status=<?php echo htmlspecialchars($filter_status); ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
