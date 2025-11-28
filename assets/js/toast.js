/**
 * Toast Notification System
 * 
 * Usage:
 *   Toast.success('Item added to cart');
 *   Toast.error('Something went wrong');
 *   Toast.info('Please wait...');
 *   Toast.warning('Low stock warning');
 */

const Toast = (function() {
    'use strict';
    
    let container = null;
    const DURATION = 4000; // 4 seconds
    const ANIMATION_DURATION = 300;
    
    // Toast types with their icons and colors
    const TYPES = {
        success: {
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>`,
            bgClass: 'bg-green-500',
            textClass: 'text-white'
        },
        error: {
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>`,
            bgClass: 'bg-red-500',
            textClass: 'text-white'
        },
        warning: {
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>`,
            bgClass: 'bg-yellow-500',
            textClass: 'text-black'
        },
        info: {
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>`,
            bgClass: 'bg-blue-500',
            textClass: 'text-white'
        }
    };
    
    /**
     * Initialize the toast container
     */
    function init() {
        if (container) return;
        
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);
    }
    
    /**
     * Show a toast notification
     * 
     * @param {string} message - The message to display
     * @param {string} type - Toast type: success, error, warning, info
     * @param {object} options - Additional options
     */
    function show(message, type = 'info', options = {}) {
        init();
        
        const config = TYPES[type] || TYPES.info;
        const duration = options.duration || DURATION;
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `
            pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg
            transform translate-x-full opacity-0 transition-all duration-300 ease-out
            ${config.bgClass} ${config.textClass}
            min-w-[280px] max-w-[400px]
        `.replace(/\s+/g, ' ').trim();
        
        toast.innerHTML = `
            <span class="flex-shrink-0">${config.icon}</span>
            <span class="flex-1 text-sm font-medium">${escapeHtml(message)}</span>
            <button type="button" class="flex-shrink-0 opacity-70 hover:opacity-100 transition-opacity" aria-label="Close">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        
        // Add close button handler
        const closeBtn = toast.querySelector('button');
        closeBtn.addEventListener('click', () => dismiss(toast));
        
        // Add to container
        container.appendChild(toast);
        
        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');
        });
        
        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => dismiss(toast), duration);
        }
        
        return toast;
    }
    
    /**
     * Dismiss a toast
     */
    function dismiss(toast) {
        if (!toast || !toast.parentNode) return;
        
        toast.classList.remove('translate-x-0', 'opacity-100');
        toast.classList.add('translate-x-full', 'opacity-0');
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, ANIMATION_DURATION);
    }
    
    /**
     * Dismiss all toasts
     */
    function dismissAll() {
        if (!container) return;
        
        const toasts = container.querySelectorAll('div');
        toasts.forEach(toast => dismiss(toast));
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Public API
    return {
        show: show,
        success: (msg, opts) => show(msg, 'success', opts),
        error: (msg, opts) => show(msg, 'error', opts),
        warning: (msg, opts) => show(msg, 'warning', opts),
        info: (msg, opts) => show(msg, 'info', opts),
        dismiss: dismiss,
        dismissAll: dismissAll
    };
})();

// Also expose as window.showToast for backward compatibility
window.showToast = function(message, type = 'info') {
    return Toast.show(message, type);
};
