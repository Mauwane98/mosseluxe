document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar-scroll');
    const announcementBar = document.querySelector('.announcement-bar');
    const body = document.body;

    let announcementBarHeight = 0; // Declare outside to make it accessible
    let navbarHeight = 0; // Declare outside to make it accessible

    const setBodyPadding = () => {
        announcementBarHeight = announcementBar ? announcementBar.offsetHeight : 0;
        navbarHeight = navbar ? navbar.offsetHeight : 0;
        console.log('Announcement Bar Height:', announcementBarHeight);
        console.log('Navbar Height:', navbarHeight);
        body.style.paddingTop = `${announcementBarHeight + navbarHeight}px`;
    };

    // Set padding on load
    setBodyPadding();

    // Recalculate padding on resize
    window.addEventListener('resize', setBodyPadding);

    // Navbar Scroll Effect
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > announcementBarHeight) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

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
            dot.classList.add('carousel-dot', 'w-3', 'h-3', 'bg-white', 'rounded-full', 'cursor-pointer');
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
                    dot.classList.remove('bg-white');
                    dot.classList.add('bg-gray-800'); // Active dot color
                } else {
                    dot.classList.remove('active');
                    dot.classList.remove('bg-gray-800');
                    dot.classList.add('bg-white'); // Inactive dot color
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
     * Page content fade-in animation
     * Applies a fade-in effect to the main content area on page load.
     */
    const pageContent = document.querySelector('.page-content');
    if (pageContent) {
        pageContent.classList.add('content-fade-in');
        setTimeout(() => { pageContent.style.opacity = 1; pageContent.style.transform = 'translateY(0)'; }, 50); // Small delay
    }

    /**
     * Sidebar Toggler for Admin pages
     * Toggles the 'active' class on the sidebar for mobile view.
     */
    const sidebar = document.querySelector('.sidebar');
    const toggler = document.getElementById('sidebar-toggler');
    if (toggler) {
        toggler.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
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
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
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
                        
                        // Update cart counts on all relevant elements without reloading
                        const cartCountElements = document.querySelectorAll('#cart-item-count-desktop, #cart-item-count-mobile, .bottom-nav .badge');
                        cartCountElements.forEach(el => {
                            if(el) {
                                el.textContent = data.cart_item_count;
                            }
                        });
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
});
