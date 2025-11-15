<?php
require_once 'bootstrap.php';
require_once '../includes/export_handler.php';
$conn = get_db_connection();

// Get format parameter
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';
if (!in_array($format, ['csv', 'pdf'])) {
    $format = 'csv';
}

// Fetch all customers with enhanced data
$customers = [];
$sql = "SELECT
    u.id,
    u.name,
    u.email,
    u.created_at AS created_at,
    COUNT(o.id) as total_orders,
    COALESCE(SUM(o.total_price), 0) as total_spent,
    MAX(o.created_at) as last_order
FROM users u
LEFT JOIN orders o ON u.id = o.user_id AND o.status NOT IN ('Cancelled', 'Failed')
WHERE u.role = 'user'
GROUP BY u.id, u.name, u.email, u.created_at
ORDER BY u.created_at DESC";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    $result->close();
}

// Use ExportHandler to generate and output the report
ExportHandler::exportCustomers($customers, $format);
