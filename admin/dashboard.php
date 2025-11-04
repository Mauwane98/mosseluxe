<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include admin authentication check
require_once '../includes/admin_auth.php';

// Include database connection for dynamic data
require_once '../includes/db_connect.php';
$conn = get_db_connection();

// Fetch dynamic data for dashboard
$total_sales = 0.00;
$pending_orders_count = 0;
$total_orders_count = 0;
$new_messages_count = 0;

// Fetch Total Sales
$sql_sales = "SELECT SUM(total_price) AS total FROM orders WHERE status IN ('Paid', 'Processing', 'Shipped', 'Delivered')";
if ($stmt_sales = $conn->prepare($sql_sales)) {
    if ($stmt_sales->execute()) {
        $stmt_sales->store_result();
        if ($stmt_sales->num_rows > 0) {
            $stmt_sales->bind_result($total);
            $stmt_sales->fetch();
            $total_sales = $total ?: 0.00; // Use 0.00 if total is null
        }
    }
    $stmt_sales->close();
}

// Fetch Pending Orders Count
$sql_pending = "SELECT COUNT(id) AS count FROM orders WHERE status = 'Paid'"; // 'Paid' status means it's pending fulfillment
if ($stmt_pending = $conn->prepare($sql_pending)) {
    if ($stmt_pending->execute()) {
        $stmt_pending->store_result();
        if ($stmt_pending->num_rows > 0) {
            $stmt_pending->bind_result($count);
            $stmt_pending->fetch();
            $pending_orders_count = $count ?: 0;
        }
    }
    $stmt_pending->close();
}

// Fetch Total Orders Count
$sql_total_orders = "SELECT COUNT(id) AS count FROM orders";
if ($stmt_total_orders = $conn->prepare($sql_total_orders)) {
    if ($stmt_total_orders->execute()) {
        $stmt_total_orders->store_result();
        if ($stmt_total_orders->num_rows > 0) {
            $stmt_total_orders->bind_result($count);
            $stmt_total_orders->fetch();
            $total_orders_count = $count ?: 0;
        }
    }
    $stmt_total_orders->close();
}

// Fetch New Messages Count (assuming messages are stored in a 'messages' table and have a 'read_at' column or similar)
// For simplicity, let's assume messages without a 'read_at' timestamp are new.
// If no 'read_at' column, you might need a 'is_read' boolean column.
// For this example, let's assume a 'created_at' column and we check if it's recent or if there's an 'is_read' flag.
// Let's assume a simple 'is_read' column (0 for unread, 1 for read).
$sql_messages = "SELECT COUNT(id) AS count FROM messages WHERE is_read = 0";
if ($stmt_messages = $conn->prepare($sql_messages)) {
    if ($stmt_messages->execute()) {
        $stmt_messages->store_result();
        if ($stmt_messages->num_rows > 0) {
            $stmt_messages->bind_result($count);
            $stmt_messages->fetch();
            $new_messages_count = $count ?: 0;
        }
    }
    $stmt_messages->close();
}

// Fetch Recent Orders
$recent_orders = [];
$sql_recent_orders = "SELECT o.id, u.name AS customer_name, o.total_price, o.status 
                      FROM orders o 
                      LEFT JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC 
                      LIMIT 5";
