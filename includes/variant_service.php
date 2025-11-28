<?php
require_once __DIR__ . '/config.php';

/**
 * Get all active variant options by type
 */
function get_variant_options_by_type($variant_type) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("
        SELECT id, option_value, display_name, sort_order
        FROM variant_options
        WHERE variant_type = ? AND is_active = 1
        ORDER BY sort_order ASC, display_name ASC
    ");
    $stmt->bind_param("s", $variant_type);
    $stmt->execute();
    $result = $stmt->get_result();

    $options = [];
    while ($row = $result->fetch_assoc()) {
        $options[] = $row;
    }

    $stmt->close();
    return $options;
}

/**
 * Get product variants
 */
function get_product_variants($product_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("
        SELECT id, variant_name, variant_value, sku, stock, price_modifier, sort_order
        FROM product_variants
        WHERE product_id = ? AND is_active = 1
        ORDER BY sort_order ASC, variant_name ASC, variant_value ASC
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $variants = [];
    while ($row = $result->fetch_assoc()) {
        $variants[] = $row;
    }

    $stmt->close();
    return $variants;
}

/**
 * Get product variants organized by type
 */
function get_product_variants_by_type($product_id) {
    $variants = get_product_variants($product_id);
    $organized = [];

    foreach ($variants as $variant) {
        if (!isset($organized[$variant['variant_name']])) {
            $organized[$variant['variant_name']] = [];
        }
        $organized[$variant['variant_name']][] = $variant;
    }

    return $organized;
}

/**
 * Get specific variant details
 */
function get_variant_by_id($variant_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("
        SELECT id, product_id, variant_name, variant_value, sku, stock, price_modifier
        FROM product_variants
        WHERE id = ? AND is_active = 1
    ");
    $stmt->bind_param("i", $variant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $variant = $result->fetch_assoc();
    $stmt->close();
    return $variant;
}

/**
 * Add variant to product
 */
function add_product_variant($product_id, $variant_name, $variant_value, $sku = null, $stock = 0, $price_modifier = 0.00) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("
        INSERT INTO product_variants (product_id, variant_name, variant_value, sku, stock, price_modifier)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssdd", $product_id, $variant_name, $variant_value, $sku, $stock, $price_modifier);

    $success = $stmt->execute();
    $insert_id = $stmt->insert_id;
    $stmt->close();
    return $success ? $insert_id : false;
}

/**
 * Check if variant combination is available for a product
 */
function get_variant_stock($product_id, $variants = []) {
    $conn = get_db_connection();

    if (empty($variants)) {
        // Get base product stock
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['stock'] ?? 0;
    }

    // Build dynamic query for variants
    $where_parts = [];
    $params = [$product_id];
    $types = 'i';

    foreach ($variants as $name => $value) {
        $where_parts[] = "(variant_name = ? AND variant_value = ?)";
        $params[] = $name;
        $params[] = $value;
        $types .= 'ss';
    }

    $where_clause = implode(' OR ', $where_parts);
    $sql = "SELECT stock FROM product_variants WHERE product_id = ? AND ($where_clause) AND is_active = 1";

    // For now, return the minimum stock across all selected variants
    // In a more complex system, you'd match exact combinations
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $stocks = [];
    while ($row = $result->fetch_assoc()) {
        $stocks[] = $row['stock'];
    }
    $stmt->close();

    return !empty($stocks) ? min($stocks) : 0;
}

/**
 * Calculate variant price
 */
function calculate_variant_price($base_price, $variants = []) {
    $total_modifier = 0;

    if (!empty($variants)) {
        $conn = get_db_connection();

        // Get modifiers for selected variants
        foreach ($variants as $name => $value) {
            $stmt = $conn->prepare("
                SELECT price_modifier
                FROM product_variants
                WHERE product_id = (
                    SELECT product_id FROM product_variants
                    WHERE variant_name = ? AND variant_value = ? AND is_active = 1
                    LIMIT 1
                ) AND variant_name = ? AND variant_value = ? AND is_active = 1
            ");
            $stmt->bind_param("ssss", $name, $value, $name, $value);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $total_modifier += $row['price_modifier'];
            }

            $stmt->close();
        }
    }

    return $base_price + $total_modifier;
}

/**
 * Update variant stock
 */
function update_variant_stock($variant_id, $quantity_change) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("UPDATE product_variants SET stock = GREATEST(0, stock + ?) WHERE id = ?");
    $stmt->bind_param("ii", $quantity_change, $variant_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Delete product variant
 */
function delete_product_variant($variant_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("DELETE FROM product_variants WHERE id = ?");
    $stmt->bind_param("i", $variant_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Get all unique variant names for a product
 */
function get_product_variant_types($product_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("
        SELECT DISTINCT variant_name
        FROM product_variants
        WHERE product_id = ? AND is_active = 1
        ORDER BY variant_name ASC
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $types = [];
    while ($row = $result->fetch_assoc()) {
        $types[] = $row['variant_name'];
    }

    $stmt->close();
    return $types;
}

/**
 * Format variant selection for display
 */
function format_variant_selection($variants = []) {
    $formatted = [];

    foreach ($variants as $name => $value) {
        // Try to get display name if it's a standard option
        $display = get_variant_display_name($name, $value);
        $formatted[] = $display ?: "$name: $value";
    }

    return implode(', ', $formatted);
}

/**
 * Get display name for variant option
 */
function get_variant_display_name($variant_type, $option_value) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("
        SELECT display_name
        FROM variant_options
        WHERE variant_type = ? AND option_value = ? AND is_active = 1
    ");
    $stmt->bind_param("ss", $variant_type, $option_value);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['display_name'] ?? $option_value;
}

/**
 * Validate variant selection for a product
 */
function validate_variant_selection($product_id, $selected_variants = []) {
    $available_variants = get_product_variants_by_type($product_id);
    $errors = [];

    foreach ($selected_variants as $type => $value) {
        if (!isset($available_variants[$type])) {
            $errors[] = "Invalid variant type: $type";
            continue;
        }

        $found = false;
        foreach ($available_variants[$type] as $variant) {
            if ($variant['variant_value'] === $value) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $errors[] = "Invalid $type option: $value";
        }
    }

    return $errors;
}
// No closing PHP tag - prevents accidental whitespace output