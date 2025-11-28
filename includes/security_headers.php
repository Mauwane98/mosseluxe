<?php
/**
 * Security Headers Configuration
 * Add additional security headers to protect against common vulnerabilities
 */

// Only set headers if not already sent
if (!headers_sent()) {
    // Prevent clickjacking attacks
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection in browsers
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust as needed for your site)
    if (defined('APP_ENV') && APP_ENV === 'production') {
        // Stricter CSP for production
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://www.google-analytics.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://api.yoco.com https://www.payfast.co.za;");
    }
    
    // Force HTTPS in production
    if (defined('APP_ENV') && APP_ENV === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    
    // Permissions Policy (formerly Feature Policy)
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}
// No closing PHP tag - prevents accidental whitespace output