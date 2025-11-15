<?php
require_once 'bootstrap.php';
require_once '../includes/export_handler.php';
$conn = get_db_connection();

// CSRF protection
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token validation failed.');
}

// Get parameters
$format = isset($_POST['format']) ? strtolower($_POST['format']) : 'csv';
if (!in_array($format, ['csv', 'pdf'])) {
    $format = 'csv';
}

$start_date_str = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-29 days'));
$end_date_str = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');

// Add time to dates for a full-day range in SQL
$start_date_sql = $start_date_str . ' 00:00:00';
$end_date_sql = $end_date_str . ' 23:59:59';

// Fetch all data for the sales report
$summary_stats = [
    'total_revenue' => 0,
    'total_orders' => 0,
    'average_order_value' => 0
];
$daily_sales = [];
$orders_by_status = [];
$top_products = [];

// 1. Fetch summary statistics
$sql_summary = "SELECT
                    SUM(total_price) as total_revenue,
                    COUNT(id) as total_orders,
                    AVG(total_price) as average_order_value
                FROM orders
                WHERE status NOT IN ('Cancelled', 'Failed', 'pending')
                  AND created_at BETWEEN ? AND ?";
if ($stmt = $conn->prepare($sql_summary)) {
    $stmt->bind_param("ss", $start_date_sql, $end_date_sql);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $summary_stats['total_revenue'] = $stats['total_revenue'] ?? 0;
    $summary_stats['total_orders'] = $stats['total_orders'] ?? 0;
    $summary_stats['average_order_value'] = $stats['average_order_value'] ?? 0;
    $stmt->close();
}

// 2. Fetch daily sales
$sql_daily = "SELECT
                    DATE(created_at) as date,
                    COUNT(id) as orders,
                    SUM(total_price) as revenue
                FROM orders
                WHERE status NOT IN ('Cancelled', 'Failed', 'pending')
                  AND created_at BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY DATE(created_at) ASC";
if ($stmt = $conn->prepare($sql_daily)) {
    $stmt->bind_param("ss", $start_date_sql, $end_date_sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $daily_sales[] = [
            'date' => $row['date'],
            'orders' => $row['orders'],
            'revenue' => $row['revenue']
        ];
    }
    $stmt->close();
}

// 3. Orders by status
$sql_status = "SELECT status, COUNT(id) as count
               FROM orders
               WHERE created_at BETWEEN ? AND ?
               GROUP BY status
               ORDER BY status";
if ($stmt = $conn->prepare($sql_status)) {
    $stmt->bind_param("ss", $start_date_sql, $end_date_sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders_by_status[$row['status']] = $row['count'];
    }
    $stmt->close();
}

// 4. Top products
$sql_top_products = "SELECT
                        p.name,
                        SUM(oi.quantity) as total_sold,
                        SUM(oi.quantity * oi.price) as total_revenue
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.status NOT IN ('Cancelled', 'Failed', 'pending')
                      AND o.created_at BETWEEN ? AND ?
                    GROUP BY p.id, p.name
                    ORDER BY total_sold DESC
                    LIMIT 10";
if ($stmt = $conn->prepare($sql_top_products)) {
    $stmt->bind_param("ss", $start_date_sql, $end_date_sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $top_products[] = [
            'name' => $row['name'],
            'total_sold' => $row['total_sold'],
            'total_revenue' => $row['total_revenue']
        ];
    }
    $stmt->close();
}

// Prepare data for ExportHandler
$sales_data = [
    'period' => $start_date_str . ' to ' . $end_date_str,
    'total_sales' => $summary_stats['total_revenue'],
    'total_orders' => $summary_stats['total_orders'],
    'avg_order_value' => $summary_stats['average_order_value'],
    'orders_by_status' => $orders_by_status,
    'daily_sales' => $daily_sales,
    'top_products' => $top_products
];

// Use ExportHandler to generate and output the report
ExportHandler::exportSalesReport($sales_data, $format);
?>
