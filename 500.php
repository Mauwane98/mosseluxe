<?php
/**
 * 500 Error Page - User-friendly server error display
 */
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - <?php echo get_setting('site_title', 'Mossé Luxe'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            color: white;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 2rem;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 900;
            margin: 0;
            background: linear-gradient(45deg, #ff4757, #ff3838);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .error-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 1rem 0 2rem 0;
        }

        .error-message {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .error-actions {
            margin-top: 3rem;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: white;
            color: black;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 900;
            margin-top: 3rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .error-container {
                padding: 1rem;
            }

            .error-code {
                font-size: 5rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            .btn {
                display: block;
                margin: 0.5rem auto;
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">500</h1>
        <h2 class="error-title">Server Error</h2>
        <p class="error-message">
            We're experiencing some technical difficulties. Our team has been notified and is working to resolve this issue.
        </p>

        <div class="error-actions">
            <a href="<?php echo SITE_URL; ?>" class="btn">Return Home</a>
            <a href="javascript:history.back()" class="btn">Go Back</a>
        </div>

        <div class="brand">Mossé Luxe</div>
    </div>

    <script>
        // Auto-reload attempt in case it's a temporary issue
        setTimeout(function() {
            if (confirm('Would you like to try refreshing the page?')) {
                window.location.reload();
            }
        }, 30000); // 30 seconds
    </script>
</body>
</html>
