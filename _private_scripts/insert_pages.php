<?php
require_once '../includes/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pages data simplified
$pages = [
    ['About Us', 'about', '<h1>About Us Title</h1><p>This is the about us content.</p>'],
    ['Contact', 'contact', '<h1>Contact Details</h1><p>Here are our contact details.</p>'],  
    ['FAQ', 'faq', '<h1>Frequently Asked Questions</h1><p>Here are the FAQs.</p>'],
    ['Privacy Policy', 'privacy-policy', '<h1>Privacy Policy</h1><p>This is our privacy policy.</p>'],
    ['Terms of Service', 'terms-of-service', '<h1>Terms of Service</h1><p>These are our terms.</p>'],
    ['Shipping & Returns', 'shipping-returns', '<h1>Shipping & Returns</h1><p>Shipping and returns information.</p>'],
    ['Careers', 'careers', '<h1>Careers</h1><p>Join our team.</p>']
];

foreach ($pages as $page) {
    $stmt = $conn->prepare("INSERT INTO pages (title, slug, content, status) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE content = VALUES(content)");
    $stmt->bind_param("sss", $page[0], $page[1], $page[2]);
    if ($stmt->execute()) {
        echo "Inserted/Updated: {$page[0]}\n";
    } else {
        echo "Error: " . $stmt->error . "\n";
    }
    $stmt->close();
}

$conn->close();
echo "Pages inserted successfully.\n";
?>
