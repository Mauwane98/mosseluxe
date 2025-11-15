<?php
$conn = new mysqli('localhost','root','','');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->query('CREATE DATABASE IF NOT EXISTS mosse_luxe_db;');
echo 'Database mosse_luxe_db created or already exists.';
$conn->close();
?>
