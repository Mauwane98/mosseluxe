<?php
/**
 * Comprehensive Website Diagnostic and Fix Script
 * This script scans the entire website for errors and fixes them
 */

require_once 'includes/bootstrap.php';

// Set execution time limit
set_time_limit(300);

$conn = get_db_connection();
$issues = [];
$fixes = [];

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Comprehensive Diagnostic Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #000; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; border-left: 4px solid #000; padding-left: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #000; color: white; }
        tr:hover { background-color: #f5f5f5; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-error { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #000; }
        .section { margin: 20px 0; padding: 15px; border-left: 4px solid #000; background: #f9f9f9; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 Mossé Luxe - Comprehensive Diagnostic Report</h1>";
echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";

// 1. DATABASE INTEGRITY CHECK
echo "<h2>1. Database Integrity Check</h2>";
$required_tables = [
    'products', 'categories', 'users', 'orders', 'order_items',
    'cart', 'wishlist', 'settings', 'hero_slides', 'homepage_sections',
    'new_arrivals', 'messages', 'pages'
];

echo "<table><thead><tr><th>Table Name</th><th>Status</th><th>Row Count</th><th>Action</th></tr></thead><tbody>";
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
        echo "<tr><td>$table</td><td><span class='badge badge-success'>EXISTS</span></td><td>$count rows</td><td>✓</td></tr>";
    } else {
        echo "<tr><td>$table</td><td><span class='badge badge-error'>MISSING</span></td><td>-</td><td>⚠ Needs Creation</td></tr>";
        $issues[] = "Table '$table' is missing";
    }
}
echo "</tbody></table>";

