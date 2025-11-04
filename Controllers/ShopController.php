<?php

namespace App\Controllers;

use Twig\Environment;

class ShopController
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
        // Initialize variables for product fetching
        $products = [];
        $category_filter = $_GET['category'] ?? '';
        $sort_by = $_GET['sort'] ?? 'default';

        $sql = "SELECT p.id, p.name, p.price, p.sale_price, p.image, COALESCE(p.sale_price, p.price) as effective_price FROM products p LEFT JOIN categories c ON p.category = c.id WHERE p.status = 1";
        $sql_count = "SELECT COUNT(p.id) FROM products p LEFT JOIN categories c ON p.category = c.id WHERE p.status = 1";

        $params = [];
        $types = '';

        // Handle category filtering
        if (!empty($category_filter)) {
            $sql .= " AND c.name = ?";
            $sql_count .= " AND c.name = ?";
            $params[] = $category_filter;
            $types .= 's';
        }

        // --- Pagination Logic ---
        $items_per_page = 9; // 3x3 grid
        $current_page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT) : 1;
        $offset = ($current_page - 1) * $items_per_page;

        // Handle sorting
        $order_by_sql = '';
        switch ($sort_by) {
            case 'price_asc':
                $order_by_sql = ' ORDER BY effective_price ASC';
                break;
            case 'price_desc':
                $order_by_sql = ' ORDER BY effective_price DESC';
                break;
            case 'name_asc':
                $order_by_sql = ' ORDER BY p.name ASC';
                break;
            case 'name_desc':
                $order_by_sql = ' ORDER BY p.name DESC';
                break;
            default:
                $order_by_sql = ' ORDER BY p.id DESC'; // Default to newest
        }

        // --- Count total items for pagination ---
        $total_items = 0;
        if ($stmt_count = $this->conn->prepare($sql_count)) {
            if (!empty($params)) {
                $stmt_count->bind_param($types, ...$params);
            }
            $stmt_count->execute();
            $stmt_count->bind_result($total_items);
            $stmt_count->fetch();
            $stmt_count->close();
        }
        $total_pages = ceil($total_items / $items_per_page);

        // --- Fetch products for the current page ---
        $sql .= $order_by_sql . " LIMIT ? OFFSET ?";
        $params[] = $items_per_page;
        $types .= 'i';
        $params[] = $offset;
        $types .= 'i';

        // Prepare and execute the SQL query
        if ($stmt = $this->conn->prepare($sql)) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            } else {
                error_log("Error executing product query: " . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log("Error preparing product query: " . $this->conn->error);
        }

        // Fetch categories for the sidebar
        $categories = [];
        $sql_categories = "SELECT name FROM categories ORDER BY name ASC";
        if ($result_categories = $this->conn->query($sql_categories)) {
            while ($row_category = $result_categories->fetch_assoc()) {
                $categories[] = $row_category;
            }
        }

        echo $this->twig->render('shop/index.html', [
            'products' => $products,
            'categories' => $categories,
            'category_filter' => $category_filter,
            'sort_by' => $sort_by,
            'current_page' => $current_page,
            'total_pages' => $total_pages,
            'cart_item_count' => $_SESSION['cart_item_count'] ?? 0, // Assuming cart_item_count is set in session
            'loggedin' => $_SESSION['loggedin'] ?? false // Assuming loggedin status is set in session
        ]);
    }
}
