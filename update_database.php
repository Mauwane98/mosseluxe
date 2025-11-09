<?php
// Database Update Script for Mossé Luxe
// This script adds missing tables and updates existing ones

require_once 'includes/db_connect.php';
$conn = get_db_connection();

// Create pages table if it doesn't exist
$create_pages_table = "
CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Published, 0=Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($create_pages_table)) {
    echo "✓ Pages table created or already exists<br>";
} else {
    echo "✗ Error creating pages table: " . $conn->error . "<br>";
}

// Insert sample pages if they don't exist
$sample_pages = [
    [
        'title' => 'Terms of Service',
        'slug' => 'terms-of-service',
        'content' => '<h2>Terms of Service</h2>
<p>Welcome to Mossé Luxe. By accessing and using our website, you agree to comply with the following terms and conditions.</p>

<h3>1. Acceptance of Terms</h3>
<p>By using our website, you agree to these terms of service. If you do not agree, please do not use our website.</p>

<h3>2. Use of Website</h3>
<p>You may use our website for lawful purposes only. You agree not to use our website for any illegal or unauthorized purpose.</p>

<h3>3. Product Information</h3>
<p>We strive to provide accurate product information, but we do not warrant that product descriptions are complete or error-free.</p>

<h3>4. Pricing and Payment</h3>
<p>All prices are subject to change without notice. Payment is processed securely through our payment partners.</p>

<h3>5. Shipping and Delivery</h3>
<p>We will make reasonable efforts to deliver products within the estimated timeframe, but we are not responsible for delays caused by circumstances beyond our control.</p>

<h3>6. Returns and Refunds</h3>
<p>Please refer to our Returns Policy for information about returns and refunds.</p>

<h3>7. Limitation of Liability</h3>
<p>Mossé Luxe shall not be liable for any indirect, incidental, or consequential damages arising from your use of our website.</p>

<h3>8. Governing Law</h3>
<p>These terms are governed by the laws of South Africa.</p>

<h3>9. Contact Us</h3>
<p>If you have any questions about these terms, please contact us through our contact form.</p>',
        'status' => 1
    ],
    [
        'title' => 'Privacy Policy',
        'slug' => 'privacy-policy',
        'content' => '<h2>Privacy Policy</h2>
<p>At Mossé Luxe, we are committed to protecting your privacy and ensuring the security of your personal information.</p>

<h3>1. Information We Collect</h3>
<p>We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us.</p>

<h3>2. How We Use Your Information</h3>
<p>We use your information to process orders, provide customer service, send marketing communications (with your consent), and improve our website.</p>

<h3>3. Information Sharing</h3>
<p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>

<h3>4. Data Security</h3>
<p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

<h3>5. Cookies</h3>
<p>We use cookies to enhance your browsing experience and analyze website traffic.</p>

<h3>6. Your Rights</h3>
<p>You have the right to access, update, or delete your personal information. You may also opt out of marketing communications at any time.</p>

<h3>7. Changes to This Policy</h3>
<p>We may update this privacy policy from time to time. We will notify you of any changes by posting the new policy on this page.</p>

<h3>8. Contact Us</h3>
<p>If you have any questions about this privacy policy, please contact us through our contact form.</p>',
        'status' => 1
    ],
    [
        'title' => 'Shipping & Returns',
        'slug' => 'shipping-returns',
        'content' => '<h2>Shipping & Returns</h2>
<p>Learn about our shipping options, delivery times, and return policy.</p>

<h3>Shipping Information</h3>
<h4>Standard Shipping</h4>
<p>Delivery within 3-5 business days. Free on orders over R1500.</p>

<h4>Express Shipping</h4>
<p>Delivery within 1-2 business days. R150 for orders under R1500.</p>

<h4>International Shipping</h4>
<p>Delivery within 7-14 business days. Rates calculated at checkout.</p>

<h3>Return Policy</h3>
<p>We accept returns within 30 days of purchase for items that are:</p>
<ul>
<li>Unworn and unwashed</li>
<li>In their original condition with all tags attached</li>
<li>In their original packaging</li>
</ul>

<h4>How to Return an Item</h4>
<ol>
<li>Contact us through our contact form or email</li>
<li>Include your order number and reason for return</li>
<li>Pack the item securely in its original packaging</li>
<li>Ship to: [Return Address]</li>
</ol>

<h4>Refunds</h4>
<p>Refunds will be processed within 5-7 business days after we receive your return. Refunds will be issued to the original payment method.</p>

<h4>Exchanges</h4>
<p>If you would like to exchange an item, please follow the same return process. We will process your exchange once we receive the returned item.</p>

<h3>Damaged or Defective Items</h3>
<p>If you receive a damaged or defective item, please contact us immediately. We will arrange for a replacement or full refund at no cost to you.</p>

<h3>Contact Us</h3>
<p>If you have any questions about shipping or returns, please contact us through our contact form.</p>',
        'status' => 1
    ]
];

foreach ($sample_pages as $page) {
    // Check if page already exists
    $stmt = $conn->prepare("SELECT id FROM pages WHERE slug = ?");
    $stmt->bind_param("s", $page['slug']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Insert the page
        $stmt = $conn->prepare("INSERT INTO pages (title, slug, content, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $page['title'], $page['slug'], $page['content'], $page['status']);

        if ($stmt->execute()) {
            echo "✓ Sample page '{$page['title']}' created<br>";
        } else {
            echo "✗ Error creating page '{$page['title']}': " . $conn->error . "<br>";
        }
    } else {
        echo "✓ Sample page '{$page['title']}' already exists<br>";
    }
    $stmt->close();
}

// Create homepage_sections table if it doesn't exist
$create_homepage_table = "
CREATE TABLE IF NOT EXISTS `homepage_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_key` varchar(50) NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `content` text,
  `button_text` varchar(100) DEFAULT NULL,
  `button_url` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `background_color` varchar(20) DEFAULT NULL,
  `text_color` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_key` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($create_homepage_table)) {
    echo "✓ Homepage sections table created or already exists<br>";
} else {
    echo "✗ Error creating homepage sections table: " . $conn->error . "<br>";
}

// Check if settings table exists and create it if not
$create_settings_table = "
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($create_settings_table)) {
    echo "✓ Settings table created or already exists<br>";
} else {
    echo "✗ Error creating settings table: " . $conn->error . "<br>";
}

// Insert default settings if they don't exist
$default_settings = [
    ['store_name', 'Mossé Luxe'],
    ['store_email', 'info@mosseluxe.com'],
    ['store_phone', '+27 12 345 6789'],
    ['store_address', '123 Fashion Street, Cape Town, South Africa']
];

foreach ($default_settings as $setting) {
    $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->bind_param("ss", $setting[0], $setting[1]);

    if ($stmt->execute()) {
        echo "✓ Default setting '{$setting[0]}' created or already exists<br>";
    } else {
        echo "✗ Error creating setting '{$setting[0]}': " . $conn->error . "<br>";
    }
    $stmt->close();
}

$conn->close();

echo "<br><strong>Database update completed!</strong><br>";
echo "<a href='admin/dashboard.php'>Go to Admin Dashboard</a>";
?>
