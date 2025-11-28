<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$sql = "SELECT id, title, slug, status, content FROM pages WHERE slug IN ('about', 'contact', 'careers', 'faq', 'privacy-policy', 'shipping-returns', 'terms-of-service', 'home')";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Slug: {$row['slug']}, Status: {$row['status']}, Content Length: " . strlen($row['content']) . " chars\n";
    }
}

$conn->close();
?>
