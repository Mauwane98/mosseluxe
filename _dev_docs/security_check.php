<?php
/**
 * Security Check Script
 * 
 * Tests for common security vulnerabilities in the deployment.
 * Run from CLI: php _dev_docs/security_check.php
 * 
 * @author Mossé Luxe Development Team
 */

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Configuration
$baseUrl = 'http://localhost/mosseluxe/';
$timeout = 5;

// Colors for CLI output
define('RED', "\033[31m");
define('GREEN', "\033[32m");
define('YELLOW', "\033[33m");
define('RESET', "\033[0m");

echo "\n";
echo "========================================\n";
echo "  Mossé Luxe Security Check\n";
echo "========================================\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

// Test 1: .env file should not be accessible
echo "1. Checking .env file access... ";
$result = checkUrl($baseUrl . '.env', $timeout);
if ($result['code'] === 403 || $result['code'] === 404) {
    echo GREEN . "PASS (blocked)" . RESET . "\n";
    $passed++;
} else {
    echo RED . "FAIL (accessible - HTTP {$result['code']})" . RESET . "\n";
    $failed++;
}

// Test 2: .htaccess should not be accessible
echo "2. Checking .htaccess file access... ";
$result = checkUrl($baseUrl . '.htaccess', $timeout);
if ($result['code'] === 403 || $result['code'] === 404) {
    echo GREEN . "PASS (blocked)" . RESET . "\n";
    $passed++;
} else {
    echo RED . "FAIL (accessible - HTTP {$result['code']})" . RESET . "\n";
    $failed++;
}

// Test 3: composer.json should not be accessible
echo "3. Checking composer.json access... ";
$result = checkUrl($baseUrl . 'composer.json', $timeout);
if ($result['code'] === 403 || $result['code'] === 404) {
    echo GREEN . "PASS (blocked)" . RESET . "\n";
    $passed++;
} else {
    echo RED . "FAIL (accessible - HTTP {$result['code']})" . RESET . "\n";
    $failed++;
}

// Test 4: _private_scripts should not be accessible
echo "4. Checking _private_scripts access... ";
$result = checkUrl($baseUrl . '_private_scripts/', $timeout);
if ($result['code'] === 403 || $result['code'] === 404) {
    echo GREEN . "PASS (blocked)" . RESET . "\n";
    $passed++;
} else {
    echo RED . "FAIL (accessible - HTTP {$result['code']})" . RESET . "\n";
    $failed++;
}

// Test 5: _archive should not be accessible
echo "5. Checking _archive access... ";
$result = checkUrl($baseUrl . '_archive/', $timeout);
if ($result['code'] === 403 || $result['code'] === 404) {
    echo GREEN . "PASS (blocked)" . RESET . "\n";
    $passed++;
} else {
    echo RED . "FAIL (accessible - HTTP {$result['code']})" . RESET . "\n";
    $failed++;
}

// Test 6: includes directory should not be accessible
echo "6. Checking includes directory access... ";
$result = checkUrl($baseUrl . 'includes/', $timeout);
if ($result['code'] === 403 || $result['code'] === 404) {
    echo GREEN . "PASS (blocked)" . RESET . "\n";
    $passed++;
} else {
    echo RED . "FAIL (accessible - HTTP {$result['code']})" . RESET . "\n";
    $failed++;
}

// Test 7: app directory should not be accessible
echo "7. Checking app directory access... ";
$result = checkUrl($baseUrl . 'app/', $timeout);
if ($result['code'] === 403 || $result['code'] === 404) {
    echo GREEN . "PASS (blocked)" . RESET . "\n";
    $passed++;
} else {
    echo RED . "FAIL (accessible - HTTP {$result['code']})" . RESET . "\n";
    $failed++;
}

// Test 8: logs directory should not be accessible
echo "8. Checking logs directory access... ";
$result = checkUrl($baseUrl . 'logs/', $timeout);
if ($result['code'] === 403 || $result['code'] === 404) {
    echo GREEN . "PASS (blocked)" . RESET . "\n";
    $passed++;
} else {
    echo RED . "FAIL (accessible - HTTP {$result['code']})" . RESET . "\n";
    $failed++;
}

