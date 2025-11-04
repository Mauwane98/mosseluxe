<?php

namespace App\Controllers;

use Twig\Environment;

class ApplyDiscountController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function apply()
    {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            return json_encode(['success' => false, 'message' => 'Invalid CSRF token!']);
        }

        $discount_code = trim($_POST['discount_code']);

        if (empty($discount_code)) {
            return json_encode(['success' => false, 'message' => 'Please enter a discount code.']);
        }

        // Check discount code in database
        $sql = "SELECT * FROM discount_codes WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())";
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("s", $discount_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $discount = $result->fetch_assoc();
            $stmt->close();

            if ($discount) {
                if ($discount['usage_limit'] > 0 && $discount['usage_count'] >= $discount['usage_limit']) {
                    return json_encode(['success' => false, 'message' => 'Discount code has reached its usage limit.']);
                }

                // Calculate subtotal from cart
                $subtotal = 0;
                if (isset($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $item) {
                        $subtotal += (float)$item['price'] * (int)$item['quantity'];
                    }
                }

                $discount_amount = 0;
                if ($discount['type'] === 'percentage') {
                    $discount_amount = $subtotal * ($discount['value'] / 100);
                } elseif ($discount['type'] === 'fixed') {
                    $discount_amount = $discount['value'];
                }

                // Ensure discount amount does not exceed subtotal
                $discount_amount = min($discount_amount, $subtotal);

                $_SESSION['discount'] = [
                    'code' => $discount_code,
                    'amount' => $discount_amount,
                    'type' => $discount['type'],
                    'value' => $discount['value']
                ];

                // Increment usage count
                $update_sql = "UPDATE discount_codes SET usage_count = usage_count + 1 WHERE code = ?";
                if ($update_stmt = $this->conn->prepare($update_sql)) {
                    $update_stmt->bind_param("s", $discount_code);
                    $update_stmt->execute();
                    $update_stmt->close();
                }

                $new_total = $subtotal - $discount_amount + SHIPPING_COST;

                return json_encode([
                    'success' => true,
                    'message' => 'Discount applied successfully!',
                    'discount_amount' => $discount_amount,
                    'discount_amount_formatted' => 'R ' . number_format($discount_amount, 2),
                    'new_total' => $new_total,
                    'new_total_formatted' => 'R ' . number_format($new_total, 2)
                ]);

            } else {
                return json_encode(['success' => false, 'message' => 'Invalid or expired discount code.']);
            }
        } else {
            error_log("Error preparing discount code query: " . $this->conn->error);
            return json_encode(['success' => false, 'message' => 'An error occurred.']);
        }
    }
}
