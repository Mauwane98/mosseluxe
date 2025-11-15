<?php
$pageTitle = "Product Details - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';
$conn = get_db_connection();
require_once 'includes/header.php';

$product = null;
if (isset($_GET['id'])) {
    $product_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
    $sql = "SELECT id, name, description, price, sale_price, image, stock, is_featured, is_coming_soon, is_bestseller, is_new FROM products WHERE id = ? AND status = 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
        }
        $stmt->close();
    }

    // Fetch additional product images
    if ($product) {
        $images_sql = "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC";
        if ($stmt_imgs = $conn->prepare($images_sql)) {
            $stmt_imgs->bind_param("i", $product_id);
            $stmt_imgs->execute();
            $result_imgs = $stmt_imgs->get_result();
            $product_images = [];
            while ($row = $result_imgs->fetch_assoc()) {
                $product_images[] = $row['image_path'];
            }
            $stmt_imgs->close();
        }

        // Add to recently viewed (session-based)
        if (!isset($_SESSION['recently_viewed'])) {
            $_SESSION['recently_viewed'] = [];
        }
        // Remove if already exists, then add to beginning
        $_SESSION['recently_viewed'] = array_filter($_SESSION['recently_viewed'], function($id) use ($product_id) {
            return $id != $product_id;
        });
        array_unshift($_SESSION['recently_viewed'], $product_id);
        // Keep only last 10
        $_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 10);
    }
}


