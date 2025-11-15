        </main> <!-- End Main Content -->

        <!-- Footer -->
        <footer class="py-16 md:py-24 bg-white border-t border-black/10">
            <div class="container mx-auto px-4 md:px-6">
                <!-- Footer Links Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-12">
                    <!-- Column 1: Company -->
                    <div>
                        <h5 class="font-bold uppercase tracking-wider mb-4">Company</h5>
                        <ul class="space-y-3">
                            <li><a href="<?php echo SITE_URL; ?>/about" class="text-sm text-black/60 hover:text-black transition-colors">About Us</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/contact" class="text-sm text-black/60 hover:text-black transition-colors">Contact</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/careers" class="text-sm text-black/60 hover:text-black transition-colors">Careers</a></li>
                        </ul>
                    </div>
                    <!-- Column 2: Help -->
                    <div>
                        <h5 class="font-bold uppercase tracking-wider mb-4">Help</h5>
                        <ul class="space-y-3">
                            <li><a href="<?php echo SITE_URL; ?>/faq" class="text-sm text-black/60 hover:text-black transition-colors">FAQs</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/shipping-returns" class="text-sm text-black/60 hover:text-black transition-colors">Shipping & Returns</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/track-order" class="text-sm text-black/60 hover:text-black transition-colors">Track Order</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/my-account" class="text-sm text-black/60 hover:text-black transition-colors">My Account</a></li>
                            <?php if (isset($_SESSION['loggedin']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <li><a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="text-sm text-black/60 hover:text-black transition-colors font-bold">Admin Panel</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <!-- Column 3: Legal -->
                    <div>
                        <h5 class="font-bold uppercase tracking-wider mb-4">Legal</h5>
                        <ul class="space-y-3">
                            <li><a href="<?php echo SITE_URL; ?>/privacy-policy" class="text-sm text-black/60 hover:text-black transition-colors">Privacy Policy</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/terms-of-service" class="text-sm text-black/60 hover:text-black transition-colors">Terms & Conditions</a></li>
                        </ul>
                    </div>
                    <!-- Column 4: Follow Us -->
                    <div>
                        <h5 class="font-bold uppercase tracking-wider mb-4">Follow Us</h5>
                        <div class="flex space-x-4">
                            <a href="https://www.instagram.com/mosseluxe/" aria-label="Instagram"><!-- Insert Instagram URL Here -->
                                <svg class="w-6 h-6 text-black/60 hover:text-black transition-colors" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12.315 2c-4.068 0-4.6 0-6.18 0-1.58 0-2.65.1-3.57.5-1.02.4-1.8.9-2.6 1.8-.7.7-1.3 1.5-1.7 2.6-.4 1-.5 2-.5 3.5v6.2c0 1.6 0 2.2.5 3.6.4 1 1 1.8 1.8 2.6.7.7 1.5 1.3 2.6 1.7 1 .4 2 .5 3.6.5h6.2c1.6 0 2.2 0 3.6-.5 1-.4 1.8-1 2.6-1.8.7-.7 1.3-1.5 1.7-2.6.4-1 .5-2 .5-3.6v-6.2c0-1.6 0-2.2-.5-3.6-.4-1-1-1.8-1.8-2.6-.7-.7-1.5-1.3-2.6-1.7-1-.4-2-.5-3.6-.5h-6.2zM12 4.1c1.5 0 1.7 0 2.3.1.6.1 1 .2 1.5.4.5.2.9.5 1.3.9.4.4.7.8.9 1.3.2.5.3 1 .4 1.5.1.6.1.8.1 2.3s0 1.7-.1 2.3c-.1.6-.2 1-.4 1.5-.2.5-.5.9-.9 1.3-.4.4-.8.7-1.3.9-.5.2-1 .3-1.5.4-.6.1-.8.1-2.3.1s-1.7 0-2.3-.1c-.6-.1-1-.2-1.5-.4-.5-.2-.9-.5-1.3-.9-.4-.4-.7-.8-.9-1.3-.2-.5-.3-1-.4-1.5-.1-.6-.1-.8-.1-2.3s0-1.7.1-2.3c.1-.6.2-1 .4-1.5.2-.5.5-.9.9-1.3.4-.4.8-.7 1.3-.9.5-.2 1-.3 1.5-.4.6-.1.8-.1 2.3-.1zM12 7.1a5 5 0 100 10 5 5 0 000-10zm0 8.2a3.1 3.1 0 110-6.2 3.1 3.1 0 010 6.2zM16.9 6.1a1.2 1.2 0 100 2.4 1.2 1.2 0 000-2.4z" clip-rule="evenodd" /></svg>
                            </a>
                            <a href="https://twitter.com/mosseluxe" aria-label="Twitter"><!-- Insert Twitter URL Here -->
                                <svg class="w-6 h-6 text-black/60 hover:text-black transition-colors" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" /></svg>
                            </a>
                            <a href="https://www.facebook.com/mosseluxe" aria-label="Facebook"><!-- Insert Facebook URL Here -->
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

    <!-- Yoco SDK -->
    <script src="https://js.yoco.com/sdk/v1/yoco-sdk-web.js"></script>

    <script src="<?php echo SITE_URL; ?>assets/js/main.js"></script>

    <!-- WhatsApp Component -->
    <?php
    define('IN_MOSSE_LUXE', true);
    include 'whatsapp_component.php';
    ?>



    </body>
    </html>
