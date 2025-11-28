<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Read the SQL file
$sql = file_get_contents('create_missing_tables.sql');
echo "Read missing tables SQL file...\n";

// Split by statements
$statements = array_filter(array_map('trim', explode(';', $sql)));
echo "Number of statements: " . count($statements) . "\n";

foreach ($statements as $statement) {
    if (!empty($statement)) {
        // Remove comment lines
        $statement = preg_replace('/^--.*$/m', '', $statement);
        $statement = trim($statement);
        if (!empty($statement)) {
            // Convert InnoDB to MyISAM if needed for Windows
            $statement = str_replace('ENGINE=InnoDB', 'ENGINE=MyISAM', $statement);
            echo "Executing: " . substr($statement, 0, 100) . "...\n";
            try {
                $conn->query($statement);
                echo "Success\n";
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "Missing tables creation done.\n";
?>
