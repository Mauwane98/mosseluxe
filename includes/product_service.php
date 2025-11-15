<?php
require_once __DIR__ . '/config.php';

/**
 * Check if product is in low stock (less than 10 units)
 */
function is_low_stock($stock_quantity) {
    return $stock_quantity > 0 && $stock_quantity <= 10;
}

/**
 * Check if product is out of stock
 */
function is_out_of_stock($stock_quantity) {
    return $stock_quantity <= 0;
}

/**
 * Get product stock status
 */
function get_stock_status($stock_quantity) {
    if ($stock_quantity <= 0) {
        return 'Out of Stock';
    } elseif ($stock_quantity <= 10) {
        return 'Low Stock';
    } else {
        return 'In Stock';
    }
}

/**
 * Get stock status color class for UI
 */
function get_stock_status_class($stock_quantity) {
    if ($stock_quantity <= 0) {
        return 'bg-red-100 text-red-800';
    } elseif ($stock_quantity <= 10) {
        return 'bg-yellow-100 text-yellow-800';
    } else {
        return 'bg-green-100 text-green-800';
    }
}

/**
 * Update product stock after purchase
 */
function update_product_stock($product_id, $quantity_sold) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
    $stmt->bind_param("ii", $quantity_sold, $product_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Get total products count
 */
function get_total_products_count() {
    $conn = get_db_connection();
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 1");
    $count = $result->fetch_assoc()['count'];
    $result->free();
    return $count;
}

/**
 * Get low stock products for admin alerts
 */
function get_low_stock_products() {
    $conn = get_db_connection();
    $result = $conn->query("
        SELECT id, name, stock, image
        FROM products
        WHERE status = 1 AND stock > 0 AND stock <= 10
        ORDER BY stock ASC
    ");

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->free();
    return $products;
}

/**
 * Get out of stock products
 */
function get_out_of_stock_products() {
    $conn = get_db_connection();
    $result = $conn->query("
        SELECT id, name, stock, image
        FROM products
        WHERE status = 1 AND stock <= 0
        ORDER BY name ASC
    ");

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->free();
    return $products;
}

/**
 * Add stock alert for a user when product is back in stock
 */
function add_stock_alert($product_id, $email) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("
        INSERT INTO stock_notifications (product_id, email)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
    ");
    $stmt->bind_param("is", $product_id, $email);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Remove stock alert (when user unsubscribes or after notification)
 */
function remove_stock_alert($product_id, $email) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("DELETE FROM stock_notifications WHERE product_id = ? AND email = ?");
    $stmt->bind_param("is", $product_id, $email);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Get pending stock alerts for a product
 */
function get_stock_alerts($product_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("
        SELECT email
        FROM stock_notifications
        WHERE product_id = ? AND notified_at IS NULL
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }

    $stmt->close();
    return $emails;
}

/**
 * Mark stock alerts as notified
 */
function mark_stock_alerts_notified($product_id, $emails) {
    if (empty($emails)) return;

    $conn = get_db_connection();
    $placeholders = str_repeat('?,', count($emails) - 1) . '?';

    $stmt = $conn->prepare("
        UPDATE stock_notifications
        SET notified_at = CURRENT_TIMESTAMP
        WHERE product_id = ? AND email IN ($placeholders)
    ");

    $params = array_merge([$product_id], $emails);
    $types = 'i' . str_repeat('s', count($emails));
    $stmt->bind_param($types, ...$params);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Get recently viewed products for a user
 */
function get_recently_viewed($user_id, $limit = 10) {
    $recently_viewed = $_SESSION['recently_viewed'] ?? [];
    if (empty($recently_viewed)) {
        return [];
    }

    $conn = get_db_connection();
    $placeholders = str_repeat('?,', count($recently_viewed) - 1) . '?';

    $stmt = $conn->prepare("
        SELECT id, name, price, sale_price, image, stock
        FROM products
        WHERE id IN ($placeholders) AND status = 1
        ORDER BY FIELD(id, " . implode(',', array_reverse($recently_viewed)) . ")
        LIMIT ?
    ");

    $params = array_merge($recently_viewed, [$limit]);
    $types = str_repeat('i', count($recently_viewed)) . 'i';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $stmt->close();
    return $products;
}

/**
 * Add product to recently viewed
 */
function add_to_recently_viewed($product_id) {
    if (!isset($_SESSION['recently_viewed'])) {
        $_SESSION['recently_viewed'] = [];
    }

    // Remove if already exists
    $key = array_search($product_id, $_SESSION['recently_viewed']);
    if ($key !== false) {
        unset($_SESSION['recently_viewed'][$key]);
    }

    // Add to beginning
    array_unshift($_SESSION['recently_viewed'], $product_id);

    // Limit to 20 items
    $_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 20);
}

/**
 * Get featured products
 */
function get_featured_products($limit = 8) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("
        SELECT id, name, price, sale_price, image, stock
        FROM products
        WHERE status = 1 AND is_featured = 1
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $stmt->close();
    return $products;
}

/**
 * Get products by category
 */
function get_products_by_category($category_id, $limit = null) {
    $conn = get_db_connection();
    $sql = "
        SELECT id, name, price, sale_price, image, stock, description
        FROM products
        WHERE category = ? AND status = 1
        ORDER BY is_featured DESC, created_at DESC
    ";

    if ($limit) {
        $sql .= " LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $category_id, $limit);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $category_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $stmt->close();
    return $products;
}

/**
 * Search products by query
 */
function search_products($query, $limit = 50) {
    $conn = get_db_connection();
    $search_term = "%{$query}%";

    $stmt = $conn->prepare("
        SELECT id, name, price, sale_price, image, stock, description
        FROM products
        WHERE status = 1
        AND (name LIKE ? OR description LIKE ?)
        ORDER BY
            CASE
                WHEN name LIKE ? THEN 0
                ELSE 1
            END,
            name ASC
        LIMIT ?
    ");
    $exact_match = "%{$query}%"; // For exact matches
    $stmt->bind_param("sssi", $search_term, $search_term, $exact_match, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $stmt->close();
    return $products;
}
?>
