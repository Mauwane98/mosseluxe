<?php
require_once 'includes/db_connect.php';
$conn = get_db_connection();

echo "<pre>";

// Insert pages
$pages = [
    [
        'slug' => 'shipping-returns',
        'title' => 'Shipping & Returns',
        'content' => '
<h2>Shipping Information</h2>
<p>We offer standard and express shipping options nationwide. Standard shipping typically takes 3-5 business days, while express shipping takes 1-2 business days. All orders over R1500 qualify for free standard shipping.</p>

<h2>Return Policy</h2>
<p>We accept returns within 30 days of purchase for items that are unworn, unwashed, and in their original condition with all tags attached. To initiate a return, please contact our customer service team.</p>

<h2>Exchanges</h2>
<p>If you need to exchange an item for a different size or color, please contact us within 30 days of purchase. Exchanges are subject to availability.</p>

<h2>Refunds</h2>
<p>Refunds will be processed within 5-7 business days after we receive your returned item. The refund will be issued to the original payment method.</p>
        '
    ],
    [
        'slug' => 'privacy-policy',
        'title' => 'Privacy Policy',
        'content' => '
<h2>Information We Collect</h2>
<p>We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us for support.</p>

<h2>How We Use Your Information</h2>
<p>We use the information we collect to process orders, provide customer service, send marketing communications, and improve our website.</p>

<h2>Information Sharing</h2>
<p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>

<h2>Data Security</h2>
<p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
        '
    ],
    [
        'slug' => 'terms-of-service',
        'title' => 'Terms of Service',
        'content' => '
<h2>Acceptance of Terms</h2>
<p>By accessing and using Mossé Luxe, you accept and agree to be bound by the terms and provision of this agreement.</p>

<h2>Use License</h2>
<p>Permission is granted to temporarily access the materials on Mossé Luxe for personal, non-commercial transitory viewing only.</p>

<h2>Disclaimer</h2>
<p>The materials on Mossé Luxe are provided on an \'as is\' basis. Mossé Luxe makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties.</p>

<h2>Limitations</h2>
<p>In no event shall Mossé Luxe or its suppliers be liable for any damages arising out of the use or inability to use the materials on Mossé Luxe.</p>
        '
    ]
];

foreach ($pages as $page) {
    $stmt = $conn->prepare("INSERT INTO pages (slug, title, content, status) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $page['slug'], $page['title'], $page['content']);
    if ($stmt->execute()) {
        echo "Inserted page: {$page['title']}\n";
    } else {
        echo "Error inserting {$page['title']}: " . $stmt->error . "\n";
    }
    $stmt->close();
}

echo "Pages seeding complete!\n";
echo "</pre>";

$conn->close();
?>
