<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
$conn = get_db_connection();

$active_page = 'customers';
$page_title = 'Manage Customers';

// Fetch all users with the 'user' role
$customers = [];
$sql = "SELECT id, name, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

$conn->close();
?>
<?php include '../includes/admin_page_header.php'; ?>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../includes/admin_header.php'; ?>

    <div class="d-flex justify-content-end mb-3">
        <a href="export_customers.php" class="btn btn-outline-success" target="_blank">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export All Customers to CSV
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Date Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($customers)): ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo $customer['id']; ?></td>
                                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <a href="orders.php?search_query=<?php echo urlencode($customer['name']); ?>" class="btn btn-sm btn-outline-dark" title="View Orders">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No customers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>