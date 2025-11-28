<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// List of page slugs to retrieve
$page_slugs = [
    'shipping-returns',
    'home',
    'faq',
    'careers',
    'about',
    'contact',
    'privacy-policy',
    'terms-of-service'
];

echo "Retrieving page content for the following slugs:\n";
echo implode(', ', $page_slugs) . "\n\n";

foreach ($page_slugs as $slug) {
    $stmt = $conn->prepare("SELECT id, title, subtitle, content, slug FROM pages WHERE slug = ? AND status = 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $page = $result->fetch_assoc();
        echo "=== PAGE: {$page['title']} (slug: {$page['slug']}) ===\n";
        if (!empty($page['subtitle'])) {
            echo "Subtitle: {$page['subtitle']}\n";
        }
        echo "Content:\n";
        echo $page['content'] . "\n";
        echo "\n" . str_repeat("-", 80) . "\n\n";
    } else {
        echo "=== PAGE: $slug ===\n";
        echo "NOT FOUND - No active page with this slug\n\n";
        echo str_repeat("-", 80) . "\n\n";
    }

    $stmt->close();
}

$conn->close();
?>
