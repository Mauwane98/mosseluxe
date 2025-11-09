<?php
// Redirect to the canonical dashboard page. Some admin pages include
// `admin/header.php` / `admin/footer.php` while others use
// `includes/admin_page_header.php`. To keep the admin entrypoint
// consistent, forward `index.php` to `dashboard.php` which handles
// authentication and layout.
header('Location: dashboard.php');
exit();
?>