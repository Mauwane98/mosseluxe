<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_service.php';

// Use the centralized logout method to destroy the session securely
Auth::logout();

header("Location: index.php");
exit();
?>
