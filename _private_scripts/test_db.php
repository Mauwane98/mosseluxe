<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/bootstrap.php';

echo "Attempting to connect to the database...\n";

$conn = get_db_connection();

if ($conn) {
    echo "Database connection successful!\n";
    $conn->close();
} else {
    echo "Database connection failed!\n";
}

