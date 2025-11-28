<?php
// Router error handling is managed by bootstrap.php
// Logs are written to logs/php_errors.log

// Log router access
error_log("[" . date('Y-m-d H:i:s') . "] Router accessed: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n", 3, __DIR__ . '/logs/router_errors.log');

try {
    $uri = urldecode(
        parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    );
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Error parsing URI: " . $e->getMessage() . "\n", 3, __DIR__ . '/logs/router_errors.log');
    http_response_code(500);
    die('Internal Server Error: Unable to parse request URI');
}

// Serve static files directly FIRST (if this was run via PHP built-in server)
// This must happen before URI processing to avoid path manipulation
if (php_sapi_name() === 'cli-server') {
    $file_path = __DIR__ . $uri;
    if ($uri !== '/' && file_exists($file_path) && is_file($file_path)) {
        error_log("[" . date('Y-m-d H:i:s') . "] Serving static file: $file_path\n", 3, __DIR__ . '/logs/router_errors.log');
        return false; // Let PHP serve the file
    }
}

// Handle /api/ROUTE BEFORE URI processing to preserve the /api prefix
if (preg_match('#^/api/(.*)$#', $uri, $matches)) {
    try {
        $_GET['_route'] = $matches[1];
        error_log("[" . date('Y-m-d H:i:s') . "] Loading API route: {$matches[1]}\n", 3, __DIR__ . '/logs/router_errors.log');
        
        if (!file_exists(__DIR__ . '/api/index.php')) {
            error_log("[" . date('Y-m-d H:i:s') . "] ERROR: api/index.php not found\n", 3, __DIR__ . '/logs/router_errors.log');
            http_response_code(500);
            die('Internal Server Error: API handler not found');
        }
        
        include __DIR__ . '/api/index.php';
        return;
    } catch (Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] Error loading API: " . $e->getMessage() . "\n", 3, __DIR__ . '/logs/router_errors.log');
        http_response_code(500);
        die('Internal Server Error: ' . $e->getMessage());
    }
}

// Strip subdirectory if present (e.g. /mosseluxe/)
try {
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
        $uri = substr($uri, strlen($scriptName));
    }
    if ($uri === '') $uri = '/';
    error_log("[" . date('Y-m-d H:i:s') . "] Processed URI: $uri\n", 3, __DIR__ . '/logs/router_errors.log');
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Error processing URI: " . $e->getMessage() . "\n", 3, __DIR__ . '/logs/router_errors.log');
    http_response_code(500);
    die('Internal Server Error: Unable to process request URI');
}

// Handle /product/ID/SLUG
if (preg_match('#^/product/([0-9]+)(?:/([^/]*))?$#', $uri, $matches)) {
    try {
        $_GET['id'] = $matches[1];
        error_log("[" . date('Y-m-d H:i:s') . "] Loading product-details.php for ID: {$matches[1]}\n", 3, __DIR__ . '/logs/router_errors.log');
        
        if (!file_exists(__DIR__ . '/product-details.php')) {
            error_log("[" . date('Y-m-d H:i:s') . "] ERROR: product-details.php not found\n", 3, __DIR__ . '/logs/router_errors.log');
            http_response_code(500);
            die('Internal Server Error: Product details page not found');
        }
        
        include __DIR__ . '/product-details.php';
        return;
    } catch (Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] Error loading product-details.php: " . $e->getMessage() . "\n", 3, __DIR__ . '/logs/router_errors.log');
        http_response_code(500);
        die('Internal Server Error: ' . $e->getMessage());
    }
}

// API route handling moved above - this section is now removed

// Handle /slug -> /slug.php
try {
    $slug_php = __DIR__ . $uri . '.php';
    if (file_exists($slug_php)) {
        error_log("[" . date('Y-m-d H:i:s') . "] Loading slug file: $slug_php\n", 3, __DIR__ . '/logs/router_errors.log');
        include $slug_php;
        return;
    }
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Error loading slug file: " . $e->getMessage() . "\n", 3, __DIR__ . '/logs/router_errors.log');
    http_response_code(500);
    die('Internal Server Error: ' . $e->getMessage());
}

// If it's just a slash, it's index.php
if ($uri === '/') {
    try {
        error_log("[" . date('Y-m-d H:i:s') . "] Loading index.php\n", 3, __DIR__ . '/logs/router_errors.log');
        
        if (!file_exists(__DIR__ . '/index.php')) {
            error_log("[" . date('Y-m-d H:i:s') . "] ERROR: index.php not found\n", 3, __DIR__ . '/logs/router_errors.log');
            http_response_code(500);
            die('Internal Server Error: Homepage not found');
        }
        
        include __DIR__ . '/index.php';
        return;
    } catch (Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] Error loading index.php: " . $e->getMessage() . "\n", 3, __DIR__ . '/logs/router_errors.log');
        http_response_code(500);
        die('Internal Server Error: ' . $e->getMessage());
    }
}

// 404
error_log("[" . date('Y-m-d H:i:s') . "] 404 Not Found: $uri\n", 3, __DIR__ . '/logs/router_errors.log');
http_response_code(404);

if (file_exists(__DIR__ . '/404.php')) {
    include __DIR__ . '/404.php';
} else {
    error_log("[" . date('Y-m-d H:i:s') . "] ERROR: 404.php not found\n", 3, __DIR__ . '/logs/router_errors.log');
    die('404 - Page Not Found');
}
?>
