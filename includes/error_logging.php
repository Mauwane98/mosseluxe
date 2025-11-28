<?php
/**
 * Error Logging and Production Configuration
 */

// Enable error logging in production
if (APP_ENV === 'production') {
    // Disable display of errors
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');

    // Enable error logging
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

    // Set error reporting level for production
    error_reporting(E_ALL);
} else {
    // Development settings - show all errors
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Custom error handler for production
function production_error_handler($errno, $errstr, $errfile, $errline) {
    $error_message = date('Y-m-d H:i:s') . " - Error [$errno]: $errstr in $errfile on line $errline\n";

    if (APP_ENV === 'production') {
        // Log to file
        error_log($error_message, 3, __DIR__ . '/../logs/php_errors.log');

        // For critical errors, send email notification (optional)
        if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR) {
            $subject = "Critical Error on Mossé Luxe Website";
            $message = "A critical error occurred on the website.\n\n" . $error_message;
            send_admin_notification($subject, $message);
        }

        // Don't display error to user in production
        return true;
    }

    // Show error in development
    return false;
}

// Custom exception handler
function production_exception_handler($exception) {
    $error_message = date('Y-m-d H:i:s') . " - Uncaught Exception: " . $exception->getMessage() .
                    " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";

    // Clean up session data on critical failures
    cleanup_session_on_error();

    if (APP_ENV === 'production') {
        error_log($error_message, 3, __DIR__ . '/../logs/php_errors.log');

        // Show user-friendly error page
        http_response_code(500);
        include __DIR__ . '/../500.php';
        exit();
    } else {
        echo "<pre>Fatal Error: $error_message</pre>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    }
}

// Set custom error and exception handlers
set_error_handler('production_error_handler');
set_exception_handler('production_exception_handler');

/**
 * Send admin notification email
 */
function send_admin_notification($subject, $message) {
    $admin_email = get_setting('admin_email', 'admin@mosseluxe.com');

    $headers = 'From: no-reply@mosseluxe.com' . "\r\n" .
               'Reply-To: no-reply@mosseluxe.com' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    // Only try to send if SMTP is configured
    if (function_exists('mail') && !empty(get_setting('smtp_host'))) {
        mail($admin_email, $subject, $message, $headers);
    }
}

/**
 * Log security events
 */
function log_security_event($event_type, $message, $user_id = null, $ip_address = null) {
    $log_entry = date('Y-m-d H:i:s') . " - SECURITY [$event_type]: $message";
    $log_entry .= " | User: " . ($user_id ?: 'Anonymous');
    $log_entry .= " | IP: " . ($ip_address ?: $_SERVER['REMOTE_ADDR'] ?? 'Unknown');

    error_log($log_entry . "\n", 3, __DIR__ . '/../logs/security.log');
}

/**
 * Validate input data
 */
function sanitize_input($data, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'string':
        default:
            return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Rate limiting function (basic implementation)
 */
function check_rate_limit($action, $max_attempts = 5, $time_window = 300) {
    $session_key = "rate_limit_$action";
    $now = time();

    if (!isset($_SESSION[$session_key])) {
        $_SESSION[$session_key] = ['attempts' => 0, 'first_attempt' => $now];
    }

    $data = &$_SESSION[$session_key];

    // Reset counter if time window has passed
    if ($now - $data['first_attempt'] > $time_window) {
        $data['attempts'] = 0;
        $data['first_attempt'] = $now;
    }

    $data['attempts']++;

    if ($data['attempts'] > $max_attempts) {
        log_security_event('RATE_LIMIT_EXCEEDED', "Rate limit exceeded for action: $action");
        return false;
    }

    return true;
}

/**
 * Clean up session data on critical errors to prevent corrupted state
 */
function cleanup_session_on_error() {
    // Clear cart data and other non-essential session variables on critical errors
    // Preserve authentication data to avoid logging users out unnecessarily
    $preserve_keys = ['admin_loggedin', 'admin_id', 'admin_name', 'admin_role', 'user_id', 'name', 'email', 'loggedin'];

    foreach ($_SESSION as $key => $value) {
        if (!in_array($key, $preserve_keys)) {
            unset($_SESSION[$key]);
        }
    }

    // Log the session cleanup
    log_security_event('SESSION_CLEANUP', 'Session data cleaned up after critical error');
}

/**
 * Create necessary log directories
 */
$logs_dir = __DIR__ . '/../logs';
if (!file_exists($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

// Create log files if they don't exist
$log_files = ['php_errors.log', 'security.log', 'access.log'];
foreach ($log_files as $log_file) {
    $log_path = $logs_dir . '/' . $log_file;
    if (!file_exists($log_path)) {
        touch($log_path);
        chmod($log_path, 0644);
    }
}

// No closing PHP tag - prevents accidental whitespace output