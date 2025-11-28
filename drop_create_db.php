<?php

// Simple script to drop and recreate database
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect without database first
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop database if exists
    $pdo->exec("DROP DATABASE IF EXISTS mosse_luxe_db");
    echo "Database dropped successfully.\n";

    // Create database
    $pdo->exec("CREATE DATABASE mosse_luxe_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Database created successfully.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}

?>
