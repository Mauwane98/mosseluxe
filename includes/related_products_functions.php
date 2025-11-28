<?php
/**
 * Related Products Functions
 * Handles product recommendations, upselling, and cross-selling
 */

/**
 * Get related products for a product
 */
function getRelatedProducts($conn, $product_id, $type = 'related', $limit = 4) {
    // First try to get manually assigned related products
    $stmt = $conn->prepare("SELECT p.* FROM products p
        INNER JOIN related_products rp ON p.id = rp.related_product_id
        WHERE rp.product_id = ? AND rp.relation_type = ? AND p.status = 1
        ORDER BY rp.sort_order ASC, RAND()
        LIMIT ?");
    $stmt->bind_param("isi", $product_id, $type, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
    
    // If not enough manual relations, get automatic suggestions
    if (count($products) < $limit) {
        $remaining = $limit - count($products);
        $existing_ids = array_column($products, 'id');
        $existing_ids[] = $product_id; // Exclude current product
        $placeholders = str_repeat('?,', count($existing_ids) - 1) . '?';
        
        // Get products from same category
        $stmt = $conn->prepare("SELECT p.* FROM products p
            WHERE p.category = (SELECT category FROM products WHERE id = ?)
            AND p.id NOT IN ($placeholders)
            AND p.status = 1
            ORDER BY RAND()
            LIMIT ?");
        
        $types = str_repeat('i', count($existing_ids) + 1);
        $params = array_merge([$product_id], $existing_ids, [$remaining]);
        $stmt->bind_param($types . 'i', ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
    }
    
    return $products;
}

/**
 * Get frequently bought together products
 */
function getFrequentlyBoughtTogether($conn, $product_id, $limit = 3) {
    // Get products that are frequently ordered together
    $stmt = $conn->prepare("SELECT p.*, COUNT(*) as frequency
        FROM products p
        INNER JOIN order_items oi1 ON p.id = oi1.product_id
        INNER JOIN order_items oi2 ON oi1.order_id = oi2.order_id
        WHERE oi2.product_id = ? AND oi1.product_id != ? AND p.status = 1
        GROUP BY p.id
        ORDER BY frequency DESC, p.id DESC
        LIMIT ?");
    $stmt->bind_param("iii", $product_id, $product_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
    
    // If no order history, fall back to related products
    if (empty($products)) {
        $products = getRelatedProducts($conn, $product_id, 'bundle', $limit);
    }
    
    return $products;
}

/**
 * Get upsell products (higher priced alternatives)
 */
function getUpsellProducts($conn, $product_id, $limit = 4) {
    // Get current product price
    $stmt = $conn->prepare("SELECT price, category FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current = $result->fetch_assoc();
    $stmt->close();
    
    if (!$current) return [];
    
    // Get products from same category with higher price
    $stmt = $conn->prepare("SELECT * FROM products
        WHERE category = ? AND price > ? AND id != ? AND status = 1
        ORDER BY price ASC
        LIMIT ?");
    $stmt->bind_param("idii", $current['category'], $current['price'], $product_id, $limit);
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
 * Get cross-sell products for cart
 */
function getCrossSellProducts($conn, $cart_product_ids, $limit = 4) {
    if (empty($cart_product_ids)) return [];
    
    $placeholders = str_repeat('?,', count($cart_product_ids) - 1) . '?';
    
    // Get products related to items in cart
    $stmt = $conn->prepare("SELECT DISTINCT p.* FROM products p
        INNER JOIN related_products rp ON p.id = rp.related_product_id
        WHERE rp.product_id IN ($placeholders)
        AND rp.relation_type = 'cross_sell'
        AND p.id NOT IN ($placeholders)
        AND p.status = 1
        ORDER BY RAND()
        LIMIT ?");
    
    $types = str_repeat('i', count($cart_product_ids) * 2 + 1);
    $params = array_merge($cart_product_ids, $cart_product_ids, [$limit]);
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
 * Add a related product relationship
 */
function addRelatedProduct($conn, $product_id, $related_product_id, $type = 'related', $sort_order = 0) {
    $stmt = $conn->prepare("INSERT INTO related_products 
        (product_id, related_product_id, relation_type, sort_order) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE sort_order = ?");
    $stmt->bind_param("iisii", $product_id, $related_product_id, $type, $sort_order, $sort_order);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Get bestsellers for recommendations
 */
function getBestsellers($conn, $limit = 8, $exclude_ids = []) {
    $where_clause = '';
    if (!empty($exclude_ids)) {
        $placeholders = str_repeat('?,', count($exclude_ids) - 1) . '?';
        $where_clause = "AND p.id NOT IN ($placeholders)";
    }
    
    $sql = "SELECT p.*, COALESCE(SUM(oi.quantity), 0) as total_sold
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        WHERE p.status = 1 $where_clause
        GROUP BY p.id
        ORDER BY total_sold DESC, p.id DESC
        LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($exclude_ids)) {
        $types = str_repeat('i', count($exclude_ids)) . 'i';
        $params = array_merge($exclude_ids, [$limit]);
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param("i", $limit);
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
// No closing PHP tag - prevents accidental whitespace output