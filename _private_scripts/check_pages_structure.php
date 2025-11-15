<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

// Check pages table structure
echo "Pages table structure:\n";
$result = $conn->query('DESCRIBE pages');
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']} ({$row['Type']})" . (!empty($row['Comment']) ? " - {$row['Comment']}" : "") . "\n";
}

echo "\nCurrent pages in database:\n";
$result = $conn->query('SELECT id, title, slug FROM pages ORDER BY title');
$count = 0;
while ($row = $result->fetch_assoc()) {
    echo "- ID: {$row['id']} | Title: {$row['title']} | Slug: {$row['slug']}\n";
    $count++;
}

echo "\nTotal pages: $count\n";

$conn->close();
?>
