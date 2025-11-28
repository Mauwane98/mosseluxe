<?php
require_once __DIR__ . '/config.php';

/**
 * Generate SEO meta tags for a page
 */
function generate_meta_tags($title = '', $description = '', $keywords = '', $image = '', $url = '', $type = 'website') {
    $site_name = 'Mossé Luxe';
    $separator = ' | ';
    $default_description = 'Discover luxury fashion at Mossé Luxe. Premium quality clothing and accessories for the modern fashion enthusiast.';
    $default_keywords = 'fashion,luxury,clothing,premium,style,menswear,womenswear,accessories';

    // Clean title
    if (empty($title)) {
        $title = $site_name;
    } elseif ($title !== $site_name) {
        $title = $title . $separator . $site_name;
    }

    // Clean description
    if (empty($description)) {
        $description = $default_description;
    }

    // Clean keywords
    if (empty($keywords)) {
        $keywords = $default_keywords;
    }

    // Default image
    if (empty($image)) {
        $image = SITE_URL . '/assets/images/hero.jpeg'; // Default hero image
    } elseif (!filter_var($image, FILTER_VALIDATE_URL)) {
        $image = SITE_URL . $image; // Convert relative to absolute
    }

    // Default URL
    if (empty($url)) {
        $url = SITE_URL . $_SERVER['REQUEST_URI'];
    }

    // Generate meta tags
    $meta_tags = [];

    // Basic meta tags
    $meta_tags[] = '<title>' . htmlspecialchars($title) . '</title>';
    $meta_tags[] = '<meta name="description" content="' . htmlspecialchars($description) . '">';
    $meta_tags[] = '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">';

    // Open Graph tags
    $meta_tags[] = '<meta property="og:title" content="' . htmlspecialchars($title) . '">';
    $meta_tags[] = '<meta property="og:description" content="' . htmlspecialchars($description) . '">';
    $meta_tags[] = '<meta property="og:image" content="' . htmlspecialchars($image) . '">';
    $meta_tags[] = '<meta property="og:url" content="' . htmlspecialchars($url) . '">';
    $meta_tags[] = '<meta property="og:type" content="' . htmlspecialchars($type) . '">';
    $meta_tags[] = '<meta property="og:site_name" content="' . htmlspecialchars($site_name) . '">';

    // Twitter Card tags
    $meta_tags[] = '<meta name="twitter:card" content="summary_large_image">';
    $meta_tags[] = '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">';
    $meta_tags[] = '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">';
    $meta_tags[] = '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">';

    // Canonical URL
    $meta_tags[] = '<link rel="canonical" href="' . htmlspecialchars($url) . '">';

    return implode("\n    ", $meta_tags);
}

/**
 * Generate structured data (JSON-LD) for a product
 */