// Test 9: vendor directory should not be accessible
echo "9. Checking vendor directory access... ";
$result = checkUrl($baseUrl . 'vendor/', $timeout);
if ($result['code'] === 403 || $result['code'] === 404) {
    echo GREEN . "PASS (blocked)" . RESET . "\n";
    $passed++;
} else {
    echo RED . "FAIL (accessible - HTTP {$result['code']})" . RESET . "\n";
    $failed++;
}

// Test 10: Check for directory listing disabled
echo "10. Checking directory listing disabled... ";
$result = checkUrl($baseUrl . 'assets/', $timeout);
if ($result['code'] === 403 || strpos($result['body'] ?? '', 'Index of') === false) {
    echo GREEN . "PASS (disabled)" . RESET . "\n";
    $passed++;
} else {
    echo YELLOW . "WARNING (may be enabled)" . RESET . "\n";
    $warnings++;
}

// Test 11: Check security headers
echo "11. Checking security headers... ";
$result = checkUrl($baseUrl, $timeout, true);
$headers = $result['headers'] ?? [];

$securityHeaders = [
    'X-Frame-Options' => false,
    'X-Content-Type-Options' => false,
    'X-XSS-Protection' => false
];

foreach ($headers as $header) {
    if (stripos($header, 'X-Frame-Options') !== false) {
        $securityHeaders['X-Frame-Options'] = true;
    }
    if (stripos($header, 'X-Content-Type-Options') !== false) {
        $securityHeaders['X-Content-Type-Options'] = true;
    }
    if (stripos($header, 'X-XSS-Protection') !== false) {
        $securityHeaders['X-XSS-Protection'] = true;
    }
}

$headersPassed = array_filter($securityHeaders);
if (count($headersPassed) === 3) {
    echo GREEN . "PASS (all present)" . RESET . "\n";
    $passed++;
} elseif (count($headersPassed) > 0) {
    echo YELLOW . "PARTIAL (" . count($headersPassed) . "/3 headers)" . RESET . "\n";
    $warnings++;
} else {
    echo RED . "FAIL (no security headers)" . RESET . "\n";
    $failed++;
}

// Test 12: SQL injection test on product page
echo "12. Testing SQL injection protection... ";
$result = checkUrl($baseUrl . "product-details.php?id=1'OR'1'='1", $timeout);
if ($result['code'] === 404 || $result['code'] === 400) {
    echo GREEN . "PASS (blocked)" . RESET . "\n";
    $passed++;
} elseif ($result['code'] === 200 && strpos($result['body'] ?? '', 'Product Not Found') !== false) {
    echo GREEN . "PASS (sanitized)" . RESET . "\n";
    $passed++;
} else {
    echo YELLOW . "REVIEW (check manually)" . RESET . "\n";
    $warnings++;
}

// Summary
echo "\n";
echo "========================================\n";
echo "  Summary\n";
echo "========================================\n\n";

echo GREEN . "✓ Passed: $passed" . RESET . "\n";
echo YELLOW . "⚠ Warnings: $warnings" . RESET . "\n";
echo RED . "✗ Failed: $failed" . RESET . "\n";

if ($failed > 0) {
    echo "\n" . RED . "SECURITY ISSUES DETECTED!" . RESET . "\n";
    echo "Please fix the failed checks before deploying to production.\n";
    exit(1);
} elseif ($warnings > 0) {
    echo "\n" . YELLOW . "Some warnings detected. Review recommended." . RESET . "\n";
    exit(0);
} else {
    echo "\n" . GREEN . "All security checks passed!" . RESET . "\n";
    exit(0);
}

/**
 * Check a URL and return its status
 */
function checkUrl(string $url, int $timeout, bool $includeHeaders = false): array
{
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => $includeHeaders,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'MosseLuxe-SecurityCheck/1.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    curl_close($ch);
    
    $result = ['code' => $httpCode];
    
    if ($includeHeaders && $response) {
        $headerStr = substr($response, 0, $headerSize);
        $result['headers'] = explode("\r\n", $headerStr);
        $result['body'] = substr($response, $headerSize);
    } else {
        $result['body'] = $response;
    }
    
    return $result;
}
