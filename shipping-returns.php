<?php
$pageTitle = "Shipping & Returns - Mossé Luxe";
require_once 'includes/header.php';
?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <!-- Page Content -->
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Shipping & Returns</h1>
            </div>

            <div class="bg-white p-8 md:p-12 rounded-lg shadow-md">
                <div class="space-y-8">
                    <!-- Shipping Information -->
                    <div class="border-b border-black/10 pb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">Shipping Information</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">We offer standard and express shipping options nationwide. Standard shipping typically takes 3-5 business days, while express shipping takes 1-2 business days. All orders over R1500 qualify for free standard shipping.</p>
                    </div>

                    <!-- Return Policy -->
                    <div class="border-b border-black/10 pb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">Return Policy</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">We accept returns within 30 days of purchase for items that are unworn, unwashed, and in their original condition with all tags attached. To initiate a return, please contact our customer service team.</p>
                    </div>

                    <!-- Exchanges -->
                    <div class="border-b border-black/10 pb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">Exchanges</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">If you need to exchange an item for a different size or color, please contact us within 30 days of purchase. Exchanges are subject to availability.</p>
                    </div>

                    <!-- Refunds -->
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">Refunds</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">Refunds will be processed within 5-7 business days after we receive your returned item. The refund will be issued to the original payment method.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
