<?php
/**
 * Web-Accessible Cart System Initialization
 */

// Enable comprehensive error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Cart System Init</title></head><body><h1>Initializing Cart System...</h1><pre>";

// Include the cart system initialization
require_once 'run_cart_system_init.php';

echo "</pre><h2>✅ Cart System Initialization Complete!</h2>";
echo "<p><a href='bug_checker.php'>← Run Bug Checker to verify fixes</a></p>";
echo "<p><a href='shop.php'>🛒 Test the Shop</a></p>";
?></body></html>
