/**
 * Quick View Modal - Simplified & Fixed
 */

class QuickView {
    constructor() {
        this.modal = null;
        this.init();
    }

    init() {
        // Create modal
        this.createModal();
        
        // Listen for quick view button clicks
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.quick-view-btn');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                const productId = btn.getAttribute('data-product-id');
                if (productId) {
                    this.open(productId);
                }
            }
        });
    }

    createModal() {
        const modal = document.createElement('div');
        modal.id = 'quick-view-modal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm hidden items-center justify-center p-4';
        modal.style.zIndex = '99999';
        
        modal.innerHTML = `
            <div class="bg-white rounded-3xl max-w-5xl w-full max-h-[90vh] overflow-hidden relative shadow-2xl" id="quick-view-container">
                <!-- Close Button -->
                <button id="close-quick-view" class="absolute top-6 right-6 z-50 bg-black text-white rounded-full p-3 hover:bg-gray-800 shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                
                <!-- Content -->
                <div id="quick-view-content" class="overflow-y-auto max-h-[90vh] p-8"></div>
            </div>
        `;
        
        document.body.appendChild(modal);
        this.modal = modal;
        this.container = modal.querySelector('#quick-view-container');
        
        // Close handlers
        modal.querySelector('#close-quick-view').onclick = () => this.close();
        modal.onclick = (e) => {
            if (e.target === modal) this.close();
        };
        
        // ESC key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                this.close();
            }
        });
    }

    async open(productId) {
        // Show modal immediately
        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        
        // Loading state
        const content = document.getElementById('quick-view-content');
        content.innerHTML = `
            <div class="flex flex-col justify-center items-center py-32">
                <div class="relative">
                    <div class="animate-spin rounded-full h-16 w-16 border-4 border-gray-200"></div>
                    <div class="animate-spin rounded-full h-16 w-16 border-4 border-black border-t-transparent absolute top-0 left-0"></div>
                </div>
                <p class="mt-6 text-gray-600 font-medium">Loading product...</p>
            </div>
        `;
        
        try {
            const response = await fetch(`${window.SITE_URL}api/product.php?id=${productId}`);
            const data = await response.json();
            
            if (data.success && data.product) {
                this.renderProduct(data.product);
            } else {
                console.error('Product not found');
                content.innerHTML = '<div class="text-center py-20"><p class="text-red-500 font-semibold">Product not found</p></div>';
            }
        } catch (error) {
            console.error('Error loading product:', error);
            content.innerHTML = '<div class="text-center py-20"><p class="text-red-500 font-semibold">Error loading product</p></div>';
        }
    }

    renderProduct(product) {
        const salePrice = parseFloat(product.sale_price) || 0;
        const regularPrice = parseFloat(product.price) || 0;
        const price = salePrice > 0 ? salePrice : regularPrice;
        const originalPrice = salePrice > 0 ? regularPrice : null;
        
        // Fix image path - check if it already has SITE_URL
        let imagePath = product.image;
        if (!imagePath.startsWith('http') && !imagePath.startsWith(window.SITE_URL)) {
            imagePath = window.SITE_URL + (imagePath.startsWith('/') ? imagePath.substring(1) : imagePath);
        }
        
        const content = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <!-- Product Image -->
                <div class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl overflow-hidden group">
                    <img src="${imagePath}" alt="${product.name || 'Product'}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.src='${window.SITE_URL}assets/images/placeholder.svg'">
                    ${salePrice > 0 ? '<div class="absolute top-6 left-6 bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-lg animate-pulse">SALE</div>' : ''}
                    ${product.stock < 5 && product.stock > 0 ? `<div class="absolute top-6 right-6 bg-gradient-to-r from-orange-500 to-orange-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-lg">Only ${product.stock} left!</div>` : ''}
                </div>
                
                <!-- Product Details -->
                <div class="flex flex-col py-4">
                    <h2 class="text-4xl font-black mb-3 tracking-tight">${product.name}</h2>
                    <div class="h-1 w-20 bg-black mb-6"></div>
                    
                    <!-- Price -->
                    <div class="mb-8 bg-gray-50 p-5 rounded-xl border border-gray-100">
                        ${originalPrice ? `
                            <div class="flex items-baseline gap-3 flex-wrap">
                                <span class="text-4xl font-black text-black">R ${parseFloat(price).toFixed(2)}</span>
                                <span class="text-xl text-gray-400 line-through">R ${parseFloat(originalPrice).toFixed(2)}</span>
                                <span class="bg-gradient-to-r from-red-500 to-red-600 text-white px-3 py-1.5 rounded-lg text-sm font-bold shadow-md">
                                    SAVE ${Math.round((1 - price / originalPrice) * 100)}%
                                </span>
                            </div>
                        ` : `
                            <span class="text-4xl font-black text-black">R ${parseFloat(price).toFixed(2)}</span>
                        `}
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-8">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500 mb-3">Description</h3>
                        <p class="text-gray-700 leading-relaxed text-base">${product.description || 'No description available.'}</p>
                    </div>
                    
                    <!-- Stock Status -->
                    <div class="mb-8">
                        ${product.stock > 0 ? `
                            <div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-bold text-green-700">In Stock</span>
                                <span class="text-green-600 text-sm">(${product.stock} available)</span>
                            </div>
                        ` : `
                            <div class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                                <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-bold text-red-700">Out of Stock</span>
                            </div>
                        `}
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-4 mt-auto">
                        ${product.stock > 0 ? `
                            <button onclick="window.AppCart?.addItem(${product.id}, 1); window.QuickViewInstance?.close(); window.showToast?.('Added to cart!', 'success');" class="flex-1 bg-black text-white py-4 px-8 rounded-xl font-bold uppercase text-sm tracking-wider hover:bg-gray-800 transition-all duration-200 hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Add to Cart
                            </button>
                        ` : ''}
                        <a href="${window.SITE_URL}product/${product.id}/${product.name.toLowerCase().replace(/\s+/g, '-')}" class="flex-1 border-2 border-black text-black py-4 px-8 rounded-xl font-bold uppercase text-sm tracking-wider hover:bg-black hover:text-white transition-all duration-200 text-center flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        const contentElement = document.getElementById('quick-view-content');
        contentElement.innerHTML = content;
    }

    showError(message) {
        document.getElementById('quick-view-content').innerHTML = `
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-lg text-gray-700">${message}</p>
            </div>
        `;
    }

    close() {
        this.modal.classList.add('hidden');
        this.modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.QuickViewInstance = new QuickView();
});