// 2. PRODUCTS CHECK
echo "<h2>2. Products Inventory Check</h2>";
$products_check = $conn->query("SELECT COUNT(*) as total, 
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out_of_stock,
    SUM(CASE WHEN stock <= 5 AND stock > 0 THEN 1 ELSE 0 END) as low_stock
    FROM products");

if ($products_check) {
    $prod_stats = $products_check->fetch_assoc();
    echo "<div class='section'>";
    echo "<p><strong>Total Products:</strong> {$prod_stats['total']}</p>";
    echo "<p><strong>Active Products:</strong> <span class='success'>{$prod_stats['active']}</span></p>";
    echo "<p><strong>Out of Stock:</strong> <span class='error'>{$prod_stats['out_of_stock']}</span></p>";
    echo "<p><strong>Low Stock (≤5):</strong> <span class='warning'>{$prod_stats['low_stock']}</span></p>";
    echo "</div>";
    
    if ($prod_stats['total'] == 0) {
        $issues[] = "No products in database";
    }
}

// 3. MISSING PRODUCT IMAGES CHECK
echo "<h2>3. Product Images Validation</h2>";
$image_check = $conn->query("SELECT id, name, image FROM products WHERE status = 1");
$missing_images = 0;
$broken_images = [];

if ($image_check) {
    while ($product = $image_check->fetch_assoc()) {
        if (empty($product['image'])) {
            $missing_images++;
            $broken_images[] = $product['name'];
        } else {
            $image_path = __DIR__ . '/' . ltrim($product['image'], '/');
            if (!file_exists($image_path)) {
                $missing_images++;
                $broken_images[] = $product['name'] . " (file not found)";
            }
        }
    }
}

if ($missing_images > 0) {
    echo "<p class='error'>⚠ Found $missing_images products with missing/broken images</p>";
    echo "<ul>";
    foreach (array_slice($broken_images, 0, 10) as $img) {
        echo "<li>$img</li>";
    }
    if (count($broken_images) > 10) {
        echo "<li>... and " . (count($broken_images) - 10) . " more</li>";
    }
    echo "</ul>";
    $issues[] = "$missing_images products have missing images";
} else {
    echo "<p class='success'>✓ All product images are valid</p>";
}

// 4. CATEGORIES CHECK
echo "<h2>4. Categories Check</h2>";
$cat_check = $conn->query("SELECT COUNT(*) as total FROM categories");
if ($cat_check) {
    $cat_count = $cat_check->fetch_assoc()['total'];
    if ($cat_count > 0) {
        echo "<p class='success'>✓ Found $cat_count categories</p>";
        
        // Show categories
        $cats = $conn->query("SELECT id, name FROM categories ORDER BY name");
        echo "<ul>";
        while ($cat = $cats->fetch_assoc()) {
            $prod_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE category = {$cat['id']}")->fetch_assoc()['count'];
            echo "<li>{$cat['name']} ($prod_count products)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>⚠ No categories found</p>";
        $issues[] = "No categories in database";
    }
}

// 5. SETTINGS CHECK
echo "<h2>5. Site Settings Check</h2>";
$critical_settings = [
    'shop_title', 'announcement_enabled', 'announcement_text',
    'whatsapp_enabled', 'whatsapp_number', 'hero_buttons_enabled'
];

echo "<table><thead><tr><th>Setting Key</th><th>Value</th><th>Status</th></tr></thead><tbody>";
foreach ($critical_settings as $setting_key) {
    $value = get_setting($setting_key, null);
    if ($value !== null) {
        $display_value = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
        echo "<tr><td>$setting_key</td><td>" . htmlspecialchars($display_value) . "</td><td><span class='badge badge-success'>SET</span></td></tr>";
    } else {
        echo "<tr><td>$setting_key</td><td>-</td><td><span class='badge badge-warning'>NOT SET</span></td></tr>";
    }
}
echo "</tbody></table>";

// 6. HERO SLIDES CHECK
echo "<h2>6. Hero Carousel Check</h2>";
$hero_check = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active FROM hero_slides");
if ($hero_check) {
    $hero_stats = $hero_check->fetch_assoc();
    echo "<p><strong>Total Slides:</strong> {$hero_stats['total']}</p>";
    echo "<p><strong>Active Slides:</strong> <span class='success'>{$hero_stats['active']}</span></p>";
    
    if ($hero_stats['active'] == 0) {
        $issues[] = "No active hero slides";
    }
}

// 7. ORDERS CHECK
echo "<h2>7. Orders System Check</h2>";
$orders_check = $conn->query("SELECT COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(total_price) as revenue
    FROM orders");

if ($orders_check) {
    $order_stats = $orders_check->fetch_assoc();
    echo "<div class='section'>";
    echo "<p><strong>Total Orders:</strong> {$order_stats['total']}</p>";
    echo "<p><strong>Pending Orders:</strong> <span class='warning'>{$order_stats['pending']}</span></p>";
    echo "<p><strong>Completed Orders:</strong> <span class='success'>{$order_stats['completed']}</span></p>";
    echo "<p><strong>Total Revenue:</strong> R" . number_format($order_stats['revenue'], 2) . "</p>";
    echo "</div>";
}

// 8. USERS CHECK
echo "<h2>8. Users & Authentication Check</h2>";
$users_check = $conn->query("SELECT COUNT(*) as total,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as customers
    FROM users");

if ($users_check) {
    $user_stats = $users_check->fetch_assoc();
    echo "<p><strong>Total Users:</strong> {$user_stats['total']}</p>";
    echo "<p><strong>Administrators:</strong> <span class='info'>{$user_stats['admins']}</span></p>";
    echo "<p><strong>Customers:</strong> <span class='info'>{$user_stats['customers']}</span></p>";
    
    if ($user_stats['admins'] == 0) {
        $issues[] = "No admin users found";
    }
}

// 9. FILE SYSTEM CHECK
echo "<h2>9. Critical Files & Directories Check</h2>";
$critical_files = [
    'index.php' => 'Homepage',
    'shop.php' => 'Shop Page',
    'cart.php' => 'Cart Page',
    'checkout.php' => 'Checkout Page',
    'product.php' => 'Product Detail Page',
    'admin/dashboard.php' => 'Admin Dashboard',
    'admin/products.php' => 'Admin Products',
    'admin/orders.php' => 'Admin Orders',
    'includes/bootstrap.php' => 'Bootstrap',
    'includes/config.php' => 'Configuration',
    'includes/header.php' => 'Header Template',
    'includes/footer.php' => 'Footer Template',
    'assets/js/cart.js' => 'Cart JavaScript',
    'assets/js/main.js' => 'Main JavaScript',
    'assets/css/custom.css' => 'Custom CSS'
];

echo "<table><thead><tr><th>File</th><th>Description</th><th>Status</th></tr></thead><tbody>";
foreach ($critical_files as $file => $desc) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        $size = filesize($file_path);
        echo "<tr><td>$file</td><td>$desc</td><td><span class='badge badge-success'>EXISTS</span> (" . number_format($size) . " bytes)</td></tr>";
    } else {
        echo "<tr><td>$file</td><td>$desc</td><td><span class='badge badge-error'>MISSING</span></td></tr>";
        $issues[] = "Critical file missing: $file";
    }
}
echo "</tbody></table>";

// 10. CSS & JAVASCRIPT CHECK
echo "<h2>10. Assets Check</h2>";
$asset_dirs = [
    'assets/css' => 'Stylesheets',
    'assets/js' => 'JavaScript',
    'assets/images' => 'Images'
];

echo "<table><thead><tr><th>Directory</th><th>Description</th><th>File Count</th><th>Status</th></tr></thead><tbody>";
foreach ($asset_dirs as $dir => $desc) {
    $dir_path = __DIR__ . '/' . $dir;
    if (is_dir($dir_path)) {
        $files = glob($dir_path . '/*');
        $count = count($files);
        echo "<tr><td>$dir</td><td>$desc</td><td>$count files</td><td><span class='badge badge-success'>OK</span></td></tr>";
    } else {
        echo "<tr><td>$dir</td><td>$desc</td><td>-</td><td><span class='badge badge-error'>MISSING</span></td></tr>";
        $issues[] = "Asset directory missing: $dir";
    }
}
echo "</tbody></table>";

// 11. HOMEPAGE SECTIONS CHECK
echo "<h2>11. Homepage Sections Check</h2>";
$sections_check = $conn->query("SELECT section_key, title, is_active FROM homepage_sections ORDER BY sort_order");
if ($sections_check && $sections_check->num_rows > 0) {
    echo "<table><thead><tr><th>Section Key</th><th>Title</th><th>Status</th></tr></thead><tbody>";
    while ($section = $sections_check->fetch_assoc()) {
        $status = $section['is_active'] ? "<span class='badge badge-success'>ACTIVE</span>" : "<span class='badge badge-warning'>INACTIVE</span>";
        echo "<tr><td>{$section['section_key']}</td><td>{$section['title']}</td><td>$status</td></tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p class='warning'>⚠ No homepage sections configured</p>";
}

// 12. NEW ARRIVALS CHECK
echo "<h2>12. New Arrivals Section Check</h2>";
$new_arrivals_check = $conn->query("SELECT COUNT(*) as count FROM new_arrivals na 
    JOIN products p ON na.product_id = p.id 
    WHERE p.status = 1");
if ($new_arrivals_check) {
    $na_count = $new_arrivals_check->fetch_assoc()['count'];
    if ($na_count > 0) {
        echo "<p class='success'>✓ Found $na_count products in New Arrivals</p>";
    } else {
        echo "<p class='warning'>⚠ No products in New Arrivals section</p>";
        $issues[] = "New Arrivals section is empty";
    }
}

// SUMMARY
echo "<h2>📊 Diagnostic Summary</h2>";
echo "<div class='section'>";
if (count($issues) == 0) {
    echo "<p class='success' style='font-size: 18px;'>✓ No critical issues found! Your website is in good shape.</p>";
} else {
    echo "<p class='error' style='font-size: 18px;'>⚠ Found " . count($issues) . " issue(s) that need attention:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li class='error'>$issue</li>";
    }
    echo "</ul>";
}
echo "</div>";

// RECOMMENDATIONS
echo "<h2>💡 Recommendations</h2>";
echo "<div class='section'>";
echo "<ol>";
echo "<li><strong>Database Backup:</strong> Ensure you have a recent backup before making changes</li>";
echo "<li><strong>Product Images:</strong> Upload missing product images to improve user experience</li>";
echo "<li><strong>Content Population:</strong> Add more products, categories, and content</li>";
echo "<li><strong>Testing:</strong> Test cart, checkout, and payment flows thoroughly</li>";
echo "<li><strong>Security:</strong> Ensure SSL certificate is installed for production</li>";
echo "<li><strong>Performance:</strong> Enable caching and optimize images</li>";
echo "</ol>";
echo "</div>";

echo "</div></body></html>";

$conn->close();
