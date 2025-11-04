<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Corrected paths to match the 'phpmailer' directory structure
require_once __DIR__ . '/../phpmailer/Exception.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';

class NotificationService {

    // Define a low stock threshold. This could also be a global constant in config.php.
    const LOW_STOCK_THRESHOLD = 5;

    /**
     * A centralized method to send emails using PHPMailer.
     *
     * @param string $to The recipient's email address.
     * @param string $subject The email subject.
     * @param string $body The HTML email body.
     * @return bool True on success, false on failure.
     */
    private static function sendEmail($to, $subject, $body) {
        require_once __DIR__ . '/config.php';
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port       = SMTP_PORT;

            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            return $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Sends a low stock alert email to the admin.
     *
     * @param array $product The product data (must include 'id', 'name', and 'stock').
     */
    public static function sendLowStockAlert($product) {
        // In a real-world application, using a library like PHPMailer is highly recommended
        $admin_email = "admin@mosseluxe.com"; // It's good practice to make this configurable.
        $subject = "Low Stock Alert: " . $product['name'];
        
        $message = "
        <html>
        <body style='font-family: sans-serif; color: #333;'>
            <h2 style='color: #C5A572;'>Low Stock Alert</h2>
            <p>This is an automated alert from your Mossé Luxe store.</p>
            <p>The stock for the following product has fallen to or below the threshold of " . self::LOW_STOCK_THRESHOLD . " units:</p>
            <ul style='list-style-type: none; padding: 0;'>
                <li><strong>Product:</strong> " . htmlspecialchars($product['name']) . "</li>
                <li><strong>Product ID:</strong> " . $product['id'] . "</li>
                <li><strong>Current Stock:</strong> " . $product['stock'] . "</li>
            </ul>
            <p>Please consider restocking this item soon.</p>
        </body>
        </html>
        ";
        self::sendEmail($admin_email, $subject, $message);
    }

    /**
     * Sends a "Back in Stock" alert email to a customer.
     *
     * @param array $product The product data (must include 'id', 'name').
     * @param string $recipient_email The customer's email address.
     */
    public static function sendBackInStockAlert($product, $recipient_email) {
        require_once __DIR__ . '/config.php'; // To get SITE_URL

        $subject = "It's Back! " . $product['name'] . " is now in stock";
        $product_url = SITE_URL . 'product.php?id=' . $product['id'];

        $message = "
        <html>
        <body style='font-family: sans-serif; color: #333;'>
            <h2 style='color: #C5A572;'>Good News!</h2>
            <p>You asked to be notified, and we're excited to let you know that the following item is back in stock:</p>
            <h3 style='font-weight: bold;'>" . htmlspecialchars($product['name']) . "</h3>
            <p>Don't miss out! You can purchase it now by visiting the link below:</p>
            <p><a href='" . $product_url . "' style='display: inline-block; padding: 10px 20px; background-color: #C5A572; color: #000; text-decoration: none; border-radius: 5px;'>View Product</a></p>
            <p>Thank you for your interest in Mossé Luxe.</p>
        </body>
        </html>
        ";
        self::sendEmail($recipient_email, $subject, $message);
    }

    /**
     * Sends a password reset link to a user.
     *
     * @param string $recipient_email The user's email address.
     * @param string $reset_link The password reset link.
     */
    public static function sendPasswordResetLink($recipient_email, $reset_link) {
        $subject = "Your Password Reset Link for Mossé Luxe";
        $message = "
        <html>
        <body style='font-family: sans-serif; color: #333;'>
            <h2 style='color: #C5A572;'>Password Reset Request</h2>
            <p>You requested a password reset for your Mossé Luxe account. Please click the link below to set a new password. This link is valid for 1 hour.</p>
            <p><a href='" . $reset_link . "' style='display: inline-block; padding: 10px 20px; background-color: #000; color: #C5A572; text-decoration: none; border-radius: 5px;'>Reset Your Password</a></p>
            <p>If you did not request a password reset, please ignore this email.</p>
        </body>
        </html>
        ";
        self::sendEmail($recipient_email, $subject, $message);
    }

    /**
     * Finds and sends "Back in Stock" alerts for a given product.
     * This should be called when a product's stock is increased.
     *
     * @param int $product_id The ID of the product that has been restocked.
     * @param object $conn The database connection object.
     */
    public static function processBackInStockNotifications($product_id, $conn) {
        // First, get product details
        $product_sql = "SELECT id, name, stock FROM products WHERE id = ?";
        $stmt_prod = $conn->prepare($product_sql);
        $stmt_prod->bind_param("i", $product_id);
        $stmt_prod->execute();
        $product_result = $stmt_prod->get_result();
        $product = $product_result->fetch_assoc();
        $stmt_prod->close();

        if (!$product || $product['stock'] <= 0) {
            // Product doesn't exist or is still out of stock
            return;
        }

        // Find users who requested notification and haven't been notified yet
        $notify_sql = "SELECT u.email FROM stock_notifications sn JOIN users u ON sn.user_id = u.id WHERE sn.product_id = ? AND sn.notified = 0";
        $stmt_notify = $conn->prepare($notify_sql);
        $stmt_notify->bind_param("i", $product_id);
        $stmt_notify->execute();
        $notify_result = $stmt_notify->get_result();

        $notified_users = 0;
        while ($user = $notify_result->fetch_assoc()) {
            self::sendBackInStockAlert($product, $user['email']);
            $notified_users++;
        }
        $stmt_notify->close();

        // Mark notifications as sent
        if ($notified_users > 0) {
            $update_sql = "UPDATE stock_notifications SET notified = 1 WHERE product_id = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("i", $product_id);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }
}