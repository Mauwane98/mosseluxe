<?php
/**
 * Mossé Luxe - Engagement & Loyalty Service
 * Handles price alerts, back-in-stock alerts, and loyalty rewards
 */

function get_loyalty_setting($key) {
    static $cache = [];

    if (!isset($cache[$key])) {
        $conn = get_db_connection();
        $sql = "SELECT setting_value FROM loyalty_settings WHERE setting_key = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $cache[$key] = $row ? $row['setting_value'] : null;
        $stmt->close();
    }

    return $cache[$key];
}

function ensure_user_loyalty_account($user_id) {
    $conn = get_db_connection();

    // Check if user already has loyalty account
    $check_sql = "SELECT id FROM loyalty_points WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows == 0) {
        // Create loyalty account
        $insert_sql = "INSERT INTO loyalty_points (user_id) VALUES (?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("i", $user_id);
        $insert_stmt->execute();
        $insert_stmt->close();

        // Award signup points
        $signup_points = (int) get_loyalty_setting('points_signup');
        if ($signup_points > 0) {
            award_loyalty_points($user_id, $signup_points, 'Account signup bonus', 'signup');
        }
    }
    $check_stmt->close();
}

function award_loyalty_points($user_id, $points, $description, $reference_type = null, $reference_id = null) {
    $conn = get_db_connection();

    // Ensure user has loyalty account
    ensure_user_loyalty_account($user_id);

    // Add transaction
    $expiry_days = (int) get_loyalty_setting('points_expiry_days');
    $expires_at = $expiry_days > 0 ? date('Y-m-d H:i:s', strtotime("+$expiry_days days")) : null;

    $transaction_sql = "INSERT INTO loyalty_transactions (user_id, transaction_type, points, description, reference_id, expires_at)
                       VALUES (?, 'earned', ?, ?, ?, ?)";
    $transaction_stmt = $conn->prepare($transaction_sql);
    $transaction_stmt->bind_param("iisss", $user_id, $points, $description,
                                  $reference_id ? "$reference_type:$reference_id" : null, $expires_at);
    $transaction_stmt->execute();
    $transaction_stmt->close();

    // Update user balance
    $update_sql = "UPDATE loyalty_points
                  SET points = points + ?,
                      total_earned = total_earned + ?,
                      updated_at = NOW()
                  WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iii", $points, $points, $user_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Update tier
    update_user_loyalty_tier($user_id);
}

function spend_loyalty_points($user_id, $points, $description, $reference_id = null) {
    $conn = get_db_connection();

    // Check if user has enough points
    $check_sql = "SELECT points FROM loyalty_points WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $check_stmt->close();

    if (!$row || $row['points'] < $points) {
        return false;
    }

    // Add transaction
    $transaction_sql = "INSERT INTO loyalty_transactions (user_id, transaction_type, points, description, reference_id)
                       VALUES (?, 'spent', ?, ?, ?)";
    $transaction_stmt = $conn->prepare($transaction_sql);
    $transaction_stmt->bind_param("iiss", $user_id, $points, $description, $reference_id);
    $transaction_stmt->execute();
    $transaction_stmt->close();

    // Update balance
    $update_sql = "UPDATE loyalty_points
                  SET points = points - ?,
                      total_spent = total_spent + ?,
                      updated_at = NOW()
                  WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iii", $points, $points, $user_id);
    $update_stmt->execute();
    $update_stmt->close();

    return true;
}

