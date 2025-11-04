<?php
/**
 * This script ensures that the current user is an authenticated admin.
 * It centralizes the authentication check for all admin pages.
 */

require_once __DIR__ . '/auth_service.php';

// The checkAdmin method will handle session start, verification, and redirection if needed.
Auth::checkAdmin();
?>
