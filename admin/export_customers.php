<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
$conn = get_db_connection();

// Fetch all users with the 'user' role
$customers = [];
$sql = "SELECT id, name, email, created_at FROM users WHERE role = 'user' ORDER BY id ASC";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}
$conn->close();

// --- Generate and Output CSV ---
$filename = "mosse_luxe_customers_" . date('Y-m-d') . ".csv";

// Set headers to force download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Write the header row
fputcsv($output, [
    'Customer ID',
    'Full Name',
    'Email Address',
    'Registration Date'
]);

// Write the data rows
if (!empty($customers)) {
    foreach ($customers as $customer) {
        fputcsv($output, $customer);
    }
}

fclose($output);
exit();