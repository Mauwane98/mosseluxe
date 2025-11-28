<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$sql = "SELECT id, title, content, status FROM pages WHERE slug = 'terms-of-service'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Page found:\n";
    echo "ID: " . $row['id'] . "\n";
    echo "Title: " . $row['title'] . "\n";
    echo "Status: " . $row['status'] . "\n";
    echo "Content length: " . strlen($row['content']) . "\n";
    echo "Content preview: " . substr($row['content'], 0, 100) . "\n";
} else {
    echo "Page not found";
}

$conn->close();
?>