function generate_product_structured_data($product) {
    if (!$product) return '';

    $structured_data = [
        "@context" => "https://schema.org",
        "@type" => "Product",
        "name" => htmlspecialchars($product['name']),
        "description" => htmlspecialchars($product['description']),
        "image" => SITE_URL . htmlspecialchars($product['image']),
        "sku" => htmlspecialchars($product['id']),
        "brand" => [
            "@type" => "Brand",
            "name" => "Mossé Luxe"
        ]
    ];

    // Add offers
    $structured_data["offers"] = [
        "@type" => "Offer",
        "price" => number_format($product['price'], 2, '.', ''),
        "priceCurrency" => "ZAR",
        "availability" => $product['stock'] > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
        "seller" => [
            "@type" => "Organization",
            "name" => "Mossé Luxe"
        ]
    ];

    // Add sale price if exists
    if (!empty($product['sale_price'])) {
        $structured_data["offers"]["priceValidUntil"] = date('Y-m-d', strtotime('+1 year'));
        $structured_data["offers"]["price"] = number_format($product['sale_price'], 2, '.', '');
    }

    // Add aggregate rating if product has reviews
    $rating_data = get_product_rating_data($product['id']);
    if ($rating_data['count'] > 0) {
        $structured_data["aggregateRating"] = [
            "@type" => "AggregateRating",
            "ratingValue" => $rating_data['average'],
            "ratingCount" => $rating_data['count'],
            "bestRating" => 5,
            "worstRating" => 1
        ];
    }

    return '<script type="application/ld+json">' . json_encode($structured_data, JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate structured data for organization/website
 */
function generate_organization_structured_data() {
    $structured_data = [
        "@context" => "https://schema.org",
        "@type" => "Organization",
        "name" => "Mossé Luxe",
        "url" => SITE_URL,
        "logo" => SITE_URL . "/assets/images/logo.png",
        "description" => "Discover luxury fashion at Mossé Luxe. Premium quality clothing and accessories for the modern fashion enthusiast.",
        "contactPoint" => [
            "@type" => "ContactPoint",
            "telephone" => "+27-123-456-7890",
            "contactType" => "customer service"
        ],
        "sameAs" => [
            FACEBOOK_URL,
            INSTAGRAM_URL,
            TWITTER_URL
        ]
    ];

    return '<script type="application/ld+json">' . json_encode($structured_data, JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate breadcrumb structured data
 */
function generate_breadcrumb_structured_data($breadcrumbs = []) {
    if (empty($breadcrumbs)) return '';

    $items = [];
    $position = 1;

    foreach ($breadcrumbs as $name => $url) {
        $items[] = [
            "@type" => "ListItem",
            "position" => $position,
            "name" => htmlspecialchars($name),
            "item" => $url
        ];
        $position++;
    }

    $structured_data = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => $items
    ];

    return '<script type="application/ld+json">' . json_encode($structured_data, JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Get product rating data for structured data
 */
function get_product_rating_data($product_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("
        SELECT AVG(rating) as average, COUNT(*) as count
        FROM product_reviews
        WHERE product_id = ? AND is_approved = 1
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    return [
        'average' => round($data['average'] ?? 0, 1),
        'count' => $data['count'] ?? 0
    ];
}

/**
 * Generate robots.txt content
 */
function generate_robots_txt() {
    $content = "User-agent: *\n";
    $content .= "Disallow: /admin/\n";
    $content .= "Disallow: /_private_scripts/\n";
    $content .= "Disallow: /includes/\n";
    $content .= "Disallow: /tests/\n";
    $content .= "Disallow: /*?*\n"; // Disallow URLs with query parameters (except sitemap)
    $content .= "\n";
    $content .= "Allow: /assets/\n";
    $content .= "Allow: /shop\n";
    $content .= "Allow: /search\n";
    $content .= "Allow: /product.php\n";
    $content .= "\n";
    $content .= "Sitemap: " . SITE_URL . "/sitemap.xml\n";

    return $content;
}

/**
 * Generate dynamic sitemap
 */
function generate_sitemap() {
    $conn = get_db_connection();
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    // Home page
    $xml .= add_sitemap_url(SITE_URL . '/', '1.0', 'daily');

    // Static pages
    $pages = [
        'about.php',
        'careers.php',
        'contact.php',
        'faq.php',
        'shipping-returns.php',
        'privacy-policy.php',
        'terms-of-service.php'
    ];

    foreach ($pages as $page) {
        if (file_exists($page)) {
            $xml .= add_sitemap_url(SITE_URL . '/' . $page, '0.7', 'monthly');
        }
    }

    // Shop page
    $xml .= add_sitemap_url(SITE_URL . '/shop.php', '0.9', 'daily');

    // Categories
    $categories_result = $conn->query("SELECT id FROM categories WHERE 1");
    while ($category = $categories_result->fetch_assoc()) {
        $xml .= add_sitemap_url(SITE_URL . '/shop.php?category=' . $category['id'], '0.8', 'weekly');
    }

    // Products
    $products_result = $conn->query("
        SELECT id, created_at
        FROM products
        WHERE status = 1
        ORDER BY created_at DESC
    ");
    while ($product = $products_result->fetch_assoc()) {
        $priority = '0.6';
        $changefreq = 'weekly';

        // Newer products get higher priority
        if (strtotime($product['created_at']) > strtotime('-30 days')) {
            $priority = '0.8';
            $changefreq = 'daily';
        }

        $xml .= add_sitemap_url(SITE_URL . '/product.php?id=' . $product['id'], $priority, $changefreq);
    }

    $xml .= '</urlset>';

    $conn->close();
    return $xml;
}

/**
 * Helper function to add sitemap URL
 */
function add_sitemap_url($url, $priority, $changefreq, $lastmod = null) {
    if (!$lastmod) {
        $lastmod = date('Y-m-d');
    }

    $xml = "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    $xml .= "    <lastmod>$lastmod</lastmod>\n";
    $xml .= "    <changefreq>$changefreq</changefreq>\n";
    $xml .= "    <priority>$priority</priority>\n";
    $xml .= "  </url>\n";

    return $xml;
}

/**
 * Generate Google Analytics tracking code
 */
function generate_analytics_code($tracking_id) {
    if (empty($tracking_id) || strlen($tracking_id) < 12) {
        return '';
    }

    return "<!-- Google Analytics -->
<script async src='https://www.googletagmanager.com/gtag/js?id=$tracking_id'></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '$tracking_id', {
    'custom_map': {'dimension1': 'page_category'},
    'page_title': document.title
  });

  // E-commerce tracking
  gtag('event', 'page_view', {
    'page_title': document.title,
    'page_location': window.location.href
  });

  // Track product views
  function trackProductView(productId, productName, productCategory) {
    gtag('event', 'view_item', {
      items: [{
        id: productId,
        name: productName,
        category: productCategory,
        quantity: 1
      }]
    });
  }

  // Track add to cart
  function trackAddToCart(productId, productName, productCategory, quantity, price) {
    gtag('event', 'add_to_cart', {
      items: [{
        id: productId,
        name: productName,
        category: productCategory,
        quantity: quantity,
        price: price
      }]
    });
  }

  // Track purchases
  function trackPurchase(orderId, total, items) {
    gtag('event', 'purchase', {
      transaction_id: orderId,
      value: total,
      currency: 'ZAR',
      items: items
    });
  }
</script>";
}

/**
 * Generate Facebook Pixel code
 */
function generate_facebook_pixel($pixel_id) {
    if (empty($pixel_id)) {
        return '';
    }

    return "<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '$pixel_id');
fbq('track', 'PageView');

fbq('track', 'ViewContent', {
  content_type: 'product'
});
</script>
<noscript><img height='1' width='1' style='display:none'
  src='https://www.facebook.com/tr?ev=$pixel_id&noscript=1'
/></noscript>";
}

/**
 * Generate SEO-friendly URL slug
 */
function generate_seo_slug($text) {
    // Convert to lowercase
    $slug = strtolower($text);

    // Replace non-letter or digits with -
    $slug = preg_replace('/[^\\pL\\pN]+/u', '-', $slug);

    // Trim leading/trailing -
    $slug = trim($slug, '-');

    // Remove duplicate -
    $slug = preg_replace('/-+/', '-', $slug);

    // Transliterate if needed (for non-ASCII chars)
    $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);

    return $slug;
}

/**
 * Get SEO URL for product
 */
function get_product_seo_url($product_id, $product_name) {
    $slug = generate_seo_slug($product_name);
    return SITE_URL . "/product.php?id=$product_id&name=$slug";
}

/**
 * Get SEO URL for category
 */
function get_category_seo_url($category_id, $category_name) {
    $slug = generate_seo_slug($category_name);
    return SITE_URL . "/shop.php?category=$category_id&cat=$slug";
}

/**
 * Check if current URL is SEO-friendly
 */
function is_seo_friendly_url() {
    $current_url = $_SERVER['REQUEST_URI'];
    $query_string = $_SERVER['QUERY_STRING'];

    // Check for search parameters (allowed)
    if (strpos($current_url, '/search') !== false && strpos($current_url, 'q=') !== false) {
        return true;
    }

    // Check for product parameter
    if (strpos($current_url, 'product.php') !== false && strpos($query_string, 'id=') !== false) {
        return true;
    }

    // Check for category parameter
    if (strpos($current_url, 'shop.php') !== false && strpos($query_string, 'category=') !== false) {
        return true;
    }

    // Basic pages
    $seo_pages = ['/', '/shop.php', '/cart.php', '/checkout.php', '/login.php', '/register.php'];
    if (in_array($current_url, $seo_pages)) {
        return true;
    }

    // Static pages
    $static_pages = ['about.php', 'careers.php', 'contact.php', 'faq.php', 'shipping-returns.php', 'privacy-policy.php', 'terms-of-service.php'];
    foreach ($static_pages as $page) {
        if (strpos($current_url, $page) !== false) {
            return true;
        }
    }

    return false;
}
// No closing PHP tag - prevents accidental whitespace output