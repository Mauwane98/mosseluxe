<?php
// Start output buffering to prevent header sent warnings
ob_start();

use Dotenv\Dotenv;

if (!defined('BOOTSTRAP_LOADED')) {
    define('BOOTSTRAP_LOADED', true);
}

// Ensure session is started at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file using phpdotenv
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Include configuration
require_once __DIR__ . '/config.php';

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Include CSRF protection functions
require_once __DIR__ . '/csrf.php';

// Include image service
require_once __DIR__ . '/image_service.php';

/**
 * Get a setting value from the settings table
 *
 * @param string $key
 * @param string $default
 * @return string
 */
function get_setting($key, $default = '') {
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        $conn = get_db_connection();
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        $result->close();
    }

    return $settings[$key] ?? $default;
}

// Add any other global includes or initializations here
// For example, a general utility file or an autoloader if Composer is used more broadly.

/**
 * Get all active pages for dropdown selectors in admin
 *
 * @return array Array of pages with id, title, slug
 */
function get_pages_for_dropdown() {
    static $pages = null;

    if ($pages === null) {
        $pages = [];
        $conn = get_db_connection();
        $sql = "SELECT id, title, slug FROM pages WHERE status = 1 ORDER BY title";
        if ($result = $conn->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $pages[] = $row;
            }
            $result->close();
        }
    }

    return $pages;
}

?>
