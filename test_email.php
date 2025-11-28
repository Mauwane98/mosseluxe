<?php
/**
 * Email Testing Script
 * Use this to test and debug email configuration
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error display for testing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Email Configuration Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:4px;}</style>";

// Display current configuration
echo "<h2>Current SMTP Configuration:</h2>";
echo "<pre>";
echo "SMTP_HOST: " . (defined('SMTP_HOST') ? SMTP_HOST : 'NOT DEFINED') . "\n";
echo "SMTP_PORT: " . (defined('SMTP_PORT') ? SMTP_PORT : 'NOT DEFINED') . "\n";
echo "SMTP_SECURE: " . (defined('SMTP_SECURE') ? SMTP_SECURE : 'NOT DEFINED') . "\n";
echo "SMTP_USERNAME: " . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NOT DEFINED') . "\n";
echo "SMTP_PASSWORD: " . (defined('SMTP_PASSWORD') && !empty(SMTP_PASSWORD) ? '***SET***' : 'NOT SET OR EMPTY') . "\n";
echo "SMTP_FROM_EMAIL: " . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'NOT DEFINED') . "\n";
echo "SMTP_FROM_NAME: " . (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'NOT DEFINED') . "\n";
echo "</pre>";

// Check if password is set
if (!defined('SMTP_PASSWORD') || empty(SMTP_PASSWORD)) {
    echo "<p class='error'><strong>ERROR:</strong> SMTP_PASSWORD is not set! Emails will fail.</p>";
    echo "<p class='info'>To fix this:</p>";
    echo "<ol>";
    echo "<li>Create a <code>.env</code> file in the root directory</li>";
    echo "<li>Add: <code>SMTP_PASSWORD=your_actual_password</code></li>";
    echo "<li>Or update <code>includes/config.php</code> line 81 with your password</li>";
    echo "</ol>";
}

// Test email sending if requested
if (isset($_GET['send'])) {
    $test_email = isset($_GET['email']) ? filter_var($_GET['email'], FILTER_VALIDATE_EMAIL) : '';
    
    if (!$test_email) {
        echo "<p class='error'>Please provide a valid email address in the URL: ?send=1&email=your@email.com</p>";
    } else {
        echo "<h2>Sending Test Email to: $test_email</h2>";
        
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = 2; // Enable verbose debug output
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            
            // Try to determine the best encryption method
            if (SMTP_PORT == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif (SMTP_PORT == 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = SMTP_SECURE;
            }
            
            $mail->Port = SMTP_PORT;
            
            // SSL options for compatibility
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($test_email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Test Email from Mossé Luxe';
            $mail->Body = '
                <html>
                <body style="font-family: Arial, sans-serif; padding: 20px;">
                    <h2>Email Test Successful!</h2>
                    <p>This is a test email from your Mossé Luxe e-commerce platform.</p>
                    <p>If you received this email, your SMTP configuration is working correctly.</p>
                    <p><strong>Configuration Details:</strong></p>
                    <ul>
                        <li>SMTP Host: ' . SMTP_HOST . '</li>
                        <li>SMTP Port: ' . SMTP_PORT . '</li>
                        <li>Encryption: ' . $mail->SMTPSecure . '</li>
                        <li>From: ' . SMTP_FROM_EMAIL . '</li>
                    </ul>
                    <p>Best regards,<br>Mossé Luxe Team</p>
                </body>
                </html>
            ';
            $mail->AltBody = 'Email Test Successful! Your SMTP configuration is working correctly.';
            
            echo "<pre style='background:#000;color:#0f0;padding:10px;'>";
            $mail->send();
            echo "</pre>";
            echo "<p class='success'><strong>SUCCESS!</strong> Test email sent successfully to $test_email</p>";
            
        } catch (Exception $e) {
            echo "<pre style='background:#000;color:#f00;padding:10px;'>";
            echo "Error: " . $mail->ErrorInfo;
            echo "</pre>";
            echo "<p class='error'><strong>FAILED!</strong> Could not send email. Error: {$mail->ErrorInfo}</p>";
        }
    }
} else {
    echo "<h2>Send Test Email</h2>";
    echo "<form method='get'>";
    echo "<input type='hidden' name='send' value='1'>";
    echo "<label>Email Address: <input type='email' name='email' required placeholder='your@email.com' style='padding:5px;width:300px;'></label>";
    echo "<button type='submit' style='padding:5px 15px;margin-left:10px;'>Send Test Email</button>";
    echo "</form>";
}

echo "<hr>";
echo "<h2>Common Issues & Solutions:</h2>";
echo "<ul>";
echo "<li><strong>Authentication failed:</strong> Check SMTP username and password</li>";
echo "<li><strong>Connection timeout:</strong> Check SMTP host and port, ensure firewall allows outbound SMTP</li>";
echo "<li><strong>SSL/TLS errors:</strong> Try different encryption methods (SMTPS for port 465, STARTTLS for port 587)</li>";
echo "<li><strong>Empty password:</strong> Set SMTP_PASSWORD in .env file or config.php</li>";
echo "</ul>";

echo "<h2>Recommended Configuration:</h2>";
echo "<pre>";
echo "For Gmail:\n";
echo "  SMTP_HOST=smtp.gmail.com\n";
echo "  SMTP_PORT=587\n";
echo "  SMTP_SECURE=tls\n";
echo "  SMTP_USERNAME=your-email@gmail.com\n";
echo "  SMTP_PASSWORD=your-app-password\n\n";

echo "For cPanel/Shared Hosting:\n";
echo "  SMTP_HOST=mail.yourdomain.com\n";
echo "  SMTP_PORT=465\n";
echo "  SMTP_SECURE=ssl\n";
echo "  SMTP_USERNAME=noreply@yourdomain.com\n";
echo "  SMTP_PASSWORD=your-email-password\n";
echo "</pre>";
?>
