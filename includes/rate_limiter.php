<?php
/**
 * Rate Limiter Service
 * Prevents brute force attacks and API abuse
 */

class RateLimiter {
    
    /**
     * Check if an action is rate limited
     * 
     * @param string $action Action identifier (e.g., 'login', 'checkout', 'api_call')
     * @param string $identifier User identifier (IP, email, user_id)
     * @param int $max_attempts Maximum attempts allowed
     * @param int $time_window Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => int]
     */
    public static function check($action, $identifier, $max_attempts = 5, $time_window = 300) {
        $conn = get_db_connection();
        
        // Clean identifier
        $identifier = hash('sha256', $action . ':' . $identifier);
        $current_time = time();
        $window_start = $current_time - $time_window;
        
        // Get or create rate limit record
        $stmt = $conn->prepare("
            SELECT attempts, first_attempt, last_attempt 
            FROM rate_limits 
            WHERE identifier = ? AND action = ? AND first_attempt > ?
        ");
        $stmt->bind_param("ssi", $identifier, $action, $window_start);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $record = $result->fetch_assoc();
            $attempts = $record['attempts'];
            $reset_at = $record['first_attempt'] + $time_window;
            
            if ($attempts >= $max_attempts) {
                $stmt->close();
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'reset_at' => $reset_at,
                    'message' => "Too many attempts. Please try again in " . ceil(($reset_at - $current_time) / 60) . " minutes."
                ];
            }
            
            // Update attempt count
            $stmt->close();
            $stmt = $conn->prepare("
                UPDATE rate_limits 
                SET attempts = attempts + 1, last_attempt = ? 
                WHERE identifier = ? AND action = ?
            ");
            $stmt->bind_param("iss", $current_time, $identifier, $action);
            $stmt->execute();
            $stmt->close();
            
            return [
                'allowed' => true,
                'remaining' => $max_attempts - $attempts - 1,
                'reset_at' => $reset_at
            ];
        } else {
            // Create new record
            $stmt->close();
            $stmt = $conn->prepare("
                INSERT INTO rate_limits (identifier, action, attempts, first_attempt, last_attempt) 
                VALUES (?, ?, 1, ?, ?)
            ");
            $stmt->bind_param("ssii", $identifier, $action, $current_time, $current_time);
            $stmt->execute();
            $stmt->close();
            
            return [
                'allowed' => true,
                'remaining' => $max_attempts - 1,
                'reset_at' => $current_time + $time_window
            ];
        }
    }
    
    /**
     * Reset rate limit for an identifier
     */
    public static function reset($action, $identifier) {
        $conn = get_db_connection();
        $identifier = hash('sha256', $action . ':' . $identifier);
        
        $stmt = $conn->prepare("DELETE FROM rate_limits WHERE identifier = ? AND action = ?");
        $stmt->bind_param("ss", $identifier, $action);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Clean up old rate limit records (run via cron)
     */
    public static function cleanup($older_than_hours = 24) {
        $conn = get_db_connection();
        $cutoff = time() - ($older_than_hours * 3600);
        
        $stmt = $conn->prepare("DELETE FROM rate_limits WHERE last_attempt < ?");
        $stmt->bind_param("i", $cutoff);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        
        return $deleted;
    }
}

// Create rate_limits table if it doesn't exist
function create_rate_limits_table() {
    $conn = get_db_connection();
    
    $sql = "CREATE TABLE IF NOT EXISTS `rate_limits` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `identifier` VARCHAR(64) NOT NULL,
        `action` VARCHAR(50) NOT NULL,
        `attempts` INT(11) NOT NULL DEFAULT 1,
        `first_attempt` INT(11) NOT NULL,
        `last_attempt` INT(11) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `identifier_action` (`identifier`, `action`),
        KEY `last_attempt` (`last_attempt`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->query($sql);
}

// Auto-create table on first use
if (!defined('RATE_LIMITER_TABLE_CREATED')) {
    create_rate_limits_table();
    define('RATE_LIMITER_TABLE_CREATED', true);
}
// No closing PHP tag - prevents accidental whitespace output