<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
$conn = get_db_connection();

$active_page = 'reports';

// --- Date Filtering Logic ---
$today = new DateTime();
$start_date_str = (new DateTime())->sub(new DateInterval('P29D'))->format('Y-m-d'); // Default to last 30 days
$end_date_str = $today->format('Y-m-d');

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start_date_str = $_GET['start_date'];
}
if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $end_date_str = $_GET['end_date'];
}

// Add time to dates for a full-day range in SQL
$start_date_sql = $start_date_str . ' 00:00:00';
$end_date_sql = $end_date_str . ' 23:59:59';

// --- Data Fetching ---
$summary_stats = [
    'total_revenue' => 0,
    'total_orders' => 0,
    'average_order_value' => 0
];
$chart_data = [];
$top_products_data = [];
$discount_usage_data = [];

// Fetch summary statistics
$sql_summary = "SELECT 
                    SUM(total_price) as total_revenue,
                    COUNT(id) as total_orders,
                    AVG(total_price) as average_order_value
                FROM orders
                 WHERE status NOT IN ('Cancelled', 'Failed', 'pending')
                  AND created_at BETWEEN ? AND ?";
if ($stmt_summary = $conn->prepare($sql_summary)) {
    $stmt_summary->bind_param("ss", $start_date_sql, $end_date_sql);
    $stmt_summary->execute();
    $result = $stmt_summary->get_result();
    $stats = $result->fetch_assoc();
    if ($stats) {
        $summary_stats['total_revenue'] = $stats['total_revenue'] ?? 0;
        $summary_stats['total_orders'] = $stats['total_orders'] ?? 0;
        $summary_stats['average_order_value'] = $stats['average_order_value'] ?? 0;
    }
    $stmt_summary->close();
}

// Fetch data for the chart (daily sales)
$sql_chart = "SELECT 
                    DATE(created_at) as sale_date, 
                    SUM(total_price) as daily_sales 
                FROM orders 
                WHERE status NOT IN ('Cancelled', 'Failed', 'pending') 
                  AND created_at BETWEEN ? AND ?
                GROUP BY DATE(created_at) 
                ORDER BY sale_date ASC";
if ($stmt_chart = $conn->prepare($sql_chart)) {
    $stmt_chart->bind_param("ss", $start_date_sql, $end_date_sql);
    $stmt_chart->execute();
    $result_chart = $stmt_chart->get_result();
    while ($row = $result_chart->fetch_assoc()) {
        $chart_data[$row['sale_date']] = $row['daily_sales'];
    }
    $stmt_chart->close();
}

// Fetch data for Top Selling Products chart
$sql_top_products = "SELECT 
                        p.name as product_name,
                        SUM(oi.quantity) as total_quantity_sold
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.status NOT IN ('Cancelled', 'Failed', 'pending')
                      AND o.created_at BETWEEN ? AND ?
                    GROUP BY p.id, p.name
                    ORDER BY total_quantity_sold DESC
                    LIMIT 5";

if ($stmt_top_products = $conn->prepare($sql_top_products)) {
    $stmt_top_products->bind_param("ss", $start_date_sql, $end_date_sql);
    $stmt_top_products->execute();
    $result_top_products = $stmt_top_products->get_result();
    while ($row = $result_top_products->fetch_assoc()) {
        $top_products_data[] = $row;
    }
    $stmt_top_products->close();
}

// Fetch data for Discount Code Usage chart
$sql_discount_usage = "SELECT 
                            discount_code,
                            COUNT(id) as usage_count
                        FROM orders
                        WHERE discount_code IS NOT NULL
                          AND status NOT IN ('Cancelled', 'Failed', 'pending')
                          AND created_at BETWEEN ? AND ?
                        GROUP BY discount_code
                        ORDER BY usage_count DESC
                        LIMIT 7";
if ($stmt_discount_usage = $conn->prepare($sql_discount_usage)) {
    $stmt_discount_usage->bind_param("ss", $start_date_sql, $end_date_sql);
    $stmt_discount_usage->execute();
    $result_discount_usage = $stmt_discount_usage->get_result();
    while ($row = $result_discount_usage->fetch_assoc()) {
        $discount_usage_data[] = $row;
    }
    $stmt_discount_usage->close();
}

$conn->close();

// --- Prepare data for Chart.js ---
$labels = [];
$data = [];
$current_date = new DateTime($start_date_str);
$end_date_obj = new DateTime($end_date_str);

while ($current_date <= $end_date_obj) {
    $date_key = $current_date->format('Y-m-d');
    $labels[] = $current_date->format('M d'); // Format for display (e.g., "Jan 23")
    $data[] = $chart_data[$date_key] ?? 0; // Use sales data or 0 if no sales on that day
    $current_date->add(new DateInterval('P1D'));
}

