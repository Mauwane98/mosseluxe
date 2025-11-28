<?php
/**
 * Email Helper - Unified PHPMailer Configuration
 * Provides consistent email configuration across all services
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    
    /**
     * Get a configured PHPMailer instance
     * 
     * @param bool $debug Enable SMTP debug output
     * @return PHPMailer
     * @throws Exception
     */
    public static function getMailer($debug = false) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'mail.mosseluxe.co.za';
            $mail->SMTPAuth = true;
            $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : 'info@mosseluxe.co.za';
            $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
            
            // Determine encryption based on port
            $port = defined('SMTP_PORT') ? SMTP_PORT : 465;
            $mail->Port = $port;
            
            if ($port == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
            } elseif ($port == 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
            } else {
                // Use configured value or default to SSL
                $secure = defined('SMTP_SECURE') ? SMTP_SECURE : 'ssl';
                $mail->SMTPSecure = ($secure === 'tls') ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            }
            
            // SSL/TLS options for compatibility
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'ciphers' => 'HIGH:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!SRP:!CAMELLIA'
                )
            );
            
            // Debug mode
            if ($debug || (defined('EMAIL_DEBUG') && EMAIL_DEBUG)) {
                $mail->SMTPDebug = 2; // Verbose debug output
            } else {
                $mail->SMTPDebug = 0; // No debug output
            }
            
            // Default sender
            $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'info@mosseluxe.co.za';
            $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Mossé Luxe';
            $mail->setFrom($fromEmail, $fromName);
            
            // Email settings
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            
            // Check if password is set
            if (empty($mail->Password)) {
                error_log("WARNING: SMTP_PASSWORD is not set. Emails will likely fail. Please set SMTP_PASSWORD in .env file.");
            }
            
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
            throw $e;
        }
        
        return $mail;
    }
    
    /**
     * Send a simple email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body HTML body
     * @param string $altBody Plain text alternative
     * @param string $toName Recipient name (optional)
     * @return bool Success status
     */
    public static function send($to, $subject, $body, $altBody = '', $toName = '') {
        try {
            $mail = self::getMailer();
            
            if ($toName) {
                $mail->addAddress($to, $toName);
            } else {
                $mail->addAddress($to);
            }
            
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $altBody ?: strip_tags($body);
            
            $result = $mail->send();
            
            if ($result) {
                error_log("Email sent successfully to: $to - Subject: $subject");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Failed to send email to $to. Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate email configuration
     * 
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public static function validateConfig() {
        $errors = [];
        
        if (!defined('SMTP_HOST') || empty(SMTP_HOST)) {
            $errors[] = 'SMTP_HOST is not configured';
        }
        
        if (!defined('SMTP_USERNAME') || empty(SMTP_USERNAME)) {
            $errors[] = 'SMTP_USERNAME is not configured';
        }
        
        if (!defined('SMTP_PASSWORD') || empty(SMTP_PASSWORD)) {
            $errors[] = 'SMTP_PASSWORD is not configured - emails will fail';
        }
        
        if (!defined('SMTP_FROM_EMAIL') || empty(SMTP_FROM_EMAIL)) {
            $errors[] = 'SMTP_FROM_EMAIL is not configured';
        }
        
        if (!defined('SMTP_PORT') || empty(SMTP_PORT)) {
            $errors[] = 'SMTP_PORT is not configured';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get email configuration status
     * 
     * @return array Configuration details
     */
    public static function getConfig() {
        return [
            'host' => defined('SMTP_HOST') ? SMTP_HOST : 'NOT SET',
            'port' => defined('SMTP_PORT') ? SMTP_PORT : 'NOT SET',
            'secure' => defined('SMTP_SECURE') ? SMTP_SECURE : 'NOT SET',
            'username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NOT SET',
            'password_set' => defined('SMTP_PASSWORD') && !empty(SMTP_PASSWORD),
            'from_email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'NOT SET',
            'from_name' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'NOT SET',
        ];
    }
}
// No closing PHP tag - prevents accidental whitespace output