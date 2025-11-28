/**
 * Modern Modal System
 * Replaces basic alert() and confirm() with styled modals
 */

class ModalSystem {
    constructor() {
        this.activeModal = null;
        this.toastContainer = null;
        this.init();
    }

    init() {
        // Create toast container
        this.toastContainer = document.createElement('div');
        this.toastContainer.id = 'toast-container-custom';
        this.toastContainer.style.cssText = 'position: fixed; top: 24px; right: 24px; z-index: 10000;';
        document.body.appendChild(this.toastContainer);
    }

    /**
     * Show alert modal
     */
    alert(message, title = 'Notice', type = 'info') {
        return new Promise((resolve) => {
            const modal = this.createModal({
                title,
                message,
                type,
                buttons: [
                    {
                        text: 'OK',
                        class: 'modal-btn-primary',
                        onClick: () => {
                            this.closeModal();
                            resolve(true);
                        }
                    }
                ]
            });
            this.showModal(modal);
        });
    }

    /**
     * Show confirm modal
     */
    confirm(message, title = 'Confirm', options = {}) {
        return new Promise((resolve) => {
            const {
                confirmText = 'Confirm',
                cancelText = 'Cancel',
                type = 'warning',
                confirmClass = 'modal-btn-primary'
            } = options;

            const modal = this.createModal({
                title,
                message,
                type,
                buttons: [
                    {
                        text: cancelText,
                        class: 'modal-btn-secondary',
                        onClick: () => {
                            this.closeModal();
                            resolve(false);
                        }
                    },
                    {
                        text: confirmText,
                        class: confirmClass,
                        onClick: () => {
                            this.closeModal();
                            resolve(true);
                        }
                    }
                ]
            });
            this.showModal(modal);
        });
    }

    /**
     * Show prompt modal
     */
    prompt(message, title = 'Input Required', defaultValue = '') {
        return new Promise((resolve) => {
            const inputId = 'modal-prompt-input-' + Date.now();
            
            const modal = this.createModal({
                title,
                message,
                type: 'info',
                customBody: `
                    <input type="text" 
                           id="${inputId}" 
                           class="modal-input" 
                           value="${this.escapeHtml(defaultValue)}" 
                           placeholder="Enter value...">
                `,
                buttons: [
                    {
                        text: 'Cancel',
                        class: 'modal-btn-secondary',
                        onClick: () => {
                            this.closeModal();
                            resolve(null);
                        }
                    },
                    {
                        text: 'Submit',
                        class: 'modal-btn-primary',
                        onClick: () => {
                            const input = document.getElementById(inputId);
                            const value = input ? input.value : '';
                            this.closeModal();
                            resolve(value);
                        }
                    }
                ]
            });
            
            this.showModal(modal);
            
            // Focus input after modal is shown
            setTimeout(() => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 100);
        });
    }

    /**
     * Show toast notification
     */
    toast(message, title = '', type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        
        const icons = {
            success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>',
            error: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
            warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>',
            info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
        };

        const colors = {
            success: { bg: '#dcfce7', color: '#16a34a' },
            error: { bg: '#fee2e2', color: '#dc2626' },
            warning: { bg: '#fef3c7', color: '#f59e0b' },
            info: { bg: '#dbeafe', color: '#2563eb' }
        };

        const color = colors[type] || colors.info;
        
        toast.innerHTML = `
            <div class="toast-icon" style="background: ${color.bg}; color: ${color.color};">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${icons[type] || icons.info}
                </svg>
            </div>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${this.escapeHtml(title)}</div>` : ''}
                <div class="toast-message">${this.escapeHtml(message)}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;

        this.toastContainer.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);

        // Auto remove
        if (duration > 0) {
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        return toast;
    }

    /**
     * Create modal element
     */
    createModal({ title, message, type, buttons, customBody }) {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        
        const icons = {
            success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>',
            error: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
            warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>',
            info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
        };

        overlay.innerHTML = `
            <div class="modal-container">
                <div class="modal-header">
                    <div class="modal-title">
                        <div class="modal-icon ${type}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                ${icons[type] || icons.info}
                            </svg>
                        </div>
                        <span>${this.escapeHtml(title)}</span>
                    </div>
                    <button class="modal-close" onclick="window.Modal.closeModal()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    ${customBody || `<div class="modal-message">${this.escapeHtml(message)}</div>`}
                </div>
                <div class="modal-footer">
                    ${buttons.map((btn, index) => 
                        `<button class="modal-btn ${btn.class}" data-btn-index="${index}">${this.escapeHtml(btn.text)}</button>`
                    ).join('')}
                </div>
            </div>
        `;

        // Add button click handlers
        buttons.forEach((btn, index) => {
            const btnElement = overlay.querySelector(`[data-btn-index="${index}"]`);
            if (btnElement && btn.onClick) {
                btnElement.addEventListener('click', btn.onClick);
            }
        });

        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                this.closeModal();
            }
        });

        // Close on Escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);

        return overlay;
    }

    /**
     * Show modal
     */
    showModal(modal) {
        if (this.activeModal) {
            this.closeModal();
        }
        
        this.activeModal = modal;
        document.body.appendChild(modal);
        
        // Add modal-open class to body (handled by CSS)
        document.documentElement.classList.add('modal-open');
        
        // Trigger animation
        setTimeout(() => modal.classList.add('active'), 10);
    }

    /**
     * Close modal
     */
    closeModal() {
        if (!this.activeModal) return;
        
        this.activeModal.classList.remove('active');
        
        // Remove modal-open class
        document.documentElement.classList.remove('modal-open');
        
        setTimeout(() => {
            if (this.activeModal) {
                this.activeModal.remove();
                this.activeModal = null;
            }
        }, 300);
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize global modal system
window.Modal = new ModalSystem();

// Override native alert and confirm (optional)
window.showAlert = (message, title, type) => window.Modal.alert(message, title, type);
window.showConfirm = (message, title, options) => window.Modal.confirm(message, title, options);

// Smart showToast wrapper that handles both signatures:
// showToast(message, type) - 2 params (backward compatible)
// showToast(message, title, type, duration) - 4 params (full featured)
window.showToast = (message, titleOrType, type, duration) => {
    // If only 2 parameters, second param is type
    if (arguments.length === 2) {
        return window.Modal.toast(message, '', titleOrType, 3000);
    }
    // Otherwise use all parameters
    return window.Modal.toast(message, titleOrType, type, duration);
};
