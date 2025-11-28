<?php
require_once 'includes/bootstrap.php';
$conn = get_db_connection();

// Pages to create with rich content
$pages_to_create = [
    'careers' => [
        'title' => 'Careers',
        'slug' => 'careers',
        'content' => '
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
        'status' => 1
    ],

    'faq' => [
        'title' => 'FAQ',
        'slug' => 'faq',
        'content' => '
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
        'status' => 1
    ]
];

foreach ($pages_to_create as $slug => $page_data) {
    // Check if page already exists
    $check_sql = "SELECT id FROM pages WHERE slug = ? AND status = 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $slug);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_stmt->close();

    if ($check_result->num_rows == 0) {
        // Create the page
        $insert_sql = "INSERT INTO pages (title, slug, content, status) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssi", $page_data['title'], $page_data['slug'], $page_data['content'], $page_data['status']);

        if ($insert_stmt->execute()) {
            echo "✅ Created page: {$page_data['title']} ({$page_data['slug']})\n";
        } else {
            echo "❌ Error creating {$slug}: " . $insert_stmt->error . "\n";
        }
        $insert_stmt->close();
    } else {
        echo "⚠️  Page {$slug} already exists\n";
    }
}

$conn->close();
echo "\nMissing pages creation complete.\n";
?>
