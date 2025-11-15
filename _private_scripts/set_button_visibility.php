<?php
$conn = new mysqli('localhost', 'root', '', 'mosse_luxe_db');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);
$conn->query('UPDATE hero_slides SET button_visibility = 1 WHERE id = 4');
echo 'Updated button_visibility for slide id 4';
$conn->close();
?>
