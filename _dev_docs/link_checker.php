<?php
/**
 * Internal Link Checker
 * 
 * Crawls the sitemap and checks for broken links (404 errors).
 * Run from CLI: php _dev_docs/link_checker.php
 * 
 * @author Mossé Luxe Development Team
 */

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Configuration
$baseUrl = 'http://localhost/mosseluxe/';
$sitemapPath = __DIR__ . '/../sitemap.xml';
$timeout = 10; // seconds

// Colors for CLI output
define('RED', "\033[31m");
define('GREEN', "\033[32m");
define('YELLOW', "\033[33m");
define('RESET', "\033[0m");

echo "\n";
echo "========================================\n";
echo "  Mossé Luxe Internal Link Checker\n";
echo "========================================\n\n";

// Check if sitemap exists
if (!file_exists($sitemapPath)) {
    echo RED . "ERROR: Sitemap not found at $sitemapPath\n" . RESET;
    exit(1);
}

// Parse sitemap
$sitemap = simplexml_load_file($sitemapPath);
if (!$sitemap) {
    echo RED . "ERROR: Could not parse sitemap\n" . RESET;
    exit(1);
}

$urls = [];
foreach ($sitemap->url as $url) {
    $urls[] = (string) $url->loc;
}

echo "Found " . count($urls) . " URLs in sitemap\n\n";

// Check each URL
$results = [
    'success' => [],
    'redirect' => [],
    'error' => [],
    'timeout' => []
];

foreach ($urls as $index => $url) {
    $progress = sprintf("[%d/%d]", $index + 1, count($urls));
    echo "$progress Checking: $url ... ";
    
    $result = checkUrl($url, $timeout);
    
    switch ($result['status']) {
        case 'success':
            echo GREEN . "OK ({$result['code']})" . RESET . "\n";
            $results['success'][] = $url;
            break;
            
        case 'redirect':
            echo YELLOW . "REDIRECT ({$result['code']} -> {$result['location']})" . RESET . "\n";
            $results['redirect'][] = ['url' => $url, 'code' => $result['code'], 'location' => $result['location']];
            break;
            
        case 'error':
            echo RED . "ERROR ({$result['code']})" . RESET . "\n";
            $results['error'][] = ['url' => $url, 'code' => $result['code']];
            break;
            
        case 'timeout':
            echo RED . "TIMEOUT" . RESET . "\n";
            $results['timeout'][] = $url;
            break;
    }
    
    // Small delay to avoid overwhelming the server
    usleep(100000); // 100ms
}

// Summary
echo "\n";
echo "========================================\n";
echo "  Summary\n";
echo "========================================\n\n";

echo GREEN . "✓ Successful: " . count($results['success']) . RESET . "\n";
echo YELLOW . "→ Redirects: " . count($results['redirect']) . RESET . "\n";
echo RED . "✗ Errors: " . count($results['error']) . RESET . "\n";
echo RED . "⏱ Timeouts: " . count($results['timeout']) . RESET . "\n";

// Show details for errors
if (!empty($results['error'])) {
    echo "\n" . RED . "Broken Links:" . RESET . "\n";
    foreach ($results['error'] as $error) {
        echo "  - [{$error['code']}] {$error['url']}\n";
    }
}

if (!empty($results['redirect'])) {
    echo "\n" . YELLOW . "Redirects (consider updating links):" . RESET . "\n";
    foreach ($results['redirect'] as $redirect) {
        echo "  - [{$redirect['code']}] {$redirect['url']}\n";
        echo "    → {$redirect['location']}\n";
    }
}

// Exit code based on errors
$exitCode = empty($results['error']) && empty($results['timeout']) ? 0 : 1;
exit($exitCode);

/**
 * Check a URL and return its status
 */
function checkUrl(string $url, int $timeout): array
{
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true, // HEAD request
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => false, // Don't follow redirects
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'MosseLuxe-LinkChecker/1.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        if (strpos($error, 'timed out') !== false) {
            return ['status' => 'timeout', 'code' => 0];
        }
        return ['status' => 'error', 'code' => 0, 'error' => $error];
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['status' => 'success', 'code' => $httpCode];
    }
    
    if ($httpCode >= 300 && $httpCode < 400) {
        // Extract Location header
        preg_match('/Location:\s*(.+)/i', $response, $matches);
        $location = isset($matches[1]) ? trim($matches[1]) : 'unknown';
        return ['status' => 'redirect', 'code' => $httpCode, 'location' => $location];
    }
    
    return ['status' => 'error', 'code' => $httpCode];
}
