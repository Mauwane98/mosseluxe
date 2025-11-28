<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();
$conn->query('ALTER TABLE users ADD COLUMN role ENUM("user","admin") NOT NULL DEFAULT "user"');
echo 'Role column added.\n';
?>
