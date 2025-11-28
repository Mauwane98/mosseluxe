<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Read the SQL file
$sql = file_get_contents('_private_scripts/create_homepage_table.sql');
echo "Read homepage table SQL file...\n";

// Split by statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement) && !preg_match('/^--/', $statement)) {
        try {
            $conn->query($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "Homepage table creation done.\n";
?>
