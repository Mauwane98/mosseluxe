<?php
// Include the admin bootstrap for automatic setup
require_once 'bootstrap.php';
$conn = get_db_connection();

// --- Comprehensive Data Fetching ---

// Today's Stats
$today = date('Y-m-d');
$todayStart = $today . ' 00:00:00';
$todayEnd = $today . ' 23:59:59';

// Today's Sales
$todaySalesResult = $conn->prepare("SELECT SUM(total_price) as today_sales FROM orders WHERE status = 'Completed' AND created_at BETWEEN ? AND ?");
if ($todaySalesResult) {
    $todaySalesResult->bind_param("ss", $todayStart, $todayEnd);
    if ($todaySalesResult->execute()) {
        $todaySales = $todaySalesResult->get_result()->fetch_assoc()['today_sales'] ?? 0;
    } else {
        error_log("Error executing today's sales query: " . $todaySalesResult->error);
    }
    $todaySalesResult->close();
} else {
    error_log("Error preparing today's sales query: " . $conn->error);
}

// Today's Orders
$todayOrdersResult = $conn->prepare("SELECT COUNT(id) as today_orders FROM orders WHERE created_at BETWEEN ? AND ?");
if ($todayOrdersResult) {
    $todayOrdersResult->bind_param("ss", $todayStart, $todayEnd);
    if ($todayOrdersResult->execute()) {
        $todayOrders = $todayOrdersResult->get_result()->fetch_assoc()['today_orders'] ?? 0;
    } else {
        error_log("Error executing today's orders query: " . $todayOrdersResult->error);
    }
    $todayOrdersResult->close();
} else {
    error_log("Error preparing today's orders query: " . $conn->error);
}

// Total Sales (All time)
$totalSalesResult = $conn->prepare("SELECT SUM(total_price) as total_sales FROM orders WHERE status = 'Completed'");
if ($totalSalesResult) {
    if ($totalSalesResult->execute()) {
        $totalSales = $totalSalesResult->get_result()->fetch_assoc()['total_sales'] ?? 0;
    } else {
        error_log("Error executing total sales query: " . $totalSalesResult->error);
    }
    $totalSalesResult->close();
} else {
    error_log("Error preparing total sales query: " . $conn->error);
}

// Total Orders
$totalOrdersResult = $conn->prepare("SELECT COUNT(id) as total_orders FROM orders");
if ($totalOrdersResult) {
    if ($totalOrdersResult->execute()) {
        $totalOrders = $totalOrdersResult->get_result()->fetch_assoc()['total_orders'] ?? 0;
    } else {
        error_log("Error executing total orders query: " . $totalOrdersResult->error);
    }
    $totalOrdersResult->close();
} else {
    error_log("Error preparing total orders query: " . $conn->error);
}

// Total Customers
$totalCustomersResult = $conn->prepare("SELECT COUNT(id) as total_customers FROM users WHERE role = 'user'");
if ($totalCustomersResult) {
    if ($totalCustomersResult->execute()) {
        $totalCustomers = $totalCustomersResult->get_result()->fetch_assoc()['total_customers'] ?? 0;
    } else {
        error_log("Error executing total customers query: " . $totalCustomersResult->error);
    }
    $totalCustomersResult->close();
} else {
    error_log("Error preparing total customers query: " . $conn->error);
}

// Total Products
$totalProductsResult = $conn->prepare("SELECT COUNT(id) as total_products FROM products WHERE status = 1");
if ($totalProductsResult) {
    if ($totalProductsResult->execute()) {
        $totalProducts = $totalProductsResult->get_result()->fetch_assoc()['total_products'] ?? 0;
    } else {
        error_log("Error executing total products query: " . $totalProductsResult->error);
    }
    $totalProductsResult->close();
} else {
    error_log("Error preparing total products query: " . $conn->error);
}

// Low Stock Products
$lowStockProducts = [];
$sql = "SELECT id, name, stock FROM products WHERE stock <= ? AND status = 1 ORDER BY stock ASC LIMIT ?";
if ($stmt = $conn->prepare($sql)) {
    $limit = 5;
    $stock_threshold = 5;
    $stmt->bind_param("ii", $stock_threshold, $limit);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $lowStockProducts[] = $row;
        }
    } else {
        error_log("Error executing low stock products query: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Error preparing low stock products query: " . $conn->error);
}

// Pending Orders
$pendingOrdersResult = $conn->prepare("SELECT COUNT(id) as pending_orders FROM orders WHERE status = 'Pending'");
if ($pendingOrdersResult) {
    if ($pendingOrdersResult->execute()) {
        $pendingOrders = $pendingOrdersResult->get_result()->fetch_assoc()['pending_orders'] ?? 0;
    } else {
        error_log("Error executing pending orders query: " . $pendingOrdersResult->error);
    }
    $pendingOrdersResult->close();
} else {
    error_log("Error preparing pending orders query: " . $conn->error);
}

