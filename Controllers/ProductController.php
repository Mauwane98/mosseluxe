<?php

namespace App\Controllers;

use Twig\Environment;

class ProductController
{
    private $conn;
    private $twig;

    public function __construct($conn, Environment $twig)
    {
        $this->conn = $conn;
        $this->twig = $twig;
    }

    public function index($id)
    {
        $product = null;
        $sql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category = c.id WHERE p.id = ? AND p.status = 1";
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
        }

        if (!$product) {
            // Handle product not found, e.g., redirect to 404 or shop page
            header("Location: /shop");
            exit();
        }

        echo $this->twig->render('product/index.html', [
            'product' => $product,
            'cart_item_count' => $_SESSION['cart_item_count'] ?? 0,
            'loggedin' => $_SESSION['loggedin'] ?? false,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }
}
