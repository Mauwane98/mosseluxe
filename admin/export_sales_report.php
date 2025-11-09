<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';

if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

$format = isset($_POST['format']) ? $_POST['format'] : 'csv';
$start_date_str = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date_str = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');

// Validate dates
if (!strtotime($start_date_str) || !strtotime($end_date_str)) {
    die('Invalid date format');
}

$start_date_sql = $start_date_str . ' 00:00:00';
$end_date_sql = $end_date_str . ' 23:59:59';

$conn = get_db_connection();

// Fetch sales data
$sql = "SELECT
            DATE(o.created_at) as sale_date,
            COUNT(o.id) as orders_count,
            SUM(o.total_price) as daily_revenue,
            AVG(o.total_price) as avg_order_value
        FROM orders o
        WHERE o.status NOT IN ('Cancelled', 'Failed', 'pending')
          AND o.created_at BETWEEN ? AND ?
        GROUP BY DATE(o.created_at)
        ORDER BY sale_date ASC";

$data = [];
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ss", $start_date_sql, $end_date_sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
}

// Calculate totals
$total_revenue = array_sum(array_column($data, 'daily_revenue'));
$total_orders = array_sum(array_column($data, 'orders_count'));
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

$conn->close();

if ($format === 'csv') {
    // Export as CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . $start_date_str . '_to_' . $end_date_str . '.csv"');

    $output = fopen('php://output', 'w');

    // Write header
    fputcsv($output, ['Date', 'Orders Count', 'Daily Revenue (R)', 'Average Order Value (R)']);

    // Write data
    foreach ($data as $row) {
        fputcsv($output, [
            $row['sale_date'],
            $row['orders_count'],
            number_format($row['daily_revenue'], 2),
            number_format($row['avg_order_value'], 2)
        ]);
    }

    // Write summary
    fputcsv($output, []); // Empty row
    fputcsv($output, ['SUMMARY', '', '', '']);
    fputcsv($output, ['Total Revenue', '', number_format($total_revenue, 2), '']);
    fputcsv($output, ['Total Orders', '', $total_orders, '']);
    fputcsv($output, ['Average Order Value', '', number_format($avg_order_value, 2), '']);
    fputcsv($output, ['Report Period', $start_date_str . ' to ' . $end_date_str, '', '']);

    fclose($output);
    exit;

} elseif ($format === 'pdf') {
    // For PDF export, we'll create a simple HTML-based PDF
    // In a real application, you'd use a library like TCPDF or DomPDF

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="sales_report_' . $start_date_str . '_to_' . $end_date_str . '.pdf"');

    // Simple HTML to PDF conversion (basic implementation)
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Sales Report - $start_date_str to $end_date_str</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .summary { background-color: #e9ecef; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <h1>Mossé Luxe Sales Report</h1>
        <p><strong>Report Period:</strong> $start_date_str to $end_date_str</p>
        <p><strong>Generated on:</strong> " . date('Y-m-d H:i:s') . "</p>

        <div class='summary'>
            <h3>Summary</h3>
            <p><strong>Total Revenue:</strong> R " . number_format($total_revenue, 2) . "</p>
            <p><strong>Total Orders:</strong> $total_orders</p>
            <p><strong>Average Order Value:</strong> R " . number_format($avg_order_value, 2) . "</p>
        </div>

        <h3>Daily Sales Breakdown</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Orders Count</th>
                    <th>Daily Revenue (R)</th>
                    <th>Average Order Value (R)</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($data as $row) {
        echo "<tr>
                <td>{$row['sale_date']}</td>
                <td>{$row['orders_count']}</td>
                <td>" . number_format($row['daily_revenue'], 2) . "</td>
                <td>" . number_format($row['avg_order_value'], 2) . "</td>
              </tr>";
    }

    echo "</tbody>
        </table>
    </body>
    </html>";

    exit;
} else {
    // Invalid format
    header("Location: sales_report.php?error=invalid_format");
    exit;
}
?>
