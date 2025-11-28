<?php
/**
 * Flash Sales Page
 * Display active and upcoming flash sales
 */

$pageTitle = "Flash Sales - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/flash_sales_functions.php';

$conn = get_db_connection();

// Clean expired sales
cleanExpiredFlashSales($conn);

// Get active and upcoming sales
$active_sales = getActiveFlashSales($conn, 20);
$upcoming_sales = getUpcomingFlashSales($conn, 10);

require_once 'includes/header.php';
?>

<main class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-red-600 to-orange-600 text-white py-16">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-6xl font-black uppercase mb-4">⚡ Flash Sales</h1>
            <p class="text-xl md:text-2xl mb-2">Limited Time Offers - Don't Miss Out!</p>
            <p class="text-lg opacity-90">Exclusive deals that won't last long</p>
        </div>
    </div>

    <!-- Active Flash Sales -->
    <?php if (!empty($active_sales)): ?>
    <div class="container mx-auto px-4 py-16">
        <div class="mb-8">
            <h2 class="text-3xl font-bold mb-2">🔥 Active Now</h2>
            <p class="text-gray-600">Grab these deals before time runs out!</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($active_sales as $sale): 
                $time_remaining = getTimeRemaining($sale['end_time']);
                $remaining_stock = $sale['quantity_limit'] ? ($sale['quantity_limit'] - $sale['quantity_sold']) : null;
            ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                    <!-- Flash Sale Badge -->
                    <div class="relative">
                        <img src="<?php echo SITE_URL . htmlspecialchars($sale['image']); ?>" 
                             alt="<?php echo htmlspecialchars($sale['name']); ?>" 
                             class="w-full h-64 object-cover">
                        <div class="absolute top-4 left-4 bg-red-600 text-white px-4 py-2 rounded-full font-bold shadow-lg">
                            -<?php echo $sale['discount_percentage']; ?>% OFF
                        </div>
                        <?php if ($remaining_stock !== null && $remaining_stock <= 10): ?>
                            <div class="absolute top-4 right-4 bg-orange-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                Only <?php echo $remaining_stock; ?> left!
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($sale['name']); ?></h3>
                        
                        <!-- Price -->
                        <div class="flex items-center gap-3 mb-4">
                            <span class="text-3xl font-bold text-red-600">R <?php echo number_format($sale['sale_price'], 2); ?></span>
                            <span class="text-lg text-gray-500 line-through">R <?php echo number_format($sale['original_price'], 2); ?></span>
                        </div>

                        <!-- Countdown Timer -->
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2 font-semibold">⏰ Ends In:</p>
                            <div data-countdown="<?php echo $sale['end_time']; ?>"></div>
                        </div>

                        <!-- Progress Bar (if quantity limited) -->
                        <?php if ($sale['quantity_limit']): 
                            $sold_percentage = ($sale['quantity_sold'] / $sale['quantity_limit']) * 100;
                        ?>
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>Sold: <?php echo $sale['quantity_sold']; ?>/<?php echo $sale['quantity_limit']; ?></span>
                                    <span><?php echo round($sold_percentage); ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-red-600 h-2 rounded-full transition-all" style="width: <?php echo $sold_percentage; ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Action Button -->
                        <a href="product/<?php echo $sale['product_id']; ?>/<?php echo urlencode(str_replace(' ', '-', strtolower($sale['name']))); ?>" 
                           class="block w-full bg-black text-white text-center py-3 rounded-md font-semibold hover:bg-black/80 transition-colors">
                            Shop Now
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="container mx-auto px-4 py-16">
        <div class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="w-20 h-20 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-2xl font-bold text-gray-700 mb-2">No Active Flash Sales</h3>
            <p class="text-gray-600">Check back soon for amazing deals!</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upcoming Flash Sales -->
    <?php if (!empty($upcoming_sales)): ?>
    <div class="container mx-auto px-4 py-16 bg-white">
        <div class="mb-8">
            <h2 class="text-3xl font-bold mb-2">📅 Coming Soon</h2>
            <p class="text-gray-600">Mark your calendar for these upcoming deals</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($upcoming_sales as $sale): ?>
                <div class="bg-gray-50 rounded-lg overflow-hidden border-2 border-gray-200">
                    <div class="relative">
                        <img src="<?php echo SITE_URL . htmlspecialchars($sale['image']); ?>" 
                             alt="<?php echo htmlspecialchars($sale['name']); ?>" 
                             class="w-full h-48 object-cover opacity-75">
                        <div class="absolute inset-0 bg-black/30 flex items-center justify-center">
                            <span class="bg-white text-black px-4 py-2 rounded-full font-bold">
                                Coming Soon
                            </span>
                        </div>
                    </div>
                    <div class="p-4">
                        <h4 class="font-semibold mb-2"><?php echo htmlspecialchars($sale['name']); ?></h4>
                        <p class="text-sm text-gray-600 mb-2">
                            Starts: <?php echo date('M d, Y H:i', strtotime($sale['start_time'])); ?>
                        </p>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-bold text-red-600">-<?php echo $sale['discount_percentage']; ?>%</span>
                            <span class="text-sm text-gray-500">OFF</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php
require_once 'includes/footer.php';
?>
