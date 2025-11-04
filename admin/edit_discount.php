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
$error = '';
$discount_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : 0;

if (!$discount_id) {
    header("Location: manage_discounts.php");
    exit();
}

// Fetch discount details
$stmt = $conn->prepare("SELECT * FROM discount_codes WHERE id = ?");
$stmt->bind_param("i", $discount_id);
$stmt->execute();
$result = $stmt->get_result();
$discount = $result->fetch_assoc();
$stmt->close();

if (!$discount) {
    header("Location: manage_discounts.php?error=not_found");
    exit();
}

// Handle form submission for updating the discount
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_discount'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $type = $_POST['type'];
        $value = filter_var($_POST['value'], FILTER_VALIDATE_FLOAT);
        $usage_limit = filter_var($_POST['usage_limit'], FILTER_VALIDATE_INT);
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

        if (empty($type) || $value === false || $usage_limit === false) {
            $error = "Please fill all fields with valid data.";
        } else {
            $sql = "UPDATE discount_codes SET type = ?, value = ?, usage_limit = ?, expires_at = ? WHERE id = ?";
            if ($stmt_update = $conn->prepare($sql)) {
                $stmt_update->bind_param("sdisi", $type, $value, $usage_limit, $expires_at, $discount_id);
                if ($stmt_update->execute()) {
                    header("Location: manage_discounts.php?success=updated");
                    exit();
                } else {
                    $error = "Failed to update discount code.";
                }
                $stmt_update->close();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Discount - Mossé Luxe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #1a1a1a; color: #f8f9fa; }
        .gold-text { color: #C5A572; }
        .main-content { margin-left: 250px; padding: 40px; }
        .card { background-color: #000; border: 1px solid #333; }
        .form-control, .form-select { background-color: #222; border-color: #444; color: #fff; }
        .form-control:focus, .form-select:focus { background-color: #222; border-color: #C5A572; color: #fff; box-shadow: none; }
        .btn-gold { background-color: #C5A572; color: #000; border: 1px solid #C5A572; }
        .btn-gold:hover { background-color: #d4b38a; border-color: #d4b38a; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Discount Code</h1>
    </header>

    <div class="card p-4">
        <div class="card-body">
            <?php if(!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <form action="edit_discount.php?id=<?php echo $discount_id; ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="mb-3">
                    <label for="code" class="form-label">Discount Code</label>
                    <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($discount['code']); ?>" readonly disabled>
                </div>
                <div class="mb-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="percentage" <?php if($discount['type'] === 'percentage') echo 'selected'; ?>>Percentage (%)</option>
                        <option value="fixed" <?php if($discount['type'] === 'fixed') echo 'selected'; ?>>Fixed Amount (R)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="value" class="form-label">Value</label>
                    <input type="number" class="form-control" id="value" name="value" step="0.01" required value="<?php echo htmlspecialchars($discount['value']); ?>">
                </div>
                <div class="mb-3">
                    <label for="usage_limit" class="form-label">Usage Limit</label>
                    <input type="number" class="form-control" id="usage_limit" name="usage_limit" required value="<?php echo htmlspecialchars($discount['usage_limit']); ?>">
                </div>
                <div class="mb-3">
                    <label for="expires_at" class="form-label">Expiry Date (Optional)</label>
                    <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" value="<?php echo $discount['expires_at'] ? date('Y-m-d\TH:i', strtotime($discount['expires_at'])) : ''; ?>">
                </div>
                <button type="submit" name="update_discount" class="btn btn-primary-dark mt-3">Update Discount</button>
                <a href="manage_discounts.php" class="btn btn-outline-secondary mt-3 ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>