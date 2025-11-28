/**
 * Mossé Luxe Cart System
 *
 * This file contains the core client-side logic for the e-commerce cart.
 * It defines a Cart class that acts as the single source of truth for cart data,
 * and a CartAPI object that handles all communication with the server.
 */


// The CartAPI object is responsible for all AJAX communication with the server.
window.CartAPI = {
    csrfToken: null,

    init: function(csrfToken) {
        this.csrfToken = csrfToken;
    },

    _sendRequest: function(endpoint, method, data, callback) {
        const url = window.SITE_URL + endpoint;
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': this.csrfToken
            }
        };

        if (data && method !== 'GET' && method !== 'HEAD') {
            options.body = JSON.stringify(data);
        }

        fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                return response.text().then(text => {
                    console.error('Non-JSON response from cart API:', text.substring(0, 200));
                    throw new Error("Server returned HTML instead of JSON. Check server logs.");
                });
            }
            return response.json();
        })
        .then(data => {
            if (callback) {
                callback(data);
            }
        })
        .catch(error => {
            console.error('CartAPI Request Error:', error);
            if (window.CartUI && window.CartUI.showToast) {
                window.CartUI.showToast('A network error occurred. Please try again.', 'error');
            }
            if (callback) {
                callback({success: false, message: 'Network error occurred'});
            }
        });
    },

    getCart: function(callback) {
        this._sendRequest('api/cart', 'GET', null, callback);
    },

    addItem: function(productId, quantity, callback) {
        const data = {
            product_id: productId,
            quantity: quantity
        };
        this._sendRequest('api/cart/items', 'POST', data, callback);
    },

    updateItem: function(productId, quantity, callback) {
        const data = {
            quantity: quantity
        };
        this._sendRequest(`api/cart/items/${productId}`, 'PUT', data, callback);
    },

    removeItem: function(productId, callback) {
        this._sendRequest(`api/cart/items/${productId}`, 'DELETE', null, callback);
    },

    clearCart: function(callback) {
        this._sendRequest('api/cart', 'DELETE', null, callback);
    }
};

// The Cart class is the single source of truth for cart data on the client side.
class Cart {
    constructor() {
        this.items = {};
        this.count = 0;
        this.subtotal = 0;
        this.total = 0;
    }

    init(cartData) {
        // Always reset items to ensure clean state
        this.items = {};
        
        // Prefer structured cart_items object if present (product_id => item)
        if (cartData && cartData.cart_items && Object.keys(cartData.cart_items).length > 0) {
            this.items = cartData.cart_items;
        } else if (cartData && Array.isArray(cartData.cart) && cartData.cart.length > 0) {
            // Convert cart array [{ product_id, ... }] into a keyed object
            const mappedItems = {};
            cartData.cart.forEach(item => {
                if (!item || typeof item.product_id === 'undefined') return;
                const id = item.product_id;
                mappedItems[id] = {
                    name: item.name,
                    // Ensure price is numeric even if the API returns formatted strings
                    price: (typeof item.price === 'number')
                        ? item.price
                        : (parseFloat(String(item.price).replace(/,/g, '')) || 0),
                    image: item.image,
                    quantity: item.quantity || 0
                };
            });
            this.items = mappedItems;
        } else if (cartData && Array.isArray(cartData.cart_data) && cartData.cart_data.length > 0) {
            // Legacy fallback: convert cart_data array
            const mappedItems = {};
            cartData.cart_data.forEach(item => {
                if (!item || typeof item.product_id === 'undefined') return;
                const id = item.product_id;
                mappedItems[id] = {
                    name: item.name,
                    price: (typeof item.price === 'number')
                        ? item.price
                        : (parseFloat(String(item.price).replace(/,/g, '')) || 0),
                    image: item.image,
                    quantity: item.quantity || 0
                };
            });
            this.items = mappedItems;
        }

        this.count = (cartData && typeof cartData.item_count === 'number')
            ? cartData.item_count
            : (cartData && typeof cartData.cart_count === 'number')
                ? cartData.cart_count
                : 0;

        const totals = cartData && cartData.totals ? cartData.totals : {};

        // Support both new totals.subtotal/total and legacy new_subtotal/new_total fields
        const subtotal = (typeof totals.subtotal === 'number')
            ? totals.subtotal
            : (typeof cartData?.new_subtotal === 'number'
                ? cartData.new_subtotal
                : (parseFloat(String(cartData?.new_subtotal || 0).replace(/,/g, '')) || 0));

        const total = (typeof totals.total === 'number')
            ? totals.total
            : (typeof cartData?.new_total === 'number'
                ? cartData.new_total
                : (parseFloat(String(cartData?.new_total || 0).replace(/,/g, '')) || 0));

        this.subtotal = subtotal;
        this.total = total;
        this.updateUI();
    }

