/**
 * Loading State Utilities
 * 
 * Provides button loading states and full-page loading overlays.
 * 
 * Usage:
 *   Loading.button(buttonElement, true);  // Show spinner
 *   Loading.button(buttonElement, false); // Restore button
 *   Loading.page(true);  // Show page overlay
 *   Loading.page(false); // Hide page overlay
 */

const Loading = (function() {
    'use strict';
    
    const SPINNER_SVG = `
        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    `;
    
    let pageOverlay = null;
    
    /**
     * Set loading state on a button
     * 
     * @param {HTMLElement} button - The button element
     * @param {boolean} loading - Whether to show loading state
     * @param {string} loadingText - Optional text to show while loading
     */
    function setButtonLoading(button, loading, loadingText = null) {
        if (!button) return;
        
        if (loading) {
            // Store original content
            button.dataset.originalContent = button.innerHTML;
            button.dataset.originalWidth = button.style.width || '';
            
            // Set fixed width to prevent layout shift
            const width = button.offsetWidth;
            button.style.width = `${width}px`;
            
            // Set loading content
            const text = loadingText || 'Loading...';
            button.innerHTML = `
                <span class="flex items-center justify-center gap-2">
                    ${SPINNER_SVG}
                    <span>${text}</span>
                </span>
            `;
            
            // Disable button
            button.disabled = true;
            button.classList.add('cursor-wait', 'opacity-75');
        } else {
            // Restore original content
            if (button.dataset.originalContent) {
                button.innerHTML = button.dataset.originalContent;
                delete button.dataset.originalContent;
            }
            
            // Restore width
            button.style.width = button.dataset.originalWidth || '';
            delete button.dataset.originalWidth;
            
            // Enable button
            button.disabled = false;
            button.classList.remove('cursor-wait', 'opacity-75');
        }
    }
    
    /**
     * Show/hide page loading overlay
     * 
     * @param {boolean} loading - Whether to show the overlay
     * @param {string} message - Optional message to display
     */
    function setPageLoading(loading, message = 'Loading...') {
        if (loading) {
            if (!pageOverlay) {
                pageOverlay = document.createElement('div');
                pageOverlay.id = 'page-loading-overlay';
                pageOverlay.className = `
                    fixed inset-0 z-[9999] flex items-center justify-center
                    bg-black/50 backdrop-blur-sm
                    opacity-0 transition-opacity duration-300
                `.replace(/\s+/g, ' ').trim();
                
                pageOverlay.innerHTML = `
                    <div class="bg-white rounded-lg shadow-xl p-6 flex flex-col items-center gap-4">
                        <div class="animate-spin h-10 w-10 text-black">
                            ${SPINNER_SVG.replace('h-5 w-5', 'h-10 w-10')}
                        </div>
                        <p class="text-gray-700 font-medium" id="loading-message">${message}</p>
                    </div>
                `;
                
                document.body.appendChild(pageOverlay);
                document.body.style.overflow = 'hidden';
                
                // Trigger animation
                requestAnimationFrame(() => {
                    pageOverlay.classList.remove('opacity-0');
                    pageOverlay.classList.add('opacity-100');
                });
            } else {
                // Update message if overlay already exists
                const msgEl = pageOverlay.querySelector('#loading-message');
                if (msgEl) msgEl.textContent = message;
            }
        } else {
            if (pageOverlay) {
                pageOverlay.classList.remove('opacity-100');
                pageOverlay.classList.add('opacity-0');
                
                setTimeout(() => {
                    if (pageOverlay && pageOverlay.parentNode) {
                        pageOverlay.parentNode.removeChild(pageOverlay);
                        pageOverlay = null;
                    }
                    document.body.style.overflow = '';
                }, 300);
            }
        }
    }
    
    /**
     * Add loading state to a form submission
     * 
     * @param {HTMLFormElement} form - The form element
     * @param {Function} submitHandler - Async function to handle submission
     */
    function handleFormSubmit(form, submitHandler) {
        if (!form) return;
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            
            try {
                setButtonLoading(submitBtn, true, 'Processing...');
                await submitHandler(form);
            } catch (error) {
                console.error('Form submission error:', error);
                if (window.Toast) {
                    Toast.error(error.message || 'An error occurred');
                }
            } finally {
                setButtonLoading(submitBtn, false);
            }
        });
    }
    
    // Public API
    return {
        button: setButtonLoading,
        page: setPageLoading,
        form: handleFormSubmit
    };
})();

// Expose globally
window.Loading = Loading;
