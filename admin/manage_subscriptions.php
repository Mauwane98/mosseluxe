<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
$conn = get_db_connection();

$active_page = 'subscriptions';
$csrf_token = generate_csrf_token();
$message = '';
$error = '';

// Handle subscription deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_subscription'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $subscription_id = filter_var($_POST['subscription_id'], FILTER_SANITIZE_NUMBER_INT);
        if ($subscription_id) {
            $sql = "DELETE FROM stock_notifications WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $subscription_id);
                if ($stmt->execute()) {
                    header("Location: manage_subscriptions.php?success=deleted");
                    exit();
                } else {
                    $error = "Failed to delete subscription.";
                }
                $stmt->close();
            }
        }
    }
}

// Fetch all pending stock notification subscriptions
$subscriptions = [];
$sql_subscriptions = "SELECT sn.id, sn.email, sn.created_at, p.name as product_name 
                      FROM stock_notifications sn
                      JOIN products p ON sn.product_id = p.id
                      WHERE sn.notified_at IS NULL
                      ORDER BY sn.created_at DESC";
if ($result = $conn->query($sql_subscriptions)) {
    while ($row = $result->fetch_assoc()) {
        $subscriptions[] = $row;
    }
}

if (isset($_GET['success']) && $_GET['success'] == 'deleted') {
    $message = "Subscription removed successfully.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subscriptions - Mossé Luxe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php 
    $page_title = 'Stock Notification Subscriptions';
    include '../includes/admin_header.php'; 
    ?>

    <?php if(!empty($message)): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if(!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Subscriber Email</th>
                    <th>Date Subscribed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($subscriptions)): ?>
                    <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($sub['email']); ?></td>
                            <td><?php echo date('d M Y, H:i', strtotime($sub['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteSubModal<?php echo $sub['id']; ?>" title="Delete">
                                    <i class="bi bi-trash-fill"></i>
                                </button>

                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteSubModal<?php echo $sub['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Deletion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete the subscription for <strong><?php echo htmlspecialchars($sub['email']); ?></strong>?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form action="manage_subscriptions.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                    <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                                                    <button type="submit" name="delete_subscription" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No pending stock notification subscriptions.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>