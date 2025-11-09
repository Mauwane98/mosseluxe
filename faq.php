<?php
$pageTitle = "Frequently Asked Questions - Mossé Luxe";
require_once 'includes/header.php';
?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <!-- Page Content -->
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Frequently Asked Questions</h1>
            </div>

            <div class="bg-white p-8 md:p-12 rounded-lg shadow-md">
                <div class="space-y-4" id="faq-accordion">
                    <!-- FAQ Item 1 -->
                    <div class="border-b border-black/10 pb-4">
                        <button class="faq-toggle flex justify-between items-center w-full text-left">
                            <span class="text-lg font-bold">What are your shipping options?</span>
                            <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">
                            <p class="pt-4 text-black/70">We offer standard and express shipping options nationwide. Standard shipping typically takes 3-5 business days, while express shipping takes 1-2 business days. All orders over R1500 qualify for free standard shipping.</p>
                        </div>
                    </div>
                    <!-- FAQ Item 2 -->
                    <div class="border-b border-black/10 pb-4">
                        <button class="faq-toggle flex justify-between items-center w-full text-left">
                            <span class="text-lg font-bold">How can I track my order?</span>
                            <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">
                            <p class="pt-4 text-black/70">Once your order has been shipped, you will receive an email with a tracking number and a link to the courier's website. You can also use our Track Order page to check the status.</p>
                        </div>
                    </div>
                    <!-- FAQ Item 3 -->
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
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const faqToggles = document.querySelectorAll('.faq-toggle');
    faqToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const content = toggle.nextElementSibling;
            const icon = toggle.querySelector('svg');

            if (content.style.maxHeight) {
                content.style.maxHeight = null;
                icon.style.transform = 'rotate(0deg)';
            } else {
                content.style.maxHeight = content.scrollHeight + 'px';
                icon.style.transform = 'rotate(180deg)';
            }
        });
    });
});
</script>
<?php
require_once 'includes/footer.php';
?>
