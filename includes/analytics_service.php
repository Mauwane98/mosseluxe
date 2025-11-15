<?php
require_once __DIR__ . '/config.php';

/**
 * Track page view for analytics
 */
function track_page_view($page_type = 'unknown', $page_id = null, $session_id = null) {
    $conn = get_db_connection();

    // Use session ID or generate one
    if (!$session_id) {
        $session_id = session_id();
    }

    $user_id = $_SESSION['user_id'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO analytics_page_views
        (session_id, user_id, page_type, page_id, user_agent, ip_address, referrer, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sisssss", $session_id, $user_id, $page_type, $page_id, $user_agent, $ip_address, $referrer);
    $stmt->execute();
    $stmt->close();
}

/**
 * Track product interaction
 */
function track_product_interaction($product_id, $interaction_type, $session_id = null) {
    $conn = get_db_connection();

    if (!$session_id) {
        $session_id = session_id();
    }

    $user_id = $_SESSION['user_id'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO analytics_product_interactions
        (session_id, user_id, product_id, interaction_type, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("siis", $session_id, $user_id, $product_id, $interaction_type);
    $stmt->execute();
    $stmt->close();
}

/**
 * Track cart action
 */
function track_cart_action($product_id, $action, $quantity, $session_id = null) {
    $conn = get_db_connection();

    if (!$session_id) {
        $session_id = session_id();
    }

    $user_id = $_SESSION['user_id'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO analytics_cart_actions
        (session_id, user_id, product_id, action, quantity, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("siisi", $session_id, $user_id, $product_id, $action, $quantity);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get dashboard statistics
 */
function get_dashboard_stats() {
    $conn = get_db_connection();
    $stats = [];

    // Total products
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 1");
    $stats['total_products'] = $result->fetch_assoc()['count'];

    // Total orders today
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
    $stats['orders_today'] = $result->fetch_assoc()['count'];

    // Total revenue today
    $result = $conn->query("SELECT COALESCE(SUM(total_price), 0) as revenue FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'Completed'");
    $stats['revenue_today'] = $result->fetch_assoc()['revenue'];

    // Total customers
    $result = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM orders WHERE user_id IS NOT NULL");
    $stats['total_customers'] = $result->fetch_assoc()['count'];

    // Low stock products
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 1 AND stock > 0 AND stock <= 10");
    $stats['low_stock_count'] = $result->fetch_assoc()['count'];

    // Out of stock products
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 1 AND stock = 0");
    $stats['out_of_stock_count'] = $result->fetch_assoc()['count'];

    // Pending orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
    $stats['pending_orders'] = $result->fetch_assoc()['count'];

    // Recent orders
    $result = $conn->query("
        SELECT o.id, o.order_id, o.total_price, o.status, o.created_at, u.name as customer_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stats['recent_orders'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['recent_orders'][] = $row;
    }

    $conn->close();
    return $stats;
}

/**
 * Get sales chart data
 */
function get_sales_chart_data($days = 30) {
    $conn = get_db_connection();
    $data = [];

    $stmt = $conn->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_price) as revenue
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND status = 'Completed'
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'date' => $row['date'],
            'orders' => (int)$row['orders'],
            'revenue' => (float)$row['revenue']
        ];
    }

    $stmt->close();
    return $data;
}

/**
 * Get product performance data
 */
function get_product_performance($limit = 10) {
    $conn = get_db_connection();
    $products = [];

    $stmt = $conn->prepare("
        SELECT
            p.id, p.name, p.image,
            COUNT(DISTINCT oi.order_id) as orders_count,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.quantity * oi.price) as total_revenue,
            AVG(pr.rating) as average_rating,
            COUNT(pr.id) as review_count
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'Completed'
        LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.is_approved = 1
        WHERE p.status = 1
        GROUP BY p.id, p.name, p.image
        ORDER BY total_revenue DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'image' => $row['image'],
            'orders_count' => (int)$row['orders_count'],
            'total_quantity' => (int)$row['total_quantity'],
            'total_revenue' => (float)$row['total_revenue'],
            'average_rating' => $row['review_count'] > 0 ? round($row['average_rating'], 1) : null,
            'review_count' => (int)$row['review_count']
        ];
    }

    $stmt->close();
    return $products;
}

/**
 * Get customer analytics
 */
function get_customer_analytics() {
    $conn = get_db_connection();
    $analytics = [];

    // Customer registration trends
    $result = $conn->query("
        SELECT DATE(created_at) as date, COUNT(*) as registrations
        FROM users
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $analytics['registrations'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['registrations'][] = $row;
    }

    // Top customers by spending
    $result = $conn->query("
        SELECT u.id, u.name, u.email, COUNT(o.id) as order_count, SUM(o.total_price) as total_spent
        FROM users u
        JOIN orders o ON u.id = o.user_id
        WHERE o.status = 'Completed'
        GROUP BY u.id, u.name, u.email
        ORDER BY total_spent DESC
        LIMIT 10
    ");
    $analytics['top_customers'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['top_customers'][] = $row;
    }

    // Customer retention (orders per customer)
    $result = $conn->query("
        SELECT
            CASE
                WHEN order_count = 1 THEN '1 order'
                WHEN order_count BETWEEN 2 AND 5 THEN '2-5 orders'
                WHEN order_count BETWEEN 6 AND 10 THEN '6-10 orders'
                ELSE '10+ orders'
            END as segment,
            COUNT(*) as customer_count
        FROM (
            SELECT COUNT(*) as order_count
            FROM orders
            WHERE user_id IS NOT NULL AND status = 'Completed'
            GROUP BY user_id
        ) customer_orders
        GROUP BY segment
        ORDER BY
            CASE segment
                WHEN '1 order' THEN 1
                WHEN '2-5 orders' THEN 2
                WHEN '6-10 orders' THEN 3
                WHEN '10+ orders' THEN 4
            END
    ");
    $analytics['customer_segments'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['customer_segments'][] = $row;
    }

    $conn->close();
    return $analytics;
}

/**
 * Get traffic analytics
 */
function get_traffic_analytics($days = 30) {
    $conn = get_db_connection();
    $analytics = [];

    // Daily page views
    $stmt = $conn->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as page_views, COUNT(DISTINCT session_id) as unique_visitors
        FROM analytics_page_views
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $result = $stmt->get_result();

    $analytics['traffic'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['traffic'][] = $row;
    }
    $stmt->close();

    // Popular pages
    $result = $conn->query("
        SELECT page_type, page_id, COUNT(*) as views
        FROM analytics_page_views
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY page_type, page_id
        ORDER BY views DESC
        LIMIT 10
    ");
    $analytics['popular_pages'] = [];
    while ($row = $result->fetch_assoc()) {
        $page_name = get_page_display_name($row['page_type'], $row['page_id']);
        $analytics['popular_pages'][] = [
            'name' => $page_name,
            'views' => (int)$row['views'],
            'page_type' => $row['page_type'],
            'page_id' => $row['page_id']
        ];
    }

    // Device/browser breakdown
    $result = $conn->query("
        SELECT
            CASE
                WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
                WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
                ELSE 'Desktop'
            END as device_type,
            COUNT(*) as count
        FROM analytics_page_views
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY device_type
        ORDER BY count DESC
    ");
    $analytics['devices'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['devices'][] = $row;
    }

    $conn->close();
    return $analytics;
}

/**
 * Helper function to get display name for page analytics
 */
function get_page_display_name($page_type, $page_id) {
    if (!$page_id) {
        return ucfirst($page_type);
    }

    $conn = get_db_connection();

    switch ($page_type) {
        case 'product':
            $stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->bind_param("i", $page_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $name = $result->fetch_assoc()['name'] ?? "Product #{$page_id}";
            $stmt->close();
            return "Product: {$name}";
        case 'category':
            $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
            $stmt->bind_param("i", $page_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $name = $result->fetch_assoc()['name'] ?? "Category #{$page_id}";
            $stmt->close();
            return "Category: {$name}";
        case 'search':
            return "Search: {$page_id}";
        default:
            return ucfirst($page_type);
    }
}

/**
 * Get cart abandonment rate
 */
function get_cart_abandonment_rate() {
    $conn = get_db_connection();

    // Get total sessions with cart actions
    $result = $conn->query("
        SELECT COUNT(DISTINCT session_id) as sessions_with_cart
        FROM analytics_cart_actions
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $sessions_with_cart = $result->fetch_assoc()['sessions_with_cart'];

    // Get sessions that completed purchase
    $result = $conn->query("
        SELECT COUNT(DISTINCT session_id) as completed_sessions
        FROM analytics_cart_actions aca
        JOIN orders o ON aca.session_id = o.session_id
        WHERE aca.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND o.status = 'Completed'
    ");
    $completed_sessions = $result->fetch_assoc()['completed_sessions'];

    $conn->close();

    if ($sessions_with_cart == 0) {
        return 0;
    }

    $abandonment_rate = (($sessions_with_cart - $completed_sessions) / $sessions_with_cart) * 100;
    return round($abandonment_rate, 2);
}

/**
 * Get conversion funnel data
 */
function get_conversion_funnel() {
    $conn = get_db_connection();
    $funnel = [];

    $date_range = "created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

    // Step 1: Page visitors
    $result = $conn->query("SELECT COUNT(DISTINCT session_id) as count FROM analytics_page_views WHERE $date_range");
    $funnel['visitors'] = $result->fetch_assoc()['count'];

    // Step 2: Product viewers
    $result = $conn->query("SELECT COUNT(DISTINCT session_id) as count FROM analytics_product_interactions WHERE interaction_type = 'view' AND $date_range");
    $funnel['product_views'] = $result->fetch_assoc()['count'];

    // Step 3: Cart adders
    $result = $conn->query("SELECT COUNT(DISTINCT session_id) as count FROM analytics_cart_actions WHERE action = 'add' AND $date_range");
    $funnel['cart_adds'] = $result->fetch_assoc()['count'];

    // Step 4: Checkout starters
    $result = $conn->query("SELECT COUNT(DISTINCT session_id) as count FROM analytics_page_views WHERE page_type = 'checkout' AND $date_range");
    $funnel['checkout_starts'] = $result->fetch_assoc()['count'];

    // Step 5: Completed purchases
    $result = $conn->query("SELECT COUNT(DISTINCT session_id) as count FROM orders WHERE status = 'Completed' AND $date_range");
    $funnel['purchases'] = $result->fetch_assoc()['count'];

    $conn->close();
    return $funnel;
}

/**
 * Clean old analytics data (older than 90 days)
 */
function clean_old_analytics() {
    $conn = get_db_connection();

    $tables = [
        'analytics_page_views',
        'analytics_product_interactions',
        'analytics_cart_actions'
    ];

    foreach ($tables as $table) {
        $conn->query("DELETE FROM $table WHERE created_at < DATE_SUB(CURDATE(), INTERVAL 90 DAY)");
    }

    $conn->close();
}
?>
