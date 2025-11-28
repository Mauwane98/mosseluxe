<?php
/**
 * Referral Tracking System
 * Tracks referral links without requiring database tables
 */

// Track referral in session
function track_referral() {
    // Check if there's a referral code in the URL
    if (isset($_GET['ref']) && !empty($_GET['ref'])) {
        $referral_code = sanitize_text_field($_GET['ref']);
        
        // Store in session for 30 days worth of browsing
        $_SESSION['referral_code'] = $referral_code;
        $_SESSION['referral_timestamp'] = time();
        
        // Store in cookie as backup (30 days)
        setcookie('mosseluxe_ref', $referral_code, time() + (30 * 24 * 60 * 60), '/');
        
        // Log the referral visit
        error_log("Referral tracked: Code={$referral_code}, IP=" . $_SERVER['REMOTE_ADDR']);
    }
}

// Get stored referral code
function get_referral_code() {
    // Check session first
    if (isset($_SESSION['referral_code'])) {
        return $_SESSION['referral_code'];
    }
    
    // Check cookie as fallback
    if (isset($_COOKIE['mosseluxe_ref'])) {
        return $_COOKIE['mosseluxe_ref'];
    }
    
    return null;
}

// Generate referral code for user
function generate_user_referral_code($user_id) {
    return 'ML' . strtoupper(substr(md5($user_id . 'mosseluxe'), 0, 6));
}

// Get referral link for user
function get_user_referral_link($user_id) {
    $code = generate_user_referral_code($user_id);
    return SITE_URL . '?ref=' . $code;
}

// Decode referral code to get user ID (for future use when tables are created)
function decode_referral_code($code) {
    // This would query the database when tables are available
    // For now, we just validate the format
    if (preg_match('/^ML[A-Z0-9]{6}$/', $code)) {
        return true;
    }
    return false;
}

// Clear referral tracking (after successful conversion)
function clear_referral_tracking() {
    unset($_SESSION['referral_code']);
    unset($_SESSION['referral_timestamp']);
    setcookie('mosseluxe_ref', '', time() - 3600, '/');
}

// Check if referral is still valid (within 30 days)
function is_referral_valid() {
    if (!isset($_SESSION['referral_timestamp'])) {
        return false;
    }
    
    $age = time() - $_SESSION['referral_timestamp'];
    $max_age = 30 * 24 * 60 * 60; // 30 days
    
    return $age < $max_age;
}

// Sanitize text field
function sanitize_text_field($text) {
    return preg_replace('/[^A-Z0-9]/i', '', $text);
}

// Auto-track referrals on page load
if (session_status() === PHP_SESSION_ACTIVE) {
    track_referral();
}
// No closing PHP tag - prevents accidental whitespace output