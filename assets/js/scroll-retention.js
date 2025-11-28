document.addEventListener('DOMContentLoaded', function() {
    // Page Scroll Position Retention System
    // Restores scroll position after page reloads, navigations, and forms

    const SCROLL_STORAGE_KEY = 'mosseluxe_page_scroll';

    // Pages that should always start at the top (no scroll retention)
    const PAGES_START_AT_TOP = [
        'faq', 'about', 'contact', 'careers', 'privacy', 'terms',
        'shipping-returns', 'track_order', 'my_account', 'wishlist',
        'loyalty', 'flash-sales', 'recover-cart', 'login', 'register',
        'forgot-password', 'reset-password'
    ];

    // Check if current page should start at top
    function shouldStartAtTop() {
        const currentPath = window.location.pathname;
        return PAGES_START_AT_TOP.some(page => currentPath.includes(page));
    }

    // Restore scroll position on page load
    function restoreScrollPosition() {
        // Always start at top for specific pages
        if (shouldStartAtTop()) {
            window.scrollTo(0, 0);
            sessionStorage.removeItem(SCROLL_STORAGE_KEY);
            return;
        }

        const savedScroll = sessionStorage.getItem(SCROLL_STORAGE_KEY);
        if (savedScroll && parseFloat(savedScroll) > 0) {
            // Small delay to ensure page content is loaded
            setTimeout(() => {
                window.scrollTo(0, parseFloat(savedScroll));
            }, 10);
        }
    }

    // Save scroll position before page unload
    function saveScrollPosition() {
        const currentScroll = window.scrollY || window.pageYOffset;
        if (currentScroll > 0) {
            sessionStorage.setItem(SCROLL_STORAGE_KEY, currentScroll.toString());
        }
    }

    // Save scroll position before navigation
    function setupLinkHandlers() {
        const links = document.querySelectorAll('a[href]');
        links.forEach(link => {
            // Check if link goes to a page that should start at top
            const linkGoesToTopPage = PAGES_START_AT_TOP.some(page => 
                link.href.includes(page)
            );

            if (link.classList.contains('nav-link') || linkGoesToTopPage) {
                // For navigation links and top-starting pages, clear scroll position
                link.addEventListener('click', () => {
                    sessionStorage.removeItem(SCROLL_STORAGE_KEY);
                });
            } else if (link.href.includes(window.location.hostname) || link.href.startsWith('/')) {
                // Only track internal non-navigation links for scroll saving
                link.addEventListener('click', saveScrollPosition);
            }
        });
    }

    // Save scroll position before form submissions
    function setupFormHandlers() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            // Skip forms that explicitly handle scroll (like AJAX forms)
            if (!form.classList.contains('ajax-scroll-ignore')) {
                form.addEventListener('submit', saveScrollPosition);
            }
        });
    }

    // Enhanced AJAX action scroll handling
    function setupAjaxHandlers() {
        // Intercept AJAX calls that might cause scroll issues
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const url = args[0];
            const options = args[1] || {};

            // Save scroll position for cart/wishlist AJAX calls
            if (url.includes('ajax_cart_handler') ||
                url.includes('wishlist_actions') ||
                url.includes('ajax_bulk') ||
                url.includes('ajax_toggle')) {
                saveScrollPosition();
            }

            return originalFetch.apply(this, args).then(response => {
                // Restore scroll position with a small delay for DOM updates
                if (url.includes('ajax_cart_handler') ||
                    url.includes('wishlist_actions') ||
                    url.includes('ajax_bulk') ||
                    url.includes('ajax_toggle')) {
                    setTimeout(() => {
                        const savedScroll = sessionStorage.getItem(SCROLL_STORAGE_KEY);
                        if (savedScroll) {
                            window.scrollTo(0, parseFloat(savedScroll));
                        }
                    }, 100);
                }
                return response;
            });
        };
    }

    // Admin panel specific handlers
    function setupAdminHandlers() {
        // Enhanced sidebar click handling
        const sidebarLinks = document.querySelectorAll('#sidebar a, #sidebar button');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', saveScrollPosition);
        });

        // Admin form handlers (similar to regular forms but more specific)
        const adminForms = document.querySelectorAll('form[action*="admin"]');
        adminForms.forEach(form => {
            form.addEventListener('submit', saveScrollPosition);
        });

        // Admin AJAX handlers
        const adminAjaxButtons = document.querySelectorAll('[onclick*="ajax"], [data-ajax]');
        adminAjaxButtons.forEach(button => {
            button.addEventListener('click', function() {
                saveScrollPosition();
                // Store current scroll for restoration after AJAX
                setTimeout(() => {
                    const savedScroll = sessionStorage.getItem(SCROLL_STORAGE_KEY);
                    if (savedScroll) {
                        window.scrollTo(0, parseFloat(savedScroll));
                    }
                }, 200);
            });
        });
    }

    // Browser scroll restoration API fallback
    function setupBrowserScrollRestoration() {
        if ('scrollRestoration' in history) {
            // Disable browser's automatic scroll restoration
            history.scrollRestoration = 'manual';
        }
    }

    // Pagination and filter handlers
    function setupPaginationHandlers() {
        const paginationLinks = document.querySelectorAll('.pagination a, [href*="page="], [href*="filter"]');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function() {
                // For pagination, keep scroll near top or current position
                // Can be customized based on preference
                saveScrollPosition();
            });
        });
    }

    // Handle tab switching (for admin panels, product details, etc.)
    function setupTabHandlers() {
        const tabs = document.querySelectorAll('[data-tabs] button, .tab-button, [role="tab"]');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Small delay to let tab content load
                setTimeout(saveScrollPosition, 50);
            });
        });

        // Bootstrap/jQuery tab handlers
        const bsTabs = document.querySelectorAll('[data-toggle="tab"], [data-bs-toggle="tab"]');
        bsTabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', saveScrollPosition); // Bootstrap event
        });
    }

    // Mobile menu and overlay handlers
    function setupMobileHandlers() {
        // Close mobile menu on nav click
        const mobileMenuLinks = document.querySelectorAll('#mobile-menu a');
        mobileMenuLinks.forEach(link => {
            link.addEventListener('click', function() {
                const mobileMenu = document.getElementById('mobile-menu');
                if (mobileMenu) {
                    mobileMenu.classList.add('-translate-x-full');
                }
            });
        });
    }

    // Utility function to clear scroll position (for certain actions like redirects)
    window.clearScrollRetention = function() {
        sessionStorage.removeItem(SCROLL_STORAGE_KEY);
    };

    // Public API for custom implementations
    window.scrollRetention = {
        save: saveScrollPosition,
        restore: restoreScrollPosition,
        clear: window.clearScrollRetention
    };

    // Initialize all handlers
    restoreScrollPosition();
    setupLinkHandlers();
    setupFormHandlers();
    setupAjaxHandlers();
    setupAdminHandlers();
    setupBrowserScrollRestoration();
    setupPaginationHandlers();
    setupTabHandlers();
    setupMobileHandlers();

    // Save on page unload (before refresh or navigation)
    window.addEventListener('beforeunload', saveScrollPosition);

    // Also save periodically while scrolling
    let scrollSaveTimeout;
    window.addEventListener('scroll', function() {
        clearTimeout(scrollSaveTimeout);
        scrollSaveTimeout = setTimeout(saveScrollPosition, 100);
    });

    // Clear scroll position on successful actions (optional: customize as needed)
    if (document.querySelector('.alert-success, .toast-success, #toast-container .success')) {
        // If there was a successful action, keep scroll as is
        setTimeout(restoreScrollPosition, 100);
    }
});
