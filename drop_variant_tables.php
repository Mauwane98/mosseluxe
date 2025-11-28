<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

$tables = ['product_variants', 'variant_options'];

foreach ($tables as $t) {
    $conn->query("DROP TABLE IF EXISTS `$t`");
    echo "Dropped $t if existed\n";
}
?>
