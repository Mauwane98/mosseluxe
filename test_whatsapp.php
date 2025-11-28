<?php
require_once 'includes/bootstrap.php';

$whatsapp_number = '+27676162809';
$message = 'Test WhatsApp message';
?> <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test WhatsApp</title>
</head>
<body>
    <h1>WhatsApp Test</h1>
    <p>Number: <?php echo $whatsapp_number; ?></p>
    <p>Message: <?php echo $message; ?></p>
    <button onclick="testWhatsApp()">Test WhatsApp Link</button>

    <script>
        function testWhatsApp() {
            const message = "<?php echo addslashes($message); ?>";
            const cleanedNumber = "<?php echo ltrim($whatsapp_number, '+'); ?>";
            const whatsappUrl = `https://wa.me/${cleanedNumber}?text=${encodeURIComponent(message)}`;

            console.log('WhatsApp URL:', whatsappUrl);
            window.open(whatsappUrl, '_blank');
        }
    </script>
</body>
</html>
