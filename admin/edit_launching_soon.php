<?php
// Start session and include admin authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
require_once '../includes/config.php';
require_once '../includes/image_service.php';
$conn = get_db_connection();

$csrf_token = generate_csrf_token();
$edit_item_error = '';
$success_message = '';
$item_id = null;
$item_data = null;

// Check if item ID is provided in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $item_id = filter_var(trim($_GET['id']), FILTER_SANITIZE_NUMBER_INT);

    // Fetch item details from the database
    $sql_fetch_item = "SELECT id, name, image, status FROM launching_soon WHERE id = ?";
    if ($stmt_fetch = $conn->prepare($sql_fetch_item)) {
        $stmt_fetch->bind_param("i", $param_id);
        $param_id = $item_id;

        if ($stmt_fetch->execute()) {
            $result_fetch = $stmt_fetch->get_result();
            if ($row_fetch = $result_fetch->fetch_assoc()) {
                $item_data = $row_fetch;
            } else {
                header("Location: launching_soon.php?error=item_not_found");
                exit();
            }
        } else {
            header("Location: launching_soon.php?error=fetch_failed");
            exit();
        }
        $stmt_fetch->close();
    } else {
        header("Location: launching_soon.php?error=prepare_failed");
        exit();
    }
} else {
    header("Location: launching_soon.php?error=no_id");
    exit();
}

// Handle form submission for updating item
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $edit_item_error = 'Invalid CSRF token. Please try again.';
    } else {
        // Sanitize and validate inputs
        $name = trim($_POST["name"]);
        $status = filter_var(trim($_POST["status"]), FILTER_SANITIZE_NUMBER_INT);
        
        // New Image upload handling using ImageService
        $image_path = $item_data['image']; // Keep old image if new one is not uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $new_image_path = ImageService::processUpload($_FILES['image'], '../assets/images/', PRODUCT_IMAGE_WIDTH, PRODUCT_IMAGE_HEIGHT, $edit_item_error);
            if ($new_image_path) {
                $image_path = $new_image_path;
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            $edit_item_error = 'There was an error with the new image upload.';
        }

        if (empty($name) || !isset($status)) {
            $edit_item_error = 'Please fill out all required fields.';
        } else {
            // Prepare an update statement
            $sql_update_item = "UPDATE launching_soon SET name = ?, image = ?, status = ? WHERE id = ?";
            
            if ($stmt_update = $conn->prepare($sql_update_item)) {
                $stmt_update->bind_param("ssii", $param_name, $param_image, $param_status, $param_id);

                // Set parameters
                $param_name = $name;
                $param_image = $image_path;
                $param_status = $status;
                $param_id = $item_id;

                if ($stmt_update->execute()) {
                    header("Location: launching_soon.php?success=updated");
                    exit();
                } else {
                    $edit_item_error = 'Something went wrong. Please try again later.';
                }
                $stmt_update->close();
            } else {
                $edit_item_error = 'Error preparing statement. Please try again later.';
            }
        }
    }
}

$active_page = 'launching_soon';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit "Launching Soon" Item - Mossé Luxe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #1a1a1a; color: #f8f9fa; }
        .gold-text { color: #C5A572; }
        .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 250px; padding: 20px; background-color: #000; border-right: 1px solid #333; }
        .sidebar-header { text-align: center; margin-bottom: 30px; }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; }
        .nav-link { color: #adb5bd; font-size: 1.05rem; padding: 10px 15px; margin: 5px 0; border-radius: 0.25rem; }
        .nav-link:hover, .nav-link.active { background-color: #222; color: #fff; }
        .nav-link i { margin-right: 10px; }
        .main-content { margin-left: 250px; padding: 40px; }
        .card { background-color: #000; border: 1px solid #333; }
        .form-control, .form-select { background-color: #222; border-color: #444; color: #fff; }
        .form-control:focus, .form-select:focus { background-color: #222; border-color: #C5A572; color: #fff; box-shadow: none; }
        .btn-gold { background-color: #C5A572; color: #000; border: 1px solid #C5A572; }
        .btn-gold:hover { background-color: #d4b38a; border-color: #d4b38a; }
        .item-image-preview { max-width: 150px; height: auto; margin-top: 10px; border-radius: 0.25rem; border: 1px solid #444; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <header class="d-flex justify-content-between align-items-center mb-4"><h1>Edit "Launching Soon" Item</h1></header>

    <div class="card p-4">
        <div class="card-body">
            <?php if (!empty($edit_item_error)): ?>
                <div class="alert alert-danger"><?php echo $edit_item_error; ?></div>
            <?php elseif (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($item_data): ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $item_id; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($item_data['name']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image">
                        <img src="../<?php echo htmlspecialchars($item_data['image']); ?>" alt="Item Preview" class="item-image-preview">
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="1" <?php echo ($item_data['status'] == 1) ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo ($item_data['status'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary-dark">Update Item</button>
                    <a href="launching_soon.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
            <?php else: ?>
                <p class="text-center text-muted">Item data could not be loaded.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
