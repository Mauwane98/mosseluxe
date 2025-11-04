<?php

namespace App\Controllers;

use Twig\Environment;

class WishlistController
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
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit();
        }

        $user_id = $_SESSION['user_id'];
        $wishlist_items = [];

        $sql = "SELECT p.id, p.name, p.price, p.sale_price, p.image FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ? ORDER BY w.created_at DESC";
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $wishlist_items[] = $row;
            }
            $stmt->close();
        }

        echo $this->twig->render('wishlist/index.html', [
            'wishlist_items' => $wishlist_items,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

    public function toggle()
    {
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header("Location: /login");
            exit();
        }

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Invalid CSRF token!');
        }

        $user_id = $_SESSION['user_id'];
        $product_id = filter_var(trim($_POST['product_id']), FILTER_SANITIZE_NUMBER_INT);
        $action = trim($_POST['action']);

        if (!$product_id) {
            header("Location: /shop?error=invalid_product");
            exit();
        }

        if ($action === 'add') {
            $sql = "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)";
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param("ii", $user_id, $product_id);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($action === 'remove') {
            $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param("ii", $user_id, $product_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Redirect back to the page where the action was initiated, or to the wishlist page
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: /wishlist");
        }
        exit();
    }
}