function update_user_loyalty_tier($user_id) {
    $conn = get_db_connection();

    // Get current points
    $points_sql = "SELECT points FROM loyalty_points WHERE user_id = ?";
    $points_stmt = $conn->prepare($points_sql);
    $points_stmt->bind_param("i", $user_id);
    $points_stmt->execute();
    $result = $points_stmt->get_result();
    $row = $result->fetch_assoc();
    $points_stmt->close();

    if (!$row) return;

    $points = $row['points'];

    // Determine new tier
    $new_tier = 'bronze';
    $tiers = [
        'platinum' => (int) get_loyalty_setting('tier_platinum_min'),
        'gold' => (int) get_loyalty_setting('tier_gold_min'),
        'silver' => (int) get_loyalty_setting('tier_silver_min'),
        'bronze' => 0
    ];

    foreach ($tiers as $tier_name => $min_points) {
        if ($points >= $min_points) {
            $new_tier = $tier_name;
            break;
        }
    }

    // Update tier
    $update_sql = "UPDATE loyalty_points SET tier = ?, updated_at = NOW() WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_tier, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

function get_user_loyalty_info($user_id) {
    $conn = get_db_connection();

    ensure_user_loyalty_account($user_id);

    $sql = "SELECT * FROM loyalty_points WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $loyalty_data = $result->fetch_assoc();
    $stmt->close();

    if (!$loyalty_data) {
        return null;
    }

    // Get recent transactions
    $transactions_sql = "SELECT * FROM loyalty_transactions
                        WHERE user_id = ? AND transaction_type != 'expired'
                        ORDER BY created_at DESC LIMIT 10";
    $transactions_stmt = $conn->prepare($transactions_sql);
    $transactions_stmt->bind_param("i", $user_id);
    $transactions_stmt->execute();
    $transactions_result = $transactions_stmt->get_result();

    $transactions = [];
    while ($row = $transactions_result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $transactions_stmt->close();

    $loyalty_data['recent_transactions'] = $transactions;

    // Get tier progress
    $tier_info = get_loyalty_tier_progress($user_id);
    $loyalty_data['tier_progress'] = $tier_info;

    return $loyalty_data;
}

function get_loyalty_tier_progress($user_id) {
    $conn = get_db_connection();

    $points_sql = "SELECT points FROM loyalty_points WHERE user_id = ?";
    $points_stmt = $conn->prepare($points_sql);
    $points_stmt->bind_param("i", $user_id);
    $points_stmt->execute();
    $result = $points_stmt->get_result();
    $row = $result->fetch_assoc();
    $points_stmt->close();

    if (!$row) return null;

    $points = $row['points'];

    $tiers = [
        'platinum' => (int) get_loyalty_setting('tier_platinum_min'),
        'gold' => (int) get_loyalty_setting('tier_gold_min'),
        'silver' => (int) get_loyalty_setting('tier_silver_min'),
        'bronze' => 0
    ];

    $current_tier = 'bronze';
    $next_tier = null;
    $points_to_next = 0;

    foreach ($tiers as $tier_name => $min_points) {
        if ($points >= $min_points) {
            $current_tier = $tier_name;
            // Find next tier
            $tier_keys = array_keys($tiers);
            $current_index = array_search($tier_name, $tier_keys);
            if ($current_index > 0) {
                $next_tier = $tier_keys[$current_index - 1];
                $points_to_next = $tiers[$next_tier] - $points;
            }
            break;
        }
    }

    return [
        'current_tier' => $current_tier,
        'next_tier' => $next_tier,
        'points_to_next' => max(0, $points_to_next),
        'progress_percentage' => $next_tier ? min(100, (($points - $tiers[$current_tier]) / ($tiers[$next_tier] - $tiers[$current_tier])) * 100) : 100
    ];
}

// Price Alert Functions
function set_price_alert($user_id, $product_id, $alert_price) {
    $conn = get_db_connection();

    // Check if alert already exists
    $check_sql = "SELECT id FROM price_alerts WHERE user_id = ? AND product_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // Update existing alert
        $update_sql = "UPDATE price_alerts SET alert_price = ?, is_active = 1 WHERE user_id = ? AND product_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("dii", $alert_price, $user_id, $product_id);
        $result = $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Create new alert
        $insert_sql = "INSERT INTO price_alerts (user_id, product_id, alert_price) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iid", $user_id, $product_id, $alert_price);
        $result = $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_stmt->close();

    return $result;
}

function remove_price_alert($user_id, $product_id) {
    $conn = get_db_connection();

    $sql = "DELETE FROM price_alerts WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

function get_price_alert($user_id, $product_id) {
    $conn = get_db_connection();

    $sql = "SELECT alert_price FROM price_alerts WHERE user_id = ? AND product_id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $alert = $result->fetch_assoc();
    $stmt->close();

    return $alert ? $alert['alert_price'] : null;
}

// Back-in-Stock Alert Functions
function set_back_in_stock_alert($user_id, $product_id, $size_variant = null, $color_variant = null) {
    $conn = get_db_connection();

    // Get user email
    $email_sql = "SELECT email FROM users WHERE id = ?";
    $email_stmt = $conn->prepare($email_sql);
    $email_stmt->bind_param("i", $user_id);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();
    $user_data = $email_result->fetch_assoc();
    $email_stmt->close();

    if (!$user_data) return false;

    $email = $user_data['email'];

    // Check if alert already exists
    $check_sql = "SELECT id FROM back_in_stock_alerts
                 WHERE user_id = ? AND product_id = ? AND
                       size_variant <=> ? AND color_variant <=> ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iiss", $user_id, $product_id, $size_variant, $color_variant);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        return true; // Already exists
    }
    $check_stmt->close();

    // Create new alert
    $insert_sql = "INSERT INTO back_in_stock_alerts (user_id, product_id, email, size_variant, color_variant)
                   VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iisss", $user_id, $product_id, $email, $size_variant, $color_variant);
    $result = $insert_stmt->execute();
    $insert_stmt->close();

    return $result;
}

// Process order completion and award points
function process_order_loyalty_points($user_id, $order_total, $order_id) {
    // Award points for purchase based on order total
    $points_per_100 = (int) get_loyalty_setting('points_per_r100');
    if ($points_per_100 > 0) {
        $points_to_award = floor($order_total / 100) * $points_per_100;
        if ($points_to_award > 0) {
            award_loyalty_points($user_id, $points_to_award,
                                "Earned for purchase (R{$order_total})",
                                'order', $order_id);
        }
    }
}

// Process review submission and award points
function process_review_loyalty_points($user_id, $review_id) {
    $review_points = (int) get_loyalty_setting('points_review');
    if ($review_points > 0) {
        award_loyalty_points($user_id, $review_points,
                           'Points earned for leaving a product review',
                           'review', $review_id);
    }
}

// Process social media sharing points
function process_social_share_points($user_id, $platform, $content_id) {
    $share_points = (int) get_loyalty_setting('points_social_share');
    if ($share_points > 0) {
        award_loyalty_points($user_id, $share_points,
                           "{$platform} social media share",
                           'social', "{$platform}_{$content_id}");
    }
}

// Clean up expired loyalty points
function cleanup_expired_loyalty_points() {
    $conn = get_db_connection();

    // Find expired points
    $expired_sql = "SELECT id, user_id, points FROM loyalty_transactions
                   WHERE transaction_type = 'earned' AND expires_at < NOW() AND expires_at IS NOT NULL";
    $expired_stmt = $conn->prepare($expired_sql);
    $expired_stmt->execute();
    $expired_result = $expired_stmt->get_result();

    $total_expired = 0;
    while ($row = $expired_result->fetch_assoc()) {
        $total_expired += $row['points'];

        // Mark transaction as expired
        $update_sql = "UPDATE loyalty_transactions SET transaction_type = 'expired' WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $row['id']);
        $update_stmt->execute();
        $update_stmt->close();
    }
    $expired_stmt->close();

    // Deduct expired points from user balance if any users had expirations
    if ($total_expired > 0) {
        // Group by user_id and deduct totals
        $user_expiry_sql = "SELECT user_id, SUM(points) as expired_points
                           FROM loyalty_transactions
                           WHERE transaction_type = 'expired' AND
                                 created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                           GROUP BY user_id";
        $user_expiry_stmt = $conn->prepare($user_expiry_sql);
        $user_expiry_stmt->execute();
        $user_expiry_result = $user_expiry_stmt->get_result();

        while ($row = $user_expiry_result->fetch_assoc()) {
            $deduct_sql = "UPDATE loyalty_points
                          SET points = GREATEST(0, points - ?),
                              updated_at = NOW()
                          WHERE user_id = ?";
            $deduct_stmt = $conn->prepare($deduct_sql);
            $deduct_stmt->bind_param("ii", $row['expired_points'], $row['user_id']);
            $deduct_stmt->execute();
            $deduct_stmt->close();

            // Update tier for affected users
            update_user_loyalty_tier($row['user_id']);
        }
        $user_expiry_stmt->close();
    }
}
// No closing PHP tag - prevents accidental whitespace output