<?php
require_once __DIR__ . '/bootstrap.php';

class ReferralService {
    private $conn;

    public function __construct() {
        $this->conn = get_db_connection();
    }

    /**
     * Generate a unique referral code for a user
     */
    public function generateReferralCode($user_id) {
        do {
            $code = 'REF' . strtoupper(substr(md5($user_id . microtime()), 0, 7));
            $exists = $this->conn->prepare("SELECT id FROM user_referrals WHERE referral_code = ?");
            $exists->bind_param("s", $code);
            $exists->execute();
            $exists->store_result();
        } while ($exists->num_rows > 0);

        return $code;
    }

    /**
     * Create or get referral information for a user
     */
    public function getOrCreateReferral($user_id) {
        // Check if user already has a referral
        $stmt = $this->conn->prepare("SELECT * FROM user_referrals WHERE referrer_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Return existing referral
            return $result->fetch_assoc();
        }

        // Create new referral
        $referral_code = $this->generateReferralCode($user_id);
        $referral_link = SITE_URL . '?ref=' . $referral_code;

        $insert_stmt = $this->conn->prepare("INSERT INTO user_referrals (referrer_id, referral_code, referral_link) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iss", $user_id, $referral_code, $referral_link);
        $insert_stmt->execute();

        if ($insert_stmt->affected_rows > 0) {
            $referral_id = $insert_stmt->insert_id;
            $insert_stmt->close();

            // Get the created referral
            $stmt = $this->conn->prepare("SELECT * FROM user_referrals WHERE id = ?");
            $stmt->bind_param("i", $referral_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        return false;
    }

    /**
     * Process referral from URL parameter
     */
    public function processReferral($referral_code) {
        // Get referral by code
        $stmt = $this->conn->prepare("SELECT * FROM user_referrals WHERE referral_code = ?");
        $stmt->bind_param("s", $referral_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false;
        }

        $referral = $result->fetch_assoc();

        // Store referral code in session for later use during registration
        $_SESSION['referral_code'] = $referral_code;

        return $referral;
    }

    /**
     * Link referee to referrer on registration
     */
    public function linkReferralOnRegistration($new_user_id) {
        if (!isset($_SESSION['referral_code'])) {
            return false;
        }

        $referral_code = $_SESSION['referral_code'];

        // Get referral and update with referee_id and status
        $stmt = $this->conn->prepare("UPDATE user_referrals SET referee_id = ?, status = 'registered', referee_email = (SELECT email FROM users WHERE id = ?) WHERE referral_code = ? AND referee_id IS NULL");
        $stmt->bind_param("iis", $new_user_id, $new_user_id, $referral_code);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Clear session
            unset($_SESSION['referral_code']);

            // Create discount codes for both referrer and referee
            return $this->createReferralDiscountCodes($new_user_id, $referral_code);
        }

        return false;
    }

    /**
     * Create discount codes for referral program
     */
    private function createReferralDiscountCodes($referee_id, $referral_code) {
        // Get referral details
        $stmt = $this->conn->prepare("SELECT * FROM user_referrals WHERE referral_code = ?");
        $stmt->bind_param("s", $referral_code);
        $stmt->execute();
        $referral = $stmt->get_result()->fetch_assoc();

        if (!$referral) return false;

        $referrer_id = $referral['referrer_id'];

        // Create discount code for referee (referee gets 10% off first order)
        $referee_code = 'WELCOME10-' . strtoupper(substr(md5($referee_id . 'referee'), 0, 6));
        $referee_discount_stmt = $this->conn->prepare("INSERT INTO referral_discount_codes (referral_id, user_id, discount_code, type, value, expires_at) VALUES (?, ?, ?, 'percentage', 10.00, DATE_ADD(NOW(), INTERVAL 30 DAY))");
        $referee_discount_stmt->bind_param("iis", $referral['id'], $referee_id, $referee_code);
        $referee_discount_stmt->execute();

        // Create discount code for referrer (referrer gets 15% off next order)
        $referrer_code = 'FRIEND15-' . strtoupper(substr(md5($referrer_id . 'referrer'), 0, 6));
        $referrer_discount_stmt = $this->conn->prepare("INSERT INTO referral_discount_codes (referral_id, user_id, discount_code, type, value, expires_at) VALUES (?, ?, ?, 'percentage', 15.00, DATE_ADD(NOW(), INTERVAL 60 DAY))");
        $referrer_discount_stmt->bind_param("iis", $referral['id'], $referrer_id, $referrer_code);
        $referrer_discount_stmt->execute();

        return true;
    }

    /**
     * Process referral rewards when an order is completed
     */
    public function processReferralRewards($user_id) {
        // Check if this user is a referee and completed first order
        $stmt = $this->conn->prepare("
            SELECT ur.*
            FROM user_referrals ur
            JOIN orders o ON o.user_id = ur.referee_id
            WHERE ur.referee_id = ? AND ur.status = 'registered' AND ur.referee_discount_applied = 0
            ORDER BY o.created_at ASC LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $referral = $result->fetch_assoc();

            // Update referral status
            $update_stmt = $this->conn->prepare("UPDATE user_referrals SET status = 'completed_order', referee_discount_applied = 1 WHERE id = ?");
            $update_stmt->bind_param("i", $referral['id']);
            $update_stmt->execute();

            // Notify referrer about successful referral (you can add email notification here)
            // For now, just log it
            error_log("Referral completed: Referrer {$referral['referrer_id']} referred {$user_id}");
        }
    }

    /**
     * Get user's referral stats
     */
    public function getReferralStats($user_id) {
        $stats = [
            'total_referrals' => 0,
            'completed_referrals' => 0,
            'pending_rewards' => 0,
            'referral_code' => null,
            'referral_link' => null
        ];

        // Get referral information
        $referral = $this->getOrCreateReferral($user_id);
        if ($referral) {
            $stats['referral_code'] = $referral['referral_code'];
            $stats['referral_link'] = $referral['referral_link'];

            // Get total referrals
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM user_referrals WHERE referrer_id = ? AND status != 'pending'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stats['total_referrals'] = $stmt->get_result()->fetch_assoc()['count'];

            // Get completed referrals
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM user_referrals WHERE referrer_id = ? AND status = 'completed_order'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stats['completed_referrals'] = $stmt->get_result()->fetch_assoc()['count'];

            // Calculate pending rewards (completed referrals minus applied referrer discounts)
            $stats['pending_rewards'] = $stats['completed_referrals'] - $referral['referrer_discount_applied'];
        }

        return $stats;
    }

    /**
     * Check if a discount code is a referral code
     */
    public function validateReferralDiscountCode($code, $user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM referral_discount_codes WHERE discount_code = ? AND user_id = ? AND used = 0 AND (expires_at IS NULL OR expires_at > NOW())");
        $stmt->bind_param("si", $code, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return false;
    }

    /**
     * Mark referral discount code as used
     */
    public function markReferralDiscountAsUsed($code) {
        $stmt = $this->conn->prepare("UPDATE referral_discount_codes SET used = 1 WHERE discount_code = ?");
        $stmt->bind_param("s", $code);
        return $stmt->execute();
    }
}
// No closing PHP tag - prevents accidental whitespace output