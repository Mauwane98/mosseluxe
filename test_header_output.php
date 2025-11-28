<?php
require_once 'includes/bootstrap.php';

// Simulate the logic from header.php to get whatsapp_number
$whatsapp_settings_early = [];
$settings_query_early = "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'whatsapp_%'";
$settings_stmt_early = get_db_connection()->prepare($settings_query_early);
if ($settings_stmt_early) {
    $settings_stmt_early->execute();
    $result_early = $settings_stmt_early->get_result();
    while ($row = $result_early->fetch_assoc()) {
        $whatsapp_settings_early[$row['setting_key']] = $row['setting_value'];
    }
    $settings_stmt_early->close();
}
$whatsapp_number = $whatsapp_settings_early['whatsapp_number'] ?? null;

echo "--- DEBUGGING SCRIPT BLOCK ---\n\n";
echo "window.SITE_URL = \"" . SITE_URL . "\";\n";
echo "window.csrfToken = \"" . ($_SESSION['csrf_token'] ?? '') . "\";\n";
echo "window.whatsappNumber = \"" . ($whatsapp_number ? ltrim($whatsapp_number, '+') : '') . "\";\n";

echo "\n--- VARIABLE ANALYSIS ---\n";
echo "SITE_URL type: " . gettype(SITE_URL) . "\n";
echo "csrf_token type: " . gettype($_SESSION['csrf_token'] ?? null) . "\n";
echo "whatsapp_number type: " . gettype($whatsapp_number) . "\n";
echo "whatsapp_number value: '" . $whatsapp_number . "'\n";
?>
