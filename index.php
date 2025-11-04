<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Error reporting (should be configured in .env or config file for production)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and database connection
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_connect.php';
$conn = get_db_connection();
require_once __DIR__ . '/includes/csrf.php';

// Create Router instance
$router = new Bramus\Router\Router();

// Initialize Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
            // 'cache' => __DIR__ . '/cache/twig',
    ]);
    
    $twig->addFunction(new \Twig\TwigFunction('url_encode', 'urlencode'));
    
    // Pass Twig to controllers or make it globally accessible if needed// For now, we'll pass it to the HomeController constructor

// Define routes
// Example: $router->get('/', function() { echo 'Welcome!'; });

// Require web routes
require_once __DIR__ . '/routes/web.php';

// Run the router
$router->run();