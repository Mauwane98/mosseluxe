<?php
require_once 'bootstrap.php';
require_once '../includes/export_handler.php';
$conn = get_db_connection();

// --- 1. Get Filtering & Searching Parameters from URL ---
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';
if (!in_array($format, ['csv', 'pdf'])) {
    $format = 'csv';
}
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

// --- 4. Use ExportHandler to generate and output the report ---
ExportHandler::exportOrders($orders, $format);
?>
