<?php
// tests/setup_test_db.php

$db_host = 'localhost';
$db_user = 'testuser';
$db_pass = 'testpassword';
$db_name = 'test_mosse_luxe';

$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the test database
$conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
$conn->select_db($db_name);

// Create the discount_codes table
$conn->query("
CREATE TABLE `discount_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `usage_limit` int(11) NOT NULL DEFAULT 1,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// Insert a test discount code
$conn->query("INSERT INTO `discount_codes` (`code`, `type`, `value`, `usage_limit`, `usage_count`, `is_active`) VALUES ('TESTCODE', 'fixed', '10.00', 1, 0, 1)");

$conn->close();
