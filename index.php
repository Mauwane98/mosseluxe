<?php
$pageTitle = "Mossé Luxe - Luxury Streetwear";
require_once 'includes/db_connect.php';
$conn = get_db_connection();
require_once 'includes/header.php';

// Display success or error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">';
    echo '<strong class="font-bold">Success!</strong>';
    echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['success_message']) . '</span>';
    echo '</div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
    echo '<strong class="font-bold">Error!</strong>';
    echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['error_message']) . '</span>';
    echo '</div>';
    unset($_SESSION['error_message']);
}

$new_arrivals = [];
$new_arrivals_message = 'New arrivals will be available soon. Please check back later.';
$display_count = 4;

// Get new arrivals settings
$settings_sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('new_arrivals_message', 'new_arrivals_display_count')";
if ($settings_result = $conn->query($settings_sql)) {
    while ($setting = $settings_result->fetch_assoc()) {
        if ($setting['setting_key'] == 'new_arrivals_message') {
            $new_arrivals_message = $setting['setting_value'];
        } elseif ($setting['setting_key'] == 'new_arrivals_display_count') {
            $display_count = (int)$setting['setting_value'];
        }
    }
    $settings_result->free();
}

// Get featured new arrivals
$sql = "SELECT p.id, p.name, p.price, p.sale_price, p.image
        FROM products p
        JOIN new_arrivals na ON p.id = na.product_id
        WHERE p.status = 1 AND (na.release_date IS NULL OR na.release_date <= NOW())
        ORDER BY na.display_order ASC, na.release_date DESC
        LIMIT ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $display_count);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $new_arrivals[] = $row;
}

$stmt->close();
$conn->close();

