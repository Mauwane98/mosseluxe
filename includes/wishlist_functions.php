<?php
/**
 * Wishlist Functions
 * Manage customer wishlists
 */

/**
 * Add product to wishlist
 */
function addToWishlist($conn, $user_id, $product_id) {
    // Check if already in wishlist
    $stmt = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Already in wishlist'];
    }
    $stmt->close();
    
    // Add to wishlist
    $stmt = $conn->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $product_id);
    $success = $stmt->execute();
    $stmt->close();
    
    return [
        'success' => $success,
        'message' => $success ? 'Added to wishlist' : 'Failed to add'
    ];
}

/**
 * Remove product from wishlist
 */
function removeFromWishlist($conn, $user_id, $product_id) {
    $stmt = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $success = $stmt->execute();
    $stmt->close();
    
    return [
        'success' => $success,
        'message' => $success ? 'Removed from wishlist' : 'Failed to remove'
    ];
}

/**
 * Get user's wishlist
 */
function getWishlist($conn, $user_id) {
    $stmt = $conn->prepare("SELECT p.*, w.created_at as added_at 
        FROM wishlists w
        INNER JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ? AND p.status = 1
        ORDER BY w.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $wishlist = [];
    while ($row = $result->fetch_assoc()) {
        $wishlist[] = $row;
    }
    $stmt->close();
    
    return $wishlist;
}

/**
 * Check if product is in wishlist
 */
function isInWishlist($conn, $user_id, $product_id) {
    $stmt = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

/**
 * Get wishlist count
 */
function getWishlistCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'];
}

/**
 * Move wishlist item to cart
 */
function moveToCart($conn, $user_id, $product_id) {
    // Add to cart logic here
    // Then remove from wishlist
    return removeFromWishlist($conn, $user_id, $product_id);
}
// No closing PHP tag - prevents accidental whitespace output