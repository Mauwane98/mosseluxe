<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = get_db_connection();

// Content for static pages
$pages_data = [
    [
        'title' => 'About Us',
        'slug' => 'about',
        'content' => '
<!-- Section 2: The Inspiration -->
<section class="bg-white py-20 md:py-28">
    <div class="container mx-auto px-4 md:px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-20 items-center">
            <div class="order-2 md:order-1">
                <h2 class="text-sm font-bold uppercase tracking-widest text-black/50 mb-3">The Inspiration</h2>
                <h3 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-6">Moses Rapudi Mauwane</h3>
                <div class="space-y-4 text-black/80 leading-relaxed text-lg">
                    <p>The heart of Mossé Luxe is the story of a skilled South African craftsman who made his living creating handmade leather belts. With simple tools and immense care, he created items of purpose and quality.</p>
                    <p>His talent and dedication were ahead of his time. Today, his spirit is the foundation of our brand—a modern expression of a timeless legacy.</p>
                </div>
            </div>
            <div class="order-1 md:order-2">
                <img src="assets/images/potrait.png" alt="Portrait or symbolic image of the inspiration" class="w-3/4 md:w-full lg:w-4/5 mx-auto aspect-square rounded-full shadow-lg object-cover">
            </div>
        </div>
    </div>
</section>

<!-- Section 3: The Philosophy -->
<section class="relative py-24 md:py-40 bg-black text-white">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="relative container mx-auto px-4 md:px-6 text-center max-w-4xl">
        <h2 class="text-sm font-bold uppercase tracking-widest text-white/60 mb-3">Our Name</h2>
        <p class="text-3xl md:text-5xl font-black leading-tight">
            <span class="text-white/80">"Mossé"</span> to honor the man who started it all. 
            <span class="text-white/80">"Luxe"</span> for a luxury defined by quality, not price.
        </p>
        <p class="mt-8 text-2xl font-bold uppercase tracking-wider">Luxury Inspired by Legacy</p>
    </div>
</section>

<!-- Section 4: Our Commitment -->
<section class="bg-neutral-50 py-20 md:py-28">
    <div class="container mx-auto px-4 md:px-6">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter">Our Commitment</h2>
            <p class="mt-4 text-lg text-black/70">From our beginnings in leather goods to our expansion into apparel, three principles guide every stitch and every cut.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12 max-w-6xl mx-auto">
            <div>
                <h3 class="text-xl font-bold uppercase tracking-wider mb-3 border-b-2 border-black pb-2">Heritage</h3>
                <p class="text-black/70 mt-4">We ensure the story of skill and determination lives on in every piece we create.</p>
            </div>
            <div>
                <h3 class="text-xl font-bold uppercase tracking-wider mb-3 border-b-2 border-black pb-2">Craftsmanship</h3>
                <p class="text-black/70 mt-4">Our commitment to quality is unwavering, with an intense focus on detail and care.</p>
            </div>
            <div>
                <h3 class="text-xl font-bold uppercase tracking-wider mb-3 border-b-2 border-black pb-2">Modern Style</h3>
                <p class="text-black/70 mt-4">We blend our rich history with timeless design for a new generation of luxury.</p>
            </div>
        </div>
    </div>
</section>

<!-- Section 5: CTA -->
<section class="bg-white py-20 md:py-24">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-4">Discover the Collection</h2>
        <p class="text-lg text-black/70 mb-8 max-w-2xl mx-auto">Explore our range of leather goods and apparel, each piece telling a story of heritage and quality.</p>
        <a href="shop.php" class="inline-block bg-black text-white py-4 px-12 font-bold uppercase rounded-md tracking-wider text-lg hover:bg-black/80 transition-colors">
            Shop Now
        </a>
    </div>
</section>'
    ],
    [
        'title' => 'Careers',
        'slug' => 'careers',
        'content' => '
            <div class="space-y-8">
                <div class="border-b border-black/10 pb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold uppercase tracking-wider">Why Work at Mossé Luxe?</h2>
                    </div>
                    <p class="text-black/70 leading-relaxed">At Mossé Luxe, we believe in creating a workplace that reflects our values of heritage, craftsmanship, and modern style. We\'re passionate about quality and innovation, and we want team members who share that passion.</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <div class="text-center p-4 bg-neutral-50 rounded-lg">
                            <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-sm uppercase tracking-wider">Heritage</h3>
                        </div>
                        <div class="text-center p-4 bg-neutral-50 rounded-lg">
                            <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-sm uppercase tracking-wider">Innovation</h3>
                        </div>
                        <div class="text-center p-4 bg-neutral-50 rounded-lg">
                            <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-sm uppercase tracking-wider">Passion</h3>
                        </div>
                    </div>
                </div>

                <div class="border-b border-black/10 pb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold uppercase tracking-wider">Current Openings</h2>
                    </div>
                    <div class="bg-neutral-50 p-6 rounded-lg text-center">
                        <svg class="w-16 h-16 text-black/40 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <h3 class="text-lg font-bold mb-2">No Current Openings</h3>
                        <p class="text-black/70">We don\'t have any open positions at the moment, but we\'re always interested in hearing from talented individuals. Send us your resume and tell us why you\'d like to join our team.</p>
                    </div>
                </div>

                <div class="border-b border-black/10 pb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold uppercase tracking-wider">How to Apply</h2>
                    </div>
                    <p class="text-black/70 leading-relaxed">If you\'re interested in joining Mossé Luxe, please send your resume and a brief cover letter to <a href="mailto:careers@mosseluxe.com" class="font-bold underline hover:text-black">careers@mosseluxe.com</a>. We\'ll keep your information on file for future opportunities.</p>
                </div>

                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l.707.707A1 1 0 0012.414 11H13m-4 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold uppercase tracking-wider">Our Culture</h2>
                    </div>
                    <p class="text-black/70 leading-relaxed">We foster a collaborative and creative environment where every team member has the opportunity to contribute to our brand\'s success. We value diversity, creativity, and a commitment to excellence.</p>
                </div>
            </div>'
    ],
    [
        'title' => 'Frequently Asked Questions',
        'slug' => 'faq',
        'content' => '
            <div class="space-y-4" id="faq-accordion">
                <div class="border-b border-black/10 pb-4">
                    <button class="faq-toggle flex justify-between items-center w-full text-left">
                        <span class="text-lg font-bold">What are your shipping options?</span>
                        <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">
                        <p class="pt-4 text-black/70">We offer standard and express shipping options nationwide. Standard shipping typically takes 3-5 business days, while express shipping takes 1-2 business days. All orders over R1500 qualify for free standard shipping.</p>
                    </div>
                </div>
                <div class="border-b border-black/10 pb-4">
                    <button class="faq-toggle flex justify-between items-center w-full text-left">
                        <span class="text-lg font-bold">How can I track my order?</span>
                        <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">
                        <p class="pt-4 text-black/70">Once your order has been shipped, you will receive an email with a tracking number and a link to the courier\'s website. You can also use our <a href="' . SITE_URL . 'track-order" class="font-bold underline hover:text-black">Track Order</a> page to check the status.</p>
                    </div>
                </div>
                <div class="border-b border-black/10 pb-4">
                    <button class="faq-toggle flex justify-between items-center w-full text-left">
                        <span class="text-lg font-bold">What is your return policy?</span>
                        <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">
                        <p class="pt-4 text-black/70">We accept returns within 30 days of purchase for items that are unworn, unwashed, and in their original condition with all tags attached. Please visit our Shipping & Returns page for more detailed information on how to initiate a return.</p>
                    </div>
                </div>
            </div>
        <script>
        document.addEventListener(\'DOMContentLoaded\', function () {
            const faqToggles = document.querySelectorAll(\'.faq-toggle\');
            faqToggles.forEach(toggle => {
                toggle.addEventListener(\'click\', () => {
                    const content = toggle.nextElementSibling;
                    const icon = toggle.querySelector(\'svg\');

                    if (content.style.maxHeight) {
                        content.style.maxHeight = null;
                        icon.style.transform = \'rotate(0deg)\';
                    } else {
                        content.style.maxHeight = content.scrollHeight + \'px\';
                        icon.style.transform = \'rotate(180deg)\';
                    }
                });
            });
        });
        </script>'
    ],
    [
        'title' => 'Shipping & Returns',
        'slug' => 'shipping-returns',
        'content' => '<div class="space-y-8">
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
            </div>'
    ],
    [
        'title' => 'Privacy Policy',
        'slug' => 'privacy-policy',
        'content' => '<div class="space-y-8">
                    <div class="border-b border-black/10 pb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">Information We Collect</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us for support. This includes your name, email address, shipping address, and payment information.</p>
                    </div>

                    <div class="border-b border-black/10 pb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">How We Use Your Information</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">We use the information we collect to process orders, provide customer service, send marketing communications (with your consent), improve our website, and ensure a personalized shopping experience.</p>
                    </div>

                    <div class="border-b border-black/10 pb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">Information Sharing</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as required for order processing (payment providers, shipping companies) or as required by law.</p>
                    </div>

                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">Data Security</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">We implement appropriate security measures including SSL encryption, secure payment processing, and regular security audits to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
                    </div>
                </div>'
    ],
    [
        'title' => 'Terms of Service',
        'slug' => 'terms-of-service',
        'content' => '<div class="space-y-8">
                    <div class="border-b border-black/10 pb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">Acceptance of Terms</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">By accessing and using Mossé Luxe, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
                    </div>

                    <div class="border-b border-black/10 pb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">Use License</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">Permission is granted to temporarily access the materials on Mossé Luxe for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
                        <ul class="list-disc list-inside mt-3 text-black/70 space-y-1">
                            <li>modify or copy the materials</li>
                            <li>use the materials for any commercial purpose or for any public display</li>
                            <li>attempt to decompile or reverse engineer any software contained on the website</li>
                        </ul>
                    </div>

                    <div class="border-b border-black/10 pb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">Disclaimer</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">The materials on Mossé Luxe are provided on an \'as is\' basis. Mossé Luxe makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>
                    </div>

                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold uppercase tracking-wider">Limitations</h2>
                        </div>
                        <p class="text-black/70 leading-relaxed">In no event shall Mossé Luxe or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Mossé Luxe, even if Mossé Luxe or a Mossé Luxe authorized representative has been notified orally or in writing of the possibility of such damage.</p>
                    </div>
                </div>'
    ],
    [
        'title' => 'Contact',
        'slug' => 'contact',
        'content' => '
<!-- Contact Details -->
<div class="space-y-8">
    <div class="flex items-start gap-4">
        <div class="text-black/50 pt-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>
        <div>
            <h3 class="text-xl font-bold uppercase tracking-wider">Our Location</h3>
            <p class="text-black/70 text-lg mt-1">SANDTON, SOUTH AFRICA</p>
        </div>
    </div>
    <div class="flex items-start gap-4">
        <div class="text-black/50 pt-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
        </div>
        <div>
            <h3 class="text-xl font-bold uppercase tracking-wider">Email Us</h3>
            <p class="text-black/70 text-lg mt-1">INFO@MOSSELUXE.COM</p>
        </div>
    </div>
    <div class="flex items-start gap-4">
        <div class="text-black/50 pt-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
            </svg>
        </div>
        <div>
            <h3 class="text-xl font-bold uppercase tracking-wider">Call Us</h3>
            <p class="text-black/70 text-lg mt-1">+27 21 123 4567</p>
        </div>
    </div>
    <div class="flex items-start gap-4">
        <div class="text-black/50 pt-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div>
            <h3 class="text-xl font-bold uppercase tracking-wider">Our Hours</h3>
            <p class="text-black/70 text-lg mt-1">Monday - Friday: 9:00 AM - 5:00 PM</p>
        </div>
    </div>
</div>'
    ]
];

// Insert pages into database
foreach ($pages_data as $page_data) {
    $title = $page_data['title'];
    $slug = $page_data['slug'];
    $content = $page_data['content'];

    // Check if page already exists
    $check_sql = "SELECT id FROM pages WHERE slug = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $slug);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows == 0) {
        // Insert new page
        $insert_sql = "INSERT INTO pages (title, slug, content, status) VALUES (?, ?, ?, 1)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sss", $title, $slug, $content);

        if ($insert_stmt->execute()) {
            echo "✓ Created page: {$title} (slug: {$slug})\n";
        } else {
            echo "✗ Failed to create page: {$title}\n";
        }

        $insert_stmt->close();
    } else {
        echo "• Page already exists: {$title} (slug: {$slug})\n";
    }

    $check_stmt->close();
}

$conn->close();
echo "\nStatic pages seeding completed!\n";
?>
