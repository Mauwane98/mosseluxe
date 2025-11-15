<?php

require_once __DIR__ . '/config.php'; // For SMTP settings
require_once __DIR__ . '/../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationService {

    public static function sendBackInStockAlert($product, $recipientEmail) {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = SMTP_PORT;
            $mail->SMTPDebug  = 2; // Enable debug output
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug: $str");
            };

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'ciphers' => 'HIGH:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!SRP:!CAMELLIA',
                ),
            );

            //Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($recipientEmail);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Back in Stock Alert: ' . htmlspecialchars($product['name']);
            $mail->Body    = '
                <html>
                <head>
                    <title>Back in Stock Alert!</title>
                </head>
                <body>
                    <p>Good news! The product you were interested in is back in stock:</p>
                    <p><strong>' . htmlspecialchars($product['name']) . '</strong></p>
                    <p>Visit our store to purchase it now:</p>
                    <p><a href="' . SITE_URL . 'product.php?id=' . $product['id'] . '">View Product</a></p>
                    <p>Thank you for your patience!</p>
                    <p>Best regards,<br>' . SMTP_FROM_NAME . '</p>
                </body>
                </html>
            ';
            $mail->AltBody = 'Good news! The product you were interested in is back in stock: ' . $product['name'] . '. Visit our store to purchase it now: ' . SITE_URL . 'product.php?id=' . $product['id'] . '. Thank you for your patience! Best regards, ' . SMTP_FROM_NAME;

            $mail->send();
            error_log("Back in stock alert sent to " . $recipientEmail . " for product " . $product['name']);
            return true;
        } catch (Exception $e) {
            error_log("Failed to send back in stock alert to " . $recipientEmail . " for product " . $product['name'] . ". Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function sendPasswordResetEmail($recipientEmail, $resetLink) {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = SMTP_PORT;

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'ciphers' => 'HIGH:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!SRP:!CAMELLIA',
                ),
            );

            //Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($recipientEmail);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - Mossé Luxe';
            $mail->Body    = '
                <html>
                <head>
                    <title>Password Reset</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #000; color: #fff; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .button { display: inline-block; background: #000; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 4px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <div class="logo">
                                <img src="' . SITE_URL . 'assets/images/logo-light.png" alt="Mossé Luxe" style="height: 80px; width: auto;" />
                            </div>
                            <h1>Mossé Luxe</h1>
                            <p style="font-size: 18px;">Message Reply</p>
                        </div>
                        <div class="content">
                            <p>Hello,</p>
                            <p>You recently requested to reset your password for your Mossé Luxe account. Click the button below to reset your password:</p>

                            <div style="text-align: center;">
                                <a href="' . htmlspecialchars($resetLink) . '" class="button">Reset Password</a>
                            </div>

                            <div class="warning">
                                <strong>Security Notice:</strong> This link will expire in 1 hour for your security. If you did not request this password reset, please ignore this email.
                            </div>

                            <p>If the button doesn\'t work, copy and paste this link into your browser:</p>
                            <p><a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a></p>

                            <p>Best regards,<br>The Mossé Luxe Team</p>
                        </div>
                        <div class="footer">
                            <p>This email was sent to you because you requested a password reset on Mossé Luxe.</p>
                            <p>If you no longer wish to receive these emails, please contact our support team.</p>
                        </div>
                    </div>
                </body>
                </html>
            ';

            $mail->AltBody = 'Hello, You recently requested to reset your password for your Mossé Luxe account. Please visit the following link to reset your password: ' . $resetLink . '. This link will expire in 1 hour for your security. If you did not request this password reset, please ignore this email. Best regards, The Mossé Luxe Team';

            $mail->send();
            error_log("Password reset email sent to " . $recipientEmail);
            return true;
        } catch (Exception $e) {
            error_log("Failed to send password reset email to " . $recipientEmail . ". Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function sendOrderConfirmationEmail($orderData, $customerEmail, $customerName) {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // or PHPMailer::ENCRYPTION_SMTPS
            $mail->Port       = SMTP_PORT;

            //Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($customerEmail, $customerName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Order Confirmation - Mossé Luxe Order #' . $orderData['order_id'];
            $mail->Body    = '
                <html>
                <head>
                    <title>Order Confirmation</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #000; color: #fff; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .order-details { background: #fff; padding: 15px; margin: 15px 0; border: 1px solid #eee; }
                        .total { font-size: 18px; font-weight: bold; margin-top: 15px; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                        .button { display: inline-block; background: #000; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 15px 0; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>' . SMTP_FROM_NAME . '</h1>
                            <p>Order Confirmation</p>
                        </div>
                        <div class="content">
                            <p>Dear ' . htmlspecialchars($customerName) . ',</p>
                            <p>Thank you for your order! We have received your order and are preparing it for shipment.</p>

                            <div class="order-details">
                                <h3>Order Details</h3>
                                <p><strong>Order Number:</strong> ML-' . $orderData['order_id'] . '</p>
                                <p><strong>Order Date:</strong> ' . date('F j, Y \a\t g:i A') . '</p>

                                <h4>Items Ordered:</h4>
                                <ul>
            ';

            foreach ($orderData['items'] as $item) {
                $mail->Body .= '<li>' . htmlspecialchars($item['name']) . ' (x' . $item['quantity'] . ') - R ' . number_format($item['price'] * $item['quantity'], 2) . '</li>';
            }

            $mail->Body .= '
                                </ul>

                                <p><strong>Subtotal:</strong> R ' . number_format($orderData['subtotal'], 2) . '</p>
                                <p><strong>Shipping:</strong> R ' . number_format($orderData['shipping_cost'], 2) . '</p>
                                <div class="total"><strong>Total: R ' . number_format($orderData['total'], 2) . '</strong></div>
                            </div>

                            <div style="background: #e8f5e8; border: 1px solid #c3e6c3; padding: 15px; margin: 15px 0; border-radius: 4px;">
                                <p><strong>What happens next?</strong></p>
                                <p>• You will receive an email confirmation when your order ships</p>
                                <p>• You can track your order status in your account</p>
                                <p>• For any questions, contact us at ' . SMTP_FROM_EMAIL . '</p>
                            </div>

                            <p>We appreciate your business and hope you enjoy your purchase!</p>
                            <p>Best regards,<br>The ' . SMTP_FROM_NAME . ' Team</p>

                            <div style="text-align: center;">
                                <a href="' . SITE_URL . 'my_account.php?view=orders" class="button">View Order Details</a>
                            </div>
                        </div>
                        <div class="footer">
                            <p>This email was sent to ' . htmlspecialchars($customerEmail) . ' in response to your order.</p>
                            <p>If you no longer wish to receive these emails, please contact our support team.</p>
                        </div>
                    </div>
                </body>
                </html>
            ';

            $mail->AltBody = 'Dear ' . $customerName . ', Thank you for your order! Order Number: ML-' . $orderData['order_id'] . '. Total: R ' . number_format($orderData['total'], 2) . '. Best regards, The ' . SMTP_FROM_NAME . ' Team';

            $mail->send();
            error_log("Order confirmation email sent for order #" . $orderData['order_id'] . " to " . $customerEmail);
            return true;
        } catch (Exception $e) {
            error_log("Failed to send order confirmation email for order #" . $orderData['order_id'] . " to " . $customerEmail . ". Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function sendNewOrderNotification($orderData) {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            //Recipients - Send to admin email
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress(SMTP_FROM_EMAIL, 'Mossé Luxe Admin');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'New Order Received - ML-' . $orderData['order_id'];
            $mail->Body    = '
                <html>
                <head>
                    <title>New Order Notification</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #ff6b35; color: #fff; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .order-details { background: #fff; padding: 15px; margin: 15px 0; border: 1px solid #eee; }
                        .customer-info { background: #fff; padding: 15px; margin: 15px 0; border: 1px solid #eee; }
                        .total { font-size: 18px; font-weight: bold; color: #ff6b35; margin-top: 15px; }
                        .button { display: inline-block; background: #000; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 15px 0; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>New Order Received!</h1>
                            <p>Mossé Luxe Order System</p>
                        </div>
                        <div class="content">
                            <p>A new order has been placed on your website.</p>

                            <div class="order-details">
                                <h3>Order Information</h3>
                                <p><strong>Order Number:</strong> ML-' . $orderData['order_id'] . '</p>
                                <p><strong>Order Date:</strong> ' . date('F j, Y \a\t g:i A') . '</p>
                                <p><strong>Status:</strong> ' . $orderData['status'] . '</p>

                                <h4>Items Ordered:</h4>
                                <ul>
            ';

            foreach ($orderData['items'] as $item) {
                $mail->Body .= '<li>' . htmlspecialchars($item['name']) . ' - Quantity: ' . $item['quantity'] . ' - Unit Price: R ' . number_format($item['price'], 2) . ' - Total: R ' . number_format($item['price'] * $item['quantity'], 2) . '</li>';
            }

            $mail->Body .= '
                                </ul>

                                <p><strong>Subtotal:</strong> R ' . number_format($orderData['subtotal'], 2) . '</p>
                                <p><strong>Shipping:</strong> R ' . number_format($orderData['shipping_cost'], 2) . '</p>
                                <div class="total">TOTAL: R ' . number_format($orderData['total'], 2) . '</div>
                            </div>

                            <div class="customer-info">
                                <h3>Customer Information</h3>
                                <p><strong>Name:</strong> ' . htmlspecialchars($orderData['shipping_info']['firstName'] . ' ' . $orderData['shipping_info']['lastName']) . '</p>
                                <p><strong>Email:</strong> ' . htmlspecialchars($orderData['shipping_info']['email']) . '</p>
                                <p><strong>Phone:</strong> ' . htmlspecialchars($orderData['shipping_info']['phone']) . '</p>
                                <p><strong>Address:</strong> ' . htmlspecialchars($orderData['shipping_info']['address']) . ', ' . htmlspecialchars($orderData['shipping_info']['city']) . ', ' . htmlspecialchars($orderData['shipping_info']['zip']) . '</p>
            ';

            if ($orderData['user_id']) {
                $mail->Body .= '<p><strong>Account:</strong> Registered User (ID: ' . $orderData['user_id'] . ')</p>';
            } else {
                $mail->Body .= '<p><strong>Account:</strong> Guest Checkout</p>';
            }

            $mail->Body .= '
                            </div>

                            <div style="text-align: center; background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 4px;">
                                <p><strong>Action Required:</strong> Please process this order in the admin panel.</p>
                            </div>

                            <div style="text-align: center;">
                                <a href="' . SITE_URL . 'admin/orders.php" class="button">View in Admin Panel</a>
                            </div>

                            <p>Best regards,<br>Mossé Luxe Order System</p>
                        </div>
                    </div>
                </body>
                </html>
            ';

            $mail->AltBody = 'New order received! Order ML-' . $orderData['order_id'] . ' - Total: R ' . number_format($orderData['total'], 2) . ' - Customer: ' . $orderData['shipping_info']['firstName'] . ' ' . $orderData['shipping_info']['lastName'] . ' (' . $orderData['shipping_info']['email'] . ')';

            $mail->send();
            error_log("New order notification sent for order #" . $orderData['order_id']);
            return true;
        } catch (Exception $e) {
            error_log("Failed to send new order notification for order #" . $orderData['order_id'] . ". Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function sendWelcomeEmail($userName, $userEmail) {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SMTPS (465) instead of STARTTLS
            $mail->Port       = SMTP_PORT;
            $mail->SMTPDebug = 0; // Set to 2 for debugging if needed

            // Set additional SSL options for better compatibility
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'ciphers' => 'HIGH:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!SRP:!CAMELLIA',
                ),
            );

            //Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($userEmail, $userName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Welcome to Mossé Luxe - Your Fashion Journey Begins!';
            $mail->Body    = '
                <html>
                <head>
                    <title>Welcome to Mossé Luxe</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #000; color: #fff; padding: 30px; text-align: center; }
                        .content { padding: 30px; background: #f9f9f9; }
                        .welcome-box { background: #fff; padding: 25px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                        .button { display: inline-block; background: #000; color: #fff; padding: 15px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-size: 16px; font-weight: bold; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                        .features { background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #000; }
                        .highlight { color: #ff6b35; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>Mossé Luxe</h1>
                            <p style="margin: 10px 0; font-size: 20px;">Welcome to Your Fashion Journey!</p>
                        </div>
                        <div class="content">
                            <div class="welcome-box">
                                <h2>🎉 Welcome ' . htmlspecialchars($userName) . '!</h2>
                                <p>Thank you for joining the Mossé Luxe community! We\'re thrilled to have you as part of our fashion family.</p>

                                <div class="features">
                                    <h3>Your Account Benefits:</h3>
                                    <ul style="padding-left: 20px;">
                                        <li>🚚 <strong>Fast & Free Shipping</strong> on orders over R500</li>
                                        <li>🏷️ <strong>Exclusive Discounts</strong> & early access to sales</li>
                                        <li>📦 <strong>Order Tracking</strong> & easy returns</li>
                                        <li>⭐ <strong>Product Reviews</strong> & personalized recommendations</li>
                                        <li>💝 <strong>Loyalty Rewards</strong> & special offers</li>
                                    </ul>
                                </div>

                                <p>Ready to explore our collection?</p>

                                <div style="text-align: center;">
                                    <a href="' . SITE_URL . 'shop.php" class="button">Start Shopping</a>
                                </div>

                                <p style="margin-top: 25px; color: #666; font-size: 14px;">
                                    💡 <strong>Pro Tip:</strong> Create a wishlist to save your favorite items for later!
                                </p>
                            </div>

                            <div style="background: #e8f5e8; border: 1px solid #c3e6c3; padding: 20px; margin: 20px 0; border-radius: 6px;">
                                <h4 style="margin: 0 0 10px 0; color: #2d652d;">What\'s Next?</h4>
                                <ul style="margin: 0; padding-left: 20px; color: #2d652d;">
                                    <li>Sign in to your account anytime at <a href="' . SITE_URL . 'login.php" style="color: #2d652d; text-decoration: underline;">' . SITE_URL . 'login.php</a></li>
                                    <li>Browse our latest collection</li>
                                    <li>Follow us on social media for fashion inspiration</li>
                                    <li>Subscribe to our newsletter for exclusive updates</li>
                                </ul>
                            </div>

                            <p>We can\'t wait to help you discover your perfect style!</p>

                            <p>Best regards,<br>The Mossé Luxe Team</p>

                            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                                <p style="font-size: 14px; color: #666;">
                                    Questions? Reply to this email or contact us at <a href="mailto:' . SMTP_FROM_EMAIL . '">' . SMTP_FROM_EMAIL . '</a>
                                </p>
                            </div>
                        </div>
                        <div class="footer">
                            <p>This email was sent to ' . htmlspecialchars($userEmail) . ' because you registered for a Mossé Luxe account.</p>
                            <p>© ' . date('Y') . ' Mossé Luxe. All rights reserved.</p>
                            <p>If you no longer wish to receive these emails, you can unsubscribe from your account settings.</p>
                        </div>
                    </div>
                </body>
                </html>
            ';

            $mail->AltBody = 'Welcome ' . $userName . ' to Mossé Luxe! Thank you for joining our fashion community. Visit ' . SITE_URL . ' to start shopping. Your account benefits include free shipping over R500, exclusive discounts, order tracking, and more. Sign in at ' . SITE_URL . 'login.php. Best regards, The Mossé Luxe Team';

            $mail->send();
            error_log("Welcome email sent successfully to " . $userEmail . " for user " . $userName);
            return true;
        } catch (Exception $e) {
            error_log("Failed to send welcome email to " . $userEmail . ". Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function sendMessageReply($recipientEmail, $recipientName, $subject, $replyMessage, $originalMessage) {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = SMTP_PORT;

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'ciphers' => 'HIGH:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!SRP:!CAMELLIA',
                ),
            );

            //Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($recipientEmail, $recipientName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = '
                <html>
                <head>
                    <title>Message Reply from Mossé Luxe</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #000; color: #fff; padding: 30px; text-align: center; }
                        .logo { text-align: center; margin-bottom: 20px; }
                        .content { padding: 30px; background: #f9f9f9; }
                        .reply-box { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                        .original-message { background: #f5f5f5; padding: 15px; margin: 20px 0; border-left: 4px solid #000; font-size: 14px; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>Mossé Luxe</h1>
                            <p>Password Reset Request</p>
                        </div>
                        <div class="content">
                            <div class="reply-box">
                                <p>Hello ' . htmlspecialchars($recipientName) . ',</p>

                                <p>' . nl2br(htmlspecialchars($replyMessage)) . '</p>

                                <p>Best regards,<br>The Mossé Luxe Team</p>
                            </div>

                            <div class="original-message">
                                <h4>Original Message:</h4>
                                <p><strong>Subject:</strong> ' . htmlspecialchars(str_replace('Re: ', '', $subject)) . '</p>
                                <p><strong>Message:</strong></p>
                                <p>' . nl2br(htmlspecialchars($originalMessage)) . '</p>
                            </div>
                        </div>
                        <div class="footer">
                            <p>This email was sent in reply to your message to Mossé Luxe.</p>
                            <p>© ' . date('Y') . ' Mossé Luxe. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ';

            $mail->AltBody = 'Hello ' . $recipientName . ",\n\n" . $replyMessage . "\n\nBest regards,\nThe Mossé Luxe Team\n\n--- Original Message ---\n" . $originalMessage;

            $mail->send();
            error_log("Message reply sent successfully to " . $recipientEmail . " for subject: " . $subject);
            return true;
        } catch (Exception $e) {
            error_log("Failed to send message reply to " . $recipientEmail . ". Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function sendShippingStatusUpdate($orderData, $customerEmail, $customerName, $newStatus) {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            //Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($customerEmail, $customerName);

            // Determine email content based on status
            $subject = '';
            $statusMessage = '';
            $additionalInfo = '';

            switch ($newStatus) {
                case 'Processing':
                    $subject = 'Order Processing Started - Mossé Luxe Order #' . $orderData['order_id'];
                    $statusMessage = 'We have received your payment and started processing your order.';
                    $additionalInfo = 'We will send you another update once your items are packed and ready for shipment.';
                    break;
                case 'Shipped':
                    $subject = 'Order Shipped - Mossé Luxe Order #' . $orderData['order_id'];
                    $statusMessage = 'Great news! Your order has been shipped and is on its way to you.';
                    $additionalInfo = 'You should receive a tracking number via email within the next 24 hours.';
                    break;
                case 'Completed':
                case 'Delivered':
                    $subject = 'Order Delivered - Mossé Luxe Order #' . $orderData['order_id'];
                    $statusMessage = 'Your order has been successfully delivered!';
                    $additionalInfo = 'We hope you love your new items. Thank you for shopping with Mossé Luxe!';
                    break;
                case 'Cancelled':
                    $subject = 'Order Cancelled - Mossé Luxe Order #' . $orderData['order_id'];
                    $statusMessage = 'Your order has been cancelled.';
                    $additionalInfo = 'If you have any questions about this cancellation, please contact our customer service team.';
                    break;
                default:
                    $subject = 'Order Status Update - Mossé Luxe Order #' . $orderData['order_id'];
                    $statusMessage = 'Your order status has been updated to: ' . $newStatus;
                    $additionalInfo = 'If you have any questions, please contact our customer service team.';
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = '
                <html>
                <head>
                    <title>Order Status Update</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #000; color: #fff; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .status-box { background: #e8f5e8; border: 1px solid #c3e6c3; padding: 20px; margin: 20px 0; border-radius: 4px; }
                        .order-details { background: #fff; padding: 15px; margin: 15px 0; border: 1px solid #eee; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                        .button { display: inline-block; background: #000; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 15px 0; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>' . SMTP_FROM_NAME . '</h1>
                            <p>Order Status Update</p>
                        </div>
                        <div class="content">
                            <p>Dear ' . htmlspecialchars($customerName) . ',</p>

                            <div class="status-box">
                                <h3>📦 ' . $statusMessage . '</h3>
                                <p><strong>Order #:</strong> ML-' . $orderData['order_id'] . '</p>
                                <p><strong>Status:</strong> ' . $newStatus . '</p>
                                <p><strong>Update Time:</strong> ' . date('F j, Y \a\t g:i A') . '</p>
                            </div>

                            <div class="order-details">
                                <h4>Order Summary:</h4>
                                <ul>
            ';

            foreach ($orderData['items'] as $item) {
                $mail->Body .= '<li>' . htmlspecialchars($item['name']) . ' (x' . $item['quantity'] . ') - R ' . number_format($item['price'] * $item['quantity'], 2) . '</li>';
            }

            $mail->Body .= '
                                </ul>
                                <p><strong>Total: R ' . number_format($orderData['total'], 2) . '</strong></p>
                            </div>

                            <p>' . $additionalInfo . '</p>

                            <div style="text-align: center;">
                                <a href="' . SITE_URL . 'my_account.php?view=orders" class="button">View Order Details</a>
                            </div>

                            <p>If you have any questions about your order, please don\'t hesitate to contact us at ' . SMTP_FROM_EMAIL . '.</p>
                            <p>Thank you for choosing Mossé Luxe!</p>
                            <p>Best regards,<br>The ' . SMTP_FROM_NAME . ' Team</p>
                        </div>
                        <div class="footer">
                            <p>This email was sent to ' . htmlspecialchars($customerEmail) . ' about order ML-' . $orderData['order_id'] . '.</p>
                            <p>If you no longer wish to receive these emails, please contact our support team.</p>
                        </div>
                    </div>
                </body>
                </html>
            ';

            $mail->AltBody = 'Dear ' . $customerName . ', ' . $statusMessage . ' Order: ML-' . $orderData['order_id'] . ', Status: ' . $newStatus . ', Total: R ' . number_format($orderData['total'], 2) . '. ' . $additionalInfo . ' Best regards, The ' . SMTP_FROM_NAME . ' Team';

            $mail->send();
            error_log("Shipping status update email sent for order #" . $orderData['order_id'] . " - Status: " . $newStatus . " to " . $customerEmail);
            return true;
        } catch (Exception $e) {
            error_log("Failed to send shipping status update for order #" . $orderData['order_id'] . " to " . $customerEmail . ". Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
