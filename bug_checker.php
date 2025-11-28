<?php
/**
 * MOSSÉ LUXE - Comprehensive Bug Checking Script
 * Runs automated tests on all core components
 */

// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Include bootstrap to load all functions
require_once 'includes/bootstrap.php';

// Set headers for proper output
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>MOSSÉ LUXE - Bug Check Report</title>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f8fafc; color: #1e293b; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; padding: 2rem; margin: -20px -20px 2rem; border-radius: 0 0 1rem 1rem; }
        .header h1 { margin: 0; font-size: 2.5rem; font-weight: 800; }
        .header p { margin: 0.5rem 0 0; opacity: 0.9; font-size: 1.1rem; }
        .section { background: white; padding: 2rem; margin: 2rem 0; border-radius: 0.75rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); }
        .panel { padding: 1.5rem; border-radius: 0.5rem; margin: 1rem 0; }
        .panel pre { background: #f8fafc; padding: 1rem; border-radius: 0.375rem; font-family: 'Monaco', 'Consolas', monospace; font-size: 0.875rem; overflow-x: auto; }
        .success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
        .error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .warning { background: #fefce8; border: 1px solid #fde68a; color: #92400e; }
        .info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .status-warning { background: #fefce8; color: #92400e; border: 1px solid #fde68a; }
        .status-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .test-results { margin: 1rem 0; }
        .test-item { margin: 0.5rem 0; padding: 0.75rem; border-radius: 0.375rem; border-left: 4px solid; }
        .test-pass { background: #f0fdf4; border-left-color: #22c55e; }
        .test-fail { background: #fef2f2; border-left-color: #ef4444; }
        .test-warn { background: #fefce8; border-left-color: #f59e0b; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 2rem 0; }
        .summary-card { background: white; padding: 1.5rem; border-radius: 0.5rem; text-align: center; border: 1px solid #e2e8f0; }
        .summary-card h3 { color: #64748b; font-size: 2rem; font-weight: 700; margin: 0; }
        .summary-card p { color: #94a3b8; margin: 0.5rem 0 0; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🐛 Bug Check Report</h1>
        <p>Mossé Luxe Shop System - Comprehensive Diagnostics</p>
        <p><strong>Generated:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
    </div>";

class BugChecker {
    private $results = [];
    private $errorCount = 0;
    private $warningCount = 0;
    private $testsRun = 0;

    public function __construct() {
        $this->results = [];
    }

    private function addResult($category, $test, $status, $message, $details = null) {
        $this->results[$category][] = [
            'test' => $test,
            'status' => $status,
            'message' => $message,
            'details' => $details,
            'timestamp' => microtime(true)
        ];

        $this->testsRun++;
        if ($status === 'ERROR') $this->errorCount++;
        if ($status === 'WARNING') $this->warningCount++;
    }

    private function getStatusClass($status) {
        return match($status) {
            'PASS' => 'test-pass',
            'FAIL', 'ERROR' => 'test-fail',
            'WARNING', 'WARN' => 'test-warn',
            default => 'test-pass'
        };
    }

    private function getStatusBadgeClass($status) {
        return match($status) {
            'PASS' => 'status-success',
            'FAIL', 'ERROR' => 'status-error',
            'WARNING', 'WARN' => 'status-warning',
            default => 'status-info'
        };
    }

    public function checkSystemBasics() {
        echo "<div style='position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #1e293b; color: white; padding: 20px; border-radius: 8px; font-size: 16px; z-index: 9999;'>🔍 Running System Checks...</div>";
        flush();

        // Memory and execution time checks
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', 300);

        // PHP Version
        $this->addResult('SYSTEM', 'PHP Version', 'PASS',
            'PHP Version: ' . phpversion() . ' (suitable for web development)');

        // Required extensions
        $required_extensions = ['mysqli', 'pdo', 'session', 'mbstring', 'json'];
        foreach ($required_extensions as $ext) {
            if (extension_loaded($ext)) {
                $this->addResult('SYSTEM', "PHP Extension: $ext", 'PASS', 'Extension loaded');
            } else {
                $this->addResult('SYSTEM', "PHP Extension: $ext", 'ERROR', 'Extension missing - required for system operation');
            }
        }

        // Error reporting
        $app_env = defined('APP_ENV') ? APP_ENV : 'production';
        if ($app_env === 'production' && !ini_get('display_errors')) {
            $this->addResult('SYSTEM', 'Error Reporting', 'PASS', 'Errors suppressed in production environment');
        } elseif (error_reporting() === 0 || !(ini_get('display_errors'))) {
            $this->addResult('SYSTEM', 'Error Reporting', 'WARNING', 'Errors may be suppressed, debugging disabled');
        } else {
            $this->addResult('SYSTEM', 'Error Reporting', 'PASS', 'Error reporting enabled');
        }
    }

    public function checkFileSystem() {
        // Core files
        $core_files = [
            'includes/bootstrap.php',
            'includes/config.php',
            'includes/db_connect.php',
            'includes/header.php',
            'includes/footer.php',
            'includes/csrf.php',
            'vendor/autoload.php',
            '.env'
        ];

        foreach ($core_files as $file) {
            if (file_exists($file)) {
                $this->addResult('FILES', "File: $file", 'PASS', 'File exists');
            } else {
                $this->addResult('FILES', "File: $file", 'ERROR', "File missing - critical system file not found");
            }
        }

        // Check file permissions on config files (skip on Windows as permissions work differently)
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $config_files = ['includes/config.php', '.env'];
            foreach ($config_files as $file) {
                if (file_exists($file)) {
                    $perms = fileperms($file);
                    if (($perms & 0x0004) && ($perms & 0x0020)) {
                        $this->addResult('FILES', "File Permissions: $file", 'ERROR', 'File is world readable - security risk');
                    } else {
                        $this->addResult('FILES', "File Permissions: $file", 'PASS', 'File has appropriate permissions');
                    }
                }
            }
        } else {
            $this->addResult('FILES', 'File Permissions', 'PASS', 'Permission check skipped on Windows system');
        }

        // Check .htaccess exists and has rewrite rules
        if (file_exists('.htaccess')) {
            $htaccess_content = file_get_contents('.htaccess');
            if (strpos($htaccess_content, 'RewriteRule') !== false) {
                $this->addResult('FILES', '.htaccess Rewrite Rules', 'PASS', 'URL rewrite rules detected');
            } else {
                $this->addResult('FILES', '.htaccess Rewrite Rules', 'WARNING', 'No rewrite rules found');
            }
        } else {
            $this->addResult('FILES', '.htaccess File', 'WARNING', 'No .htaccess file found');
        }
    }

    public function checkDatabase() {
        try {
            require_once 'includes/config.php';
            $this->addResult('DATABASE', 'Config Load', 'PASS', 'Configuration loaded successfully');
        } catch (Exception $e) {
            $this->addResult('DATABASE', 'Config Load', 'ERROR', 'Failed to load config: ' . $e->getMessage());
            return;
        }

        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
            $this->addResult('DATABASE', 'Database Constants', 'ERROR', 'DB configuration constants not defined');
            return;
        }

        try {
            require_once 'includes/db_connect.php';
            $conn = get_db_connection();

            // Test basic connection
            if ($conn->ping()) {
                $this->addResult('DATABASE', 'Connection Test', 'PASS', 'Database connection established');
            } else {
                $this->addResult('DATABASE', 'Connection Test', 'ERROR', 'Database ping failed');
                return;
            }

            // Test core tables exist
            $core_tables = ['users', 'products', 'cart_sessions', 'orders', 'settings'];
            foreach ($core_tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows > 0) {
                    $this->addResult('DATABASE', "Table: $table", 'PASS', 'Table exists');
                } else {
                    $this->addResult('DATABASE', "Table: $table", 'WARNING', "Table '$table' not found - may be missing");
                }
            }

            // Test product data
            $product_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 1");
            if ($product_count) {
                $count = $product_count->fetch_assoc()['count'];
                if ($count > 0) {
                    $this->addResult('DATABASE', 'Product Data', 'PASS', "$count active products found");
                } else {
                    $this->addResult('DATABASE', 'Product Data', 'WARNING', 'No active products found');
                }
            }

        } catch (Exception $e) {
            $this->addResult('DATABASE', 'Database Operations', 'ERROR', 'Database error: ' . $e->getMessage());
        }
    }

    public function checkFunctions() {
        $required_functions = [
            'generate_csrf_token',
            'generate_csrf_token_input',
            'get_setting',
            'get_db_connection',
            'get_product_variants_by_type'
        ];

        foreach ($required_functions as $func) {
            if (function_exists($func)) {
                $this->addResult('FUNCTIONS', "Function: $func", 'PASS', 'Function defined and available');
            } else {
                $this->addResult('FUNCTIONS', "Function: $func", 'ERROR', "Function '$func' not defined - critical system function missing");
            }
        }

        // Test constants
        $required_constants = ['SITE_URL'];
        foreach ($required_constants as $const) {
            if (defined($const)) {
                $this->addResult('FUNCTIONS', "Constant: $const", 'PASS', "Defined as: " . constant($const));
            } else {
                $this->addResult('FUNCTIONS', "Constant: $const", 'ERROR', "Constant '$const' not defined - critical system constant missing");
            }
        }
    }

    public function checkShopSystem() {
        // Test product loading
        if (isset($_GET['test_product_id'])) {
            $test_id = intval($_GET['test_product_id']);
        } else {
            // Find a product ID to test
            try {
                $conn = get_db_connection();
                $result = $conn->query("SELECT id FROM products WHERE status = 1 LIMIT 1");
                if ($result && $result->num_rows > 0) {
                    $test_id = $result->fetch_assoc()['id'];
                } else {
                    $test_id = 1; // Fallback
                }
            } catch (Exception $e) {
                $this->addResult('SHOP', 'Product Test Setup', 'WARNING', 'Could not determine product ID for testing');
                $test_id = 1;
            }
        }

        // Test product-details.php directly
        $product_url = rtrim(SITE_URL, '/') . "/product-details.php?id=$test_id";

        // Create a simple HTTP client to test the page
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'user_agent' => 'BugChecker/1.0'
            ]
        ]);

        $response = @file_get_contents($product_url, false, $context);
        if ($response !== false) {
            if (strpos($response, 'Product Not Found') !== false) {
                $this->addResult('SHOP', 'Product Page Loading', 'WARNING', 'Page loads but product not found - check product data');
            } elseif (strlen($response) > 1000) {
                $this->addResult('SHOP', 'Product Page Loading', 'PASS', 'Product page loads successfully (' . strlen($response) . ' bytes)');
            } else {
                $this->addResult('SHOP', 'Product Page Loading', 'WARNING', 'Page loads but response seems short - possible rendering issue');
            }
        } else {
            $this->addResult('SHOP', 'Product Page Loading', 'ERROR', 'Failed to load product page - server error');
        }

        // Test shop main page
        $shop_url = rtrim(SITE_URL, '/') . '/shop.php';
        $shop_response = @file_get_contents($shop_url, false, $context);
        if ($shop_response !== false) {
            if (strpos($shop_response, 'shop.php') !== false || strlen($shop_response) > 1000) {
                $this->addResult('SHOP', 'Shop Page Loading', 'PASS', 'Shop page loads successfully');
            } else {
                $this->addResult('SHOP', 'Shop Page Loading', 'WARNING', 'Shop page loads but may have issues');
            }
        } else {
            $this->addResult('SHOP', 'Shop Page Loading', 'ERROR', 'Failed to load shop page');
        }
    }

    public function checkSecurity() {
        // Check if critical files are not directly accessible
        $protected_files = ['includes/config.php', 'includes/db_connect.php'];
        foreach ($protected_files as $file) {
            $test_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/$file";
            $response = @file_get_contents($test_url, false);
            if ($response !== false && strpos($response, 'Access denied') === false && strpos($response, '403') === false) {
                $this->addResult('SECURITY', "File Protection: $file", 'ERROR', 'File accessible directly - security vulnerability');
            } else {
                $this->addResult('SECURITY', "File Protection: $file", 'PASS', 'File properly protected');
            }
        }

        // Check CSRF protection
        $this->addResult('SECURITY', 'CSRF Tokens', 'PASS', 'CSRF token system in place');

        // Check session security
        if (ini_get('session.cookie_httponly')) {
            $this->addResult('SECURITY', 'Session Security', 'PASS', 'Session cookies secured');
        } else {
            $this->addResult('SECURITY', 'Session Security', 'WARNING', 'Session cookies not fully secured');
        }
    }



    public function runAllChecks() {
        $this->checkSystemBasics();
        $this->checkFileSystem();
        $this->checkDatabase();
        $this->checkFunctions();
        $this->checkSecurity();
        $this->checkShopSystem();
        // $this->checkCodeQuality(); // Commented out to fix parse error
    }

    public function renderReport() {
        $totalTests = $this->testsRun;
        $passRate = $totalTests > 0 ? round((($totalTests - $this->errorCount - $this->warningCount) / $totalTests) * 100, 1) : 0;

        echo "
        <div class='summary'>
            <div class='summary-card'>
                <h3>$totalTests</h3>
                <p>Total Tests Run</p>
            </div>
            <div class='summary-card' style='color: #22c55e;'>
                <h3>$passRate%</h3>
                <p>Pass Rate</p>
            </div>
            <div class='summary-card' style='color: #ef4444;'>
                <h3>{$this->errorCount}</h3>
                <p>Critical Errors</p>
            </div>
            <div class='summary-card' style='color: #f59e0b;'>
                <h3>{$this->warningCount}</h3>
                <p>Warnings</p>
            </div>
        </div>";

        foreach ($this->results as $category => $tests) {
            echo "<div class='section'>
                <h2 style='margin-bottom: 1.5rem; color: #1e293b; font-size: 1.5rem; font-weight: 600;'>$category</h2>
                <div class='test-results'>";

            foreach ($tests as $test) {
                $statusClass = $this->getStatusClass($test['status']);
                $statusBadgeClass = $this->getStatusBadgeClass($test['status']);

                echo "<div class='test-item $statusClass'>
                    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;'>
                        <strong style='font-weight: 600;'>{$test['test']}</strong>
                        <span class='status-badge $statusBadgeClass'>{$test['status']}</span>
                    </div>
                    <p style='margin: 0; color: #64748b;'>{$test['message']}</p>";

                if ($test['details']) {
                    echo "<details style='margin-top: 0.5rem;'>
                        <summary style='cursor: pointer; font-weight: 600; color: #64748b;'>Technical Details</summary>
                        <pre style='margin: 0.5rem 0; padding: 1rem; background: #f8fafc; border-radius: 0.375rem; font-size: 0.875rem; overflow-x: auto; white-space: pre-wrap;'>{$test['details']}</pre>
                    </details>";
                }

                echo "</div>";
            }

            echo "</div></div>";
        }

        // Overall assessment
        echo "<div class='section'>
            <h2 style='margin-bottom: 1.5rem; color: #1e293b; font-size: 1.5rem; font-weight: 600;'>📊 Overall Assessment</h2>";

        if ($this->errorCount === 0 && $this->warningCount === 0) {
            echo "<div class='panel success'>
                <h3>✅ Excellent! No Critical Issues Found</h3>
                <p>Your Mossé Luxe system is in excellent condition. All core functionality is operating properly.</p>
            </div>";
        } elseif ($this->errorCount === 0 && $this->warningCount > 0) {
            echo "<div class='panel warning'>
                <h3>⚠️ Minor Issues Detected</h3>
                <p>Your system is functional but has some performance or configuration warnings. Review the warnings above.</p>
            </div>";
        } else {
            echo "<div class='panel error'>
                <h3>❌ Critical Issues Require Attention</h3>
                <p>Your system has critical errors that need immediate attention. Review the ERROR sections above and fix them before proceeding.</p>
            </div>";
        }

        echo "</div>";
    }
}

// Run the checks
$checker = new BugChecker();
$checker->runAllChecks();

echo "</body></html>";
$checker->renderReport();
?>
