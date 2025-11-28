<?php
/**
 * Maintenance Mode Page
 * Rename this file to "maintenance_mode.php" to disable
 * Rename to "maintenance.php" and update .htaccess to enable
 */
http_response_code(503);
header('Retry-After: 3600'); // Retry after 1 hour
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance - Mossé Luxe</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #000 0%, #2d2d2d 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-center;
            padding: 20px;
        }

        .maintenance-container {
            text-align: center;
            max-width: 600px;
        }

        .logo {
            font-size: 3rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -2px;
            margin-bottom: 2rem;
            background: linear-gradient(45deg, #fff, #ccc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .icon {
            font-size: 5rem;
            margin-bottom: 2rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        p {
            font-size: 1.2rem;
            line-height: 1.6;
            color: #ccc;
            margin-bottom: 2rem;
        }

        .info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .info p {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .social-links {
            margin-top: 2rem;
        }

        .social-links a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-size: 1.5rem;
            transition: opacity 0.3s;
        }

        .social-links a:hover {
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .logo { font-size: 2rem; }
            h1 { font-size: 2rem; }
            p { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="logo">Mossé Luxe</div>
        
        <div class="icon">🛠️</div>
        
        <h1>We'll Be Right Back</h1>
        
        <p>
            We're currently performing scheduled maintenance to improve your shopping experience.
            Our site will be back online shortly.
        </p>

        <div class="info">
            <p><strong>Expected Downtime:</strong> 1-2 hours</p>
            <p><strong>Contact:</strong> info@mosseluxe.co.za</p>
            <p><strong>Phone:</strong> 067 616 0928</p>
        </div>

        <p style="margin-top: 2rem; font-size: 0.9rem;">
            Thank you for your patience!
        </p>
    </div>
</body>
</html>
