<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    $conn = get_db_connection();
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage() . "\n");
}

if ($argc < 2) {
    die("Usage: php run_sql.php <sql_file_path>\n");
}

$sql_file_path = $argv[1];

if (!file_exists($sql_file_path)) {
    die("Error: SQL file not found at " . $sql_file_path . "\n");
}

$sql_content = file_get_contents($sql_file_path);

// Split SQL statements by semicolon, but be careful not to split semicolons within comments or strings
$statements = array_filter(array_map('trim', explode(';', $sql_content)));

echo "Executing SQL file: " . basename($sql_file_path) . "\n";

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (empty($statement)) {
        continue;
    }
    // Remove comments
    $statement = preg_replace('/(--.*)|(\/\*[\s\S]*?\*\/)/', '', $statement);
    $statement = trim($statement);

    if (empty($statement)) {
        continue;
    }

    echo "Executing statement: " . substr($statement, 0, 100) . "...\n"; // Print first 100 chars

    if ($conn->query($statement)) {
        echo "  ✓ Success\n";
        $success_count++;
    } else {
        echo "  ✗ Error: " . $conn->error . "\n";
        $error_count++;
    }
}

echo "\nSQL file '" . basename($sql_file_path) . "' execution complete.\n";
echo "Total statements: " . count($statements) . "\n";
echo "Successful: " . $success_count . "\n";
echo "Errors: " . $error_count . "\n";

$conn->close();
