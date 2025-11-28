<?php
/**
 * Loyalty/Rewards Program Functions
 * Manage customer points and rewards
 */

// Points configuration
define('POINTS_PER_RAND', 1); // 1 point per R1 spent
define('POINTS_TO_RAND', 0.10); // 1 point = R0.10 discount
define('SIGNUP_BONUS', 100); // Points for new signup
define('REVIEW_BONUS', 50); // Points for leaving a review
define('REFERRAL_BONUS', 200); // Points for successful referral

/**
 * Get user's loyalty points balance
 */
function getLoyaltyBalance($conn, $user_id) {
    $stmt = $conn->prepare("SELECT points_balance FROM loyalty_points WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)$row['points_balance'];
    }
    
    $stmt->close();
    
    // Create loyalty account if doesn't exist
    $stmt = $conn->prepare("INSERT INTO loyalty_points (user_id, points_balance, total_earned, total_redeemed) VALUES (?, 0, 0, 0)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    return 0;
}

/**
 * Add points to user account
 */
function addLoyaltyPoints($conn, $user_id, $points, $description, $reference_type = null, $reference_id = null) {
    if ($points <= 0) return false;
    
    // Update balance
    $stmt = $conn->prepare("UPDATE loyalty_points 
        SET points_balance = points_balance + ?, 
            total_earned = total_earned + ?,
            last_activity = NOW()
        WHERE user_id = ?");
    $stmt->bind_param("iii", $points, $points, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Record transaction
    $stmt = $conn->prepare("INSERT INTO loyalty_transactions 
        (user_id, points, transaction_type, description, reference_type, reference_id) 
        VALUES (?, ?, 'earned', ?, ?, ?)");
    $stmt->bind_param("iissi", $user_id, $points, $description, $reference_type, $reference_id);
    $stmt->execute();
    $stmt->close();
    
    return true;
}

/**
 * Redeem points (deduct from balance)
 */
function redeemLoyaltyPoints($conn, $user_id, $points, $description, $reference_type = null, $reference_id = null) {
    if ($points <= 0) return false;
    
    // Check balance
    $balance = getLoyaltyBalance($conn, $user_id);
    if ($balance < $points) {
        return false; // Insufficient points
    }
    
    // Update balance
    $stmt = $conn->prepare("UPDATE loyalty_points 
        SET points_balance = points_balance - ?, 
            total_redeemed = total_redeemed + ?,
            last_activity = NOW()
        WHERE user_id = ?");
    $stmt->bind_param("iii", $points, $points, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Record transaction
    $stmt = $conn->prepare("INSERT INTO loyalty_transactions 
        (user_id, points, transaction_type, description, reference_type, reference_id) 
        VALUES (?, ?, 'redeemed', ?, ?, ?)");
    $negative_points = -$points;
    $stmt->bind_param("iissi", $user_id, $negative_points, $description, $reference_type, $reference_id);
    $stmt->execute();
    $stmt->close();
    
    return true;
}

/**
 * Award points for purchase
 */
function awardPurchasePoints($conn, $user_id, $order_id, $order_total) {
    $points = floor($order_total * POINTS_PER_RAND);
    $description = "Purchase - Order #$order_id";
    return addLoyaltyPoints($conn, $user_id, $points, $description, 'order', $order_id);
}

/**
 * Award signup bonus
 */
function awardSignupBonus($conn, $user_id) {
    $description = "Welcome Bonus";
    return addLoyaltyPoints($conn, $user_id, SIGNUP_BONUS, $description, 'signup', $user_id);
}

/**
 * Award review bonus
 */
function awardReviewBonus($conn, $user_id, $review_id) {
    $description = "Product Review Bonus";
    return addLoyaltyPoints($conn, $user_id, REVIEW_BONUS, $description, 'review', $review_id);
}

/**
 * Award referral bonus
 */
function awardReferralBonus($conn, $user_id, $referred_user_id) {
    $description = "Referral Bonus - User #$referred_user_id";
    return addLoyaltyPoints($conn, $user_id, REFERRAL_BONUS, $description, 'referral', $referred_user_id);
}

/**
 * Calculate discount from points
 */
function calculatePointsDiscount($points) {
    return $points * POINTS_TO_RAND;
}

/**
 * Calculate points needed for discount
 */
function calculatePointsNeeded($discount_amount) {
    return ceil($discount_amount / POINTS_TO_RAND);
}

/**
 * Get user's transaction history
 */
function getLoyaltyTransactions($conn, $user_id, $limit = 50, $offset = 0) {
    $stmt = $conn->prepare("SELECT * FROM loyalty_transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?");
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    $stmt->close();
    return $transactions;
}

/**
 * Get loyalty program statistics
 */
function getLoyaltyStats($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM loyalty_points WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        // Add calculated values
        $stats['discount_value'] = calculatePointsDiscount($stats['points_balance']);
        $stats['tier'] = calculateTier($stats['total_earned']);
        
        return $stats;
    }
    
    $stmt->close();
    return null;
}

/**
 * Calculate user tier based on total earned points
 */
function calculateTier($total_earned) {
    if ($total_earned >= 5000) return 'Platinum';
    if ($total_earned >= 2000) return 'Gold';
    if ($total_earned >= 500) return 'Silver';
    return 'Bronze';
}

/**
 * Get tier benefits
 */
function getTierBenefits($tier) {
    $benefits = [
        'Bronze' => [
            'multiplier' => 1.0,
            'perks' => ['Earn 1 point per R1', 'Birthday bonus']
        ],
        'Silver' => [
            'multiplier' => 1.2,
            'perks' => ['Earn 1.2x points', 'Free shipping', 'Early sale access']
        ],
        'Gold' => [
            'multiplier' => 1.5,
            'perks' => ['Earn 1.5x points', 'Free shipping', 'Priority support', 'Exclusive deals']
        ],
        'Platinum' => [
            'multiplier' => 2.0,
            'perks' => ['Earn 2x points', 'Free shipping', 'VIP support', 'Exclusive products', 'Special gifts']
        ]
    ];
    
    return $benefits[$tier] ?? $benefits['Bronze'];
}

/**
 * Get leaderboard (top earners)
 */
function getLoyaltyLeaderboard($conn, $limit = 10) {
    $sql = "SELECT lp.*, u.name, u.email 
            FROM loyalty_points lp
            INNER JOIN users u ON lp.user_id = u.id
            ORDER BY lp.total_earned DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $leaderboard = [];
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = $row;
    }
    
    $stmt->close();
    return $leaderboard;
}

/**
 * Get admin loyalty statistics
 */
function getAdminLoyaltyStats($conn) {
    $stats = [
        'total_members' => 0,
        'total_points_issued' => 0,
        'total_points_redeemed' => 0,
        'active_members' => 0
    ];
    
    // Total members
    $result = $conn->query("SELECT COUNT(*) as count FROM loyalty_points");
    if ($row = $result->fetch_assoc()) {
        $stats['total_members'] = $row['count'];
    }
    
    // Total points issued
    $result = $conn->query("SELECT SUM(total_earned) as total FROM loyalty_points");
    if ($row = $result->fetch_assoc()) {
        $stats['total_points_issued'] = $row['total'] ?? 0;
    }
    
    // Total points redeemed
    $result = $conn->query("SELECT SUM(total_redeemed) as total FROM loyalty_points");
    if ($row = $result->fetch_assoc()) {
        $stats['total_points_redeemed'] = $row['total'] ?? 0;
    }
    
    // Active members (activity in last 30 days)
    $result = $conn->query("SELECT COUNT(*) as count FROM loyalty_points 
        WHERE last_activity > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    if ($row = $result->fetch_assoc()) {
        $stats['active_members'] = $row['count'];
    }
    
    return $stats;
}
// No closing PHP tag - prevents accidental whitespace output