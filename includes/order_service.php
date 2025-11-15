<?php
require_once __DIR__ . '/config.php';

/**
 * Generates a unique, readable order ID
 * Format: MSL-YYYY-NNNNN (e.g., MSL-2024-00001)
 *
 * @return string The formatted order ID
 */
function generate_order_id() {
    // Get current year
    $year = date('Y');

    // Generate a 5-digit sequential number based on existing orders
    $conn = get_db_connection();

    // Get the highest order number for this year
    $stmt = $conn->prepare("
        SELECT order_id FROM orders
        WHERE order_id LIKE ?
        ORDER BY CAST(SUBSTRING(order_id, 9) AS UNSIGNED) DESC
        LIMIT 1
    ");
    $pattern = "MSL-{$year}-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();

    $next_number = 1;
    if ($result->num_rows > 0) {
        $last_order = $result->fetch_assoc()['order_id'];
        // Extract the number part: MSL-2024-00001 -> 00001
        $number_part = substr($last_order, -5);
        $next_number = (int)$number_part + 1;
    }

    $stmt->close();

    // Format as 5-digit number with leading zeros
    $formatted_number = str_pad($next_number, 5, '0', STR_PAD_LEFT);

    return "MSL-{$year}-{$formatted_number}";
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
?>