// Recent Orders
$recentOrders = [];
$sql = "SELECT o.id, o.total_price, o.status, o.created_at, u.name as customer_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT ?";
if ($stmt = $conn->prepare($sql)) {
    $limit = 5;
    $stmt->bind_param("i", $limit);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recentOrders[] = $row;
        }
    } else {
        error_log("Error executing recent orders query: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Error preparing recent orders query: " . $conn->error);
}

// Top Selling Products
$topProducts = [];
$sql = "SELECT p.name, SUM(oi.quantity) as total_sold
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'Completed'
        GROUP BY oi.product_id
        ORDER BY total_sold DESC
        LIMIT ?";
if ($stmt = $conn->prepare($sql)) {
    $limit = 5;
    $stmt->bind_param("i", $limit);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $topProducts[] = $row;
        }
    } else {
        error_log("Error executing top selling products query: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Error preparing top selling products query: " . $conn->error);
}

// Recent Messages
$recentMessages = [];
$sql = "SELECT id, name, subject, received_at, is_read FROM messages ORDER BY received_at DESC LIMIT ?";
if ($stmt = $conn->prepare($sql)) {
    $limit = 3;
    $stmt->bind_param("i", $limit);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recentMessages[] = $row;
        }
    } else {
        error_log("Error executing recent messages query: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Error preparing recent messages query: " . $conn->error);
}

// Sales Chart Data (Last 7 Days)
$salesData = [];
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = date('D', strtotime("-$i days"));
    $days[] = $dayName;

    $sql = "SELECT SUM(total_price) as daily_sales
            FROM orders
            WHERE DATE(created_at) = ? AND status = 'Completed'";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $date);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $salesData[] = $row['daily_sales'] ?? 0;
        } else {
            error_log("Error executing daily sales query for date " . $date . ": " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Error preparing daily sales query for date " . $date . ": " . $conn->error);
    }
}



$pageTitle = "Dashboard";
include 'header.php';

// Display any session messages
displaySuccessMessage();
displayErrorMessage();
?>

<!-- Welcome Section -->
<div class="bg-gradient-to-r from-black to-gray-800 text-white p-6 rounded-lg shadow-md mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h1>
            <p class="text-gray-300 mt-1">Here's what's happening with your store today.</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-300"><?php echo date('l, F j, Y'); ?></p>
            <p class="text-lg font-semibold"><?php echo date('H:i'); ?></p>
        </div>
    </div>
</div>

<!-- Today's Highlights -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-lg shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium opacity-90">Today's Sales</h3>
                <p class="mt-2 text-3xl font-bold">R<?php echo number_format($todaySales, 2); ?></p>
            </div>
            <div class="text-4xl opacity-80">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-lg shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium opacity-90">Today's Orders</h3>
                <p class="mt-2 text-3xl font-bold"><?php echo $todayOrders; ?></p>
            </div>
            <div class="text-4xl opacity-80">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
    </div>
    <div class="bg-gradient-to-br from-yellow-500 to-orange-500 text-white p-6 rounded-lg shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium opacity-90">Pending Orders</h3>
                <p class="mt-2 text-3xl font-bold"><?php echo $pendingOrders; ?></p>
                <?php if ($pendingOrders > 0): ?>
                    <a href="orders.php?status=Pending" class="text-xs underline hover:no-underline">View pending</a>
                <?php endif; ?>
            </div>
            <div class="text-4xl opacity-80">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>
    <div class="bg-gradient-to-br from-red-500 to-red-600 text-white p-6 rounded-lg shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium opacity-90">Low Stock Alert</h3>
                <p class="mt-2 text-3xl font-bold"><?php echo count($lowStockProducts); ?></p>
                <?php if (count($lowStockProducts) > 0): ?>
                    <a href="products.php" class="text-xs underline hover:no-underline">View products</a>
                <?php endif; ?>
            </div>
            <div class="text-4xl opacity-80">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>
</div>

