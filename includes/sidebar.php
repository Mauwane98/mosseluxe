<?php
$current_file = basename($_SERVER['PHP_SELF']);
$active_page = $active_page ?? ''; // Use the active page variable set by the parent page
require_once __DIR__ . '/config.php'; // Ensure SITE_URL is available
?>
<div class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php">
            <img src="<?php echo SITE_URL; ?>assets/images/logo.png" alt="Mossé Luxe Admin Logo" style="max-width: 80%; height: auto; max-height: 60px;">
        </a>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo ($active_page === 'dashboard') ? 'active' : ''; ?>" href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a class="nav-link <?php echo ($active_page === 'orders') ? 'active' : ''; ?>" href="orders.php"><i class="bi bi-receipt"></i> Orders</a>
        <a class="nav-link <?php echo ($active_page === 'products') ? 'active' : ''; ?>" href="products.php"><i class="bi bi-box-seam"></i> Products</a>
        <a class="nav-link <?php echo ($active_page === 'categories') ? 'active' : ''; ?>" href="categories.php"><i class="bi bi-tags"></i> Categories</a>
        <a class="nav-link <?php echo ($active_page === 'customers') ? 'active' : ''; ?>" href="customers.php"><i class="bi bi-people-fill"></i> Customers</a>
        <a class="nav-link <?php echo ($active_page === 'launching_soon') ? 'active' : ''; ?>" href="launching_soon.php"><i class="bi bi-clock-history"></i> Launching Soon</a>
        <a class="nav-link <?php echo ($active_page === 'discounts') ? 'active' : ''; ?>" href="manage_discounts.php"><i class="bi bi-percent"></i> Discounts</a>
        <a class="nav-link <?php echo ($active_page === 'reviews') ? 'active' : ''; ?>" href="manage_reviews.php"><i class="bi bi-star-half"></i> Reviews</a>
        <a class="nav-link <?php echo ($active_page === 'messages') ? 'active' : ''; ?>" href="messages.php"><i class="bi bi-envelope-paper-fill"></i> Messages</a>
        <a class="nav-link <?php echo ($active_page === 'subscriptions') ? 'active' : ''; ?>" href="manage_subscriptions.php"><i class="bi bi-bell-fill"></i> Subscriptions</a>
        <a class="nav-link <?php echo ($active_page === 'reports') ? 'active' : ''; ?>" href="sales_report.php"><i class="bi bi-bar-chart-line-fill"></i> Reports</a>
        <hr class="text-secondary">
        <a class="nav-link <?php echo ($active_page === 'manage_admins') ? 'active' : ''; ?>" href="manage_admins.php"><i class="bi bi-person-gear"></i> Manage Admins</a>
        <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
    </nav>
</div>