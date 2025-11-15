document.addEventListener('DOMContentLoaded', function() {
    // Use the globally available csrfToken
    const csrfToken = window.csrfToken;

    // Call on page load to ensure cart count is up-to-date
    if (typeof window.updateCartCountDisplay === 'function') {
        window.updateCartCountDisplay();
    }

    // Hero Carousel Functionality
    const slidesContainer = document.getElementById('hero-carousel');
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

        const updateHeroButton = () => {
            // Check if hero buttons are globally enabled
            if (!window.heroButtonsEnabled) {
                return;
            }

            if (window.heroSlideData && window.heroSlideData[currentIndex]) {
                const slideData = window.heroSlideData[currentIndex];
                const buttonContainer = document.getElementById('hero-button-container');
                const heroButton = document.getElementById('hero-button');

                // Hide button if no data for this slide
                if (!slideData.button_text || !slideData.button_url) {
                    if (buttonContainer) buttonContainer.style.display = 'none';
                    return;
                }

                if (buttonContainer) buttonContainer.style.display = 'block';

                if (heroButton) {
                    heroButton.textContent = slideData.button_text;
                    heroButton.href = slideData.button_url;
                }
            }
        };

        const updateCarousel = () => {
            slidesContainer.style.transform = `translateX(-${currentIndex * 100}%)`;
            allDots.forEach((dot, index) => {
                if (index === currentIndex) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });

            // Update hero button for current slide
            updateHeroButton();
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


    // AJAX "Add to Cart" functionality for forms with the class 'quick-add-form'
    document.querySelectorAll('.quick-add-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const originalButtonText = button.innerHTML;
            button.innerHTML = 'Adding...';
            button.disabled = true;

            const formData = new FormData(form);

            fetch(window.SITE_URL + 'ajax_cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.showToast(data.message || 'Product added successfully!', 'success');
                    if (typeof window.updateCartCountDisplay === 'function') {
                        window.updateCartCountDisplay(); // Update cart count in header
                    }
                } else {
                    window.showToast(data.message || 'Could not add product to cart.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.showToast('An error occurred while adding to cart.', 'error');
            })
            .finally(() => {
                button.innerHTML = originalButtonText;
                button.disabled = false;
            });
        });
    });

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

            fetch(window.SITE_URL + 'subscribe.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.showToast(data.message, 'success');
                    newsletterForm.reset();
                } else {
                    window.showToast(data.message || 'An error occurred.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.showToast('An error occurred while subscribing.', 'error');
            })            .finally(() => {
                button.innerHTML = originalButtonText;
                button.disabled = false;
            });
        });
    }

    // Mobile Menu
    const openMenuBtn = document.getElementById('open-menu-btn');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (openMenuBtn && closeMenuBtn && mobileMenu) {
        openMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.remove('-translate-x-full');
        });

        closeMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.add('-translate-x-full');
        });
    }

    // Cart Sidebar
    const openCartBtn = document.getElementById('open-cart-btn');
    const closeCartBtn = document.getElementById('close-cart-btn');
    const cartSidebar = document.getElementById('cart-sidebar');

    if (openCartBtn && closeCartBtn && cartSidebar) {
        openCartBtn.addEventListener('click', () => {
            cartSidebar.classList.remove('translate-x-full');
        });

        closeCartBtn.addEventListener('click', () => {
            cartSidebar.classList.add('translate-x-full');
        });
    }

    // Search Overlay
    const openSearchBtn = document.getElementById('open-search-btn');
    const closeSearchBtn = document.getElementById('close-search-btn');
    const searchOverlay = document.getElementById('search-overlay');

    if (openSearchBtn && closeSearchBtn && searchOverlay) {
        openSearchBtn.addEventListener('click', () => {
            searchOverlay.classList.remove('hidden');
            searchOverlay.classList.add('flex');
        });

        closeSearchBtn.addEventListener('click', () => {
            searchOverlay.classList.add('hidden');
            searchOverlay.classList.remove('flex');
        });
    }

    // Sticky Header on Scroll
    const announcementBar = document.getElementById('announcement-bar');
    const mainHeader = document.getElementById('main-header');
    const fixedHeaderContainer = document.getElementById('fixed-header-container');
    const pageWrapper = document.getElementById('page-wrapper');
    
    const setBodyPadding = () => {
        if (fixedHeaderContainer && pageWrapper) {
            const headerHeight = fixedHeaderContainer.offsetHeight;
            pageWrapper.style.paddingTop = `${headerHeight}px`;
        }
    };
    
    const handleScroll = () => {
        const announcementHeight = announcementBar ? announcementBar.offsetHeight : 0;
        if (mainHeader && window.scrollY > announcementHeight) {
            mainHeader.classList.add('scrolled');
        } else if (mainHeader) {
            mainHeader.classList.remove('scrolled');
        }
    };
    
    // Call on load and on resize
    setBodyPadding();
    handleScroll();
    window.addEventListener('resize', setBodyPadding);
    window.addEventListener('scroll', handleScroll);

    // FAQ Accordion Functionality
    const faqToggles = document.querySelectorAll('.faq-toggle');
    faqToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const content = toggle.nextElementSibling;
            const icon = toggle.querySelector('svg');

            if (content.style.maxHeight) {
                content.style.maxHeight = null;
                icon.style.transform = 'rotate(0deg)';
            } else {
                content.style.maxHeight = content.scrollHeight + 'px';
                icon.style.transform = 'rotate(180deg)';
            }
        });
    });

    // Wishlist AJAX Functionality
    // Function to update the wishlist UI (e.g., remove item from display)
    function updateWishlistUI(productId) {
        const itemElement = document.querySelector(`.remove-from-wishlist-btn[data-product-id="${productId}"]`).closest('.group.bg-white');
        if (itemElement) {
            itemElement.remove();
        }
        // If wishlist becomes empty, reload the page to show empty state
        if (document.querySelectorAll('.group.bg-white').length === 0) {
            location.reload();
        }
    }

    // Remove from wishlist via AJAX
    document.querySelectorAll('.remove-from-wishlist-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Are you sure you want to remove this item from your wishlist?')) {
                return;
            }

            const productId = this.dataset.productId;
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);
            formData.append('csrf_token', csrfToken);

            fetch(window.SITE_URL + 'wishlist_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.showToast(data.message, 'success');
                    updateWishlistUI(productId);
                } else {
                    window.showToast(data.message || 'An error occurred.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.showToast('An error occurred while removing item from wishlist.', 'error');
            });
        });
    });

    // Add to cart from wishlist via AJAX
    document.querySelectorAll('.add-to-cart-from-wishlist-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const quantity = 1; // Always add 1 from wishlist

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('csrf_token', csrfToken);

            fetch(window.SITE_URL + 'ajax_cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.showToast(data.message, 'success');
                    if (typeof window.updateCartCountDisplay === 'function') {
                        window.updateCartCountDisplay(); // Update cart count in header
                    }
                } else {
                    window.showToast(data.message || 'An error occurred.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.showToast('An error occurred while adding item to cart.', 'error');
            });
        });
    });

    // Cart AJAX Functionality
    // This function now dynamically updates the cart UI without a full page reload
    function updateCartDisplay(data) {
        if (data.success) {
            window.showToast(data.message, 'success');
            if (typeof window.updateCartCountDisplay === 'function') {
                window.updateCartCountDisplay(); // Update cart count in header
            }

            // Update subtotal and total
            const cartSubtotalElement = document.getElementById('cart-subtotal');
            const cartTotalElement = document.getElementById('cart-total'); // Assuming you add this ID to your total
            const emptyCartMessage = document.getElementById('empty-cart-message');
            const cartItemsContainer = document.getElementById('cart-items-container'); // Assuming this wraps all cart items

            if (cartSubtotalElement) cartSubtotalElement.textContent = `R ${data.new_subtotal.toFixed(2)}`;
            if (cartTotalElement) cartTotalElement.textContent = `R ${data.new_total.toFixed(2)}`;

            // Remove item if quantity is 0 or if it was a remove action
            if (data.removed_product_id) {
                const itemElement = document.querySelector(`.cart-quantity-input[data-product-id="${data.removed_product_id}"]`).closest('.flex.items-center.border-b.border-gray-200.py-4');
                if (itemElement) {
                    itemElement.remove();
                }
            }

            // Check if cart is empty
            if (data.cart_count === 0) {
                const cartContentDiv = document.querySelector('.grid.grid-cols-1.lg:grid-cols-3.gap-8');
                if (cartContentDiv) {
                    cartContentDiv.innerHTML = `
                        <div class="lg:col-span-3 text-center">
                            <p class="text-lg text-black/70">Your cart is empty.</p>
                            <div class="mt-8">
                                <a href="shop.php" class="text-lg font-semibold text-black border-b-2 border-black hover:border-transparent transition-colors">
                                    Continue Shopping
                                </a>
                            </div>
                        </div>
                    `;
                }
            }
        } else {
            window.showToast(data.message || 'An error occurred.', 'error');
        }
    }

    // Quantity update
    document.querySelectorAll('.cart-quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.dataset.productId;
            const newQuantity = parseInt(this.value);

            if (isNaN(newQuantity) || newQuantity < 0) {
                window.showToast('Quantity must be a positive number.', 'error');
                this.value = this.defaultValue; // Revert to previous valid quantity
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('product_id', productId);
            formData.append('quantity', newQuantity);
            formData.append('csrf_token', csrfToken);

            fetch(window.SITE_URL + 'ajax_cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                updateCartDisplay(data);
            })
            .catch(error => {
                console.error('Error:', error);
                window.showToast('An error occurred while updating quantity.', 'error');
            });
        });
    });

    // Remove from cart
    document.querySelectorAll('.remove-from-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }

            const productId = this.dataset.productId;

            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);
            formData.append('csrf_token', csrfToken);

            fetch(window.SITE_URL + 'ajax_cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                updateCartDisplay(data);
            })
            .catch(error => {
                console.error('Error:', error);
                window.showToast('An error occurred while removing item.', 'error');
            });
        });
    });
});

