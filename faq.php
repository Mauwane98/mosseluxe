<?php
$pageTitle = "FAQ - Mossé Luxe";
require_once 'includes/bootstrap.php';
require_once 'includes/header.php';
?>

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
            <p class="text-black/70 mb-6">Can't find what you're looking for?</p>
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
    const icon = button.querySelector('svg');

    button.classList.toggle('active');
    content.classList.toggle('open');

    if (content.classList.contains('open')) {
        content.style.maxHeight = content.scrollHeight + "px";
    } else {
        content.style.maxHeight = "0";
        setTimeout(() => {
            content.style.maxHeight = "";
        }, 500);
    }
}
</script>

<?php
require_once 'includes/footer.php';
?>
