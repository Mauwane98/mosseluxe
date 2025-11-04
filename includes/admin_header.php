<?php
// includes/admin_header.php

// Get the current page name, default to 'Dashboard' if not set
$page_title = $page_title ?? 'Dashboard';

?>
<header class="admin-header">
    <button class="sidebar-toggler" id="sidebar-toggler"><i class="bi bi-list"></i></button>
    <h1 class="mb-0"><?php echo htmlspecialchars($page_title); ?></h1>
    <p class="text-muted small mb-0">Current time: <?php echo date("l, j F Y, g:i A"); ?> (SAST)</p>
</header>
