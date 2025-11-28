<?php
// Fix file permissions for sensitive files
chmod('includes/config.php', 0600);
chmod('.env', 0600);
chmod('includes/db_connect.php', 0600);
echo "Permissions fixed.";
?>
