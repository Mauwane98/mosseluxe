document.addEventListener('DOMContentLoaded', function() {
    // Function to update cart count display
    function updateCartCountDisplay() {
        fetch('ajax_cart_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_count'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartCountElement = document.getElementById('cart-count');
                if (cartCountElement) {
                    cartCountElement.textContent = data.cart_count;
                }
            }
        })
        .catch(error => {
            console.error('Error fetching cart count:', error);
        });
    }

    // Call on page load
    updateCartCountDisplay();

    // Hero Carousel Functionality
    const slidesContainer = document.getElementById('slides-container');
    const prevSlideBtn = document.getElementById('prev-slide');
    const nextSlideBtn = document.getElementById('next-slide');
    const carouselDots = document.getElementById('carousel-dots');
    const slides = document.querySelectorAll('.carousel-slide');
    let currentIndex = 0;

    if (slidesContainer && slides.length > 0) {
        // Create dots
        slides.forEach((_, index) => {
            const dot = document.createElement('div');
            dot.classList.add('carousel-dot'); // Use existing CSS for styling
            if (index === 0) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(index));
            carouselDots.appendChild(dot);
        });

        const allDots = document.querySelectorAll('.carousel-dot');

        const updateCarousel = () => {
            slidesContainer.style.transform = `translateX(-${currentIndex * 100}%)`;
            allDots.forEach((dot, index) => {
                if (index === currentIndex) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        };

        const goToSlide = (index) => {
            currentIndex = index;
            updateCarousel();
        };

        prevSlideBtn.addEventListener('click', () => {
            currentIndex = (currentIndex > 0) ? currentIndex - 1 : slides.length - 1;
            updateCarousel();
        });

        nextSlideBtn.addEventListener('click', () => {
            currentIndex = (currentIndex < slides.length - 1) ? currentIndex + 1 : 0;
            updateCarousel();
        });

        // Auto-advance carousel
        setInterval(() => {
            currentIndex = (currentIndex < slides.length - 1) ? currentIndex + 1 : 0;
            updateCarousel();
        }, 5000); // Change slide every 5 seconds

        updateCarousel(); // Initial update
    }


    /**
     * AJAX "Add to Cart" functionality
     * Handles form submissions with the class 'quick-add-form' to add items
     * to the cart without a page reload and shows a toast notification.
     */
    const quickAddForms = document.querySelectorAll('.quick-add-form');
    
    if (quickAddForms.length > 0) {
        quickAddForms.forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const button = form.querySelector('button[type="submit"]');
                const originalButtonText = button.innerHTML;
                button.innerHTML = 'Adding...';
                button.disabled = true;

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        
                        updateCartCountDisplay(); // Use the function from header.php
                    } else {
                        alert(data.message || 'An error occurred.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding to cart.');
                })
                .finally(() => {
                    button.innerHTML = originalButtonText;
                    button.disabled = false;
                });
            });
        });
    }

    // Newsletter Signup Form
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const button = newsletterForm.querySelector('button[type="submit"]');
            const originalButtonText = button.innerHTML;
            button.innerHTML = 'Subscribing...';
            button.disabled = true;

            const formData = new FormData(newsletterForm);

            fetch(newsletterForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    newsletterForm.reset();
                } else {
                    alert(data.message || 'An error occurred.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while subscribing.');
            })
            .finally(() => {
                button.innerHTML = originalButtonText;
                button.disabled = false;
            });
        });
    }

            const pageWrapper = document.getElementById('page-wrapper');
            
            // Mobile Menu
            const openMenuBtn = document.getElementById('open-menu-btn');
            const closeMenuBtn = document.getElementById('close-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');

            openMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.remove('-translate-x-full');
            });

            closeMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.add('-translate-x-full');
            });

            // Cart Sidebar
            const openCartBtn = document.getElementById('open-cart-btn');
            const closeCartBtn = document.getElementById('close-cart-btn');
            const cartSidebar = document.getElementById('cart-sidebar');

            openCartBtn.addEventListener('click', () => {
                cartSidebar.classList.remove('translate-x-full');
            });

            closeCartBtn.addEventListener('click', () => {
                cartSidebar.classList.add('translate-x-full');
            });

            // Search Overlay
            const openSearchBtn = document.getElementById('open-search-btn');
            const closeSearchBtn = document.getElementById('close-search-btn');
            const searchOverlay = document.getElementById('search-overlay');

            openSearchBtn.addEventListener('click', () => {
                searchOverlay.classList.remove('hidden');
                searchOverlay.classList.add('flex');
            });

            closeSearchBtn.addEventListener('click', () => {
                searchOverlay.classList.add('hidden');
                searchOverlay.classList.remove('flex');
            });

            // Sticky Header on Scroll
            const announcementBar = document.getElementById('announcement-bar');
            const mainHeader = document.getElementById('main-header');

            const handleScroll = () => {
                const announcementHeight = announcementBar ? announcementBar.offsetHeight : 0;
                if (window.scrollY > announcementHeight) {
                    mainHeader.classList.add('scrolled');
                } else {
                    mainHeader.classList.remove('scrolled');
                }
            };

            // Call on load
            handleScroll(); // Call once on load to set initial state
            window.addEventListener('scroll', handleScroll);
});