<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Define missing pages
$missing_pages = [
    [
        'title' => 'Home',
        'slug' => 'home',
        'content' => '<h1>Welcome to Mossé Luxe</h1><p>Your premier destination for luxury streetwear and fashion.</p>',
        'subtitle' => '',
        'meta_title' => 'Home - Mossé Luxe Luxury Streetwear',
        'meta_description' => 'Discover premium luxury streetwear at Mossé Luxe. Quality fashion that merges elegance with street culture.'
    ],
    [
        'title' => 'About Us',
        'slug' => 'about',
        'content' => '<h1>About Mossé Luxe</h1><p>Learn about our story and heritage.</p>',
        'subtitle' => '',
        'meta_title' => 'About Us - Mossé Luxe',
        'meta_description' => 'Discover the story behind Mossé Luxe, our heritage, and commitment to quality.'
    ],
    [
        'title' => 'Contact Us',
        'slug' => 'contact',
        'content' => '<h1>Contact Us</h1><p>Get in touch with our team.</p>',
        'subtitle' => '',
        'meta_title' => 'Contact Us - Mossé Luxe',
        'meta_description' => 'Contact Mossé Luxe for inquiries about our products and services.'
    ],
    [
        'title' => 'FAQ',
        'slug' => 'faq',
        'content' => '<h2>Frequently Asked Questions</h2><p>Find answers to common questions about our products and services.</p>',
        'subtitle' => '',
        'meta_title' => 'FAQ - Mossé Luxe',
        'meta_description' => 'Frequently asked questions about Mossé Luxe products, shipping, and policies.'
    ],
    [
        'title' => 'Careers',
        'slug' => 'careers',
        'content' => '<h2>Join Our Team</h2><p>Explore career opportunities at Mossé Luxe.</p>',
        'subtitle' => '',
        'meta_title' => 'Careers - Mossé Luxe',
        'meta_description' => 'Join the Mossé Luxe team and be part of the luxury streetwear revolution.'
    ],
    [
        'title' => 'Privacy Policy',
        'slug' => 'privacy-policy',
        'content' => '<h1>Privacy Policy</h1><p>Learn how we protect your data.</p>',
        'subtitle' => '',
        'meta_title' => 'Privacy Policy - Mossé Luxe',
        'meta_description' => 'Our privacy policy explaining how we collect and protect your personal information.'
    ],
    [
        'title' => 'Shipping & Returns',
        'slug' => 'shipping-returns',
        'content' => '<h1>Shipping & Returns</h1><p>Information about our shipping and return policies.</p>',
        'subtitle' => '',
        'meta_title' => 'Shipping & Returns - Mossé Luxe',
        'meta_description' => 'Learn about shipping options and return policies at Mossé Luxe.'
    ],
    [
        'title' => 'Terms of Service',
        'slug' => 'terms-of-service',
        'content' => '<h1>Terms of Service</h1><p>Please read our terms and conditions.</p>',
        'subtitle' => '',
        'meta_title' => 'Terms of Service - Mossé Luxe',
        'meta_description' => 'Terms and conditions for using the Mossé Luxe website and services.'
    ]
];

foreach ($missing_pages as $page) {
    // Check if slug exists
    $stmt = $conn->prepare("SELECT id FROM pages WHERE slug = ?");
    $stmt->bind_param("s", $page['slug']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        // Insert the page
        $insert_sql = "INSERT INTO pages (title, slug, content, subtitle, meta_title, meta_description, status) VALUES (?, ?, ?, ?, ?, ?, 1)";
        $ins_stmt = $conn->prepare($insert_sql);
        $ins_stmt->bind_param("ssssss", $page['title'], $page['slug'], $page['content'], $page['subtitle'], $page['meta_title'], $page['meta_description']);
        if ($ins_stmt->execute()) {
            echo "Added page: " . $page['title'] . "\n";
        } else {
            echo "Error adding page: " . $page['title'] . " - " . $ins_stmt->error . "\n";
        }
        $ins_stmt->close();
    } else {
        echo "Page slug '" . $page['slug'] . "' already exists.\n";
    }
    $stmt->close();
}

echo "Populate missing pages script complete.\n";
?>
