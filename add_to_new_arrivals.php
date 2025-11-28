<?php
require_once 'includes/bootstrap.php';

$conn = get_db_connection();

// Get the latest product
$product_id = 26; // The product you just uploaded

// Check if it's already in new arrivals
$check = $conn->query("SELECT * FROM new_arrivals WHERE product_id = $product_id");

if ($check->num_rows > 0) {
    echo "Product is already in new arrivals!\n";
} else {
    // Get the next display order
    $order_result = $conn->query("SELECT MAX(display_order) as max_order FROM new_arrivals");
    $order_row = $order_result->fetch_assoc();
    $next_order = ($order_row['max_order'] ?? 0) + 1;
    
    // Add to new arrivals
    $stmt = $conn->prepare("INSERT INTO new_arrivals (product_id, display_order) VALUES (?, ?)");
    $stmt->bind_param("ii", $product_id, $next_order);
    
    if ($stmt->execute()) {
        echo "✓ Product added to New Arrivals!\n";
        echo "Product ID: $product_id\n";
        echo "Display Order: $next_order\n";
    } else {
        echo "✗ Error: " . $stmt->error . "\n";
    }
}
?>
