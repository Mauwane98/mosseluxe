<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

echo "Adding subtitle column to pages table...\n";

// Add subtitle column to pages table
$alter_sql = "ALTER TABLE pages ADD COLUMN subtitle VARCHAR(255) DEFAULT NULL AFTER title";

if ($conn->query($alter_sql)) {
    echo "✓ Successfully added subtitle column to pages table\n";

    // Update existing pages with their subtitles
    $updates = [
        'careers' => 'Be part of the Mossé Luxe family and help shape the future of luxury streetwear.',
        'faq' => 'Find answers to common questions about your shopping experience.',
        'shipping-returns' => 'Everything you need to know about shipping, returns, and exchanges.',
        'privacy-policy' => 'How we protect and handle your personal information.',
        'terms-of-service' => 'Our terms and conditions for using Mossé Luxe.'
    ];

    foreach ($updates as $slug => $subtitle) {
        $update_sql = "UPDATE pages SET subtitle = ? WHERE slug = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ss", $subtitle, $slug);

        if ($stmt->execute()) {
            echo "✓ Updated subtitle for {$slug}\n";
        } else {
            echo "✗ Failed to update subtitle for {$slug}\n";
        }

        $stmt->close();
    }

} else {
    echo "✗ Failed to add subtitle column: " . $conn->error . "\n";
}

$conn->close();
echo "\nSubtitle column migration completed!\n";
?>