<!-- Overall Statistics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500">
        <h3 class="text-sm font-medium text-gray-500">Total Sales</h3>
        <p class="mt-2 text-3xl font-bold text-gray-900">R<?php echo number_format($totalSales, 2); ?></p>
        <p class="text-xs text-gray-500 mt-1">All time completed orders</p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500">
        <h3 class="text-sm font-medium text-gray-500">Total Orders</h3>
        <p class="mt-2 text-3xl font-bold text-gray-900"><?php echo $totalOrders; ?></p>
        <p class="text-xs text-gray-500 mt-1">All orders placed</p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-purple-500">
        <h3 class="text-sm font-medium text-gray-500">Total Customers</h3>
        <p class="mt-2 text-3xl font-bold text-gray-900"><?php echo $totalCustomers; ?></p>
        <p class="text-xs text-gray-500 mt-1">Registered users</p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-indigo-500">
        <h3 class="text-sm font-medium text-gray-500">Active Products</h3>
        <p class="mt-2 text-3xl font-bold text-gray-900"><?php echo $totalProducts; ?></p>
        <p class="text-xs text-gray-500 mt-1">Published products</p>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <a href="add_product.php" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
            <i class="fas fa-plus-circle text-2xl text-blue-600 mb-2"></i>
            <span class="text-sm font-medium text-gray-700">Add Product</span>
        </a>
        <a href="orders.php?status=Pending" class="flex flex-col items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
            <i class="fas fa-clock text-2xl text-yellow-600 mb-2"></i>
            <span class="text-sm font-medium text-gray-700">Pending Orders</span>
        </a>
        <a href="messages.php" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
            <i class="fas fa-envelope text-2xl text-green-600 mb-2"></i>
            <span class="text-sm font-medium text-gray-700">Messages</span>
            <?php if (count(array_filter($recentMessages, fn($m) => !$m['is_read'])) > 0): ?>
                <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full -mt-1"><?php echo count(array_filter($recentMessages, fn($m) => !$m['is_read'])); ?></span>
            <?php endif; ?>
        </a>
        <a href="sales_report.php" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
            <i class="fas fa-chart-bar text-2xl text-purple-600 mb-2"></i>
            <span class="text-sm font-medium text-gray-700">Reports</span>
        </a>
        <a href="users.php" class="flex flex-col items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
            <i class="fas fa-users text-2xl text-indigo-600 mb-2"></i>
            <span class="text-sm font-medium text-gray-700">Customers</span>
        </a>
        <a href="settings.php" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
            <i class="fas fa-cog text-2xl text-gray-600 mb-2"></i>
            <span class="text-sm font-medium text-gray-700">Settings</span>
        </a>
    </div>
</div>

<!-- Charts and Analytics -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Sales Chart -->
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">Sales Trend (Last 7 Days)</h3>
            <a href="sales_report.php" class="text-sm text-blue-600 hover:text-blue-800">View full report →</a>
        </div>
        <div class="h-64">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">Top Products</h3>
            <a href="products.php" class="text-sm text-blue-600 hover:text-blue-800">View all →</a>
        </div>
        <?php if (!empty($topProducts)): ?>
            <div class="space-y-3">
                <?php foreach ($topProducts as $index => $product): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-bold mr-3">
                                <?php echo $index + 1; ?>
                            </span>
                            <span class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($product['name']); ?></span>
                        </div>
                        <span class="text-sm font-bold text-gray-800"><?php echo $product['total_sold']; ?> sold</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-8">No sales data available yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Orders -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">Recent Orders</h3>
            <a href="orders.php" class="text-sm text-blue-600 hover:text-blue-800">View all orders →</a>
        </div>
        <?php if (!empty($recentOrders)): ?>
            <div class="space-y-3">
                <?php foreach ($recentOrders as $order): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">#ML-<?php echo $order['id']; ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900">R<?php echo number_format($order['total_price'], 2); ?></p>
                            <span class="px-2 py-1 text-xs rounded-full <?php
                                $statusColors = [
                                    'Pending' => 'bg-yellow-100 text-yellow-800',
                                    'Processing' => 'bg-blue-100 text-blue-800',
                                    'Shipped' => 'bg-indigo-100 text-indigo-800',
                                    'Completed' => 'bg-green-100 text-green-800',
                                    'Cancelled' => 'bg-red-100 text-red-800'
                                ];
                                echo $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-8">No recent orders found.</p>
        <?php endif; ?>
    </div>

    <!-- Recent Messages & Alerts -->
    <div class="space-y-6">
        <!-- Recent Messages -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Recent Messages</h3>
                <a href="messages.php" class="text-sm text-blue-600 hover:text-blue-800">View all →</a>
            </div>
            <?php if (!empty($recentMessages)): ?>
                <div class="space-y-3">
                    <?php foreach ($recentMessages as $message): ?>
                        <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-envelope text-blue-600"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($message['name']); ?></p>
                                <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($message['subject']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('M d, H:i', strtotime($message['received_at'])); ?></p>
                            </div>
                            <?php if (!$message['is_read']): ?>
                                <div class="flex-shrink-0">
                                    <span class="inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No messages received yet.</p>
            <?php endif; ?>
        </div>

        <!-- Low Stock Alert -->
        <?php if (!empty($lowStockProducts)): ?>
        <div class="bg-red-50 border border-red-200 p-6 rounded-lg shadow-md">
            <div class="flex items-center mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 text-xl mr-3"></i>
                <h3 class="text-lg font-bold text-red-800">Low Stock Alert</h3>
            </div>
            <div class="space-y-2">
                <?php foreach ($lowStockProducts as $product): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-red-700"><?php echo htmlspecialchars($product['name']); ?></span>
                        <span class="text-sm font-bold text-red-800"><?php echo $product['stock']; ?> left</span>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="products.php" class="inline-block mt-3 text-sm text-red-600 hover:text-red-800 font-medium">Manage inventory →</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php echo "'" . implode("','", $days) . "'"; ?>],
            datasets: [{
                label: 'Sales (R)',
                data: [<?php echo implode(",", $salesData); ?>],
                backgroundColor: 'rgba(192, 132, 252, 0.2)',
                borderColor: 'rgba(192, 132, 252, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            return 'R' + value;
                        }
                    }
                }
            }
        }
    });
</script>

<?php include 'footer.php'; ?>
