<?php

// Simple script to create database and tables
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect without database first
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS mosse_luxe_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Database created successfully.\n";

    // Now connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=mosse_luxe_db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read and execute the schema
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $sql = file_get_contents(__DIR__ . '/database.sql');
    $pdo->exec($sql);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "Database schema created successfully.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}

?>