if ($result_recent_orders = $conn->query($sql_recent_orders)) {
    while ($row = $result_recent_orders->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// Fetch Recently Added Products
$recent_products = [];
$sql_recent_products = "SELECT id, name, image, price FROM products ORDER BY id DESC LIMIT 5";
if ($result_recent_products = $conn->query($sql_recent_products)) {
    while ($row = $result_recent_products->fetch_assoc()) {
        $recent_products[] = $row;
    }
}

// Fetch Sales by Category data
$sales_by_category = [];
$sql_category_sales = "SELECT c.name as category_name, SUM(oi.price * oi.quantity) as category_sales
                       FROM order_items oi
                       JOIN products p ON oi.product_id = p.id
                       JOIN categories c ON p.category = c.id
                       JOIN orders o ON oi.order_id = o.id
                       WHERE o.status NOT IN ('Cancelled', 'Failed', 'Pending')
                       GROUP BY c.name
                       ORDER BY category_sales DESC";
if ($result_category_sales = $conn->query($sql_category_sales)) {
    while ($row = $result_category_sales->fetch_assoc()) {
        $sales_by_category[] = $row;
    }
}
$category_labels_json = json_encode(array_column($sales_by_category, 'category_name'));
$category_data_json = json_encode(array_column($sales_by_category, 'category_sales'));


$active_page = 'dashboard';
$page_title = 'Dashboard Overview';
?>
<?php include '../includes/admin_page_header.php'; ?>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php
    include '../includes/admin_header.php'; 
    ?>

    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="stat-card">
                <h5 class="text-muted">Total Sales</h5>
                <h2 class="gold-text">R <?php echo number_format($total_sales, 2); ?></h2>
                <p class="small text-muted">From <?php echo $total_orders_count; ?> orders</p>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="stat-card">
                 <h5 class="text-muted">Pending Orders</h5>
                <h2><?php echo $pending_orders_count; ?></h2>
                <p class="small text-muted">Awaiting fulfillment</p>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="stat-card">
                 <h5 class="text-muted">New Messages</h5>
                <h2><?php echo $new_messages_count; ?></h2>
                <p class="small text-muted">From contact form</p>
            </div>
        </div>
    </div>

    <!-- Recent Activity Widgets -->
    <div class="row mt-4">
        <!-- Recent Orders Widget -->
        <div class="col-lg-7 mb-4">
            <div class="widget-card">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a href="orders.php" class="btn btn-sm btn-outline-dark">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-dark">
                            <tbody>
                                <?php if (!empty($recent_orders)): ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr class="text-dark">
                                            <td><a href="view_order.php?id=<?php echo $order['id']; ?>" class="text-dark text-decoration-none">#ML-<?php echo $order['id']; ?></a></td>
                                            <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
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
                                                        case 'cancelled': case 'failed': $status_class = 'bg-danger'; break;
                                                        default: $status_class = 'bg-light text-dark';
                                                    }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted p-3">No recent orders.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recently Added Products Widget -->
        <div class="col-lg-5 mb-4">
            <div class="widget-card">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recently Added Products</h5>
                    <a href="products.php" class="btn btn-sm btn-outline-dark">View All</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (!empty($recent_products)): ?>
                            <?php foreach ($recent_products as $product): ?>
                                <li class="list-group-item d-flex align-items-center">
                                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 0.25rem;" class="me-3">
                                    <div class="flex-grow-1">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="text-dark text-decoration-none"><?php echo htmlspecialchars($product['name']); ?></a>
                                    </div>
                                    <span class="text-muted">R <?php echo number_format($product['price'], 2); ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted p-3">No products added recently.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sales by Category Widget -->
        <div class="col-lg-5 mb-4">
            <div class="widget-card" style="height: 100%;">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sales by Category</h5>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center"><canvas id="categorySalesChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Sales by Category Doughnut Chart ---
    const categoryCtx = document.getElementById('categorySalesChart').getContext('2d');
    const categorySalesChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $category_labels_json; ?>,
            datasets: [{
                label: 'Sales',
                data: <?php echo $category_data_json; ?>,
                backgroundColor: [
                    'rgba(0, 0, 0, 0.9)',
                    'rgba(0, 0, 0, 0.7)',
                    'rgba(0, 0, 0, 0.5)',
                    'rgba(0, 0, 0, 0.3)',
                    'rgba(108, 117, 125, 0.5)',
                    'rgba(108, 117, 125, 0.3)',
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '#495057'
                    }
                },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#000',
                    bodyColor: '#000',
                    borderColor: '#dee2e6',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += new Intl.NumberFormat('en-ZA', { style: 'currency', currency: 'ZAR' }).format(context.raw);
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>
</body>
</html>
