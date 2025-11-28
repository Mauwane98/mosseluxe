<?php
/**
 * Recently Viewed Products Functions
 * Track and display user browsing history
 */

/**
 * Track a product view
 */
function trackProductView($conn, $product_id) {
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    
    // Check if already viewed recently (within last hour)
    $stmt = $conn->prepare("SELECT id FROM recently_viewed 
        WHERE session_id = ? AND product_id = ? 
        AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("si", $session_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update timestamp
        $stmt->close();
        $stmt = $conn->prepare("UPDATE recently_viewed 
            SET viewed_at = NOW() 
            WHERE session_id = ? AND product_id = ?");
        $stmt->bind_param("si", $session_id, $product_id);
        $stmt->execute();
    } else {
        // Insert new view
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO recently_viewed 
            (user_id, session_id, product_id, viewed_at) 
            VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("isi", $user_id, $session_id, $product_id);
        $stmt->execute();
    }
    $stmt->close();
    
    // Clean up old views (keep last 50 per session)
    $conn->query("DELETE FROM recently_viewed 
        WHERE session_id = '$session_id' 
        AND id NOT IN (
            SELECT id FROM (
                SELECT id FROM recently_viewed 
                WHERE session_id = '$session_id' 
                ORDER BY viewed_at DESC 
                LIMIT 50
            ) tmp
        )");
}

/**
 * Get recently viewed products
 */
function getRecentlyViewedProducts($conn, $limit = 8, $exclude_id = null) {
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    
    $exclude_clause = $exclude_id ? "AND rv.product_id != $exclude_id" : "";
    
    $sql = "SELECT DISTINCT p.* FROM products p
        INNER JOIN recently_viewed rv ON p.id = rv.product_id
        WHERE rv.session_id = ? $exclude_clause
        AND p.status = 1
        ORDER BY rv.viewed_at DESC
        LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $session_id, $limit);
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
 * Clear recently viewed for session
 */
function clearRecentlyViewed($conn) {
    $session_id = session_id();
    $stmt = $conn->prepare("DELETE FROM recently_viewed WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $stmt->close();
}
// No closing PHP tag - prevents accidental whitespace output