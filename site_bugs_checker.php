<?php
/**
 * MOSSÉ LUXE - Code Quality and Security Checker
 * Scans all PHP files for syntax errors, security vulnerabilities, and code quality issues
 */

ini_set('max_execution_time', 600); // Increase to 10 minutes
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Set headers for proper output
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>MOSSÉ LUXE - Code Quality Checker</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .results { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .error { color: red; }
        .warning { color: orange; }
        .success { color: green; }
        .info { color: blue; }
        pre { background: #f8f8f8; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
        .summary { background: #ecf0f1; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🐛 Code Quality & Security Checker</h1>
        <p>Mossé Luxe - Comprehensive Code Analysis</p>
    </div>";

class CodeQualityChecker {
    private $baseDir = __DIR__;
    private $results = [];

    public function __construct() {
        $this->results = [
            'syntax' => [],
            'security' => [],
            'quality' => []
        ];
    }

    private function getAllPhpFiles($dir) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = $file->getRealPath();
                // Skip vendor, node_modules, .git, and test directories to speed up scan
                if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) === false &&
                    strpos($path, DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR) === false &&
                    strpos($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR) === false &&
                    strpos($path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) === false &&
                    strpos($path, DIRECTORY_SEPARATOR . '_private_scripts' . DIRECTORY_SEPARATOR) === false &&
                    !preg_match('/^.*\/test.*\.php$/', $path)) { // Skip test files
                    $files[] = $path;
                }
            }
        }

        return $files;
    }

    public function checkSyntax() {
        echo "<div class='results'><h2>Syntax Check</h2>";
        $files = $this->getAllPhpFiles($this->baseDir);
        $total = count($files);
        echo "<p>Checking $total PHP files for syntax errors...</p>";

        $errors = 0;
        foreach ($files as $file) {
            exec('php -l ' . escapeshellarg($file), $output_lines, $return_code);
            $output = implode("\n", $output_lines);
            if ($return_code !== 0) {
                $errors++;
                $this->results['syntax'][] = [
                    'file' => str_replace($this->baseDir . DIRECTORY_SEPARATOR, '', $file),
                    'error' => trim($output)
                ];
                echo "<div class='error'><strong>" . htmlspecialchars(str_replace($this->baseDir . DIRECTORY_SEPARATOR, '', $file)) . "</strong><br>";
                echo "<pre>" . htmlspecialchars($output) . "</pre></div>";
            }
        }

        if ($errors == 0) {
            echo "<div class='success'>✅ All files passed syntax check</div>";
        } else {
            echo "<div class='error'>❌ Found $errors syntax errors</div>";
        }
        echo "</div>";
    }

    public function checkSecurity() {
        echo "<div class='results'><h2>Security Check</h2>";
        $files = $this->getAllPhpFiles($this->baseDir);
        echo "<p>Analyzing " . count($files) . " PHP files for security vulnerabilities...</p>";

        $issues = 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $relFile = str_replace($this->baseDir . DIRECTORY_SEPARATOR, '', $file);
            $found = false;

            // SQL Injection patterns
            if (preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*\$_[A-Z]+\s*;/', $content) ||
                preg_match('/->query\(.*\$[a-zA-Z_][a-zA-Z0-9_]*\)/', $content)) {
                $issues++;
                $this->results['security'][] = [
                    'file' => $relFile,
                    'type' => 'SQL Injection Risk',
                    'description' => 'Potential unsafe variable usage in SQL queries'
                ];
                echo "<div class='warning'><strong>$relFile</strong>: SQL Injection Risk - unsafe variable usage</div>";
                $found = true;
            }

            // XSS patterns - check for echo with variables not wrapped in htmlspecialchars
            if (preg_match('/echo\s+[^>]*\$[a-zA-Z_][a-zA-Z0-9_]*(?:\[[a-zA-Z0-9_]+\])*(?:\s*\.\s*[^;]*)?\s*;/', $content) &&
                !preg_match('/htmlspecialchars\s*\(/', $content)) {
                $issues++;
                $this->results['security'][] = [
                    'file' => $relFile,
                    'type' => 'XSS Risk',
                    'description' => 'Potential XSS vulnerability - variable echoed without htmlspecialchars'
                ];
                echo "<div class='warning'><strong>$relFile</strong>: XSS Risk - variable echoed without htmlspecialchars</div>";
                $found = true;
            }

            // File inclusion
            if (preg_match('/include\(.*\$[a-zA-Z_]/', $content) ||
                preg_match('/require\(.*\$[a-zA-Z_]/', $content)) {
                $issues++;
                $this->results['security'][] = [
                    'file' => $relFile,
                    'type' => 'File Inclusion Risk',
                    'description' => 'Potential file inclusion vulnerability'
                ];
                echo "<div class='warning'><strong>$relFile</strong>: File Inclusion Risk</div>";
                $found = true;
            }

            // Deprecated functions
            if (preg_match('/mysql_[a-zA-Z_]+\s*\(/', $content)) {
                $issues++;
                $this->results['security'][] = [
                    'file' => $relFile,
                    'type' => 'Deprecated Functions',
                    'description' => 'Uses deprecated mysql_ functions'
                ];
                echo "<div class='warning'><strong>$relFile</strong>: Uses deprecated mysql_ functions</div>";
                $found = true;
            }

            // Eval usage - more precise detection to avoid false positives
            if (preg_match('/\beval\s*\(/', $content)) {
                $issues++;
                $this->results['security'][] = [
                    'file' => $relFile,
                    'type' => 'Dangerous Code',
                    'description' => 'Uses eval function - high security risk'
                ];
                echo "<div class='error'><strong>$relFile</strong>: Uses eval() function - high security risk</div>";
                $found = true;
            }

            // Shell execution with user input
            if (preg_match('/(shell_exec|exec|system|passthru)\(/', $content)) {
                $issues++;
                $this->results['security'][] = [
                    'file' => $relFile,
                    'type' => 'Shell Injection Risk',
                    'description' => 'Uses shell execution functions - check for injection vulnerabilities'
                ];
                echo "<div class='warning'><strong>$relFile</strong>: Shell execution - check for injection vulnerabilities</div>";
                $found = true;
            }
        }

        if ($issues == 0) {
            echo "<div class='success'>✅ No obvious security vulnerabilities detected</div>";
        } else {
            echo "<div class='error'>⚠️ Found $issues potential security issues</div>";
        }
        echo "</div>";
    }

    public function checkCodeQuality() {
        echo "<div class='results'><h2>Code Quality Check</h2>";
        $files = $this->getAllPhpFiles($this->baseDir);
        echo "<p>Analyzing code quality in " . count($files) . " files...</p>";

        $largeFiles = [];
        $longLines = [];
        $mixedTabsSpaces = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $relFile = str_replace($this->baseDir . DIRECTORY_SEPARATOR, '', $file);

            // Large files
            if (strlen($content) > 50000) {
                $largeFiles[] = $relFile;
            }

            // Long lines
            $lines = explode("\n", $content);
            foreach ($lines as $lineNo => $line) {
                if (strlen($line) > 120) {
                    $longLines[] = "$relFile:$lineNo (" . strlen($line) . " chars)";
                }
            }

            // Mixed tabs and spaces
            if (preg_match('/\t/', $content) && preg_match('/^ {4,}/m', $content)) {
                $mixedTabsSpaces[] = $relFile;
            }
        }

        if (!empty($largeFiles)) {
            echo "<div class='warning'><strong>Large Files:</strong> " . implode(', ', $largeFiles) . "</div>";
            $this->results['quality'] = array_merge($this->results['quality'], array_map(function($f) {
                return ['file' => $f, 'type' => 'Large File', 'description' => 'File larger than 50KB'];
            }, $largeFiles));
        }

        if (!empty($longLines)) {
            echo "<div class='info'><strong>Long Lines:</strong><br><small>" . implode('<br>', array_slice($longLines, 0, 10)) .
                 (count($longLines) > 10 ? "<br>... and " . (count($longLines) - 10) . " more" : "") . "</small></div>";
        }

        if (!empty($mixedTabsSpaces)) {
            echo "<div class='info'><strong>Mixed Indentation:</strong> " . implode(', ', $mixedTabsSpaces) . "</div>";
        }

        echo "<div class='success'>✅ Code quality analysis completed</div>";
        echo "</div>";
    }

    public function printSummary() {
        $totalFiles = count($this->getAllPhpFiles($this->baseDir));
        $syntaxErrors = count($this->results['syntax']);
        $securityIssues = count($this->results['security']);

        echo "<div class='summary'>
            <h2>Summary</h2>
            <p><strong>Files Analyzed:</strong> $totalFiles</p>
            <p class='" . ($syntaxErrors > 0 ? 'error' : 'success') . "'><strong>Syntax Errors:</strong> $syntaxErrors</p>
            <p class='" . ($securityIssues > 0 ? 'warning' : 'success') . "'><strong>Security Issues:</strong> $securityIssues</p>
            <p><strong>Generated on:</strong> " . date('Y-m-d H:i:s') . "</p>
        </div>";
    }

    public function runAllChecks() {
        $this->checkSyntax();
        $this->checkSecurity();
        $this->checkCodeQuality();
        $this->printSummary();
    }
}

// Run the checks
ob_start();
$checker = new CodeQualityChecker();
$checker->runAllChecks();

echo "</body></html>";

$output = ob_get_clean();
file_put_contents(__DIR__ . '/site_bugs_report.html', $output);
echo $output;
?>
