<?php
/**
 * This script ensures that the current user is an authenticated admin.
 * It centralizes the authentication check for all admin pages.
 */

require_once __DIR__ . '/config.php'; // Ensures session is started first
require_once __DIR__ . '/auth_service.php';

// The checkAdmin method will handle verification and redirection if needed.
Auth::checkAdmin();
?>
