/**
 * Shop Filter Module
 * 
 * Handles AJAX-based product filtering without page reloads.
 * Provides smooth, app-like filtering experience.
 */

const ShopFilter = (function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        apiEndpoint: window.SITE_URL + 'api/products',
        debounceDelay: 300,
        skeletonCount: 8
    };
    
    // DOM Elements
    let elements = {
        form: null,
        grid: null,
        pagination: null,
        resultCount: null,
        searchInput: null,
        categorySelect: null,
        sortSelect: null,
        minPriceInput: null,
        maxPriceInput: null,
        minPriceDisplay: null,
        maxPriceDisplay: null
    };
    
    // State
    let state = {
        loading: false,
        currentFilters: {},
        debounceTimer: null
    };
    
    /**
     * Initialize the shop filter
     */
    function init() {
        // Cache DOM elements
        elements.form = document.getElementById('filter-form');
        elements.grid = document.querySelector('.product-grid, [data-product-grid]');
        elements.pagination = document.querySelector('.pagination, [data-pagination]');
        elements.resultCount = document.querySelector('[data-result-count]');
        elements.searchInput = document.getElementById('search');
        elements.categorySelect = document.getElementById('category');
        elements.sortSelect = document.getElementById('sort_by');
        elements.minPriceInput = document.getElementById('min-price');
        elements.maxPriceInput = document.getElementById('max-price');
        elements.minPriceDisplay = document.getElementById('price-min-display');
        elements.maxPriceDisplay = document.getElementById('price-max-display');
        
        if (!elements.form) {
            console.warn('ShopFilter: Filter form not found');
            return;
        }
        
        // Find product grid if not found by class
        if (!elements.grid) {
            elements.grid = document.querySelector('.grid.grid-cols-2');
        }
        
        bindEvents();
        console.log('ShopFilter initialized');
    }
    
    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Prevent form submission, use AJAX instead
        elements.form.addEventListener('submit', function(e) {
            e.preventDefault();
            fetchProducts();
        });
        
        // Live search with debounce
        if (elements.searchInput) {
            elements.searchInput.addEventListener('input', debounce(fetchProducts, CONFIG.debounceDelay));
        }
        
        // Category change
        if (elements.categorySelect) {
            elements.categorySelect.addEventListener('change', fetchProducts);
        }
        
        // Sort change
        if (elements.sortSelect) {
            elements.sortSelect.addEventListener('change', fetchProducts);
        }
        
        // Price range sliders
        if (elements.minPriceInput) {
            elements.minPriceInput.addEventListener('input', function() {
                updatePriceDisplay();
            });
            elements.minPriceInput.addEventListener('change', fetchProducts);
        }
        
        if (elements.maxPriceInput) {
            elements.maxPriceInput.addEventListener('input', function() {
                updatePriceDisplay();
            });
            elements.maxPriceInput.addEventListener('change', fetchProducts);
        }
        
        // Handle browser back/forward
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.filters) {
                applyFiltersFromState(e.state.filters);
                fetchProducts(false); // Don't push state again
            }
        });
    }
    
    /**
     * Update price display values
     */
    function updatePriceDisplay() {
        if (elements.minPriceDisplay && elements.minPriceInput) {
            elements.minPriceDisplay.textContent = formatNumber(elements.minPriceInput.value);
        }
        if (elements.maxPriceDisplay && elements.maxPriceInput) {
            elements.maxPriceDisplay.textContent = formatNumber(elements.maxPriceInput.value);
        }
    }
    
    /**
     * Fetch products via AJAX
     */
    async function fetchProducts(pushState = true) {
        if (state.loading) return;
        
        state.loading = true;
        showSkeletons();
        
        // Gather filter values
        const filters = getFilterValues();
        state.currentFilters = filters;
        
        // Build query string
        const params = new URLSearchParams();
        if (filters.search) params.set('search', filters.search);
        if (filters.category) params.set('category', filters.category);
        if (filters.sort) params.set('sort', filters.sort);
        if (filters.min_price) params.set('min_price', filters.min_price);
        if (filters.max_price) params.set('max_price', filters.max_price);
        if (filters.page) params.set('page', filters.page);
        
        try {
            const response = await fetch(`${CONFIG.apiEndpoint}?${params.toString()}`);
            const data = await response.json();
            
            if (data.success) {
                renderProducts(data.data);
                renderPagination(data.pagination);
                updateResultCount(data.pagination.total);
                
                // Update URL without reload
                if (pushState) {
                    const newUrl = `${window.location.pathname}?${params.toString()}`;
                    history.pushState({ filters }, '', newUrl);
                }
            } else {
                showError('Failed to load products');
            }
        } catch (error) {
            console.error('ShopFilter error:', error);
            showError('Network error. Please try again.');
        } finally {
            state.loading = false;
        }
    }
    
    /**
     * Get current filter values from form
     */
    function getFilterValues() {
        return {
            search: elements.searchInput?.value || '',
            category: elements.categorySelect?.value || '',
            sort: elements.sortSelect?.value || 'newest',
            min_price: elements.minPriceInput?.value || '',
            max_price: elements.maxPriceInput?.value || '',
            page: 1
        };
    }
    
    /**
     * Render products to grid
     */
    function renderProducts(products) {
        if (!elements.grid) return;
        
        if (products.length === 0) {
            elements.grid.innerHTML = `
                <div class="col-span-full text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No products found</h3>
                    <p class="text-gray-500">Try adjusting your filters</p>
                </div>
            `;
            return;
        }
        
        elements.grid.innerHTML = products.map(product => createProductCard(product)).join('');
        
        // Re-initialize any JS handlers on new cards
        initProductCardHandlers();
    }
    
    /**
     * Create product card HTML
     */
    function createProductCard(product) {
        const hasDiscount = product.sale_price && product.sale_price < product.price;
        const displayPrice = hasDiscount ? product.sale_price : product.price;
        const slug = product.name.toLowerCase().replace(/[^a-z0-9]+/g, '-');
        const productUrl = `${window.SITE_URL}product/${product.id}/${slug}`;
        const imageUrl = product.image || `${window.SITE_URL}assets/images/product-placeholder.png`;
        
        return `
            <div class="group relative product-card" data-product-id="${product.id}">
                <div class="relative aspect-w-1 aspect-h-1 bg-neutral-50 rounded-2xl overflow-hidden border border-transparent group-hover:border-black/20 transition-colors duration-300 hover:shadow-lg">
                    <a href="${productUrl}" class="absolute inset-0 z-0"></a>
                    
                    <!-- Badges -->
                    <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                        ${hasDiscount ? '<span class="inline-flex items-center bg-gradient-to-r from-red-500 to-red-600 text-white text-[10px] font-black px-2.5 py-1.5 rounded-full shadow-xl">SALE</span>' : ''}
                        ${product.is_featured ? '<span class="inline-flex items-center bg-gradient-to-r from-amber-400 to-orange-500 text-black text-[10px] font-black px-2.5 py-1.5 rounded-full shadow-xl">FEATURED</span>' : ''}
                        ${product.is_new ? '<span class="inline-flex items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white text-[10px] font-black px-2.5 py-1.5 rounded-full shadow-xl">NEW</span>' : ''}
                    </div>
                    
                    <!-- Product Image -->
                    <img src="${imageUrl}" 
                         alt="${escapeHtml(product.name)}" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                         loading="lazy"
                         onerror="this.src='${window.SITE_URL}assets/images/product-placeholder.png'">
                    
                    <!-- Quick Actions -->
                    <div class="absolute top-4 right-4 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-20">
                        <button class="quick-view-btn bg-white/90 backdrop-blur-sm text-black p-2 rounded-full shadow-xl hover:bg-blue-50 hover:text-blue-600 transition-all"
                                data-product-id="${product.id}" type="button" title="Quick View">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                        <button class="wishlist-btn bg-white/90 backdrop-blur-sm text-black p-2 rounded-full shadow-xl hover:bg-red-50 hover:text-red-600 transition-all"
                                data-product-id="${product.id}" type="button" title="Add to Wishlist">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="mt-4 text-center">
                    <a href="${productUrl}" class="block">
                        <h3 class="text-sm font-semibold text-gray-900 group-hover:text-black transition-colors line-clamp-2">${escapeHtml(product.name)}</h3>
                    </a>
                    <div class="mt-2 flex items-center justify-center gap-2">
                        ${hasDiscount 
                            ? `<span class="text-red-600 font-bold">${product.formatted_price}</span>
                               <span class="text-gray-400 line-through text-sm">R ${formatPrice(product.price)}</span>`
                            : `<span class="font-bold">${product.formatted_price}</span>`
                        }
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Show skeleton loading cards
     */
    function showSkeletons() {
        if (!elements.grid) return;
        
        const skeletons = Array(CONFIG.skeletonCount).fill(0).map(() => `
            <div class="animate-pulse">
                <div class="aspect-w-1 aspect-h-1 bg-gray-200 rounded-2xl"></div>
                <div class="mt-4 space-y-2">
                    <div class="h-4 bg-gray-200 rounded w-3/4 mx-auto"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/2 mx-auto"></div>
                </div>
            </div>
        `).join('');
        
        elements.grid.innerHTML = skeletons;
    }
    
    /**
     * Render pagination
     */
    function renderPagination(pagination) {
        if (!elements.pagination) return;
        
        if (pagination.total_pages <= 1) {
            elements.pagination.innerHTML = '';
            return;
        }
        
        let html = '<div class="flex justify-center gap-2 mt-8">';
        
        // Previous button
        if (pagination.has_prev) {
            html += `<button class="pagination-btn px-4 py-2 border rounded hover:bg-gray-100" data-page="${pagination.page - 1}">Previous</button>`;
        }
        
        // Page numbers
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === pagination.page) {
                html += `<span class="px-4 py-2 bg-black text-white rounded">${i}</span>`;
            } else if (i === 1 || i === pagination.total_pages || Math.abs(i - pagination.page) <= 2) {
                html += `<button class="pagination-btn px-4 py-2 border rounded hover:bg-gray-100" data-page="${i}">${i}</button>`;
            } else if (Math.abs(i - pagination.page) === 3) {
                html += `<span class="px-2 py-2">...</span>`;
            }
        }
        
        // Next button
        if (pagination.has_next) {
            html += `<button class="pagination-btn px-4 py-2 border rounded hover:bg-gray-100" data-page="${pagination.page + 1}">Next</button>`;
        }
        
        html += '</div>';
        elements.pagination.innerHTML = html;
        
        // Bind pagination clicks
        elements.pagination.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                state.currentFilters.page = parseInt(this.dataset.page);
                fetchProducts();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }
    
    /**
     * Update result count display
     */
    function updateResultCount(total) {
        if (elements.resultCount) {
            elements.resultCount.textContent = `${total} product${total !== 1 ? 's' : ''} found`;
        }
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        if (elements.grid) {
            elements.grid.innerHTML = `
                <div class="col-span-full text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-red-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Error</h3>
                    <p class="text-gray-500">${escapeHtml(message)}</p>
                </div>
            `;
        }
        
        if (window.Toast) {
            Toast.error(message);
        }
    }
    
    /**
     * Initialize handlers on product cards
     */
    function initProductCardHandlers() {
        // Quick view buttons
        document.querySelectorAll('.quick-view-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const productId = this.dataset.productId;
                if (window.QuickView) {
                    window.QuickView.open(productId);
                }
            });
        });
        
        // Wishlist buttons
        document.querySelectorAll('.wishlist-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const productId = this.dataset.productId;
                if (typeof toggleWishlist === 'function') {
                    toggleWishlist(productId, this);
                }
            });
        });
    }
    
    /**
     * Apply filters from history state
     */
    function applyFiltersFromState(filters) {
        if (elements.searchInput) elements.searchInput.value = filters.search || '';
        if (elements.categorySelect) elements.categorySelect.value = filters.category || '';
        if (elements.sortSelect) elements.sortSelect.value = filters.sort || 'newest';
        if (elements.minPriceInput) elements.minPriceInput.value = filters.min_price || '';
        if (elements.maxPriceInput) elements.maxPriceInput.value = filters.max_price || '';
        updatePriceDisplay();
    }
    
    // Utility functions
    function debounce(func, wait) {
        return function(...args) {
            clearTimeout(state.debounceTimer);
            state.debounceTimer = setTimeout(() => func.apply(this, args), wait);
        };
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }
    
    function formatPrice(price) {
        return new Intl.NumberFormat('en-ZA', { minimumFractionDigits: 2 }).format(price);
    }
    
    // Public API
    return {
        init: init,
        refresh: fetchProducts
    };
})();

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    ShopFilter.init();
});

// Expose globally
window.ShopFilter = ShopFilter;
