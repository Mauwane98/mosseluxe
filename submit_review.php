<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $rating = filter_var($_POST['rating'], FILTER_VALIDATE_INT);
    $review_text = trim($_POST['review_text'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // CSRF validation
    if (!verify_csrf_token($csrf_token)) {
        $response = ['success' => false, 'message' => 'Invalid security token.'];
        echo json_encode($response);
        exit;
    }

    // Check if user is logged in
    if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || !isset($_SESSION['user_id'])) {
        $response = ['success' => false, 'message' => 'You must be logged in to submit a review.'];
        echo json_encode($response);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Validate inputs
    if (!$product_id || $product_id <= 0) {
        $response = ['success' => false, 'message' => 'Invalid product.'];
        echo json_encode($response);
        exit;
    }

    if (!$rating || $rating < 1 || $rating > 5) {
        $response = ['success' => false, 'message' => 'Please select a rating between 1 and 5 stars.'];
        echo json_encode($response);
        exit;
    }

    if (empty($review_text) || strlen($review_text) < 10) {
        $response = ['success' => false, 'message' => 'Please write a review with at least 10 characters.'];
        echo json_encode($response);
        exit;
    }

    $conn = get_db_connection();

    // Check if product exists and is active
    $product_check = $conn->prepare("SELECT id FROM products WHERE id = ? AND status = 1");
    $product_check->bind_param("i", $product_id);
    $product_check->execute();
    if ($product_check->get_result()->num_rows == 0) {
        $response = ['success' => false, 'message' => 'Product not found or not available.'];
        echo json_encode($response);
        exit;
    }
    $product_check->close();

    // Check if user already reviewed this product
    $review_check = $conn->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?");
    $review_check->bind_param("ii", $product_id, $user_id);
    $review_check->execute();
    if ($review_check->get_result()->num_rows > 0) {
        $response = ['success' => false, 'message' => 'You have already submitted a review for this product.'];
        $review_check->close();
        echo json_encode($response);
        exit;
    }
    $review_check->close();

    // Insert the review
    $insert_sql = "INSERT INTO product_reviews (product_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)";
    if ($stmt = $conn->prepare($insert_sql)) {
        $stmt->bind_param("iiis", $product_id, $user_id, $rating, $review_text);
        if ($stmt->execute()) {
            $review_id = $conn->insert_id;

            // Award loyalty points for leaving a review
            process_review_loyalty_points($user_id, $review_id);

            $response = [
                'success' => true,
                'message' => 'Your review has been submitted successfully! It will be published after approval by our team.'
            ];
        } else {
            $response = ['success' => false, 'message' => 'Failed to submit review. Please try again.'];
        }
        $stmt->close();
    } else {
        $response = ['success' => false, 'message' => 'Database error. Please try again.'];
    }

    $conn->close();
}

echo json_encode($response);
?>
