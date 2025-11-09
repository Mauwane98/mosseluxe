<?php
$pageTitle = "Product Details - Mossé Luxe";
require_once 'includes/db_connect.php';
$conn = get_db_connection();
require_once 'includes/header.php';

$product = null;
if (isset($_GET['id'])) {
    $product_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
    $sql = "SELECT id, name, description, price, sale_price, image, stock FROM products WHERE id = ? AND status = 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <?php if ($product): ?>
            <div class="grid md:grid-cols-2 gap-8 lg:gap-16">
                <!-- Product Image -->
                <div class="aspect-w-1 aspect-h-1 bg-neutral-100 rounded-md overflow-hidden">
                    <img 
                        src="<?php echo htmlspecialchars($product['image']); ?>" 
                        alt="<?php echo htmlspecialchars($product['name']); ?>" 
                        class="w-full h-full object-contain p-4"
                        onerror="this.src='https://placehold.co/600x600/f1f1f1/000000?text=Mossé+Luxe'"
                    >
                </div>

                <!-- Product Details -->
                <div>
                    <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tighter"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
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
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <div class="flex items-center">
                                <label for="quantity" class="mr-4 font-semibold">Quantity:</label>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="w-20 p-2 border border-black/20 rounded-md text-center">
                            </div>
                            <div class="mt-6">
                                <?php if ($product['stock'] > 0): ?>
                                    <button type="submit" class="w-full bg-black text-white font-bold uppercase py-3 rounded-md hover:bg-black/80 transition-colors">
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="w-full bg-neutral-400 text-white font-bold uppercase py-3 rounded-md cursor-not-allowed" disabled>
                                        Out of Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
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
    const addToCartForm = document.getElementById('add-to-cart-form');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const productId = this.querySelector('input[name="product_id"]').value;
            const quantity = this.querySelector('input[name="quantity"]').value;

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
    }
});
</script>
