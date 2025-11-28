<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$pages = [
    'shipping-returns' => 'Shipping & Returns',
    'privacy-policy' => 'Privacy Policy'
];

foreach ($pages as $slug => $title) {
    $stmt = $conn->prepare("INSERT IGNORE INTO pages (title, slug, content, status) VALUES (?, ?, '<p>Content for $title will be updated soon.</p>', 1)");
    $stmt->bind_param("ss", $title, $slug);
    $stmt->execute();
    $stmt->close();
    echo "Added/ignored $slug\n";
}
?>
