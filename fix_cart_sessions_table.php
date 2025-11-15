<?php
require_once 'includes/bootstrap.php';

$conn = get_db_connection();

$sql = "CREATE TABLE IF NOT EXISTS cart_sessions (
    id int(11) NOT NULL AUTO_INCREMENT,
    session_id varchar(255) NOT NULL,
    product_id int(11) NOT NULL,
    quantity int(11) NOT NULL DEFAULT 1,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY session_product (session_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql)) {
    echo "✓ cart_sessions table created successfully\n";
} else {
    echo "✗ Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
