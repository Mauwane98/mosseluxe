<?php
// Start session and include admin authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
$conn = get_db_connection();

// --- 1. Get Filtering & Searching Parameters from URL ---
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

// --- 2. Build the SQL Query (similar to orders.php, but without pagination) ---
$sql_orders = "SELECT o.id, u.name AS customer_name, u.email AS customer_email, o.created_at, o.total_price, o.status 
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id 
               WHERE 1=1";
$params = [];
$types = '';

if (!empty($search_query)) {
    if (is_numeric($search_query)) {
        $sql_orders .= " AND o.id = ?";
        $params[] = $search_query;
        $types .= 'i';
    } else {
        $sql_orders .= " AND u.name LIKE ?";
        $params[] = "%" . $search_query . "%";
        $types .= 's';
    }
}
if (!empty($filter_status)) {
    $sql_orders .= " AND o.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$sql_orders .= " ORDER BY o.created_at DESC";

// --- 3. Fetch the Data ---
$orders = [];
if ($stmt = $conn->prepare($sql_orders)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    $stmt->close();
}
$conn->close();

// --- 4. Generate and Output CSV ---
$filename = "mosse_luxe_orders_" . date('Y-m-d') . ".csv";

// Set headers to force download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Write the header row
fputcsv($output, [
    'Order ID',
    'Customer Name',
    'Customer Email',
    'Order Date',
    'Total Price (R)',
    'Status'
]);

// Write the data rows
if (!empty($orders)) {
    foreach ($orders as $order) {
        fputcsv($output, [
            'ML-' . $order['id'],
            $order['customer_name'] ?? 'Guest',
            $order['customer_email'] ?? 'N/A',
            $order['created_at'],
            number_format($order['total_price'], 2, '.', ''),
            $order['status']
        ]);
    }
}

fclose($output);
exit();
?>