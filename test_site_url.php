<?php
require_once 'includes/bootstrap.php';
echo "SITE_URL constant: " . SITE_URL . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
?>