$chart_labels_json = json_encode($labels);
$chart_data_json = json_encode($data);

// Prepare data for Top Products Chart.js
$top_products_labels = [];
$top_products_values = [];
foreach ($top_products_data as $product) {
    $top_products_labels[] = $product['product_name'];
    $top_products_values[] = $product['total_quantity_sold'];
}
$top_products_labels_json = json_encode($top_products_labels);
$top_products_values_json = json_encode($top_products_values);

// Prepare data for Discount Usage Chart.js
$discount_labels = [];
$discount_values = [];
foreach ($discount_usage_data as $discount) {
    $discount_labels[] = $discount['discount_code'];
    $discount_values[] = $discount['usage_count'];
}
$discount_labels_json = json_encode($discount_labels);
$discount_values_json = json_encode($discount_values);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Mossé Luxe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php 
    $page_title = 'Sales Report';
    include '../includes/admin_header.php'; 
    ?>

    <!-- Date Filter Form -->
    <div class="card p-3 mb-4">
        <form action="sales_report.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date_str); ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date_str); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary-dark">Filter Report</button>
                <a href="sales_report.php" class="btn btn-outline-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>

    <!-- Summary Stat Cards -->
    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="stat-card">
                <h5 class="text-muted">Total Revenue</h5>
                <h2 class="gold-text">R <?php echo number_format($summary_stats['total_revenue'], 2); ?></h2>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="stat-card">
                 <h5 class="text-muted">Total Orders</h5>
                <h2><?php echo number_format($summary_stats['total_orders']); ?></h2>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="stat-card">
                 <h5 class="text-muted">Average Order Value</h5>
                <h2>R <?php echo number_format($summary_stats['average_order_value'], 2); ?></h2>
            </div>
        </div>
    </div>

    <!-- Sales Chart -->
    <div class="row mt-4 g-4">
        <div class="col-12">
            <div class="chart-container">
                <h5 class="gold-text mb-3">Daily Sales Revenue</h5>
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    <div class="row mt-4 g-4">
        <div class="col-lg-7">
            <div class="chart-container">
                <h5 class="gold-text mb-3">Top Selling Products</h5>
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="chart-container">
                <h5 class="gold-text mb-3">Discount Code Usage</h5>
                <canvas id="discountUsageChart"></canvas>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Daily Sales Line Chart ---
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    
    // Gradient fill
    const gradient = salesCtx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(197, 165, 114, 0.5)');
    gradient.addColorStop(1, 'rgba(197, 165, 114, 0)');

    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?php echo $chart_labels_json; ?>,
            datasets: [{
                label: 'Daily Sales (R)',
                data: <?php echo $chart_data_json; ?>,
                borderColor: '#C5A572',
                backgroundColor: gradient,
                borderWidth: 2,
                pointBackgroundColor: '#C5A572',
                pointRadius: 4,
                pointHoverRadius: 6,
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
                        color: '#495057',
                        callback: function(value, index, values) {
                            return 'R ' + value;
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#495057'
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#000',
                    bodyColor: '#000',
                    borderColor: '#dee2e6',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('en-ZA', { style: 'currency', currency: 'ZAR' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });

    // --- Top Selling Products Bar Chart ---
    const productsCtx = document.getElementById('topProductsChart').getContext('2d');
    const topProductsChart = new Chart(productsCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $top_products_labels_json; ?>,
            datasets: [{
                label: 'Units Sold',
                data: <?php echo $top_products_values_json; ?>,
                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                borderColor: '#000000',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y', // Horizontal bar chart
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#495057'
                    },
                    grid: {
                        display: false
                    }
                },
                x: {
                    ticks: {
                        color: '#495057',
                        precision: 0 // Ensure whole numbers for units sold
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#000',
                    bodyColor: '#000',
                    borderColor: '#dee2e6',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.x !== null) {
                                label += context.parsed.x + ' units';
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });

    // --- Discount Code Usage Doughnut Chart ---
    const discountCtx = document.getElementById('discountUsageChart').getContext('2d');
    const discountUsageChart = new Chart(discountCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $discount_labels_json; ?>,
            datasets: [{
                label: 'Times Used',
                data: <?php echo $discount_values_json; ?>,
                backgroundColor: [
                    'rgba(0, 0, 0, 0.9)',
                    'rgba(0, 0, 0, 0.7)',
                    'rgba(0, 0, 0, 0.5)',
                    'rgba(0, 0, 0, 0.3)',
                    'rgba(108, 117, 125, 0.5)',
                    'rgba(108, 117, 125, 0.3)',
                    'rgba(108, 117, 125, 0.2)'
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
                            label += context.raw + ' uses';
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