<?php
// Simple test to check if admin/manage_homepage.php can be loaded without fatal errors
try {
    // Include just to check if the includes work
    require_once 'admin/bootstrap.php';
    require_once 'includes/bootstrap.php';
    $conn = get_db_connection();

    // Test if functions exist
    if (function_exists('get_pages_for_dropdown')) {
        echo "get_pages_for_dropdown function exists\n";
    }

    // Check if tables exist
    $result = $conn->query("SHOW TABLES LIKE 'homepage_sections'");
    if ($result && $result->num_rows > 0) {
        echo "homepage_sections table exists\n";
    } else {
        echo "homepage_sections table does NOT exist\n";
    }

    $result = $conn->query("SHOW TABLES LIKE 'hero_slides'");
    if ($result && $result->num_rows > 0) {
        echo "hero_slides table exists\n";
    } else {
        echo "hero_slides table does NOT exist\n";
    }

    echo "Dependencies check passed\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
