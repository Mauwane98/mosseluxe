<?php
/**
 * AJAX Initialization
 * Include this at the start of all AJAX handlers to ensure clean JSON responses
 * This prevents PHP errors/warnings from breaking JSON output
 */

// Suppress any errors/warnings from being displayed (they'll still be logged)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Include bootstrap (it starts its own output buffering)
require_once __DIR__ . '/bootstrap.php';

// Clean ALL output buffers that might have accumulated
$buffer_count = 0;
$total_output = '';
while (ob_get_level() > 0) {
    $buffer = ob_get_clean();
    $buffer_count++;
    $total_output .= $buffer;
    
    // Log any unexpected output for debugging
    if (!empty($buffer)) {
        $caller = debug_backtrace()[0]['file'] ?? 'unknown';
        $hex = bin2hex(substr($buffer, 0, 100));
        error_log("AJAX Handler ($caller) - Buffer #$buffer_count - Length: " . strlen($buffer) . " - Hex: $hex");
        error_log("AJAX Handler ($caller) - Buffer #$buffer_count - Text: " . substr($buffer, 0, 500));
    }
}

// Log total accumulated output
if (!empty($total_output)) {
    error_log("AJAX Handler - TOTAL OUTPUT LENGTH: " . strlen($total_output) . " bytes");
}

// Set JSON header with no-cache to prevent browser caching issues
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
// No closing PHP tag - prevents accidental whitespace output
