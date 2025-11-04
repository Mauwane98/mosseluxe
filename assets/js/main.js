document.addEventListener('DOMContentLoaded', function() {
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
    const toastElement = document.getElementById('cart-toast');
    const toastBody = document.getElementById('toast-body');
    
    if (toastElement) {
        const cartToast = new bootstrap.Toast(toastElement, { delay: 3000 });

        quickAddForms.forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const button = form.querySelector('button[type="submit"]');
                const originalIcon = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                button.disabled = true;

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastBody.textContent = data.message;
                        cartToast.show();
                        
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
                .catch(error => console.error('Error:', error))
                .finally(() => {
                    button.innerHTML = originalIcon;
                    button.disabled = false;
                });
            });
        });
    }

    /**
     * New Navbar scroll effect
     */
    const navbarScroll = document.querySelector('.navbar-scroll');
    if (navbarScroll) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbarScroll.classList.add('scrolled');
            } else {
                navbarScroll.classList.remove('scrolled');
            }
        });
    }

    /**
     * Quick View Modal functionality
     * Handles fetching and displaying product details in a modal.
     */
    const quickViewModalElement = document.getElementById('quickViewModal');
    if (quickViewModalElement) {
        const quickViewModal = new bootstrap.Modal(quickViewModalElement);
        const quickViewButtons = document.querySelectorAll('.quick-view-btn');

        quickViewButtons.forEach(button => {
            button.addEventListener('click', function () {
                const productId = this.dataset.productId;
                
                // Show a loading state in the modal
                document.getElementById('quickViewModalLabel').textContent = 'Loading...';
                document.getElementById('quickViewImage').src = 'https://placehold.co/600x600/111111/333333?text=?';
                document.getElementById('quickViewPrice').textContent = '';
                document.getElementById('quickViewDescription').textContent = '';
                document.getElementById('quickViewProductId').value = '';

                // Fetch product details via AJAX
                fetch(`/product-details/${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const product = data.product;
                            document.getElementById('quickViewModalLabel').textContent = product.name;
                            document.getElementById('quickViewImage').src = product.image;
                            if (product.sale_price && parseFloat(product.sale_price) > 0) {
                                document.getElementById('quickViewPrice').innerHTML =
                                    `<h3 class="d-inline">R ${parseFloat(product.sale_price).toFixed(2)}</h3> <h4 class="d-inline text-muted text-decoration-line-through ms-2">R ${parseFloat(product.price).toFixed(2)}</h4>`;
                            } else {
                                document.getElementById('quickViewPrice').innerHTML = `<h3 class="d-inline">R ${parseFloat(product.price).toFixed(2)}</h3>`;
                            }
                            document.getElementById('quickViewDescription').textContent = product.description;
                            document.getElementById('quickViewProductId').value = product.id;
                            quickViewModal.show();
                        } else {
                            alert(data.message || 'An error occurred.');
                        }
                    })
                    .catch(error => console.error('Error fetching product details:', error));
            });
        });
    }
});