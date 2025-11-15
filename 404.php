<?php
$pageTitle = "Page Not Found - Mossé Luxe";
require_once 'includes/bootstrap.php';
require_once 'includes/header.php';
?>

<!-- Main Content -->
<main>
    <div class="flex items-center justify-center min-h-screen bg-neutral-50">
        <div class="text-center px-6">
            <!-- 404 Number -->
            <div class="text-8xl md:text-9xl font-black uppercase tracking-tighter text-black/10 mb-8">
                404
            </div>

            <!-- Error Message -->
            <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter mb-6">
                Page Not Found
            </h1>

            <p class="text-lg text-black/70 mb-12 max-w-md mx-auto">
                Sorry, the page you are looking for doesn't exist or has been moved.
            </p>

            <!-- Action Buttons -->
            <div class="space-y-4 md:space-y-0 md:space-x-4 md:flex md:justify-center md:items-center">
                <a href="<?php echo SITE_URL; ?>" class="inline-block bg-black text-white py-4 px-8 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider">
                    Go Home
                </a>
                <a href="<?php echo SITE_URL; ?>shop" class="inline-block bg-white text-black py-4 px-8 font-bold uppercase rounded-md border-2 border-black hover:bg-black hover:text-white transition-colors tracking-wider">
                    Browse Products
                </a>
            </div>

            <!-- Back Button -->
            <div class="mt-12">
                <button onclick="history.back()" class="text-sm text-black/60 hover:text-black transition-colors underline">
                    ← Go Back
                </button>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>

<style>
    /* Override main page wrapper styles for full height */
    #page-wrapper {
        min-height: 100vh;
    }

    main {
        min-height: 100vh;
        display: flex;
        align-items: center;
    }
</style>
