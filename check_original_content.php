<?php
// Read the backup database file to see what original content existed
$backup_file = 'backup_db.sql';
if (file_exists($backup_file)) {
    $content = file_get_contents($backup_file);

    // Look for about page content
    $about_matches = [];
    preg_match_all('/INSERT INTO `pages` VALUES[^;]+/', $content, $matches);

    foreach ($matches[0] as $insert) {
        if (strpos($insert, 'about') !== false || strpos($insert, 'About') !== false) {
            echo "Found pages insert statement:\n";
            echo $insert . "\n\n";
        }
    }

    // Also look for homepage sections content
    preg_match_all('/INSERT INTO `homepage_sections` VALUES[^;]+/', $content, $matches);
    if (!empty($matches[0])) {
        echo "Homepage sections found in backup:\n";
        foreach ($matches[0] as $insert) {
            echo $insert . "\n\n";
        }
    }
} else {
    echo "Backup file not found.\n";
}
?>
