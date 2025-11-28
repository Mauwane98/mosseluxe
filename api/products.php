<?php
// Only include bootstrap if not already loaded (when accessed directly)
if (!defined('BOOTSTRAP_LOADED')) {
    require_once __DIR__ . '/../includes/bootstrap.php';
}

// Only set headers if not already set by API index
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

// Use existing connection if available, otherwise create new one
if (!isset($conn)) {
    $conn = get_db_connection();
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// When included from API index, use $_GET['_route'] to get the route info
// When accessed directly, parse from REQUEST_URI
if (isset($_GET['_route'])) {
    $route_parts = explode('/', trim($_GET['_route'], '/'));
    $product_slug = isset($route_parts[1]) && !empty($route_parts[1]) ? $route_parts[1] : null;
} else {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));
    // Find 'products' in path and get the next part as slug
    $products_index = array_search('products', $path_parts);
    $product_slug = ($products_index !== false && isset($path_parts[$products_index + 1])) 
        ? $path_parts[$products_index + 1] 
        : null;
}

if ($product_slug && $method === 'GET') {
    // Get product by slug
    get_product_by_slug($conn, $product_slug);
} elseif ($method === 'GET') {
    // List products
    get_products_list($conn);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function get_products_list($conn) {
    try {
        // Get query parameters
        $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $sort_by = isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at_desc';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;

        // Validate parameters
        if ($page < 1) $page = 1;
        if ($per_page < 1 || $per_page > 100) $per_page = 12;

        $offset = ($page - 1) * $per_page;

        // Build WHERE clause
        $where = "WHERE p.status = 1";
        $params = [];
        $types = '';

        if ($category > 0) {
            $where .= " AND p.category = ?";
            $params[] = $category;
            $types .= 'i';
        }

        if (!empty($search)) {
            $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= 'ss';
        }

        // Count total products for pagination
        $count_sql = "SELECT COUNT(*) as total FROM products p $where";
        $stmt_count = $conn->prepare($count_sql);
        if (!empty($params)) {
            $stmt_count->bind_param($types, ...$params);
        }
        $stmt_count->execute();
        $total_result = $stmt_count->get_result();
        $total = $total_result->fetch_assoc()['total'];
        $stmt_count->close();

        // Build ORDER BY
        $order_by = "p.created_at DESC";
        switch ($sort_by) {
            case 'price_asc':
                $order_by = "COALESCE(p.sale_price, p.price) ASC";
                break;
            case 'price_desc':
                $order_by = "COALESCE(p.sale_price, p.price) DESC";
                break;
            case 'name_asc':
                $order_by = "p.name ASC";
                break;
            case 'name_desc':
                $order_by = "p.name DESC";
                break;
            case 'newest':
            default:
                $order_by = "p.created_at DESC";
                break;
        }

        // Get products
        $sql = "SELECT p.id, p.name, p.short_description, p.price, p.sale_price, p.image,
                       p.is_featured, p.is_new, p.stock, p.created_at,
                       c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category = c.id
                $where
                ORDER BY $order_by
                LIMIT ? OFFSET ?";

        $params[] = $per_page;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'subtitle' => $row['short_description'] ?? '',
                'price' => (float)$row['price'],
                'sale_price' => $row['sale_price'] ? (float)$row['sale_price'] : null,
                'currency' => 'R',
                'formatted_price' => 'R ' . number_format($row['sale_price'] ?: $row['price'], 2),
                'image' => SITE_URL . $row['image'],
                'is_featured' => (bool)$row['is_featured'],
                'is_new' => (bool)$row['is_new'],
                'stock' => (int)$row['stock'],
                'category' => $row['category_name'],
                'created_at' => $row['created_at']
            ];
        }
        $stmt->close();

        $total_pages = ceil($total / $per_page);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $products,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => $total_pages,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ]
        ]);

    } catch (Exception $e) {
        error_log('API Products Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
}

function get_product_by_slug($conn, $slug) {
    try {
        // First try to match by slug, then by id if slug not found
        $where = "WHERE p.status = 1 AND (p.slug = ? OR p.id = ?)";
        $stmt = $conn->prepare("
            SELECT p.id, p.name, p.short_description, p.description, p.price, p.sale_price,
                   p.image, p.sku, p.stock, p.is_featured, p.is_new,
                   p.created_at, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category = c.id
            $where
        ");

        $stmt->bind_param("si", $slug, (int)$slug);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }

        $product = $result->fetch_assoc();
        $stmt->close();

        // Get product images
        $images = [];
        $stmt_images = $conn->prepare("
            SELECT id, image_path, media_type, is_primary, position, type, url
            FROM product_images
            WHERE product_id = ?
            ORDER BY is_primary DESC, position ASC, id ASC
        ");
        $stmt_images->bind_param("i", $product['id']);
        $stmt_images->execute();
        $images_result = $stmt_images->get_result();
        while ($img = $images_result->fetch_assoc()) {
            $images[] = [
                'id' => $img['id'],
                'url' => !empty($img['url']) ? $img['url'] : SITE_URL . $img['image_path'],
                'type' => $img['type'],
                'is_primary' => (bool)$img['is_primary'],
                'media_type' => $img['media_type']
            ];
        }
        $stmt_images->close();

        // Get variants
        $variants = [];
        $stmt_variants = $conn->prepare("
            SELECT id, name, sku, price, stock
            FROM product_variants
            WHERE product_id = ?
            ORDER BY name ASC
        ");
        $stmt_variants->bind_param("i", $product['id']);
        $stmt_variants->execute();
        $variants_result = $stmt_variants->get_result();
        while ($var = $variants_result->fetch_assoc()) {
            $variants[] = [
                'id' => $var['id'],
                'name' => $var['name'],
                'sku' => $var['sku'],
                'price' => (float)$var['price'],
                'stock' => (int)$var['stock']
            ];
        }
        $stmt_variants->close();

        $response = [
            'id' => $product['id'],
            'name' => $product['name'],
            'subtitle' => $product['short_description'] ?? '',
            'description' => $product['description'],
            'price' => (float)$product['price'],
            'sale_price' => $product['sale_price'] ? (float)$product['sale_price'] : null,
            'currency' => 'R',
            'formatted_price' => 'R ' . number_format($product['sale_price'] ?: $product['price'], 2),
            'sku' => $product['sku'],
            'stock' => (int)$product['stock'],
            'image' => SITE_URL . $product['image'],
            'images' => $images,
            'variants' => $variants,
            'is_featured' => (bool)$product['is_featured'],
            'is_new' => (bool)$product['is_new'],
            'category' => $product['category_name'],
            'created_at' => $product['created_at']
        ];

        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $response]);

    } catch (Exception $e) {
        error_log('API Product Detail Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
}

$conn->close();
?>
