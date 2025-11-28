<?php
/**
 * Sitemap Generator for Mossé Luxe
 * Generates XML sitemap for SEO
 * 
 * Usage: php generate_sitemap.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line.');
}

$conn = get_db_connection();
$sitemap_file = dirname(__DIR__) . '/sitemap.xml';

// Base URL (update for production)
$base_url = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://mosseluxe.co.za';

echo "Generating sitemap for: $base_url\n";
echo "Output file: $sitemap_file\n\n";

// Start XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Homepage
$xml .= "  <url>\n";
$xml .= "    <loc>$base_url/</loc>\n";
$xml .= "    <changefreq>daily</changefreq>\n";
$xml .= "    <priority>1.0</priority>\n";
$xml .= "  </url>\n";

// Static pages
$static_pages = [
    ['url' => 'shop.php', 'changefreq' => 'daily', 'priority' => '0.9'],
    ['url' => 'about.php', 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['url' => 'contact.php', 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['url' => 'faq.php', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['url' => 'careers.php', 'changefreq' => 'monthly', 'priority' => '0.5'],
    ['url' => 'cart.php', 'changefreq' => 'weekly', 'priority' => '0.8'],
];

foreach ($static_pages as $page) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>$base_url/{$page['url']}</loc>\n";
    $xml .= "    <changefreq>{$page['changefreq']}</changefreq>\n";
    $xml .= "    <priority>{$page['priority']}</priority>\n";
    $xml .= "  </url>\n";
}

// Dynamic pages from database
$pages_query = "SELECT slug, updated_at FROM pages WHERE status = 1";
if ($result = $conn->query($pages_query)) {
    while ($row = $result->fetch_assoc()) {
        $lastmod = date('Y-m-d', strtotime($row['updated_at']));
        $xml .= "  <url>\n";
        $xml .= "    <loc>$base_url/page/{$row['slug']}</loc>\n";
        $xml .= "    <lastmod>$lastmod</lastmod>\n";
        $xml .= "    <changefreq>monthly</changefreq>\n";
        $xml .= "    <priority>0.6</priority>\n";
        $xml .= "  </url>\n";
    }
    echo "Added " . $result->num_rows . " dynamic pages\n";
}

// Products
$products_query = "SELECT id, name, updated_at FROM products WHERE status = 1";
if ($result = $conn->query($products_query)) {
    while ($row = $result->fetch_assoc()) {
        $slug = urlencode(str_replace(' ', '-', strtolower($row['name'])));
        $lastmod = date('Y-m-d', strtotime($row['updated_at']));
        $xml .= "  <url>\n";
        $xml .= "    <loc>$base_url/product/{$row['id']}/$slug</loc>\n";
        $xml .= "    <lastmod>$lastmod</lastmod>\n";
        $xml .= "    <changefreq>weekly</changefreq>\n";
        $xml .= "    <priority>0.8</priority>\n";
        $xml .= "  </url>\n";
    }
    echo "Added " . $result->num_rows . " products\n";
}

// Categories (if you have category pages)
$categories_query = "SELECT id, name FROM categories";
if ($result = $conn->query($categories_query)) {
    while ($row = $result->fetch_assoc()) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>$base_url/shop.php?category={$row['id']}</loc>\n";
        $xml .= "    <changefreq>weekly</changefreq>\n";
        $xml .= "    <priority>0.7</priority>\n";
        $xml .= "  </url>\n";
    }
    echo "Added " . $result->num_rows . " categories\n";
}

// Close XML
$xml .= '</urlset>';

// Write to file
if (file_put_contents($sitemap_file, $xml)) {
    echo "\n✅ Sitemap generated successfully: $sitemap_file\n";
    echo "File size: " . filesize($sitemap_file) . " bytes\n";
} else {
    echo "\n❌ Failed to write sitemap file\n";
}

$conn->close();
?>
