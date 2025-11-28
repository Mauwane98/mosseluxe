<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

echo "Checking all homepage sections:\n\n";

$result = $conn->query("SELECT section_key, section_name, title, button_text, button_url, is_active, sort_order FROM homepage_sections WHERE is_active = 1 ORDER BY sort_order ASC");
if ($result->num_rows > 0) {
    while ($section = $result->fetch_assoc()) {
        echo "📄 Section: {$section['section_name']} ({$section['section_key']})\n";
        echo "   Title: {$section['title']}\n";
        echo "   Button: {$section['button_text']} → {$section['button_url']}\n";
        echo "   Sort Order: {$section['sort_order']}\n";
        echo "   Status: Active\n\n";
    }
} else {
    echo "✗ No active homepage sections found\n";
}

$conn->close();
?>
