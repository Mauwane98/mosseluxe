<?php

use App\Controllers\HomeController;
use App\Controllers\ShopController;
use App\Controllers\ProductController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\ApplyDiscountController;
use App\Controllers\ProductDetailsController;
use App\Controllers\WishlistController;
use App\Controllers\LoginController;
use App\Controllers\RegisterController;
use App\Controllers\ForgotPasswordController;
use App\Controllers\ResetPasswordController;

// Web Routes

$router->get('/', function() use ($conn, $twig) {
    $controller = new HomeController($conn, $twig);
    $controller->index();
});

$router->get('/shop', function() use ($conn, $twig) {
    $controller = new ShopController($conn, $twig);
    $controller->index();
});

$router->get('/product/(\d+)', function($id) use ($conn, $twig) {
    $controller = new ProductController($conn, $twig);
    $controller->index($id);
});

$router->get('/cart', function() use ($conn, $twig) {
    $controller = new CartController($conn, $twig);
    $controller->index();
});

$router->post('/cart/add', function() use ($conn, $twig) {
    $controller = new CartController($conn, $twig);
    $controller->add();
});

$router->post('/cart/update', function() use ($conn, $twig) {
    $controller = new CartController($conn, $twig);
    $controller->update();
});

$router->post('/cart/remove', function() use ($conn, $twig) {
    $controller = new CartController($conn, $twig);
    $controller->remove();
});

$router->get('/checkout', function() use ($conn, $twig) {
    $controller = new CheckoutController($conn, $twig);
    $controller->index();
});

$router->post('/checkout', function() use ($conn, $twig) {
    $controller = new CheckoutController($conn, $twig);
    $controller->placeOrder();
});

$router->post('/apply-discount', function() use ($conn) {
    $controller = new ApplyDiscountController($conn);
    $controller->apply();
});

$router->get('/product-details/(\d+)', function($id) use ($conn) {
    $controller = new ProductDetailsController($conn);
    $controller->getDetails($id);
});

$router->get('/wishlist', function() use ($conn, $twig) {
    $controller = new WishlistController($conn, $twig);
    $controller->index();
});

$router->post('/wishlist/toggle', function() use ($conn) {
    $controller = new WishlistController($conn, null);
    $controller->toggle();
});

$router->get('/login', function() use ($conn, $twig) {
    $controller = new LoginController($conn, $twig);
    $controller->index();
});

$router->post('/login', function() use ($conn, $twig) {
    $controller = new LoginController($conn, $twig);
    $controller->authenticate();
});

$router->get('/logout', function() use ($conn, $twig) {
    $controller = new LoginController($conn, $twig);
    $controller->logout();
});

$router->get('/register', function() use ($conn, $twig) {
    $controller = new RegisterController($conn, $twig);
    $controller->index();
});

$router->post('/register', function() use ($conn, $twig) {
    $controller = new RegisterController($conn, $twig);
    $controller->register();
});

$router->get('/forgot-password', function() use ($conn, $twig) {
    $controller = new ForgotPasswordController($conn, $twig);
    $controller->index();
});

$router->post('/forgot-password', function() use ($conn, $twig) {
    $controller = new ForgotPasswordController($conn, $twig);
    $controller->sendResetLink();
});

$router->get('/reset-password', function() use ($conn, $twig) {
    $controller = new ResetPasswordController($conn, $twig);
    $controller->index();
});

$router->post('/reset-password', function() use ($conn, $twig) {
    $controller = new ResetPasswordController($conn, $twig);
    $controller->resetPassword();
});

$router->get('/about', function() use ($twig) {
    echo $twig->render('about/index.html');
});

$router->get('/contact', function() use ($twig) {
    echo $twig->render('contact/index.html');
});