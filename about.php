<?php
$pageTitle = "About Us - Mossé Luxe";
require_once 'includes/header.php';
?>

<!-- Main Content -->
<main>
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
                <span class="text-white/80">“Mossé”</span> to honor the man who started it all. 
                <span class="text-white/80">“Luxe”</span> for a luxury defined by quality, not price.
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
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
