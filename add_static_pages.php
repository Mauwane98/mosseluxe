<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Insert static pages
$pages = [
    [
        'slug' => 'about',
        'title' => 'About Mossé Luxe',
        'content' => '<h2>Our Story</h2><p>Mossé Luxe is dedicated to redefining urban luxury through premium streetwear. Founded with the vision of bridging high fashion with street culture, we craft pieces that embody both elegance and edge.</p><p>Each item is meticulously designed and produced to meet the highest standards of quality and style.</p>',
        'status' => 1
    ],
    [
        'slug' => 'contact',
        'title' => 'Contact Us',
        'content' => '<h2>Get In Touch</h2><p>Ready to elevate your style? Contact our team for inquiries about our premium collection.</p><p>Email: info@mosseluxe.com<br>Phone: +27 67 616 0928</p><p>Find us in Pretoria, South Africa.</p>',
        'status' => 1
    ],
    [
        'slug' => 'privacy-policy',
        'title' => 'Privacy Policy',
        'content' => '<h2>Privacy Policy</h2><p>We are committed to protecting your privacy. This policy outlines how we collect, use, and safeguard your personal information.</p><p>All data is securely stored and never shared with third parties without your consent.</p>',
        'status' => 1
    ],
    [
        'slug' => 'terms-of-service',
        'title' => 'Terms of Service',
        'content' => '<h2>Terms of Service</h2><p>By using Mossé Luxe, you agree to these terms of service.</p><p>All sales are final unless otherwise noted. Shipping and returns policies apply.</p>',
        'status' => 1
    ]
];

$stmt = $conn->prepare("INSERT INTO pages (slug, title, content, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content), status=VALUES(status)");

foreach ($pages as $page) {
    $stmt->bind_param("sssi",
        $page['slug'],
        $page['title'],
        $page['content'],
        $page['status']
    );

    if ($stmt->execute()) {
        echo "Inserted page: {$page['title']}\n";
    } else {
        echo "Error: " . $stmt->error . "\n";
    }
}

$stmt->close();

echo "Static pages added.\n";

$conn->close();
?>
