<?php
/**
 * Abandoned Cart Recovery Functions
 * Track and recover abandoned shopping carts
 */

/**
 * Save or update abandoned cart
 */
function saveAbandonedCart($conn, $cart_data, $email = null) {
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    
    if (empty($cart_data)) {
        return false;
    }
    
    // Calculate total
    $total = 0;
    foreach ($cart_data as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    // Generate recovery token
    $recovery_token = bin2hex(random_bytes(32));
    
    // Check if cart already exists for this session
    $stmt = $conn->prepare("SELECT id FROM abandoned_carts WHERE session_id = ? AND recovered = 0");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing cart
        $cart_id = $result->fetch_assoc()['id'];
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE abandoned_carts 
            SET cart_data = ?, total_amount = ?, email = ?, recovery_token = ?, updated_at = NOW() 
            WHERE id = ?");
        $cart_json = json_encode($cart_data);
        $stmt->bind_param("sdssi", $cart_json, $total, $email, $recovery_token, $cart_id);
        $stmt->execute();
        $stmt->close();
        
        return $cart_id;
    } else {
        // Insert new cart
        $stmt->close();
        
        $stmt = $conn->prepare("INSERT INTO abandoned_carts 
            (user_id, session_id, email, cart_data, total_amount, recovery_token) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $cart_json = json_encode($cart_data);
        $stmt->bind_param("isssds", $user_id, $session_id, $email, $cart_json, $total, $recovery_token);
        $stmt->execute();
        $cart_id = $stmt->insert_id;
        $stmt->close();
        
        return $cart_id;
    }
}

/**
 * Get abandoned cart by token
 */
function getAbandonedCartByToken($conn, $token) {
    $stmt = $conn->prepare("SELECT * FROM abandoned_carts WHERE recovery_token = ? AND recovered = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $cart = $result->fetch_assoc();
        $cart['cart_data'] = json_decode($cart['cart_data'], true);
        $stmt->close();
        return $cart;
    }
    
    $stmt->close();
    return null;
}

/**
 * Mark cart as recovered
 */
function markCartAsRecovered($conn, $cart_id) {
    $stmt = $conn->prepare("UPDATE abandoned_carts 
        SET recovered = 1, recovered_at = NOW() 
        WHERE id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get all abandoned carts (for admin)
 */
function getAbandonedCarts($conn, $limit = 50, $offset = 0) {
    $sql = "SELECT * FROM abandoned_carts 
            WHERE recovered = 0 
            AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $carts = [];
    while ($row = $result->fetch_assoc()) {
        $row['cart_data'] = json_decode($row['cart_data'], true);
        $carts[] = $row;
    }
    
    $stmt->close();
    return $carts;
}

/**
 * Get abandoned cart statistics
 */
function getAbandonedCartStats($conn) {
    $stats = [
        'total_abandoned' => 0,
        'total_value' => 0,
        'recovered_count' => 0,
        'recovered_value' => 0,
        'recovery_rate' => 0
    ];
    
    // Total abandoned (last 30 days)
    $result = $conn->query("SELECT COUNT(*) as count, SUM(total_amount) as value 
        FROM abandoned_carts 
        WHERE recovered = 0 
        AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    if ($row = $result->fetch_assoc()) {
        $stats['total_abandoned'] = $row['count'];
        $stats['total_value'] = $row['value'] ?? 0;
    }
    
    // Recovered (last 30 days)
    $result = $conn->query("SELECT COUNT(*) as count, SUM(total_amount) as value 
        FROM abandoned_carts 
        WHERE recovered = 1 
        AND recovered_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    if ($row = $result->fetch_assoc()) {
        $stats['recovered_count'] = $row['count'];
        $stats['recovered_value'] = $row['value'] ?? 0;
    }
    
    // Calculate recovery rate
    $total = $stats['total_abandoned'] + $stats['recovered_count'];
    if ($total > 0) {
        $stats['recovery_rate'] = ($stats['recovered_count'] / $total) * 100;
    }
    
    return $stats;
}

/**
 * Clean old abandoned carts (older than 90 days)
 */
function cleanOldAbandonedCarts($conn) {
    $conn->query("DELETE FROM abandoned_carts 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    return $conn->affected_rows;
}

/**
 * Send recovery email (placeholder - integrate with email service)
 */
function sendRecoveryEmail($conn, $cart_id) {
    $stmt = $conn->prepare("SELECT * FROM abandoned_carts WHERE id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $stmt->close();
        return false;
    }
    
    $cart = $result->fetch_assoc();
    $stmt->close();
    
    if (empty($cart['email'])) {
        return false;
    }
    
    // Mark email as sent
    $stmt = $conn->prepare("UPDATE abandoned_carts 
        SET recovery_email_sent = 1, recovery_email_sent_at = NOW() 
        WHERE id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $stmt->close();
    
    // TODO: Integrate with email service (SendGrid, Mailgun, etc.)
    // For now, just return the recovery URL
    $recovery_url = SITE_URL . "recover-cart.php?token=" . $cart['recovery_token'];
    
    return [
        'success' => true,
        'email' => $cart['email'],
        'recovery_url' => $recovery_url,
        'total' => $cart['total_amount']
    ];
}
// No closing PHP tag - prevents accidental whitespace output