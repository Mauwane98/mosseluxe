<?php

namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class HomeController
{
    private $conn;
    private $twig;

    public function __construct($conn, Environment $twig)
    {
        $this->conn = $conn;
        $this->twig = $twig;
    }

    public function index()
    {
        // Fetch featured products
        $featuredProducts = [];
        $sqlFeatured = "SELECT id, name, price, sale_price, image, status FROM products WHERE is_featured = 1 AND status = 1 ORDER BY id DESC LIMIT 4";
        $resultFeatured = $this->conn->query($sqlFeatured);
        if ($resultFeatured && $resultFeatured->num_rows > 0) {
            while ($product = $resultFeatured->fetch_assoc()) {
                $featuredProducts[] = $product;
            }
        }

        // Fetch launching soon items
        $launchingSoonItems = [];
        $sqlLaunchingSoon = "SELECT * FROM launching_soon WHERE status = 1 ORDER BY id DESC LIMIT 2";
        $resultLaunchingSoon = $this->conn->query($sqlLaunchingSoon);
        if ($resultLaunchingSoon && $resultLaunchingSoon->num_rows > 0) {
            while ($item = $resultLaunchingSoon->fetch_assoc()) {
                $launchingSoonItems[] = $item;
            }
        }

        echo $this->twig->render('home/index.html', [
            'featuredProducts' => $featuredProducts,
            'launchingSoonItems' => $launchingSoonItems
        ]);
    }
}