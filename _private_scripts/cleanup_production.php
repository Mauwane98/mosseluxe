<?php
/**
 * Production Cleanup Script
 * Safely removes all test, demo, and debug files before production deployment
 * 
 * IMPORTANT: Review the file list before running!
 * Run this script ONCE before deploying to production
 * 
 * Usage: php cleanup_production.php [--dry-run] [--execute]
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line for security reasons.');
}

$dryRun = in_array('--dry-run', $argv) || !in_array('--execute', $argv);
$rootDir = dirname(__DIR__);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     MOSSÉ LUXE PRODUCTION CLEANUP SCRIPT                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE - No files will be deleted\n";
    echo "   Run with --execute flag to actually delete files\n\n";
} else {
    echo "⚠️  EXECUTE MODE - Files will be permanently deleted!\n";
    echo "   Press Ctrl+C within 5 seconds to cancel...\n\n";
    sleep(5);
}

// Files to delete (organized by category)
$filesToDelete = [
    // Test files in root
    'test' => [
        'add_test_product.php',
        'add_test_product_to_cart.php',
        'add_test_user.php',
        'browser_test.php',
        'cart_test_simple.php',
        'cart_final_test.php',
        'comprehensive_flow_test.php',
        'test_site_url.php',
        'test_site.php',
        'test_shop_debug.php',
        'test_router_logging.php',
        'test_real_cart_flow.php',
        'test_product_page_browser.php',
        'test_product_page.php',
        'test_product_2.php',
        'test_page_load.php',
        'test_new_arrivals.php',
        'test_homepage.php',
        'test_header_output.php',
        'test_functionality.php',
        'test_full_shopping_flow.php',
        'test_footer.php',
        'test_connection.php',
        'test_checkout.php',
        'test_cart_system.php',
        'test_cart_live.php',
        'test_cart_issue.php',
        'test_cart_comprehensive.php',
        'test_bootstrap.php',
        'test_all_features.php',
        'test_add_to_cart.html',
        'test_whatsapp.php',
        'test_variant_function.php',
    ],
    
    // Demo files
    'demo' => [
        'backup_demo_products.php',
        'final_demo_test.php',
    ],
    
    // Sample files
    'sample' => [
        'add_sample_products.php',
    ],
    
    // Debug files
    'debug' => [
        'bug_checker.php',
        'complete_debug.php',
        'debug.php',
        'debug_cart_contents.php',
        'debug_cart_system.php',
        'site_bugs_checker.php',
        'scan_all_in_one.php',
        'omni_scan.php',
    ],
    
    // Check/diagnostic files
    'check' => [
        'check_categories.php',
        'check_categories_v2.php',
        'check_mysql_version.php',
        'check_original_content.php',
        'check_page.php',
        'check_pages_status.php',
        'check_products.php',
        'check_session_settings.php',
        'check_table_columns.php',
        'check_tables.php',
        'check_user_table.php',
        'check_whatsapp_settings.php',
        'check_apache_errors.php',
        'check_shop_products.php',
    ],
    
    // Database utility files (keep in _private_scripts if needed)
    'db_utils' => [
        'describe_tables.php',
        'list_tables.php',
        'discard_tablespaces.php',
        'drop_create_db.php',
        'drop_variant_tables.php',
    ],
    
    // Miscellaneous
    'misc' => [
        'bugs_report_updated.html',
        'cypress_output.txt',
        'feature_inventory.json',
        'feature_inventory.txt',
        '.htaccess.bak',
    ],
];

// Directories to delete
$dirsToDelete = [
    'test-results',
    'playwright-report',
    'cypress/screenshots',
];

// Keep but document (don't delete)
$keepButDocument = [
    'tests',  // Keep for CI/CD
    'cypress',  // Keep for E2E testing
    '_private_scripts',  // Keep utility scripts
];

$stats = [
    'files_deleted' => 0,
    'files_failed' => 0,
    'dirs_deleted' => 0,
    'dirs_failed' => 0,
    'total_size_freed' => 0,
];

// Function to format file size
function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Process files
echo "═══════════════════════════════════════════════════════════\n";
echo "PROCESSING FILES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

foreach ($filesToDelete as $category => $files) {
    echo "📁 Category: " . strtoupper($category) . "\n";
    echo str_repeat("─", 60) . "\n";
    
    foreach ($files as $file) {
        $filePath = $rootDir . '/' . $file;
        
        if (file_exists($filePath)) {
            $fileSize = filesize($filePath);
            $stats['total_size_freed'] += $fileSize;
            
            if ($dryRun) {
                echo "  [DRY RUN] Would delete: $file (" . formatBytes($fileSize) . ")\n";
                $stats['files_deleted']++;
            } else {
                if (unlink($filePath)) {
                    echo "  ✓ Deleted: $file (" . formatBytes($fileSize) . ")\n";
                    $stats['files_deleted']++;
                } else {
                    echo "  ✗ Failed to delete: $file\n";
                    $stats['files_failed']++;
                }
            }
        }
    }
    echo "\n";
}

// Process directories
echo "═══════════════════════════════════════════════════════════\n";
echo "PROCESSING DIRECTORIES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

foreach ($dirsToDelete as $dir) {
    $dirPath = $rootDir . '/' . $dir;
    
    if (file_exists($dirPath) && is_dir($dirPath)) {
        if ($dryRun) {
            echo "  [DRY RUN] Would delete directory: $dir\n";
            $stats['dirs_deleted']++;
        } else {
            if (deleteDirectory($dirPath)) {
                echo "  ✓ Deleted directory: $dir\n";
                $stats['dirs_deleted']++;
            } else {
                echo "  ✗ Failed to delete directory: $dir\n";
                $stats['dirs_failed']++;
            }
        }
    }
}

// Document kept directories
echo "\n═══════════════════════════════════════════════════════════\n";
echo "DIRECTORIES KEPT (Exclude from production deployment)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

foreach ($keepButDocument as $dir) {
    $dirPath = $rootDir . '/' . $dir;
    if (file_exists($dirPath) && is_dir($dirPath)) {
        echo "  ℹ️  Keeping: $dir (exclude in deployment)\n";
    }
}

// Summary
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                    CLEANUP SUMMARY                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Files " . ($dryRun ? "to be deleted" : "deleted") . ": " . $stats['files_deleted'] . "\n";
if ($stats['files_failed'] > 0) {
    echo "Files failed: " . $stats['files_failed'] . "\n";
}
echo "Directories " . ($dryRun ? "to be deleted" : "deleted") . ": " . $stats['dirs_deleted'] . "\n";
if ($stats['dirs_failed'] > 0) {
    echo "Directories failed: " . $stats['dirs_failed'] . "\n";
}
echo "Space " . ($dryRun ? "to be freed" : "freed") . ": " . formatBytes($stats['total_size_freed']) . "\n";

if ($dryRun) {
    echo "\n💡 To execute the cleanup, run:\n";
    echo "   php cleanup_production.php --execute\n";
} else {
    echo "\n✅ Cleanup completed successfully!\n";
    echo "\n📝 Next steps:\n";
    echo "   1. Update .gitignore to exclude test directories\n";
    echo "   2. Run production deployment checklist\n";
    echo "   3. Test the application thoroughly\n";
}

echo "\n";
?>
