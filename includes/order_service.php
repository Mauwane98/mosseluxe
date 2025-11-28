<?php
require_once __DIR__ . '/config.php';

/**
 * Generates a unique, readable order ID
 * Format: MSL-YYYY-NNNNN (e.g., MSL-2024-00001)
 * 
 * Uses atomic counter table to prevent duplicates
 *
 * @return string The formatted order ID
 * @throws Exception if unable to generate unique ID
 */
function generate_order_id() {
    $conn = get_db_connection();
    $year = date('Y');
    
    try {
        // Ensure counter exists for current year
        $stmt = $conn->prepare("INSERT IGNORE INTO order_counters (year, counter) VALUES (?, 0)");
        $stmt->bind_param("i", $year);
        $stmt->execute();
        $stmt->close();
        
        // Atomically increment and get the next counter value
        // This is thread-safe and prevents race conditions
        $stmt = $conn->prepare("
            UPDATE order_counters 
            SET counter = LAST_INSERT_ID(counter + 1) 
            WHERE year = ?
        ");
        $stmt->bind_param("i", $year);
        $stmt->execute();
        $stmt->close();
        
        // Get the incremented value
        $result = $conn->query("SELECT LAST_INSERT_ID() as next_num");
        $row = $result->fetch_assoc();
        $next_number = $row['next_num'];
        
        // Format as 5-digit number with leading zeros
        $formatted_number = str_pad($next_number, 5, '0', STR_PAD_LEFT);
        
        return "MSL-{$year}-{$formatted_number}";
        
    } catch (Exception $e) {
        // Fallback to UUID-based order ID if counter fails
        error_log("Order ID generation error: " . $e->getMessage());
        $unique_id = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        return "MSL-{$year}-{$unique_id}";
    }
}

/**
 * Validates an order ID format
 *
 * @param string $order_id
 * @return bool
 */
function validate_order_id($order_id) {
    return preg_match('/^MSL-\d{4}-\d{5}$/', $order_id);
}

/**
 * Gets the numeric ID from a formatted order ID
 *
 * @param string $formatted_order_id
 * @return int|null
 */
function get_numeric_id_from_order_id($formatted_order_id) {
    if (validate_order_id($formatted_order_id)) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id FROM orders WHERE order_id = ?");
        $stmt->bind_param("s", $formatted_order_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['id'];
        }
        $stmt->close();
    }
    return null;
}

/**
 * Gets the formatted order ID from numeric ID
 *
 * @param int $numeric_id
 * @return string|null
 */
function get_order_id_from_numeric_id($numeric_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE id = ?");
    $stmt->bind_param("i", $numeric_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['order_id'];
    }
    $stmt->close();
    return null;
}
// No closing PHP tag - prevents accidental whitespace output