// Quick Add functionality
window.handleQuickAdd = function(productId, formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.submit();
    }
};

// Quick View Modal Functions
window.openQuickView = async function(productId) {
    const modal = document.getElementById('quick-view-modal');
    const content = document.getElementById('quick-view-content');
    const modalContainer = modal.querySelector('.transform');

    // Show modal with loading state
    modal.classList.remove('hidden');
    modalContainer.classList.remove('scale-95', 'opacity-0');
    modalContainer.classList.add('scale-100', 'opacity-100');

    try {
        // Fetch product details
        const response = await fetch(window.SITE_URL + `product-details.php?id=${productId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();

        // Update modal content
        content.innerHTML = `
            <!-- Product Image -->
            <div class="lg:w-1/2 bg-neutral-50 p-8 flex items-center justify-center">
                <img src="${data.image}" alt="${data.name}" class="max-w-full max-h-96 object-contain rounded-lg"
                     onerror="this.src='https://placehold.co/600x600/f1f1f1/000000?text=Mossé+Luxe'">
            </div>

            <!-- Product Details -->
            <div class="lg:w-1/2 p-8 flex flex-col justify-center">
                <!-- Badges -->
                ${data.sale_price > 0 ? '<div class="mb-4"><span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold">SALE</span></div>' : ''}

                <!-- Brand -->
                <h4 class="text-xs font-semibold uppercase tracking-widest text-gray-600 mb-1">Mossé Luxe</h4>

                <!-- Product Name -->
                <h1 class="text-3xl font-black mb-4 leading-tight">${data.name}</h1>

                <!-- Price -->
                <div class="mb-6">
                    ${data.sale_price > 0 ?
                        `<div class="flex items-center gap-3">
                            <span class="text-2xl font-black text-black">R ${parseFloat(data.sale_price).toFixed(2)}</span>
                            <span class="text-lg text-gray-500 line-through">R ${parseFloat(data.price).toFixed(2)}</span>
                        </div>` :
                        `<span class="text-2xl font-black text-black">R ${parseFloat(data.price).toFixed(2)}</span>`
                    }
                </div>

                <!-- Stock Status -->
                ${data.stock > 0 ?
                    `<p class="text-green-600 font-semibold mb-4">${data.stock > 10 ? 'In Stock' : `Only ${data.stock} left in stock`}</p>` :
                    `<p class="text-red-600 font-semibold mb-4">Out of Stock</p>`
                }

                <!-- Description -->
                ${data.description ? `<div class="mb-6 text-gray-700 leading-relaxed">${data.description}</div>` : ''}

                <!-- Quantity and Add to Cart -->
                <div class="space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center border border-gray-300 rounded-lg">
                            <button onclick="decreaseQuantity()" class="px-3 py-2 text-gray-600 hover:text-black">-</button>
                            <input id="modal-quantity" type="number" value="1" min="1" max="${data.stock}" readonly class="w-16 text-center border-0 bg-transparent py-2">
                            <button onclick="increaseQuantity()" class="px-3 py-2 text-gray-600 hover:text-black">+</button>
                        </div>
                        <span class="text-sm text-gray-600">Quantity</span>
                    </div>

                    <!-- Add to Cart Button -->
                    <button onclick="addToCartFromModal(${data.id})"
                            class="w-full bg-black text-white py-4 px-6 rounded-lg font-bold hover:bg-gray-800 transition-colors"
                            ${data.stock <= 0 ? 'disabled class="bg-gray-400 cursor-not-allowed"' : ''}>
                        ${data.stock > 0 ? 'Add to Cart' : 'Out of Stock'}
                    </button>

                    <!-- View Full Details -->
                    <a href="product/${data.id}/${data.slug}" class="block text-center py-3 border border-gray-300 rounded-lg hover:border-black transition-colors">
                        View Full Details
                    </a>
                </div>
            </div>
        `;

        // Reset quantity to 1
        document.getElementById('modal-quantity').value = 1;

    } catch (error) {
        console.error('Error loading product:', error);
        content.innerHTML = `
            <div class="flex-1 flex items-center justify-center p-12">
                <div class="text-center">
                    <p class="text-red-600 mb-4">Failed to load product details.</p>
                    <button onclick="closeQuickView()" class="bg-black text-white px-4 py-2 rounded">Close</button>
                </div>
            </div>
        `;
    }
};

window.closeQuickView = function() {
    const modal = document.getElementById('quick-view-modal');
    const modalContainer = modal.querySelector('.transform');

    modalContainer.classList.remove('scale-100', 'opacity-100');
    modalContainer.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
};

// Modal Cart Functions
window.decreaseQuantity = function() {
    const input = document.getElementById('modal-quantity');
    const currentValue = parseInt(input.value);
    if (currentValue > 1) {
        input.value = currentValue - 1;
    }
};

window.increaseQuantity = function() {
    const input = document.getElementById('modal-quantity');
    const currentValue = parseInt(input.value);
    const maxValue = parseInt(input.getAttribute('max') || 999);
    if (currentValue < maxValue) {
        input.value = currentValue + 1;
    }
};

window.addToCartFromModal = function(productId) {
    const quantity = document.getElementById('modal-quantity').value;

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('csrf_token', window.csrfToken);

    fetch(window.SITE_URL + 'ajax_cart_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.showToast(data.message, 'success');
            window.updateCartCountDisplay();
            closeQuickView();
        } else {
            window.showToast(data.message || 'Could not add product to cart.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.showToast('An error occurred while adding to cart.', 'error');
    });
};

// Cart Sidebar Functions
window.toggleCart = function() {
    const sidebar = document.getElementById('cart-sidebar');
    const overlay = document.getElementById('cart-sidebar-overlay');

    if (sidebar) {
        if (sidebar.classList.contains('translate-x-full')) {
            // Show cart
            sidebar.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            // Refresh cart content
            loadCartContent();
        } else {
            // Hide cart
            sidebar.classList.add('translate-x-full');
            overlay.classList.add('hidden');
        }
    }
};

// Wishlist Functions
window.toggleWishlist = async function(productId, button) {
    const icon = button.querySelector('svg');

    // Check if product is already in wishlist
    const isInWishlist = await checkWishlistStatus(productId);

    const formData = new FormData();
    if (isInWishlist) {
        formData.append('action', 'remove');
    } else {
        formData.append('action', 'add');
    }
    formData.append('product_id', productId);
    formData.append('csrf_token', window.csrfToken);

    try {
        const response = await fetch(window.SITE_URL + 'wishlist_actions.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            if (isInWishlist) {
                // Remove from wishlist - change icon back to outline
                icon.setAttribute('fill', 'none');
                icon.classList.remove('text-red-600');
                button.classList.remove('text-red-600', 'bg-red-50');
                window.showToast(data.message, 'success');
            } else {
                // Add to wishlist - fill icon and change colors
                icon.setAttribute('fill', 'currentColor');
                icon.classList.add('text-red-600');
                button.classList.add('text-red-600', 'bg-red-50');
                window.showToast(data.message, 'success');
            }
        } else {
            window.showToast(data.message || 'An error occurred.', 'error');
        }
    } catch (error) {
        console.error('Error toggling wishlist:', error);
        window.showToast('An error occurred while updating wishlist.', 'error');
    }
};

window.checkWishlistStatus = async function(productId) {
    try {
        const response = await fetch(window.SITE_URL + 'wishlist_actions.php?action=check&product_id=' + productId, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        return data.in_wishlist || false;
    } catch (error) {
        console.error('Error checking wishlist status:', error);
        return false;
    }
};

window.loadCartContent = function() {
    // This would load cart content dynamically, for now show a message
    console.log('Loading cart content...');
};

// Global functions for toast and cart count (moved outside DOMContentLoaded)
window.updateCartCountDisplay = function() {
    fetch(window.SITE_URL + 'ajax_cart_handler.php', {
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
                if (data.cart_count > 0) {
                    cartCountElement.classList.remove('hidden');
                } else {
                    cartCountElement.classList.add('hidden');
                }
            }
        }
    })
    .catch(error => {
        console.error('Error fetching cart count:', error);
    });
}

window.showToast = function(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || (() => {
        const div = document.createElement('div');
        div.id = 'toast-container';
        Object.assign(div.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            zIndex: '9999',
            display: 'flex',
            flexDirection: 'column',
            gap: '10px'
        });
        document.body.appendChild(div);
        return div;
    })();

    const toast = document.createElement('div');
    toast.classList.add('toast-notification', type);
    toast.textContent = message;

    toastContainer.appendChild(toast);

    void toast.offsetWidth; 
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
        toast.addEventListener('transitionend', () => toast.remove());
    }, 3000);
}
