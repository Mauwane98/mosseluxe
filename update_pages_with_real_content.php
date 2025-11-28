<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Map the page slugs to their corresponding content files
$page_content_map = [
    'about' => 'real pages content/about.php',
    'contact' => 'real pages content/contact.php',
    'careers' => 'real pages content/careers.php',
    'faq' => 'real pages content/faq.php',
    'privacy-policy' => 'real pages content/privacy-policy.php',
    'shipping-returns' => 'real pages content/shipping-returns.php',
    'terms-of-service' => 'real pages content/terms-of-service.php',
];

function extract_content_from_main_section($file_content) {
    // Find the main section start
    $main_start = strpos($file_content, '<main>');

    // Find the main section end
    $main_end = strpos($file_content, '</main>', $main_start);

    if ($main_start === false || $main_end === false) {
        return false;
    }

    $main_content = substr($file_content, $main_start + strlen('<main>'), $main_end - $main_start - strlen('<main>'));
    return trim($main_content);
}

// Rich HTML content templates for each page
$rich_content_templates = [
    'careers' => '
<section class="bg-neutral-50 py-20 md:py-28">
    <div class="container mx-auto px-4 md:px-6">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-4">Join The Mossé Luxe Family</h2>
            <p class="text-lg text-black/70">Be part of our journey in redefining urban luxury. We\'re looking for passionate individuals who share our vision of timeless style and uncompromising quality.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
            <div class="bg-white p-8 rounded-lg shadow-lg">
                <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L8 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <h3 class="text-xl font-bold uppercase tracking-wider mb-4">Craftsmanship</h3>
                <p class="text-black/70 mb-6">Seeking experienced artisans and craftsmen who understand the art of creating luxury leather goods.</p>
                <a href="#apply" class="text-black font-bold uppercase tracking-wider border-b-2 border-black pb-1 hover:text-gray-700 transition-colors">Apply Now</a>
            </div>

            <div class="bg-white p-8 rounded-lg shadow-lg">
                <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M21 13v6a3 3 0 01-3 3H6a3 3 0 01-3-3v-6h18zM12 2l8 8H4l8-8z"/></svg>
                </div>
                <h3 class="text-xl font-bold uppercase tracking-wider mb-4">Retail & Sales</h3>
                <p class="text-black/70 mb-6">Passionate about fashion and customer service? Join our retail team and help curate luxury experiences.</p>
                <a href="#apply" class="text-black font-bold uppercase tracking-wider border-b-2 border-black pb-1 hover:text-gray-700 transition-colors">Apply Now</a>
            </div>

            <div class="bg-white p-8 rounded-lg shadow-lg">
                <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-xl font-bold uppercase tracking-wider mb-4">Operations</h3>
                <p class="text-black/70 mb-6">Contribute to streamlining our operations and ensuring every customer receives exceptional service.</p>
                <a href="#apply" class="text-black font-bold uppercase tracking-wider border-b-2 border-black pb-1 hover:text-gray-700 transition-colors">Apply Now</a>
            </div>
        </div>

        <div class="text-center">
            <a href="mailto:careers@mosseluxe.com" class="inline-block bg-black text-white py-4 px-12 font-bold uppercase rounded-md tracking-wider hover:bg-black/80 transition-colors">
                Email Your Resume
            </a>
        </div>
    </div>
</section>',

    'faq' => '
<section class="bg-white py-20 md:py-28">
    <div class="container mx-auto px-4 md:px-6 max-w-4xl">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter">Frequently Asked Questions</h2>
            <p class="text-lg text-black/70 mt-4">Find answers to common questions about our products, shipping, returns, and more.</p>
        </div>

        <div class="space-y-6">
            <div class="border-b border-black/10 pb-6">
                <button class="faq-toggle flex justify-between items-center w-full text-left" onclick="toggleFAQ(this)">
                    <span class="text-xl font-bold">What materials do you use for your leather goods?</span>
                    <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out mt-4">
                    <p class="text-black/70 leading-relaxed">We source the finest Italian vegetable-tanned leather, aged naturally for superior texture and durability. All our materials are ethically sourced and meet the highest quality standards for luxury leather goods.</p>
                </div>
            </div>

            <div class="border-b border-black/10 pb-6">
                <button class="faq-toggle flex justify-between items-center w-full text-left" onclick="toggleFAQ(this)">
                    <span class="text-xl font-bold">How should I care for my leather products?</span>
                    <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out mt-4">
                    <p class="text-black/70 leading-relaxed">Apply a leather conditioner every 3-6 months using a clean, soft cloth. Avoid direct sunlight, extreme temperatures, and moisture. Store your items in our dust bags when not in use for optimal preservation.</p>
                </div>
            </div>

            <div class="border-b border-black/10 pb-6">
                <button class="faq-toggle flex justify-between items-center w-full text-left" onclick="toggleFAQ(this)">
                    <span class="text-xl font-bold">Do you offer custom sizing?</span>
                    <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out mt-4">
                    <p class="text-black/70 leading-relaxed">Yes, we offer bespoke fitting for our premium belt and accessory collections. Contact our team to discuss custom sizing options and pricing for personalized pieces.</p>
                </div>
            </div>

            <div class="border-b border-black/10 pb-6">
                <button class="faq-toggle flex justify-between items-center w-full text-left" onclick="toggleFAQ(this)">
                    <span class="text-xl font-bold">How long does shipping take?</span>
                    <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out mt-4">
                    <p class="text-black/70 leading-relaxed">Standard shipping within South Africa takes 3-5 business days. Express shipping is available for 1-2 business day delivery. International shipping takes 7-14 business days depending on destination.</p>
                </div>
            </div>
        </div>

        <div class="text-center mt-16">
            <p class="text-black/70 mb-6">Can\'t find what you\'re looking for?</p>
            <a href="contact.php" class="inline-block bg-black text-white py-3 px-8 font-bold uppercase rounded-md tracking-wider hover:bg-black/80 transition-colors">
                Contact Us
            </a>
        </div>
    </div>
</section>

<style>
.faq-content { max-height: 0; overflow: hidden; transition: max-height 0.5s ease-in-out; }
.faq-content.open { max-height: 200px; }

.faq-toggle svg { transition: transform 0.3s ease; }
.faq-toggle.active svg { transform: rotate(180deg); }
</style>

<script>
function toggleFAQ(button) {
    const content = button.nextElementSibling;
    const icon = button.querySelector(\'svg\');

    button.classList.toggle(\'active\');
    content.classList.toggle(\'open\');

    if (content.classList.contains(\'open\')) {
        content.style.maxHeight = content.scrollHeight + "px";
    } else {
        content.style.maxHeight = "0";
        setTimeout(() => {
            content.style.maxHeight = "";
        }, 500);
    }
}
</script>',

    'shipping-returns' => '
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
                                    <p class="text-sm text-black/70">We\'ll provide a prepaid return shipping label</p>
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
</section>',

    'privacy-policy' => '
<section class="bg-white py-20 md:py-28">
    <div class="container mx-auto px-4 md:px-6">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-4">Privacy Policy</h2>
                <p class="text-lg text-black/70">Your privacy matters to us. This policy outlines how we collect, use, and protect your personal information.</p>
            </div>

            <div class="bg-white p-8 md:p-12 rounded-lg shadow-lg space-y-8">
                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Information We Collect</h3>
                    <div class="space-y-4 text-black/70 leading-relaxed">
                        <p>We collect information to provide better services and improve your experience with Mossé Luxe. This includes:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Personal Information:</strong> Name, email address, shipping address, phone number</li>
                            <li><strong>Payment Information:</strong> Processed securely through our payment partners</li>
                            <li><strong>Usage Data:</strong> Website interactions, browsing preferences, device information</li>
                            <li><strong>Communication Records:</strong> Customer service inquiries and responses</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">How We Use Your Information</h3>
                    <div class="space-y-4 text-black/70 leading-relaxed">
                        <p>Your information helps us deliver an exceptional shopping experience:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Process and fulfill your orders</li>
                            <li>Provide customer support and respond to inquiries</li>
                            <li>Send order confirmations, shipping updates, and service communications</li>
                            <li>Improve our website and product offerings</li>
                            <li>Send marketing communications (with your consent)</li>
                            <li>Prevent fraud and maintain security</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Information Sharing & Security</h3>
                    <div class="space-y-4 text-black/70 leading-relaxed">
                        <p class="font-medium">We do not sell, trade, or rent your personal information to third parties.</p>
                        <p>We may share your information only in these limited circumstances:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Shipping partners</strong> to deliver your orders</li>
                            <li><strong>Payment processors</strong> to complete transactions</li>
                            <li><strong>Legal requirements</strong> when required by law</li>
                            <li><strong>Business protection</strong> to prevent fraud or harm</li>
                        </ul>

                        <div class="bg-neutral-50 p-4 rounded-lg mt-4">
                            <p class="text-sm"><strong>Security Measures:</strong></p>
                            <ul class="text-sm mt-2 space-y-1">
                                <li>• SSL encryption for all data transmission</li>
                                <li>• Secure servers with regular security audits</li>
                                <li>• Strict access controls and data monitoring</li>
                                <li>• Regular security protocol updates</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Your Rights & Choices</h3>
                    <div class="space-y-4 text-black/70 leading-relaxed">
                        <p>You have the following rights regarding your personal information:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-bold mb-2">Access & Portability</h4>
                                <p class="text-sm">Request a copy of your personal data</p>
                            </div>
                            <div>
                                <h4 class="font-bold mb-2">Data Correction</h4>
                                <p class="text-sm">Update inaccurate or incomplete information</p>
                            </div>
                            <div>
                                <h4 class="font-bold mb-2">Deletion</h4>
                                <p class="text-sm">Request removal of your personal data</p>
                            </div>
                            <div>
                                <h4 class="font-bold mb-2">Marketing Opt-Out</h4>
                                <p class="text-sm">Unsubscribe from promotional communications</p>
                            </div>
                        </div>

                        <div class="bg-black text-white p-6 rounded-lg mt-6">
                            <p class="font-medium mb-2">Contact Our Privacy Team</p>
                            <p class="text-sm opacity-90">For privacy-related requests or questions about this policy, please contact us at:</p>
                            <p class="text-sm font-bold mt-2">privacy@mosseluxe.com</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Policy Updates</h3>
                    <p class="text-black/70 leading-relaxed">This privacy policy may be updated periodically to reflect changes in our practices or legal requirements. We will notify you of any material changes via email or a prominent notice on our website.</p>
                    <p class="text-sm text-black/60 mt-4"><strong>Last updated:</strong> November 17, 2025</p>
                </div>
            </div>

            <div class="text-center mt-12">
                <p class="text-black/70 mb-4">Have questions about your privacy?</p>
                <a href="contact.php" class="inline-block bg-black text-white py-3 px-8 font-bold uppercase rounded-md tracking-wider hover:bg-black/80 transition-colors">
                    Contact Us
                </a>
            </div>
        </div>
    </div>
</section>',

    'terms-of-service' => '
<section class="bg-white py-20 md:py-28">
    <div class="container mx-auto px-4 md:px-6">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-4">Terms of Service</h2>
                <p class="text-lg text-black/70">Please read these terms carefully before using Mossé Luxe. By accessing our website and purchasing our products, you agree to be bound by these terms.</p>
            </div>

            <div class="bg-white p-8 md:p-12 rounded-lg shadow-lg space-y-8">
                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Acceptance of Terms</h3>
                    <div class="text-black/70 leading-relaxed space-y-4">
                        <p>By accessing and using the Mossé Luxe website, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this website.</p>
                        <p>These terms apply to all visitors, users, and others who access or use our service, including but not limited to online shopping, customer inquiries, and account registration.</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Products & Pricing</h3>
                    <div class="text-black/70 leading-relaxed space-y-4">
                        <p>We strive to provide accurate product descriptions, specifications, and pricing information. However, we reserve the right to correct errors or omissions at any time without prior notice.</p>
                        <div class="bg-neutral-50 p-4 rounded-lg">
                            <p class="text-sm mb-2"><strong>Pricing Policy:</strong></p>
                            <ul class="text-sm space-y-1">
                                <li>• All prices are displayed in South African Rand (ZAR)</li>
                                <li>• Prices are subject to change without notice</li>
                                <li>• Value-Added Tax (VAT) is included in all prices</li>
                                <li>• Shipping costs are calculated separately</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Order Acceptance & Processing</h3>
                    <div class="text-black/70 leading-relaxed space-y-4">
                        <p>Your order constitutes an offer to purchase our products. All orders are subject to acceptance and availability. We reserve the right to refuse or cancel any order for any reason, including but not limited to:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Product unavailability</li>
                            <li>Payment authorization issues</li>
                            <li>Incorrect or incomplete information</li>
                            <li>Fraudulent or suspicious activity</li>
                            <li>Violation of these terms</li>
                        </ul>
                        <p>Once an order is confirmed and payment processed, you will receive an order confirmation email.</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Shipping & Delivery</h3>
                    <div class="text-black/70 leading-relaxed space-y-4">
                        <p>Delivery times are estimates only and may vary due to factors beyond our control. We will make reasonable efforts to meet these timeframes but cannot guarantee specific delivery dates.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                            <div class="bg-neutral-50 p-4 rounded-lg">
                                <h4 class="font-bold mb-2">Standard Delivery</h4>
                                <p class="text-sm">3-5 business days<br><strong>R 150</strong> (Free over R 1500)</p>
                            </div>
                            <div class="bg-neutral-50 p-4 rounded-lg">
                                <h4 class="font-bold mb-2">Express Delivery</h4>
                                <p class="text-sm">1-2 business days<br><strong>R 300</strong></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Returns & Refunds</h3>
                    <div class="text-black/70 leading-relaxed space-y-4">
                        <p>We want you to be completely satisfied with your purchase. If for any reason you are not happy with your item, we accept returns within 30 days of purchase.</p>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-sm font-medium mb-2 text-red-800">Return Conditions:</p>
                            <ul class="text-sm text-red-700 space-y-1">
                                <li>• Items must be unworn, unwashed, and in original condition</li>
                                <li>• All tags must be attached</li>
                                <li>• Items must have original packaging</li>
                                <li>• Custom or personalized items cannot be returned</li>
                            </ul>
                        </div>
                        <p>Please refer to our dedicated Shipping & Returns page for detailed instructions on how to initiate a return.</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">User Account & Security</h3>
                    <div class="text-black/70 leading-relaxed space-y-4">
                        <p>If you create an account with us, you are responsible for:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Maintaining the confidentiality of your account credentials</li>
                            <li>All activities that occur under your account</li>
                            <li>Notifying us immediately of any unauthorized use</li>
                            <li>Providing accurate and complete information</li>
                        </ul>
                        <p class="font-medium">We reserve the right to terminate accounts that violate these terms or engage in fraudulent activity.</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Intellectual Property</h3>
                    <div class="text-black/70 leading-relaxed">
                        <p>The Mossé Luxe website and its original content, features, and functionality are and will remain the exclusive property of Mossé Luxe and its licensors. The service is protected by copyright, trademark, and other laws.</p>
                        <p>You may not duplicate, copy, or reuse any portion of the HTML/CSS/JavaScript code or visual design elements without express written permission.</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Limitation of Liability</h3>
                    <div class="text-black/70 leading-relaxed">
                        <p>Mossé Luxe is not liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses.</p>
                        <p>Our total liability shall not exceed the amount paid for the specific product or service that caused the liability.</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Prohibited Uses</h3>
                    <div class="space-y-4 text-black/70 leading-relaxed">
                        <p>You may not use our website or services for any unlawful purpose or to solicit the performance of any unlawful activity.</p>
                        <p>Prohibited activities include but are not limited to:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Attempting to hack, compromise, or otherwise interfere with our systems</li>
                            <li>Using automated tools to access our website without permission</li>
                            <li>Sharing false or misleading information</li>
                            <li>Violating the intellectual property rights of others</li>
                            <li>Harassing other users or our staff</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Contact Information</h3>
                    <p class="text-black/70 leading-relaxed mb-4">If you have any questions about these Terms of Service, please contact us:</p>
                    <div class="bg-black text-white p-6 rounded-lg">
                        <p class="font-bold">Mossé Luxe Customer Service</p>
                        <p class="mt-2">Email: info@mosseluxe.com</p>
                        <p>Phone: +27 67 616 0928</p>
                        <p class="mt-2 text-sm opacity-90">We aim to respond to all inquiries within 24-48 hours.</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold uppercase tracking-wider mb-6 border-b-2 border-black pb-3">Changes to Terms</h3>
                    <p class="text-black/70 leading-relaxed">We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting on the website. Continued use of our services constitutes acceptance of the updated terms.</p>
                    <p class="text-sm text-black/60 mt-4"><strong>Last updated:</strong> November 17, 2025</p>
                </div>
            </div>

            <div class="text-center mt-12">
                <p class="text-black/70 mb-4">Have questions about these terms?</p>
                <a href="contact.php" class="inline-block bg-black text-white py-3 px-8 font-bold uppercase rounded-md tracking-wider hover:bg-black/80 transition-colors">
                    Contact Us
                </a>
            </div>
        </div>
    </div>
</section>'
];

echo "Attempting to update pages with rich content...\n\n";

foreach ($rich_content_templates as $slug => $content) {
    if ($slug !== 'about' && $slug !== 'contact') { // Already handled
        // Update the database
        $update_sql = "UPDATE pages SET content = ? WHERE slug = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ss", $content, $slug);

        if ($stmt->execute()) {
            echo "✅ Updated content for page: $slug (" . $stmt->affected_rows . " row affected)\n";
        } else {
            echo "❌ Error updating $slug: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

echo "\nRich content update complete!\n";

echo "\nUpdating pages from files...\n";
foreach ($page_content_map as $slug => $file) {
    // Skip pages that already have rich content templates
    if (array_key_exists($slug, $rich_content_templates)) {
        echo "Skipping $slug (has rich template)\n";
        continue;
    }

    if (file_exists($file)) {
        $file_content = file_get_contents($file);
        $content = extract_content_from_main_section($file_content);
        if ($content !== false) {
            $update_sql = "UPDATE pages SET content = ? WHERE slug = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ss", $content, $slug);
            if ($stmt->execute()) {
                echo "✅ Updated content for page: $slug from file (" . $stmt->affected_rows . " row affected)\n";
            } else {
                echo "❌ Error updating $slug: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "Failed to extract content from $file\n";
        }
    } else {
        echo "File not found: $file\n";
    }
}

$conn->close();
echo "\nPage content update complete.\n";
?>
