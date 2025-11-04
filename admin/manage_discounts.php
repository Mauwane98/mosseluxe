<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
$conn = get_db_connection();

$active_page = 'discounts';
$csrf_token = generate_csrf_token();
$message = '';
$error = '';

// Handle form submission for adding a new discount
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_discount'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $code = strtoupper(trim($_POST['code']));
        $type = $_POST['type'];
        $value = filter_var($_POST['value'], FILTER_VALIDATE_FLOAT);
        $usage_limit = filter_var($_POST['usage_limit'], FILTER_VALIDATE_INT);
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

        if (empty($code) || empty($type) || $value === false || $usage_limit === false) {
            $error = "Please fill all fields with valid data.";
        } else {
            $sql = "INSERT INTO discount_codes (code, type, value, usage_limit, expires_at) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssdis", $code, $type, $value, $usage_limit, $expires_at);
                if ($stmt->execute()) {
                    $message = "Discount code created successfully!";
                } else {
                    $error = "Failed to create discount code. It might already exist.";
                }
                $stmt->close();
            }
        }
    }
}

// Handle toggling active status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $discount_id = filter_var($_POST['discount_id'], FILTER_SANITIZE_NUMBER_INT);
        $current_status = filter_var($_POST['current_status'], FILTER_SANITIZE_NUMBER_INT);
        $new_status = $current_status == 1 ? 0 : 1;

        $sql_toggle = "UPDATE discount_codes SET is_active = ? WHERE id = ?";
        if ($stmt_toggle = $conn->prepare($sql_toggle)) {
            $stmt_toggle->bind_param("ii", $new_status, $discount_id);
            if ($stmt_toggle->execute()) {
                header("Location: manage_discounts.php?success=status_updated");
                exit();
            }
        }
        $error = "Failed to update status.";
    }
}

// Fetch all discount codes
$discounts = [];
$sql_discounts = "SELECT * FROM discount_codes ORDER BY id DESC";
if ($result = $conn->query($sql_discounts)) {
    while ($row = $result->fetch_assoc()) {
        $discounts[] = $row;
    }
}

// Check for success/error messages from redirects
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'updated') $message = "Discount code updated successfully!";
    if ($_GET['success'] == 'status_updated') $message = "Discount status updated successfully!";
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] == 'not_found') $error = "Discount code not found.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Discounts - Mossé Luxe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php 
    $page_title = 'Manage Discount Codes';
    include '../includes/admin_header.php'; 
    ?>

    <?php if(!empty($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
    <?php if(!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card p-4">
                <h5 class="gold-text">Create New Discount</h5>
                <form action="manage_discounts.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="code" class="form-label">Discount Code</label>
                        <input type="text" class="form-control" id="code" name="code" required style="text-transform:uppercase">
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (R)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="value" class="form-label">Value</label>
                        <input type="number" class="form-control" id="value" name="value" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="usage_limit" class="form-label">Usage Limit</label>
                        <input type="number" class="form-control" id="usage_limit" name="usage_limit" required value="1">
                    </div>
                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Expiry Date (Optional)</label>
                        <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                    </div>
                    <button type="submit" name="add_discount" class="btn btn-primary-dark w-100">Create Discount</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Usage</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($discounts)): ?>
                            <?php foreach ($discounts as $discount): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($discount['code']); ?></td>
                                    <td><?php echo ucfirst($discount['type']); ?></td>
                                    <td>
                                        <?php 
                                        if ($discount['type'] === 'percentage') {
                                            echo $discount['value'] . '%';
                                        } else {
                                            echo 'R ' . number_format($discount['value'], 2);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $discount['usage_count'] . ' / ' . $discount['usage_limit']; ?></td>
                                    <td><?php echo $discount['expires_at'] ? date('d M Y', strtotime($discount['expires_at'])) : 'Never'; ?></td>
                                    <td>
                                        <?php
                                        $is_expired = $discount['expires_at'] && new DateTime() > new DateTime($discount['expires_at']);
                                        $is_used_up = $discount['usage_count'] >= $discount['usage_limit'];
                                        $is_active = $discount['is_active'] && !$is_expired && !$is_used_up;
                                        ?>
                                        <span class="badge <?php echo $is_active ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_discount.php?id=<?php echo $discount['id']; ?>" class="btn btn-sm btn-outline-dark" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <form action="manage_discounts.php" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="discount_id" value="<?php echo $discount['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $discount['is_active']; ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $discount['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $discount['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="bi <?php echo $discount['is_active'] ? 'bi-toggle-on' : 'bi-toggle-off'; ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No discount codes found.</td></tr>
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