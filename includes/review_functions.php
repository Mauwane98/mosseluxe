<?php
/**
 * Product Review Functions
 * Handles all review-related operations
 */

/**
 * Add a new product review
 */
function addProductReview($conn, $data) {
    $product_id = (int)$data['product_id'];
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $guest_name = $user_id ? null : trim($data['guest_name'] ?? '');
    $guest_email = $user_id ? null : trim($data['guest_email'] ?? '');
    $rating = (int)$data['rating'];
    $title = trim($data['title']);
    $review_text = trim($data['review_text']);
    
    // Validate
    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
    }
    
    if (empty($title) || empty($review_text)) {
        return ['success' => false, 'message' => 'Title and review text are required'];
    }
    
    if (!$user_id && (empty($guest_name) || empty($guest_email))) {
        return ['success' => false, 'message' => 'Name and email are required for guest reviews'];
    }
    
    // Check if user has purchased this product (for verified badge)
    $verified_purchase = false;
    if ($user_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items oi 
            INNER JOIN orders o ON oi.order_id = o.id 
            WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $verified_purchase = $result['count'] > 0;
        $stmt->close();
    }
    
    // Insert review
    $stmt = $conn->prepare("INSERT INTO product_reviews 
        (product_id, user_id, guest_name, guest_email, rating, title, review_text, verified_purchase, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iisssssi", $product_id, $user_id, $guest_name, $guest_email, $rating, $title, $review_text, $verified_purchase);
    
    if ($stmt->execute()) {
        $review_id = $stmt->insert_id;
        $stmt->close();
        
        // Update product rating
        updateProductRating($conn, $product_id);
        
        return [
            'success' => true, 
            'message' => 'Review submitted successfully! It will be published after moderation.',
            'review_id' => $review_id
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to submit review'];
}

/**
 * Upload review photos
 */
function uploadReviewPhotos($conn, $review_id, $files) {
    $upload_dir = __DIR__ . '/../assets/images/reviews/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $uploaded = [];
    foreach ($files['tmp_name'] as $key => $tmp_name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $file_ext = strtolower(pathinfo($files['name'][$key], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($file_ext, $allowed)) {
                $new_filename = uniqid('review_') . '.' . $file_ext;
                $destination = $upload_dir . $new_filename;
                
                if (move_uploaded_file($tmp_name, $destination)) {
                    $photo_path = 'assets/images/reviews/' . $new_filename;
                    $stmt = $conn->prepare("INSERT INTO review_photos (review_id, photo_path) VALUES (?, ?)");
                    $stmt->bind_param("is", $review_id, $photo_path);
                    $stmt->execute();
                    $stmt->close();
                    $uploaded[] = $photo_path;
                }
            }
        }
    }
    
    return $uploaded;
}

/**
 * Get reviews for a product
 */
function getProductReviews($conn, $product_id, $limit = 10, $offset = 0, $status = 'approved') {
    $stmt = $conn->prepare("SELECT r.*, 
        COALESCE(u.name, r.guest_name) as reviewer_name,
        (SELECT GROUP_CONCAT(photo_path) FROM review_photos WHERE review_id = r.id) as photos
        FROM product_reviews r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ? AND r.status = ?
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?");
    $stmt->bind_param("isii", $product_id, $status, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $row['photos'] = $row['photos'] ? explode(',', $row['photos']) : [];
        $reviews[] = $row;
    }
    
    $stmt->close();
    return $reviews;
}

/**
 * Get review statistics for a product
 */
function getReviewStats($conn, $product_id) {
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as average_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
        FROM product_reviews
        WHERE product_id = ? AND status = 'approved'");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result;
}

/**
 * Update product average rating
 */
function updateProductRating($conn, $product_id) {
    $stmt = $conn->prepare("UPDATE products SET 
        average_rating = (SELECT AVG(rating) FROM product_reviews WHERE product_id = ? AND status = 'approved'),
        review_count = (SELECT COUNT(*) FROM product_reviews WHERE product_id = ? AND status = 'approved')
        WHERE id = ?");
    $stmt->bind_param("iii", $product_id, $product_id, $product_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Mark review as helpful
 */
function markReviewHelpful($conn, $review_id) {
    $stmt = $conn->prepare("UPDATE product_reviews SET helpful_count = helpful_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $review_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}
// No closing PHP tag - prevents accidental whitespace output