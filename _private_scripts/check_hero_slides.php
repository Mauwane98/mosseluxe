<?php
include_once dirname(__DIR__) . '/includes/bootstrap.php';

$conn = get_db_connection();

$result = $conn->query('SELECT * FROM hero_slides ORDER BY sort_order');
while($row = $result->fetch_assoc()) {
    echo 'ID: ' . $row['id'] .
         ', Title: ' . ($row['title'] ?: 'NULL') .
         ', Subtitle: ' . ($row['subtitle'] ?: 'NULL') .
         ', Button: ' . ($row['button_text'] ?: 'NULL') .
         ', URL: ' . ($row['button_url'] ?: 'NULL') .
         ', Active: ' . $row['is_active'] . PHP_EOL;
}
?>
