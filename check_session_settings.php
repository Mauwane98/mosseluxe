<?php
require_once 'includes/bootstrap.php';

echo "<h1>Session Configuration Check</h1>";

echo "<p>Session save path: " . session_save_path() . "</p>";
echo "<p>Session status: " . session_status() . "</p>";
echo "<p>Session name: " . session_name() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
// Removed debug output for security
?>
