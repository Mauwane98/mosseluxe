<?php

require_once __DIR__ . '/../includes/config.php';

// Database connection details from config.php
$host = DB_HOST;
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected to database successfully.\n";

    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/database.sql');

    if ($sql === false) {
        die("Error: Could not read database.sql file.\n");
    }

    // Execute SQL queries
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    echo "Disabled foreign key checks.\n";
    $pdo->exec($sql);
    echo "Database schema created successfully.\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Enabled foreign key checks.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}


?>