?>
        <!-- Main Content -->
        <main>
            <!-- Hero Carousel Section -->
            <section id="hero-carousel" class="relative h-screen min-h-[600px] w-full overflow-hidden">
                <!-- Slides Container -->
                <div id="slides-container" class="flex h-full transition-transform duration-700 ease-in-out">
                    

                                        <!-- Slide 1: Explore Our Collection -->
                                        <div class="carousel-slide">
                                            <img
                                                src="assets/images/hero2.png"
                                                alt="Explore Our Collection"
                                                onerror="this.style.display='none'; this.parentElement.style.backgroundColor='#111';"
                                            >
                                            <div class="absolute inset-0 bg-black/50 z-10"></div>
                                            <div class="carousel-slide-content">
                                                <a href="#new-arrivals" class="mt-6 inline-block bg-white text-black py-3 px-10 font-bold uppercase rounded-md tracking-wider text-lg hover:bg-white/90 transition-colors">
                                                    Explore Our Collection
                                                </a>
                                            </div>
                                        </div>
                                        <!-- Slide 2: The Art of Luxe -->
                                        <div class="carousel-slide">
                                            <img
                                                src="assets/images/hero1.png"
                                                alt="The Art of Luxe"
                                                onerror="this.style.display='none'; this.parentElement.style.backgroundColor='#333';"
                                            >
                                            <div class="absolute inset-0 bg-black/40 z-10"></div>
                                            <div class="carousel-slide-content">
                                                <a href="#new-arrivals" class="mt-6 inline-block bg-white text-black py-3 px-10 font-bold uppercase rounded-md tracking-wider text-lg hover:bg-white/90 transition-colors">
                                                    Shop Mossé Luxe
                                                </a>
                                            </div>
                                        </div>
                                        <!-- Slide 3: New Season, New Style -->
                                        <div class="carousel-slide">
                                            <img
                                                src="assets/images/hero.jpeg"
                                                alt="New Season, New Style"
                                                onerror="this.style.display='none'; this.parentElement.style.backgroundColor='#555';"
                                            >
                                            <div class="absolute inset-0 bg-black/40 z-10"></div>
                                            <div class="carousel-slide-content">
                                                <a href="#new-arrivals" class="mt-6 inline-block bg-white text-black py-3 px-10 font-bold uppercase rounded-md tracking-wider text-lg hover:bg-white/90 transition-colors">
                                                    Discover More
                                                </a>
                                            </div>
                                        </div>
                </div>

                <!-- Carousel Controls -->
                <button id="prev-slide" class="carousel-control prev" aria-label="Previous slide">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button id="next-slide" class="carousel-control next" aria-label="Next slide">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>

                <!-- Carousel Dots -->
                <div id="carousel-dots" class="carousel-dots">
                    <!-- Dots will be generated by JS -->
                </div>
            </section>



            <!-- New Arrivals Section -->
            <section id="new-arrivals" class="py-24 md:py-32 bg-white">
                <div class="container mx-auto px-4 md:px-6">
                    <h2 class="text-4xl md:text-5xl font-black text-center uppercase tracking-tighter mb-12 md:mb-16">
                        New Arrivals
                    </h2>
                    
                    <!-- Product Grid -->
                    <?php if (!empty($new_arrivals)): ?>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                        <?php foreach ($new_arrivals as $product): ?>
                            <div class="group">
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <div class="relative aspect-w-1 aspect-h-1 bg-neutral-100 rounded-md overflow-hidden border border-transparent group-hover:border-black/10 transition-colors">
                                        <img 
                                            src="<?php echo htmlspecialchars($product['image']); ?>" 
                                            alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                            class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-300"
                                            onerror="this.src='https://placehold.co/600x600/f1f1f1/000000?text=Mossé+Luxe'"
                                        >
                                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 w-11/12">
                                            <button class="w-full bg-white/90 text-black text-sm font-bold uppercase py-2.5 rounded-md opacity-0 group-hover:opacity-100 transition-all duration-300 backdrop-blur-sm hover:bg-white quick-add-btn" data-product-id="<?php echo $product['id']; ?>">
                                                Quick Add
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-4 text-left">
                                        <h4 class="text-sm text-black/60">Mossé Luxe</h4>
                                        <h3 class="text-base md:text-lg font-bold truncate"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <?php if ($product['sale_price'] > 0): ?>
                                            <p class="text-md font-semibold mt-1"><span class="text-red-600">R <?php echo number_format($product['sale_price'], 2); ?></span> <span class="line-through text-black/50">R <?php echo number_format($product['price'], 2); ?></span></p>
                                        <?php else: ?>
                                            <p class="text-md font-semibold mt-1">R <?php echo number_format($product['price'], 2); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <p class="text-center text-lg text-black/60"><?php echo htmlspecialchars($new_arrivals_message); ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Brand Statement Section -->
            <section class="py-24 md:py-32 bg-white">
                <div class="container mx-auto px-4 text-center max-w-3xl">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-black/50 mb-3">Our Philosophy</h2>
                    <h3 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">
                        Luxury Inspired by Legacy
                    </h3>
                    <p class="mt-6 text-lg md:text-xl text-black/80 leading-relaxed">
                        We define luxury not by price, but by quality, craftsmanship, and timeless design. Each piece is a modern expression of a timeless legacy, blending our rich history with a style for today's world.
                    </p>
                    <a href="about.php" class="mt-8 inline-block font-bold uppercase tracking-wider text-black border-b-2 border-black pb-1 hover:border-black/60 hover:text-black/60 transition-colors">
                        Read Our Story
                    </a>
                </div>
            </section>

            <!-- Newsletter Signup -->
            <section id="newsletter-signup" class="py-16 md:py-24 bg-neutral-100">
                <div class="container mx-auto px-4 text-center max-w-xl">
                    <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-4">
                        Join the List
                    </h2>
                    <p class="text-lg text-black/60 mb-8">
                        Be the first to know about new drops, exclusive events, and insider-only deals.
                    </p>
                    <form action="subscribe.php" method="POST" class="flex flex-col md:flex-row gap-4 max-w-md mx-auto">
                        <input 
                            type="email" 
                            placeholder="Enter your email" 
                            required 
                            class="flex-grow p-3 bg-white border border-black/50 rounded-md text-black placeholder-black/50 focus:outline-none focus:ring-2 focus:ring-black"
                        >
                        <button 
                            type="submit" 
                            class="bg-black text-white py-3 px-8 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider"
                        >
                            Subscribe
                        </button>
                    </form>
                </div>
            </section>
        </main>
<?php
require_once 'includes/footer.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.quick-add-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default button action
            const productId = this.dataset.productId;
            const quantity = 1; // Quick add always adds 1 item

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            fetch('ajax_cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    updateCartCountDisplay(); // Update cart count in header
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding to cart.');
            });
        });
    });
});
</script>
