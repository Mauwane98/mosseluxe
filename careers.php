<?php
$pageTitle = "Careers - Mossé Luxe";
require_once 'includes/bootstrap.php';
require_once 'includes/header.php';
?>

<main>
<section class="bg-neutral-50 py-20 md:py-28">
    <div class="container mx-auto px-4 md:px-6">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-4">Join The Mossé Luxe Family</h2>
            <p class="text-lg text-black/70">Be part of our journey in redefining urban luxury. We're looking for passionate individuals who share our vision of timeless style and uncompromising quality.</p>
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
</section>
</main>

<?php
require_once 'includes/footer.php';
?>
