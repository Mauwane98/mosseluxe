<?php
require_once 'bootstrap.php';
require_once '../includes/auth_service.php';

Auth::logout();

// Redirect to login page
header("location: login.php");
exit;
?>