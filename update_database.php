<?php
require_once 'includes/db_connect.php';
$conn = get_db_connection();

echo "<h3>Updating database schema...</h3>";

$sql_content = file_get_contents('database.sql');

// Find all table names from the CREATE TABLE statements
preg_match_all('/CREATE TABLE `([^`]+)`/', $sql_content, $matches);
$tables = $matches[1];

// 1. Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0;");
echo "<p>Foreign key checks disabled.</p>";

// 2. Drop all tables found in the SQL file
if (!empty($tables)) {
    foreach (array_reverse($tables) as $table) { // Drop in reverse order to help with dependencies
        $drop_sql = "DROP TABLE IF EXISTS `$table`;";
        if ($conn->query($drop_sql)) {
            echo "<p>Table `$table` dropped successfully.</p>";
        } else {
            echo "<p style='color: red;'>Error dropping table `$table`: " . $conn->error . "</p>";
        }
    }
}

// 3. Re-execute the entire database.sql file to create all tables
if ($conn->multi_query($sql_content)) {
    // We need to loop through and clear results from each query
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "<p style='color: green;'>Database schema created successfully.</p>";
} else {
    echo "<p style='color: red;'>Error creating database schema: " . $conn->error . "</p>";
}

// 4. Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1;");
echo "<p>Foreign key checks enabled.</p>";

echo "<h4>Database update complete. You may now want to <a href='seed_database.php'>seed the database</a> with initial data.</h4>";

$conn->close();
?>
