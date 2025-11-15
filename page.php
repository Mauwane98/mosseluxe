<?php
require_once 'includes/bootstrap.php';

$conn = get_db_connection();

$page = null;
$content = '';

if (isset($_GET['slug'])) {
    $slug = trim($_GET['slug']);

    // Fetch page content from database
    $sql = "SELECT id, title, subtitle, content FROM pages WHERE slug = ? AND status = 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $page = $result->fetch_assoc();
            // Use title if available, otherwise use "Untitled Page" for display
            $displayTitle = !empty(trim($page['title'])) ? trim($page['title']) : 'Untitled Page';
            $pageTitle = htmlspecialchars($displayTitle) . " - Mossé Luxe";
            $content = $page['content'];
        }
        $stmt->close();
    }
}

require_once 'includes/header.php';

$conn->close();
?>

<!-- Main Content -->
<main>
    <div class="container mx-auto px-4 py-16 md:py-24">
        <?php if ($page): ?>
            <!-- Page Content -->
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-12">
                    <?php if (!empty($page['title'])): ?>
                        <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter"><?php echo htmlspecialchars($page['title']); ?></h1>
                    <?php endif; ?>
                    <?php if (!empty($page['subtitle'])): ?>
                        <p class="mt-4 text-lg text-black/70"><?php echo htmlspecialchars($page['subtitle']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="bg-white p-8 md:p-12 rounded-lg shadow-md">
                    <div class="prose prose-lg max-w-none">
                        <?php echo $content; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php http_response_code(404); ?>
            <!-- Page Not Found -->
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-black uppercase tracking-tighter">Page Not Found</h1>
                <p class="mt-4 text-lg text-black/70">The page you are looking for does not exist or has been removed.</p>
                <div class="mt-8">
                    <a href="<?php echo SITE_URL; ?>" class="text-lg font-semibold text-black border-b-2 border-black hover:border-transparent transition-colors">
                        Go Home
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
