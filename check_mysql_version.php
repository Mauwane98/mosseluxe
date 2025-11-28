<?php
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    echo 'MySQL version: ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
