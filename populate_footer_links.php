<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Define footer links
$footer_links = [
    ['link_group' => 'Company', 'title' => 'About Us', 'url' => '/about.php', 'sort_order' => 1],
    ['link_group' => 'Company', 'title' => 'Careers', 'url' => '/page.php?slug=careers', 'sort_order' => 2],
    ['link_group' => 'Company', 'title' => 'FAQ', 'url' => '/page.php?slug=faq', 'sort_order' => 3],
    ['link_group' => 'Help', 'title' => 'Contact', 'url' => '/contact.php', 'sort_order' => 1],
    ['link_group' => 'Help', 'title' => 'Shipping', 'url' => '/shipping-returns.php', 'sort_order' => 2],
    ['link_group' => 'Help', 'title' => 'Track Order', 'url' => '/track_order.php', 'sort_order' => 3],
    ['link_group' => 'Legal', 'title' => 'Privacy Policy', 'url' => '/privacy-policy.php', 'sort_order' => 1],
    ['link_group' => 'Legal', 'title' => 'Terms of Service', 'url' => '/terms-of-service.php', 'sort_order' => 2],
    ['link_group' => 'Legal', 'title' => 'Returns', 'url' => '/returns.php', 'sort_order' => 3],
    ['link_group' => 'Follow Us', 'title' => 'Instagram', 'url' => '#', 'sort_order' => 1],
    ['link_group' => 'Follow Us', 'title' => 'Facebook', 'url' => '#', 'sort_order' => 2],
    ['link_group' => 'Follow Us', 'title' => 'Twitter', 'url' => '#', 'sort_order' => 3]
];

foreach ($footer_links as $link) {
    // Check if exists by title and group
    $stmt = $conn->prepare("SELECT id FROM footer_links WHERE link_group = ? AND title = ?");
    $stmt->bind_param("ss", $link['link_group'], $link['title']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $ins_sql = "INSERT INTO footer_links (link_group, title, url, sort_order, status) VALUES (?, ?, ?, ?, 1)";
        $ins_stmt = $conn->prepare($ins_sql);
        $ins_stmt->bind_param("sssi", $link['link_group'], $link['title'], $link['url'], $link['sort_order']);
        if ($ins_stmt->execute()) {
            echo "Added footer link: " . $link['link_group'] . " - " . $link['title'] . "\n";
        } else {
            echo "Error adding footer link: " . $link['title'] . " - " . $ins_stmt->error . "\n";
        }
        $ins_stmt->close();
    } else {
        echo "Footer link '" . $link['link_group'] . " - " . $link['title'] . "' already exists.\n";
    }
    $stmt->close();
}

echo "Populate footer links script complete.\n";
?>
