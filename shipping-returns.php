<?php
$pageTitle = "Shipping & Returns - Mossé Luxe";
require_once 'includes/bootstrap.php';
require_once 'includes/header.php';
?>

<main>
<section class="bg-white py-20 md:py-28">
    <div class="container mx-auto px-4 md:px-6">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-4">Shipping & Returns</h2>
                <p class="text-lg text-black/70">Your satisfaction is our priority. Learn about our shipping options and hassle-free returns policy.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Shipping Information -->
                <div class="space-y-8">
                    <div class="bg-neutral-50 p-8 rounded-lg">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-4 4m0 0h18"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold uppercase tracking-wider">Shipping Options</h3>
                        </div>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-3 border-b border-black/10">
                                <span class="font-medium">Standard Shipping (SA)</span>
                                <span class="font-bold">R 150</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-black/10">
                                <span class="font-medium">Express Shipping (SA)</span>
                                <span class="font-bold">R 300</span>
                            </div>
                            <div class="flex justify-between items-center py-3">
                                <span class="font-medium">International Shipping</span>
                                <span class="font-bold">From R 800</span>
                            </div>
                        </div>

                        <div class="mt-6">
                            <p class="text-sm text-black/60 mb-2"><strong>Free Shipping:</strong> Orders over R 1500</p>
                            <p class="text-sm text-black/60"><strong>Delivery Time:</strong> 3-5 business days (Standard), 1-2 days (Express)</p>
                        </div>
                    </div>

                    <div class="bg-neutral-50 p-8 rounded-lg">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold uppercase tracking-wider">Shipping Protection</h3>
                        </div>

                        <ul class="space-y-3 text-sm text-black/70">
                            <li class="flex items-start gap-3">
                                <svg class="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Premium packaging in branded presentation boxes</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Full insurance coverage for all shipments</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Signature upon delivery for added security</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Returns Information -->
                <div class="space-y-8">
                    <div class="bg-neutral-50 p-8 rounded-lg">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold uppercase tracking-wider">30-Day Returns</h3>
                        </div>

                        <p class="text-black/70 mb-6">We accept returns within 30 days of purchase for items that are unworn, unwashed, and in their original condition with all tags attached.</p>

                        <div class="space-y-3">
                            <div class="flex items-start gap-3 text-sm">
                                <div class="w-2 h-2 bg-black rounded-full mt-2 flex-shrink-0"></div>
                                <span class="text-black/70">Items must be in original packaging with all tags attached</span>
                            </div>
                            <div class="flex items-start gap-3 text-sm">
                                <div class="w-2 h-2 bg-black rounded-full mt-2 flex-shrink-0"></div>
                                <span class="text-black/70">Items showing signs of wear, wash, or alteration cannot be returned</span>
                            </div>
                            <div class="flex items-start gap-3 text-sm">
                                <div class="w-2 h-2 bg-black rounded-full mt-2 flex-shrink-0"></div>
                                <span class="text-black/70">Custom or personalized items are not eligible for return</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-neutral-50 p-8 rounded-lg">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold uppercase tracking-wider">Return Process</h3>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-black text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</div>
                                <div>
                                    <h4 class="font-bold">Contact Customer Service</h4>
                                    <p class="text-sm text-black/70">Fill out the returns form or email us with your order details</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-black text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</div>
                                <div>
                                    <h4 class="font-bold">Receive Prepaid Label</h4>
                                    <p class="text-sm text-black/70">We'll provide a prepaid return shipping label</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-black text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</div>
                                <div>
                                    <h4 class="font-bold">Send Your Item</h4>
                                    <p class="text-sm text-black/70">Package the item securely and attach the return label</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-black text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">4</div>
                                <div>
                                    <h4 class="font-bold">Refund Processed</h4>
                                    <p class="text-sm text-black/70">Your refund will be processed within 3-5 business days</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 text-center">
                            <a href="contact.php" class="text-black font-bold uppercase tracking-wider border-b-2 border-black pb-1 hover:text-gray-700 transition-colors">
                                Start Return Process
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</main>

<?php
require_once 'includes/footer.php';
?>
