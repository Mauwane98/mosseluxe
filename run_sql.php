<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Read the SQL file
$sql = file_get_contents('_private_scripts/database.sql');
echo "Read SQL file...\n";

// Split by statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement) && !preg_match('/^--/', $statement)) {
        // Convert InnoDB to MyISAM if needed for Windows
        $statement = str_replace('ENGINE=InnoDB', 'ENGINE=MyISAM', $statement);
        try {
            $conn->query($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "Done.\n";
?>
