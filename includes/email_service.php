<?php
/**
 * Email Service
 * Centralized email sending with templates
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    
    private static function getMailer() {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'mail.mosseluxe.co.za';
            $mail->SMTPAuth = true;
            $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : 'info@mosseluxe.co.za';
            $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
            $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 465;
            
            // Default sender
            $mail->setFrom(
                defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'info@mosseluxe.co.za',
                defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Mossé Luxe'
            );
            
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
            throw $e;
        }
        
        return $mail;
    }
    
    /**
     * Send order confirmation email
     */
    public static function sendOrderConfirmation($order_id, $customer_email, $customer_name, $order_data) {
        try {
            $mail = self::getMailer();
            
            $mail->addAddress($customer_email, $customer_name);
            $mail->Subject = "Order Confirmation #$order_id - Mossé Luxe";
            
            // Build email body
            $body = self::getOrderConfirmationTemplate($order_id, $customer_name, $order_data);
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send order confirmation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send contact form email
     */
    public static function sendContactForm($name, $email, $subject, $message) {
        try {
            $mail = self::getMailer();
            
            $mail->addAddress(defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'info@mosseluxe.co.za');
            $mail->addReplyTo($email, $name);
            $mail->Subject = "Contact Form: $subject";
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>New Contact Form Submission</h2>
                <p><strong>From:</strong> $name ($email)</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong></p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            </body>
            </html>
            ";
            
            $mail->Body = $body;
            $mail->AltBody = "From: $name ($email)\nSubject: $subject\n\nMessage:\n$message";
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send contact form: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send newsletter confirmation
     */
    public static function sendNewsletterConfirmation($email) {
        try {
            $mail = self::getMailer();
            
            $mail->addAddress($email);
            $mail->Subject = "Welcome to Mossé Luxe Newsletter";
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: #000; color: #fff; padding: 20px; text-align: center;'>
                    <h1 style='margin: 0;'>MOSSÉ LUXE</h1>
                </div>
                <div style='padding: 30px;'>
                    <h2>Welcome to Our Newsletter!</h2>
                    <p>Thank you for subscribing to Mossé Luxe newsletter.</p>
                    <p>You'll be the first to know about:</p>
                    <ul>
                        <li>New product launches</li>
                        <li>Exclusive offers and discounts</li>
                        <li>Style tips and trends</li>
                        <li>Special events</li>
                    </ul>
                    <p style='margin-top: 30px;'>
                        <a href='" . SITE_URL . "shop.php' style='background: #000; color: #fff; padding: 12px 30px; text-decoration: none; display: inline-block;'>Shop Now</a>
                    </p>
                </div>
                <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666;'>
                    <p>Mossé Luxe | Pretoria, South Africa</p>
                    <p><a href='" . SITE_URL . "unsubscribe?email=$email' style='color: #666;'>Unsubscribe</a></p>
                </div>
            </body>
            </html>
            ";
            
            $mail->Body = $body;
            $mail->AltBody = "Welcome to Mossé Luxe Newsletter! You'll receive updates about new products, exclusive offers, and more.";
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send newsletter confirmation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Order confirmation email template
     */
    private static function getOrderConfirmationTemplate($order_id, $customer_name, $order_data) {
        $items_html = '';
        foreach ($order_data['items'] as $item) {
            $items_html .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>R" . number_format($item['price'], 2) . "</td>
            </tr>
            ";
        }
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #000; color: #fff; padding: 20px; text-align: center;'>
                <h1 style='margin: 0;'>MOSSÉ LUXE</h1>
            </div>
            <div style='padding: 30px;'>
                <h2>Order Confirmation</h2>
                <p>Hi $customer_name,</p>
                <p>Thank you for your order! We're getting it ready for shipment.</p>
                
                <div style='background: #f5f5f5; padding: 15px; margin: 20px 0;'>
                    <strong>Order #$order_id</strong>
                </div>
                
                <table style='width: 100%; border-collapse: collapse;'>
                    <thead>
                        <tr style='background: #f5f5f5;'>
                            <th style='padding: 10px; text-align: left;'>Item</th>
                            <th style='padding: 10px; text-align: center;'>Qty</th>
                            <th style='padding: 10px; text-align: right;'>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        $items_html
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan='2' style='padding: 10px; text-align: right;'><strong>Subtotal:</strong></td>
                            <td style='padding: 10px; text-align: right;'>R" . number_format($order_data['subtotal'], 2) . "</td>
                        </tr>
                        <tr>
                            <td colspan='2' style='padding: 10px; text-align: right;'><strong>Shipping:</strong></td>
                            <td style='padding: 10px; text-align: right;'>R" . number_format($order_data['shipping'], 2) . "</td>
                        </tr>
                        <tr>
                            <td colspan='2' style='padding: 10px; text-align: right;'><strong>Total:</strong></td>
                            <td style='padding: 10px; text-align: right;'><strong>R" . number_format($order_data['total'], 2) . "</strong></td>
                        </tr>
                    </tfoot>
                </table>
                
                <p style='margin-top: 30px;'>We'll send you another email when your order ships.</p>
                
                <p style='margin-top: 30px;'>
                    <a href='" . SITE_URL . "my-account.php' style='background: #000; color: #fff; padding: 12px 30px; text-decoration: none; display: inline-block;'>View Order Status</a>
                </p>
            </div>
            <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666;'>
                <p>Questions? Contact us at " . CONTACT_EMAIL . " or " . CONTACT_PHONE . "</p>
                <p>Mossé Luxe | Pretoria, South Africa</p>
            </div>
        </body>
        </html>
        ";
    }
}
// No closing PHP tag - prevents accidental whitespace output