?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <?php if ($product): ?>
            <div class="grid md:grid-cols-2 gap-8 lg:gap-16">
                <!-- Product Image Gallery -->
                <div class="space-y-4">
                    <!-- Main Image -->
                    <div class="aspect-w-1 aspect-h-1 bg-neutral-100 rounded-md overflow-hidden">
                        <img id="main-product-image"
                            src="<?php echo SITE_URL . htmlspecialchars($product['image']); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="w-full h-full object-contain p-4"
                            onerror="this.src='https://placehold.co/600x600/f1f1f1/000000?text=Mossé+Luxe'"
                        >
                    </div>
                    <!-- Thumbnails -->
                    <?php if (!empty($product_images)): ?>
                        <div class="flex gap-2 overflow-x-auto">
                            <!-- Main image thumbnail -->
                            <button type="button" onclick="changeImage('<?php echo SITE_URL . htmlspecialchars($product['image']); ?>')" class="flex-shrink-0 w-16 h-16 border-2 border-black rounded-md overflow-hidden">
                                <img src="<?php echo SITE_URL . htmlspecialchars($product['image']); ?>" alt="" class="w-full h-full object-cover">
                            </button>
                            <!-- Additional images thumbnails -->
                            <?php foreach ($product_images as $img): ?>
                                <button type="button" onclick="changeImage('<?php echo SITE_URL . htmlspecialchars($img); ?>')" class="flex-shrink-0 w-16 h-16 border-2 border-gray-300 rounded-md overflow-hidden hover:border-black transition-colors">
                                    <img src="<?php echo SITE_URL . htmlspecialchars($img); ?>" alt="" class="w-full h-full object-cover">
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <script>
                        function changeImage(imageSrc) {
                            document.getElementById('main-product-image').src = imageSrc;
                        }
                        </script>
                    <?php endif; ?>
                </div>

                <!-- Product Details -->
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <?php if (!empty($product['name'])): ?>
                            <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tighter"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <?php else: ?>
                            <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tighter">PRODUCT</h1>
                        <?php endif; ?>
                        <!-- Premium Badges -->
                        <div class="flex flex-wrap gap-2">
                            <?php if ($product['is_featured']): ?>
                                <span class="inline-flex items-center bg-gradient-to-r from-amber-400 to-orange-500 text-black text-xs font-black px-3 py-2 rounded-full shadow-lg border-2 border-white/30">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    FEATURED
                                </span>
                            <?php endif; ?>
                            <?php if ($product['is_bestseller']): ?>
                                <span class="inline-flex items-center bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-xs font-black px-3 py-2 rounded-full shadow-lg border-2 border-white/20">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path></svg>
                                    BESTSELLER
                                </span>
                            <?php endif; ?>
                            <?php if ($product['is_new']): ?>
                                <span class="inline-flex items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white text-xs font-black px-3 py-2 rounded-full shadow-lg border-2 border-white/20 backdrop-blur-sm">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    NEW
                                </span>
                            <?php endif; ?>
                            <?php if ($product['is_coming_soon']): ?>
                                <span class="inline-flex items-center bg-gradient-to-r from-purple-500 to-pink-600 text-white text-xs font-black px-3 py-2 rounded-full shadow-lg border-2 border-white/20">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                                    COMING SOON
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4">
                        <?php if ($product['sale_price'] > 0): ?>
                            <p class="text-3xl font-semibold"><span class="text-red-600">R <?php echo number_format($product['sale_price'], 2); ?></span> <span class="line-through text-black/50 text-xl">R <?php echo number_format($product['price'], 2); ?></span></p>
                        <?php else: ?>
                            <p class="text-3xl font-semibold">R <?php echo number_format($product['price'], 2); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mt-6">
                        <p class="text-black/70"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>

                    <div class="mt-8">
                        <form id="add-to-cart-form">
                            <?php echo generate_csrf_token_input(); ?>
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <div class="flex items-center">
                                <label for="quantity" class="mr-4 font-semibold">Quantity:</label>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="w-20 p-2 border border-black/20 rounded-md text-center">
                            </div>
                            <div class="mt-6 space-y-3">
                                <?php if ($product['stock'] > 0): ?>
                                    <button type="submit" class="w-full bg-black text-white font-bold uppercase py-3 rounded-md hover:bg-black/80 transition-colors">
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="w-full bg-neutral-400 text-white font-bold uppercase py-3 rounded-md cursor-not-allowed" disabled>
                                        Out of Stock
                                    </button>
                                <?php endif; ?>

                                <!-- WhatsApp Inquiry Button -->
                                <a href="#" onclick="openWhatsAppProduct('<?php echo htmlspecialchars($product['name']); ?>')"
                                   class="product-whatsapp-btn w-full justify-center">
                                    <i class="fab fa-whatsapp"></i>
                                    Inquire on WhatsApp
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <div class="mt-16">
                <h2 class="text-2xl md:text-3xl font-black uppercase tracking-tighter mb-8">Customer Reviews</h2>

                <!-- Display Approved Reviews -->
                <?php
                // Fetch approved reviews for this product
                $reviews = [];
                $reviews_sql = "SELECT r.rating, r.review_text, r.created_at, u.name as customer_name
                               FROM product_reviews r
                               JOIN users u ON r.user_id = u.id
                               WHERE r.product_id = ? AND r.is_approved = 1
                               ORDER BY r.created_at DESC";
                if ($reviews_stmt = $conn->prepare($reviews_sql)) {
                    $reviews_stmt->bind_param("i", $product_id);
                    $reviews_stmt->execute();
                    $reviews_result = $reviews_stmt->get_result();
                    while ($row = $reviews_result->fetch_assoc()) {
                        $reviews[] = $row;
                    }
                    $reviews_stmt->close();
                }
                ?>

                <?php if (!empty($reviews)): ?>
                    <div class="space-y-6 mb-8">
                        <?php foreach ($reviews as $review): ?>
                            <div class="border border-black/10 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-semibold"><?php echo htmlspecialchars($review['customer_name']); ?></div>
                                        <div class="flex items-center">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="text-sm text-black/50"><?php echo date('d M Y', strtotime($review['created_at'])); ?></div>
                                </div>
                                <p class="text-black/70"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Write Review Form (for logged-in users) -->
                <div id="review-form-section">
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                        <div class="bg-white border border-black/10 rounded-lg p-6">
                            <h3 class="text-lg font-bold uppercase tracking-wider mb-4">Write a Review</h3>

                            <!-- Check if user already reviewed this product -->
                            <?php
                            $user_reviewed = false;
                            if (isset($_SESSION['user_id'])) {
                                $check_review_sql = "SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?";
                                if ($check_stmt = $conn->prepare($check_review_sql)) {
                                    $check_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
                                    $check_stmt->execute();
                                    $check_stmt->store_result();
                                    $user_reviewed = $check_stmt->num_rows > 0;
                                    $check_stmt->close();
                                }
                            }
                            ?>

                            <?php if ($user_reviewed): ?>
                                <p class="text-black/70">You have already submitted a review for this product. It will be published after approval.</p>
                            <?php else: ?>
                                <form id="review-form">
                                    <?php echo generate_csrf_token_input(); ?>
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

                                    <!-- Rating -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                                        <div class="flex gap-1" id="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <button type="button" class="star-rating text-2xl text-gray-300 hover:text-yellow-400" data-rating="<?php echo $i; ?>">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            <?php endfor; ?>
                                        </div>
                                        <input type="hidden" name="rating" id="rating-input" required>
                                        <p class="text-sm text-black/50 mt-1" id="rating-text">Click to rate</p>
                                    </div>

                                    <!-- Review Text -->
                                    <div class="mb-4">
                                        <label for="review_text" class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
                                        <textarea name="review_text" id="review_text" rows="4" required
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"
                                                  placeholder="Share your thoughts about this product..."></textarea>
                                    </div>

                                    <button type="submit" class="bg-black text-white px-6 py-2 rounded-md hover:bg-black/80 transition-colors">
                                        Submit Review
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <p class="text-black/70 mb-4">Please <a href="login.php?redirect=product?id=<?php echo $product_id; ?>" class="text-black font-semibold underline">log in</a> to write a review.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Product Not Found</h1>
                <p class="mt-4 text-lg text-black/70">The product you are looking for does not exist or is no longer available.</p>
                <div class="mt-8">
                    <a href="shop.php" class="text-lg font-semibold text-black border-b-2 border-black hover:border-transparent transition-colors">
                        Continue Shopping
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add to Cart Form
    const addToCartForm = document.getElementById('add-to-cart-form');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const productId = this.querySelector('input[name="product_id"]').value;
            const quantity = this.querySelector('input[name="quantity"]').value;
            const csrfToken = this.querySelector('input[name="csrf_token"]').value;

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('csrf_token', csrfToken);

            fetch('ajax_cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    if (typeof updateCartCountDisplay === 'function') {
                        updateCartCountDisplay(); // Update cart count in header
                    }
                } else {
                    showToast(data.message || 'An error occurred.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while adding to cart.', 'error');
            });
        });
    }

    // Star Rating Functionality
    const ratingStars = document.querySelectorAll('.star-rating');
    const ratingInput = document.getElementById('rating-input');
    const ratingText = document.getElementById('rating-text');

    if (ratingStars.length > 0 && ratingInput && ratingText) {
        let currentRating = 0;

        ratingStars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                currentRating = rating;

                // Update visual rating
                ratingStars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('text-gray-300');
                        s.classList.add('text-yellow-400');
                    } else {
                        s.classList.remove('text-yellow-400');
                        s.classList.add('text-gray-300');
                    }
                });

                // Update hidden input and text
                ratingInput.value = rating;
                ratingText.textContent = `${rating} star${rating !== 1 ? 's' : ''}`;
            });

            star.addEventListener('mouseover', function() {
                const rating = parseInt(this.dataset.rating);
                ratingStars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('text-gray-300');
                        s.classList.add('text-yellow-400');
                    } else {
                        s.classList.remove('text-yellow-400');
                        s.classList.add('text-gray-300');
                    }
                });
            });
        });

        // Reset on mouseout if no rating is selected
        document.getElementById('rating-stars').addEventListener('mouseout', function() {
            if (currentRating === 0) {
                ratingStars.forEach(s => {
                    s.classList.remove('text-yellow-400');
                    s.classList.add('text-gray-300');
                });
            } else {
                ratingStars.forEach((s, index) => {
                    if (index < currentRating) {
                        s.classList.remove('text-gray-300');
                        s.classList.add('text-yellow-400');
                    } else {
                        s.classList.remove('text-yellow-400');
                        s.classList.add('text-gray-300');
                    }
                });
            }
        });
    }

    // Review Form Submission
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';

            const formData = new FormData(reviewForm);

            fetch('<?php echo SITE_URL; ?>submit_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Replace form with success message
                    document.getElementById('review-form-section').innerHTML = `
                        <div class="text-center py-8 bg-green-50 rounded-lg border border-green-200">
                            <p class="text-green-800 font-semibold">Thank you for your review!</p>
                            <p class="text-green-600 mt-2">Your review has been submitted and will be published after approval.</p>
                        </div>
                    `;
                } else {
                    showToast(data.message || 'Failed to submit review.', 'error');
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while submitting your review.', 'error');
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }
});
</script>
