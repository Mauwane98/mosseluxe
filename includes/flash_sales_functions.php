<?php
/**
 * Flash Sales Functions
 * Manage time-limited special offers
 */

/**
 * Create a flash sale
 */
function createFlashSale($conn, $product_id, $sale_price, $discount_percentage, $start_time, $end_time, $quantity_limit = null) {
    // Get original price
    $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $stmt->close();
        return false;
    }
    
    $product = $result->fetch_assoc();
    $original_price = $product['price'];
    $stmt->close();
    
    // Insert flash sale
    $stmt = $conn->prepare("INSERT INTO flash_sales 
        (product_id, original_price, sale_price, discount_percentage, start_time, end_time, quantity_limit, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("iddissi", $product_id, $original_price, $sale_price, $discount_percentage, $start_time, $end_time, $quantity_limit);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Get active flash sale for a product
 */
function getActiveFlashSale($conn, $product_id) {
    $now = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("SELECT * FROM flash_sales 
        WHERE product_id = ? 
        AND is_active = 1 
        AND start_time <= ? 
        AND end_time > ?
        AND (quantity_limit IS NULL OR quantity_sold < quantity_limit)
        LIMIT 1");
    $stmt->bind_param("iss", $product_id, $now, $now);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $sale = $result->fetch_assoc();
        $stmt->close();
        return $sale;
    }
    
    $stmt->close();
    return null;
}

/**
 * Get all active flash sales
 */
function getActiveFlashSales($conn, $limit = 10) {
    $now = date('Y-m-d H:i:s');
    
    $sql = "SELECT fs.*, p.name, p.image, p.stock 
            FROM flash_sales fs
            INNER JOIN products p ON fs.product_id = p.id
            WHERE fs.is_active = 1 
            AND fs.start_time <= ? 
            AND fs.end_time > ?
            AND (fs.quantity_limit IS NULL OR fs.quantity_sold < fs.quantity_limit)
            AND p.status = 1
            ORDER BY fs.end_time ASC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $now, $now, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sales = [];
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
    
    $stmt->close();
    return $sales;
}

/**
 * Get upcoming flash sales
 */
function getUpcomingFlashSales($conn, $limit = 10) {
    $now = date('Y-m-d H:i:s');
    
    $sql = "SELECT fs.*, p.name, p.image 
            FROM flash_sales fs
            INNER JOIN products p ON fs.product_id = p.id
            WHERE fs.is_active = 1 
            AND fs.start_time > ?
            AND p.status = 1
            ORDER BY fs.start_time ASC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $now, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sales = [];
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
    
    $stmt->close();
    return $sales;
}

/**
 * Increment quantity sold for flash sale
 */
function incrementFlashSaleQuantity($conn, $flash_sale_id, $quantity = 1) {
    $stmt = $conn->prepare("UPDATE flash_sales 
        SET quantity_sold = quantity_sold + ? 
        WHERE id = ?");
    $stmt->bind_param("ii", $quantity, $flash_sale_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * End flash sale
 */
function endFlashSale($conn, $flash_sale_id) {
    $stmt = $conn->prepare("UPDATE flash_sales SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $flash_sale_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get flash sale statistics
 */
function getFlashSaleStats($conn) {
    $stats = [
        'active_sales' => 0,
        'total_revenue' => 0,
        'items_sold' => 0,
        'upcoming_sales' => 0
    ];
    
    $now = date('Y-m-d H:i:s');
    
    // Active sales
    $result = $conn->query("SELECT COUNT(*) as count FROM flash_sales 
        WHERE is_active = 1 AND start_time <= '$now' AND end_time > '$now'");
    if ($row = $result->fetch_assoc()) {
        $stats['active_sales'] = $row['count'];
    }
    
    // Upcoming sales
    $result = $conn->query("SELECT COUNT(*) as count FROM flash_sales 
        WHERE is_active = 1 AND start_time > '$now'");
    if ($row = $result->fetch_assoc()) {
        $stats['upcoming_sales'] = $row['count'];
    }
    
    // Total revenue and items sold (last 30 days)
    $result = $conn->query("SELECT SUM(quantity_sold) as items, SUM(quantity_sold * sale_price) as revenue 
        FROM flash_sales 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    if ($row = $result->fetch_assoc()) {
        $stats['items_sold'] = $row['items'] ?? 0;
        $stats['total_revenue'] = $row['revenue'] ?? 0;
    }
    
    return $stats;
}

/**
 * Calculate time remaining for flash sale
 */
function getTimeRemaining($end_time) {
    $now = time();
    $end = strtotime($end_time);
    $diff = $end - $now;
    
    if ($diff <= 0) {
        return [
            'expired' => true,
            'days' => 0,
            'hours' => 0,
            'minutes' => 0,
            'seconds' => 0
        ];
    }
    
    return [
        'expired' => false,
        'days' => floor($diff / 86400),
        'hours' => floor(($diff % 86400) / 3600),
        'minutes' => floor(($diff % 3600) / 60),
        'seconds' => $diff % 60,
        'total_seconds' => $diff
    ];
}

/**
 * Clean expired flash sales
 */
function cleanExpiredFlashSales($conn) {
    $now = date('Y-m-d H:i:s');
    $conn->query("UPDATE flash_sales SET is_active = 0 WHERE end_time <= '$now' AND is_active = 1");
    return $conn->affected_rows;
}
// No closing PHP tag - prevents accidental whitespace output