/**
 * Mossé Luxe Cart UI
 *
 * This script is responsible for all UI updates related to the cart.
 * It is called by the main Cart class and does not contain any data logic itself.
 */
window.CartUI = {
    // Update the cart count in the header
    updateCartCount: function(count) {
        try {
            const cartCountElements = document.querySelectorAll('#cart-count');
            if (cartCountElements.length === 0) {
                console.warn('CartUI: No #cart-count elements found to update.');
                return;
            }
            cartCountElements.forEach(el => {
                el.textContent = count;
                el.classList.toggle('hidden', count <= 0);
            });
        } catch (error) {
            console.error('CartUI: Error updating cart count:', error);
        }
    },

    // Update the cart sidebar
    updateCartSidebar: function(cart) {
        try {
            const cartItemsContainer = document.getElementById('cart-items-container');
            const cartSubtotalElement = document.getElementById('cart-subtotal');
            const emptyCartMessage = document.getElementById('empty-cart-message');

            // Silently skip if sidebar elements don't exist (not all pages have the cart sidebar)
            if (!cartItemsContainer || !cartSubtotalElement || !emptyCartMessage) {
                return;
            }
            
            // Always clear container first
            cartItemsContainer.innerHTML = '';
            
            // Check if we actually have items (not just a count)
            const hasItems = cart.items && Object.keys(cart.items).length > 0;
            
            if (cart.count === 0 || !hasItems) {
                emptyCartMessage.classList.remove('hidden');
                cartSubtotalElement.textContent = 'R 0.00';
            } else {
                emptyCartMessage.classList.add('hidden');
                let subtotal = 0;
                for (const productId in cart.items) {
                    const item = cart.items[productId];
                    subtotal += item.price * item.quantity;
                    const itemElement = document.createElement('div');
                    itemElement.className = 'flex items-center border-b border-gray-200 py-4';
                    itemElement.innerHTML = `
                        <div class="w-24 h-24 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">
                            <img src="${window.SITE_URL}${item.image}" alt="${item.name}" class="h-full w-full object-cover object-center">
                        </div>
                        <div class="ml-4 flex flex-1 flex-col">
                            <div>
                                <div class="flex justify-between text-base font-medium text-gray-900">
                                    <h3><a href="product/${productId}/${item.name.toLowerCase().replace(/ /g, '-')}">${item.name}</a></h3>
                                    <p class="ml-4">R ${(item.price * item.quantity).toFixed(2)}</p>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">R ${item.price.toFixed(2)} each</p>
                            </div>
                            <div class="flex flex-1 items-end justify-between text-sm">
                                <div class="flex items-center">
                                    <label for="sidebar-quantity-${productId}" class="mr-2">Qty</label>
                                    <input type="number" id="sidebar-quantity-${productId}" value="${item.quantity}" min="1" class="w-16 p-1 border border-gray-300 rounded-md text-center sidebar-quantity-input" data-product-id="${productId}">
                                </div>
                                <div class="flex">
                                    <button type="button" class="font-medium text-red-600 hover:text-red-500 remove-from-cart-btn" data-product-id="${productId}">Remove</button>
                                </div>
                            </div>
                        </div>
                    `;
                    cartItemsContainer.appendChild(itemElement);
                }
                cartSubtotalElement.textContent = `R ${subtotal.toFixed(2)}`;
            }
        } catch (error) {
            console.error('CartUI: Error updating cart sidebar:', error);
        }
    },

    // Update the cart page
    updateCartPage: function(cart) {
        try {
            const cartItemsContainer = document.getElementById('cart-items-container');
            const cartTotalsContainer = document.getElementById('cart-totals-container');
            const emptyCartMessage = document.getElementById('empty-cart-message');

            if (!cartItemsContainer || !cartTotalsContainer || !emptyCartMessage) {
                console.warn('CartUI: Cart page elements not found.');
                return;
            }

            if (cart.count === 0) {
                cartItemsContainer.innerHTML = '';
                cartItemsContainer.classList.add('hidden');
                cartTotalsContainer.classList.add('hidden');
                emptyCartMessage.classList.remove('hidden');
            } else {
                emptyCartMessage.classList.add('hidden');
                cartItemsContainer.classList.remove('hidden');
                cartTotalsContainer.classList.remove('hidden');
                cartItemsContainer.innerHTML = ''; // Clear existing items
                let subtotal = 0;
                for (const productId in cart.items) {
                    const item = cart.items[productId];
                    subtotal += item.price * item.quantity;
                    const itemElement = document.createElement('div');
                    itemElement.className = 'flex items-center border-b border-gray-200 py-4';
                    itemElement.innerHTML = `
                        <div class="w-24 h-24 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">
                            <img src="${window.SITE_URL}${item.image}" alt="${item.name}" class="h-full w-full object-cover object-center">
                        </div>
                        <div class="ml-4 flex flex-1 flex-col">
                            <div>
                                <div class="flex justify-between text-base font-medium text-gray-900">
                                    <h3><a href="product/${productId}/${item.name.toLowerCase().replace(/ /g, '-')}">${item.name}</a></h3>
                                    <p class="ml-4">R ${(item.price * item.quantity).toFixed(2)}</p>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">R ${item.price.toFixed(2)} each</p>
                            </div>
                            <div class="flex flex-1 items-end justify-between text-sm">
                                <div class="flex items-center">
                                    <label for="quantity-${productId}" class="mr-2">Qty</label>
                                    <input type="number" id="quantity-${productId}" value="${item.quantity}" min="1" class="w-16 p-1 border border-gray-300 rounded-md text-center cart-quantity-input" data-product-id="${productId}">
                                </div>
                                <div class="flex">
                                    <button type="button" class="font-medium text-red-600 hover:text-red-500 remove-from-cart-btn" data-product-id="${productId}">Remove</button>
                                </div>
                            </div>
                        </div>
                    `;
                    cartItemsContainer.appendChild(itemElement);
                }
                const subtotalEl = cartTotalsContainer.querySelector('#cart-subtotal');
                const totalEl = cartTotalsContainer.querySelector('#cart-total');
                if(subtotalEl) subtotalEl.textContent = `R ${subtotal.toFixed(2)}`;
                if(totalEl) totalEl.textContent = `R ${(subtotal + (window.SHIPPING_COST || 100)).toFixed(2)}`;
            }
        } catch (error) {
            console.error('CartUI: Error updating cart page:', error);
        }
    },
    
    // Simple toast notification
    showToast: function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-20 right-4 px-4 py-3 rounded-lg text-white shadow-lg z-[10000]`;
        if (type === 'success') {
            toast.className += ' bg-green-500';
        } else if (type === 'error') {
            toast.className += ' bg-red-500';
        } else {
            toast.className += ' bg-blue-500';
        }
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'opacity 0.5s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }
};
