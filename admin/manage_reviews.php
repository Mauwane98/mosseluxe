<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php';
$conn = get_db_connection();

$active_page = 'reviews';
$csrf_token = generate_csrf_token();
$message = '';
$error = '';

// Handle review actions (approve, unapprove, delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $review_id = filter_var($_POST['review_id'], FILTER_SANITIZE_NUMBER_INT);
        $action = $_POST['action'] ?? '';

        if ($review_id) {
            if ($action === 'approve') {
                $sql = "UPDATE product_reviews SET is_approved = 1 WHERE id = ?";
            } elseif ($action === 'unapprove') {
                $sql = "UPDATE product_reviews SET is_approved = 0 WHERE id = ?";
            } elseif ($action === 'delete') {
                $sql = "DELETE FROM product_reviews WHERE id = ?";
            }

            if (isset($sql) && ($stmt = $conn->prepare($sql))) {
                $stmt->bind_param("i", $review_id);
                if ($stmt->execute()) {
                    header("Location: manage_reviews.php?success=true");
                    exit();
                } else {
                    $error = "Action failed. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}

// Fetch all reviews
$reviews = [];
$sql_reviews = "SELECT r.id, r.rating, r.review_text, r.is_approved, r.created_at, p.name as product_name, u.name as user_name 
                FROM product_reviews r
                JOIN products p ON r.product_id = p.id
                JOIN users u ON r.user_id = u.id
                ORDER BY r.created_at DESC";
if ($result = $conn->query($sql_reviews)) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

if (isset($_GET['success']) && $_GET['success'] == 'true') {
    $message = "Review status updated successfully.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - Mossé Luxe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php 
    $page_title = 'Manage Product Reviews';
    include '../includes/admin_header.php'; 
    ?>

    <?php if(!empty($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
    <?php if(!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi <?php echo ($i <= $review['rating']) ? 'bi-star-fill text-dark' : 'bi-star'; ?>"></i>
                                <?php endfor; ?>
                            </td>
                            <td class="review-text" title="<?php echo htmlspecialchars($review['review_text']); ?>">
                                <?php echo htmlspecialchars($review['review_text']); ?>
                            </td>
                            <td><?php echo date('d M Y', strtotime($review['created_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo $review['is_approved'] ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                    <?php echo $review['is_approved'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            </td>
                            <td>
                                <form action="manage_reviews.php" method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <?php if ($review['is_approved']): ?>
                                        <input type="hidden" name="action" value="unapprove">
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Unapprove"><i class="bi bi-x-circle"></i></button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Approve"><i class="bi bi-check-circle"></i></button>
                                    <?php endif; ?>
                                </form>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteReviewModal<?php echo $review['id']; ?>" title="Delete"><i class="bi bi-trash-fill"></i></button>

                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteReviewModal<?php echo $review['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Deletion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">Are you sure you want to permanently delete this review?</div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form action="manage_reviews.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No reviews found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>