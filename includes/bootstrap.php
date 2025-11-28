<?php
// Production-safe error handling based on environment
if (defined('APP_ENV') && APP_ENV === 'production') {
    // Production: Log errors, don't display them
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    // Development: Display errors for debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Start output buffering immediately to prevent any header issues
ob_start();

// Use composer's autoloader first
use Dotenv\Dotenv;

if (!defined('BOOTSTRAP_LOADED')) {
    define('BOOTSTRAP_LOADED', true);
}

// Ensure session is started BEFORE any other operations
if (session_status() == PHP_SESSION_NONE) {
    // Set secure session settings - do this quietly to avoid warnings
    $current_error_reporting = error_reporting();
    error_reporting($current_error_reporting & ~E_WARNING);

    ini_set('session.cookie_httponly', 1);
    // Enable secure cookies in production (HTTPS required)
    $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    ini_set('session.cookie_secure', $is_https ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax'); // Allow cookies in same-site requests
    ini_set('session.cookie_lifetime', 86400); // 24 hours
    ini_set('session.gc_maxlifetime', 86400); // 24 hours
    session_start();

    error_reporting($current_error_reporting); // Restore error reporting level
}

// Include Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file using phpdotenv
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad(); // Use safeLoad instead of load to prevent errors if .env is missing
} catch (Exception $e) {
    // Log error but continue - environment variables may be set elsewhere
    error_log("Warning: Could not load .env file: " . $e->getMessage());
}

// Include cart functions
require_once __DIR__ . '/cart_functions.php';

// Include referral tracker
require_once __DIR__ . '/referral_tracker.php';

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Include CSRF protection functions
require_once __DIR__ . '/csrf.php';

// Include security headers
require_once __DIR__ . '/security_headers.php';

// Only generate CSRF token if not exists (prevents regeneration on every request)
// Security: Secure cookies and httponly settings prevent most session fixation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize cart session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Include image service
require_once __DIR__ . '/image_service.php';

// Include variant service functions
require_once __DIR__ . '/variant_service.php';
require_once __DIR__ . '/engagement_service.php';

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
        try {
            $conn = get_db_connection();
            $result = $conn->query("SELECT setting_key, setting_value FROM settings");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
                $result->close();
            } else {
                // Settings table doesn't exist, log error but continue
                error_log("Settings table query failed: " . $conn->error);
            }
        } catch (Exception $e) {
            error_log("Error loading settings: " . $e->getMessage());
        }
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

// No closing PHP tag - prevents accidental whitespace output