    updateUI() {
        window.CartUI.updateCartCount(this.count);
        if (!window.location.pathname.includes('/cart.php')) {
            window.CartUI.updateCartSidebar(this);
        }
        // Don't override cart.php display as it has its own PHP rendering and JS handling
    }

    addItem(productId, quantity) {
        window.CartAPI.addItem(productId, quantity, (data) => {
            if (data.success) {
                window.CartUI.showToast(data.message || 'Product added to cart!', 'success');
                this.init(data);
            } else {
                window.CartUI.showToast(data.message || 'Error adding product.', 'error');
            }
        });
    }

    updateItem(productId, quantity) {
        window.CartAPI.updateItem(productId, quantity, (data) => {
            if (data.success) {
                window.CartUI.showToast('Cart updated.', 'success');
                this.init(data);
            } else {
                window.CartUI.showToast(data.message || 'Error updating cart.', 'error');
            }
        });
    }

    async removeItem(productId) {
        const confirmed = await window.showConfirm(
            'Are you sure you want to remove this item from your cart?',
            'Remove from Cart',
            { confirmText: 'Remove', cancelText: 'Cancel', type: 'warning' }
        );
        
        if (confirmed) {
            window.CartAPI.removeItem(productId, (data) => {
                if (data.success) {
                    window.CartUI.showToast(data.message || 'Item removed from cart.', 'success');
                    this.init(data);
                } else {
                    window.CartUI.showToast(data.message || 'Error removing item.', 'error');
                }
            });
        }
    }

    async clearCart() {
        const confirmed = await window.showConfirm(
            'Are you sure you want to clear your entire cart? This action cannot be undone.',
            'Clear Cart',
            { confirmText: 'Clear Cart', cancelText: 'Cancel', type: 'danger' }
        );
        
        if (confirmed) {
            window.CartAPI.clearCart((data) => {
                if (data.success) {
                    window.CartUI.showToast('Cart cleared.', 'success');
                    this.init(data);
                } else {
                    window.CartUI.showToast(data.message || 'Error clearing cart.', 'error');
                }
            });
        }
    }
}

// Initialize cart system immediately when script loads
(function() {
    const csrfToken = window.csrfToken;
    if (csrfToken) {
        window.CartAPI.init(csrfToken);
        window.AppCart = new Cart();

        // Get initial cart state from server
        window.CartAPI.getCart((data) => {
            if(data.success) {
                window.AppCart.init(data);
            }
        });
    }
})();

// Initialize the rest on DOM load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize if not already done (fallback)
    if (!window.AppCart) {
        // Try to get CSRF token from global variable or hidden input
        const csrfToken = window.csrfToken || document.getElementById('csrf_token')?.value;
        if (csrfToken) {
            window.CartAPI.init(csrfToken);
        }
        window.AppCart = new Cart();

        window.CartAPI.getCart((data) => {
            if(data.success) {
                window.AppCart.init(data);
            }
        });
    }

    // Add event listeners for cart actions
    document.body.addEventListener('click', function(e) {
        if (e.target.matches('.add-to-cart-btn')) {
            const productId = e.target.dataset.productId;
            const quantity = document.getElementById(`quantity-${productId}`)?.value || 1;
            window.AppCart.addItem(productId, quantity);
        }
        // Remove button handling - check if it's a remove button or its parent
        const removeBtn = e.target.closest('.remove-from-cart-btn');
        if (removeBtn) {
            const productId = removeBtn.dataset.productId;
            const isInMainContent = !!removeBtn.closest('main');
            const isCartPage = window.location.pathname.includes('/cart.php');
            // On the full cart page, let legacy handlers in main.js manage removals
            // So we skip handling here
            if (isInMainContent && isCartPage) {
                return;
            }
            // For sidebar and other locations, handle with AppCart
            window.AppCart.removeItem(productId);
        }
    });

    document.getElementById('add-to-cart-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const productId = this.elements.product_id.value;
        const quantity = this.elements.quantity.value;
        window.AppCart.addItem(productId, quantity);
    });

    document.body.addEventListener('change', function(e) {
        if (e.target.matches('.cart-quantity-input')) {
            const productId = e.target.dataset.productId;
            const quantity = e.target.value;
            const isInMainContent = !!e.target.closest('main');
            const isCartPage = window.location.pathname.includes('/cart.php');
            // On cart.php, use the existing AJAX logic in main.js for the main cart table
            if (isInMainContent && isCartPage) {
                return;
            }
            window.AppCart.updateItem(productId, quantity);
        }
        if (e.target.matches('.sidebar-quantity-input')) {
            const productId = e.target.dataset.productId;
            const quantity = e.target.value;
            window.AppCart.updateItem(productId, quantity);
        }
    });
});
