        </main> <!-- End Main Content -->

        <!-- Footer -->
        <footer class="py-16 md:py-24 bg-white border-t border-black/10">
            <div class="container mx-auto px-4 md:px-6">
                <!-- Newsletter Section -->
                <div class="mb-16 text-center max-w-2xl mx-auto">
                    <h3 class="text-2xl md:text-3xl font-bold uppercase tracking-tight mb-3">Stay in the Loop</h3>
                    <p class="text-black/60 mb-6">Subscribe to get special offers, free giveaways, and exclusive deals.</p>
                    <form id="newsletter-form" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="email" name="email" id="newsletter-email" placeholder="Enter your email" required class="flex-1 px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-black focus:border-transparent text-sm">
                        <button type="submit" class="bg-black text-white px-6 py-3 rounded-md font-semibold hover:bg-black/80 transition-colors whitespace-nowrap">
                            Subscribe
                        </button>
                    </form>
                    <p id="newsletter-message" class="mt-3 text-sm hidden"></p>
                </div>

                <!-- Footer Links Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-12">
                    <!-- Column 1: Company -->
                    <div>
                        <h5 class="font-bold uppercase tracking-wider mb-4"><?php echo htmlspecialchars(get_setting('footer_company_title', 'Company')); ?></h5>
                        <ul class="space-y-3">
                            <li><a href="<?php echo SITE_URL; ?>about" class="nav-link text-sm text-black/60 hover:text-black transition-colors">About Us</a></li>
                            <li><a href="<?php echo SITE_URL; ?>contact" class="nav-link text-sm text-black/60 hover:text-black transition-colors">Contact</a></li>
                            <li><a href="<?php echo SITE_URL; ?>careers" class="nav-link text-sm text-black/60 hover:text-black transition-colors">Careers</a></li>
                        </ul>
                    </div>
                    <!-- Column 2: Help -->
                    <div>
                        <h5 class="font-bold uppercase tracking-wider mb-4"><?php echo htmlspecialchars(get_setting('footer_help_title', 'Help')); ?></h5>
                        <ul class="space-y-3">
                            <li><a href="<?php echo SITE_URL; ?>faq" class="nav-link text-sm text-black/60 hover:text-black transition-colors">FAQs</a></li>
                            <li><a href="<?php echo SITE_URL; ?>shipping-returns" class="nav-link text-sm text-black/60 hover:text-black transition-colors">Shipping & Returns</a></li>
                            <li><a href="<?php echo SITE_URL; ?>track_order" class="nav-link text-sm text-black/60 hover:text-black transition-colors">Track Order</a></li>
                            <li><a href="<?php echo SITE_URL; ?>my_account" class="nav-link text-sm text-black/60 hover:text-black transition-colors">My Account</a></li>
                            <?php if (isset($_SESSION['loggedin']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <li><a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="text-sm text-black/60 hover:text-black transition-colors font-bold">Admin Panel</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <!-- Column 3: Legal -->
                    <div>
                        <h5 class="font-bold uppercase tracking-wider mb-4"><?php echo htmlspecialchars(get_setting('footer_legal_title', 'Legal')); ?></h5>
                        <ul class="space-y-3">
                            <li><a href="<?php echo SITE_URL; ?>privacy-policy" class="nav-link text-sm text-black/60 hover:text-black transition-colors">Privacy Policy</a></li>
                            <li><a href="<?php echo SITE_URL; ?>terms-of-service" class="nav-link text-sm text-black/60 hover:text-black transition-colors">Terms & Conditions</a></li>
                        </ul>
                    </div>
                    <!-- Column 4: Follow Us -->
                    <div>
                        <h5 class="font-bold uppercase tracking-wider mb-4"><?php echo htmlspecialchars(get_setting('footer_follow_title', 'Follow Us')); ?></h5>
                        <div class="flex space-x-4">
                            <a href="<?php echo INSTAGRAM_URL; ?>" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Instagram">
                                <svg class="w-6 h-6 text-black/60 hover:text-black transition-colors" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.315 2c-4.068 0-4.6 0-6.18 0-1.58 0-2.65.1-3.57.5-1.02.4-1.8.9-2.6 1.8-.7.7-1.3 1.5-1.7 2.6-.4 1-.5 2-.5 3.5v6.2c0 1.6 0 2.2.5 3.6.4 1 1 1.8 1.8 2.6.7.7 1.5 1.3 2.6 1.7 1 .4 2 .5 3.6.5h6.2c1.6 0 2.2 0 3.6-.5 1-.4 1.8-1 2.6-1.8.7-.7 1.3-1.5 1.7-2.6.4-1 .5-2 .5-3.6v-6.2c0-1.6 0-2.2-.5-3.6-.4-1-1-1.8-1.8-2.6-.7-.7-1.5-1.3-2.6-1.7-1-.4-2-.5-3.6-.5h-6.2zM12 4.1c1.5 0 1.7 0 2.3.1.6.1 1 .2 1.5.4.5.2.9.5 1.3.9.4.4.7.8.9 1.3.2.5.3 1 .4 1.5.1.6.1.8.1 2.3s0 1.7-.1 2.3c-.1.6-.2 1-.4 1.5-.2.5-.5.9-.9 1.3-.4.4-.8.7-1.3.9-.5.2-1 .3-1.5.4-.6.1-.8.1-2.3.1s-1.7 0-2.3-.1c-.6-.1-1-.2-1.5-.4-.5-.2-.9-.5-1.3-.9-.4-.4-.7-.8-.9-1.3-.2-.5-.3-1-.4-1.5-.1-.6-.1-.8-.1-2.3s0-1.7.1-2.3c.1-.6.2-1 .4-1.5.2-.5.5-.9.9-1.3.4-.4.8-.7 1.3-.9.5-.2 1-.3 1.5-.4.6-.1.8-.1 2.3-.1zM12 7.1a5 5 0 100 10 5 5 0 000-10zm0 8.2a3.1 3.1 0 110-6.2 3.1 3.1 0 010 6.2zM16.9 6.1a1.2 1.2 0 100 2.4 1.2 1.2 0 000-2.4z"
                                          clip-rule="evenodd" />
                                </svg>
                            </a>
                            <a href="<?php echo TWITTER_URL; ?>" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Twitter/X">
                                <svg class="w-6 h-6 text-black/60 hover:text-black transition-colors" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" /></svg>
                            </a>
                            <a href="<?php echo FACEBOOK_URL; ?>" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Facebook">
                                <svg class="w-6 h-6 text-black/60 hover:text-black transition-colors" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Copyright -->
                <div class="text-center mt-12 pt-8 border-t border-black/10 text-sm text-black/40">
                    &copy; <?php echo date('Y'); ?> Mossé Luxe. All Rights Reserved.
                </div>
            </div>
        </footer>

        <div id="toast-container"></div>

    </div> <!-- End Page Wrapper -->

<?php if (!isset($_GET['no_js'])): ?>
    <!-- Modal System -->
    <script src="<?php echo SITE_URL; ?>assets/js/modals.js"></script>
    
    <!-- Referral Notification -->
    <script src="<?php echo SITE_URL; ?>assets/js/referral-notification.js"></script>
    
    <!-- Yoco SDK -->
    <script src="<?php echo SITE_URL; ?>assets/js/cart-ui.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/cart.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/quick-view.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/countdown-timer.js"></script>

    <!-- Accessibility and Interactive Features -->
    <script src="<?php echo SITE_URL; ?>assets/js/accessibility.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/interactive-features.js"></script>

    <!-- Toast Notification System -->
    <script src="<?php echo SITE_URL; ?>assets/js/toast.js"></script>
    <!-- Loading State Utilities -->
    <script src="<?php echo SITE_URL; ?>assets/js/loading.js"></script>

    <script>
        // Newsletter Form Handler
        document.getElementById('newsletter-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('newsletter-email').value;
            const message = document.getElementById('newsletter-message');
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            
            button.textContent = 'Subscribing...';
            button.disabled = true;
            
            // Simulate API call (replace with actual endpoint)
            setTimeout(() => {
                message.textContent = '✓ Thanks for subscribing! Check your email for confirmation.';
                message.className = 'mt-3 text-sm text-green-600';
                message.classList.remove('hidden');
                document.getElementById('newsletter-email').value = '';
                button.textContent = 'Subscribed!';
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                    message.classList.add('hidden');
                }, 3000);
            }, 1000);
        });
        
        // Initialize the CartAPI with the CSRF token from the server
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = "<?php echo generate_csrf_token(); ?>";
            if (window.CartAPI) {
                window.CartAPI.init(csrfToken);
            }
        });
    </script>
<?php endif; ?>

    <!-- WhatsApp Component -->
    <?php
    // Include chat component if it exists
    if (file_exists(__DIR__ . '/chat_component.php')) {
        include __DIR__ . '/chat_component.php';
    }
    
    // Include WhatsApp component
    if (file_exists(__DIR__ . '/whatsapp_component.php')) {
        include __DIR__ . '/whatsapp_component.php';
    }
    ?>

    </body>
    </html>
