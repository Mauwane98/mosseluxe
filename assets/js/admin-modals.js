/**
 * Admin Panel Modal System
 * Provides comprehensive confirmation modals for all admin actions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal system
    initializeModals();

    /**
     * Initialize all modal functionalities
     */
    function initializeModals() {
        createModalContainer();
        setupDeleteConfirmations();
        setupBulkActions();
        setupStatusToggles();
        setupDangerousActions();
        setupFormConfirmations();
    }

    /**
     * Create the main modal container
     */
    function createModalContainer() {
        const modalHTML = `
            <div id="admin-modal-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
                <div id="admin-modal" class="bg-white rounded-lg shadow-2xl max-w-md w-full max-h-[90vh] overflow-hidden transform scale-95 opacity-0 transition-all duration-300">
                    <div id="admin-modal-header" class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <h3 id="admin-modal-title" class="text-lg font-semibold text-gray-900">Confirm Action</h3>
                            <button id="admin-modal-close" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div id="admin-modal-body" class="px-6 py-4 overflow-y-auto max-h-96">
                        <!-- Modal content goes here -->
                    </div>

                    <div id="admin-modal-footer" class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
                        <!-- Buttons go here -->
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    /**
     * Setup delete confirmations for all delete actions
     */
    function setupDeleteConfirmations() {
        // Handle all buttons that need delete confirmation
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-action="delete"]') || e.target.closest('[data-action="delete"]')) {
                e.preventDefault();

                const element = e.target.matches('[data-action="delete"]') ? e.target :
                              e.target.closest('[data-action="delete"]');

                const itemType = element.dataset.itemType || 'item';
                const itemName = element.dataset.itemName || 'this item';
                const itemId = element.dataset.itemId;
                const deleteUrl = element.dataset.deleteUrl || element.href;

                showDeleteConfirmation(itemType, itemName, itemId, deleteUrl);
            }
        });

        // Legacy support for onclick confirmDelete calls
        window.confirmDelete = function(id, name, type) {
            if (name === undefined && type === undefined) {
                // Handle single parameter case
                showDeleteConfirmation('item', 'this item', id, 'delete_' + id + '.php');
            } else {
                showDeleteConfirmation(type, name, id, 'delete_' + type + '.php');
            }
        };
    }

    /**
     * Setup bulk actions confirmation
     */
    function setupBulkActions() {
        // Handle bulk delete
        const bulkDeleteBtn = document.getElementById('bulkDelete');
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function(e) {
                e.preventDefault();

                const selectedItems = document.querySelectorAll('.product-checkbox:checked');
                if (selectedItems.length === 0) {
                    showFailureModal('No products selected', 'Please select products to delete.');
                    return;
                }

                const itemCount = selectedItems.length;
                const itemType = 'products';

                showBulkActionConfirmation(
                    'delete',
                    itemCount,
                    itemType,
                    function() {
                        // Submit bulk delete form
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'bulk_delete_products.php';

                        selectedItems.forEach(item => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'product_ids[]';
                            input.value = item.value;
                            form.appendChild(input);
                        });

                        // Add CSRF token
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = 'csrf_token';
                        csrfInput.value = window.csrfToken;
                        form.appendChild(csrfInput);

                        document.body.appendChild(form);
                        form.submit();
                    }
                );
            });
        }

        // Handle bulk publish/draft
        const bulkPublishBtn = document.getElementById('bulkPublish');
        const bulkDraftBtn = document.getElementById('bulkDraft');

        if (bulkPublishBtn) {
            bulkPublishBtn.addEventListener('click', function(e) {
                e.preventDefault();
                handleBulkStatusChange('publish');
            });
        }

        if (bulkDraftBtn) {
            bulkDraftBtn.addEventListener('click', function(e) {
                e.preventDefault();
                handleBulkStatusChange('draft');
            });
        }
    }

    /**
     * Setup status toggle confirmations
     */
    function setupStatusToggles() {
        // Handle product status toggles that might disable products
        document.addEventListener('click', function(e) {
            if (e.target.matches('.status-toggle') || e.target.closest('.status-toggle')) {
                const toggle = e.target.matches('.status-toggle') ? e.target :
                              e.target.closest('.status-toggle');

                // Check if this would disable a published product
                if (toggle.classList.contains('bg-green-500')) {
                    e.preventDefault();

                    showStatusToggleConfirmation(
                        toggle.dataset.id,
                        'disable',
                        'product',
                        function() {
                            // Continue with the toggle
                            toggleStatusViaAjax(toggle, 'status');
                        }
                    );
                    return;
                }
            }
        });
    }

    /**
     * Setup dangerous action confirmations
     */
    function setupDangerousActions() {
        // Handle export actions that might contain sensitive data
        const exportLinks = document.querySelectorAll('a[href*="export"], a[href*="backup"]');
        exportLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.href.includes('customers') || this.href.includes('sensitive')) {
                    e.preventDefault();

                    showExportConfirmation(this.href, this.textContent.trim().replace(/\s+/g, ' '),
                        () => window.location.href = this.href);
                }
            });
        });

        // Handle database operations
        const dbOperations = document.querySelectorAll('[data-action="drop"], [data-action="reset"], [href*="drop"], [href*="reset"]');
        dbOperations.forEach(element => {
            element.addEventListener('click', function(e) {
                if (!this.classList.contains('confirmed')) {
                    e.preventDefault();

                    const action = this.textContent.toLowerCase().includes('drop') ? 'drop' : 'reset';
                    const target = 'database';

                    showDangerousActionConfirmation(
                        action,
                        target,
                        () => {
                            this.classList.add('confirmed');
                            if (this.tagName === 'A') {
                                window.location.href = this.href;
                            } else {
                                this.click();
                            }
                        }
                    );
                }
            });
        });
    }

    /**
     * Setup form submission confirmations
     */
    function setupFormConfirmations() {
        // Handle forms that need confirmation before submission
        document.addEventListener('submit', function(e) {
            const form = e.target;

            // Check if form has confirmation data attribute
            if (form.dataset.confirmMessage) {
                e.preventDefault();

                showFormSubmissionConfirmation(
                    form.dataset.confirmMessage,
                    form.dataset.confirmTitle || 'Confirm Action',
                    function() {
                        // Remove the event listener and resubmit
                        form.removeAttribute('data-confirm-message');
                        form.submit();
                    }
                );
            }

            // Dangerous forms (deletes, resets, etc.)
            if (form.action && (
                form.action.includes('delete') ||
                form.action.includes('drop') ||
                form.action.includes('reset') ||
                form.action.includes('bulk')
            )) {
                if (!form.classList.contains('confirmed')) {
                    e.preventDefault();

                    const actionType = getActionTypeFromForm(form);
                    const itemType = getItemTypeFromForm(form);
                    const itemCount = getFormItemCount(form);

                    if (itemCount > 1) {
                        showBulkActionConfirmation(actionType, itemCount, itemType, function() {
                            form.classList.add('confirmed');
                            form.submit();
                        });
                    } else {
                        showSingleActionConfirmation(actionType, itemType, function() {
                            form.classList.add('confirmed');
                            form.submit();
                        });
                    }
                }
            }
        });
    }

    // ========== MODAL DISPLAY FUNCTIONS ==========

    /**
     * Show delete confirmation modal
     */
    function showDeleteConfirmation(itemType, itemName, itemId, deleteUrl) {
        const title = `Delete ${capitalizeFirst(itemType)}`;
        const message = `<p class="text-gray-600">Are you sure you want to delete <strong>${escapeHtml(itemName)}</strong>? This action cannot be undone.</p>`;
        const buttons = `
            <button id="modal-cancel" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </button>
            <button id="modal-confirm" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                Delete
            </button>
        `;

        showModal(title, message, buttons);

        document.getElementById('modal-confirm').addEventListener('click', function() {
            // Create and submit delete form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = deleteUrl;

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = itemId;
            form.appendChild(idInput);

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = window.csrfToken;
            form.appendChild(csrfInput);

            document.body.appendChild(form);
            form.submit();
        });
    }

    /**
     * Show bulk action confirmation
     */
    function showBulkActionConfirmation(action, itemCount, itemType, callback) {
        const actionText = capitalizeFirst(action);
        const title = `${actionText} ${itemCount} ${itemType}`;
        const message = `<p class="text-gray-600">Are you sure you want to ${action.toLowerCase()} ${itemCount} ${itemType}? This action cannot be undone.</p>`;

        let buttonColor = 'bg-red-600 hover:bg-red-700';
        if (action === 'publish') buttonColor = 'bg-green-600 hover:bg-green-700';
        if (action === 'draft') buttonColor = 'bg-yellow-600 hover:bg-yellow-700';

        const buttons = `
            <button id="modal-cancel" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </button>
            <button id="modal-confirm" class="px-4 py-2 ${buttonColor} text-white rounded-md hover:bg-opacity-90 transition-colors">
                ${actionText}
            </button>
        `;

        showModal(title, message, buttons);

        document.getElementById('modal-confirm').addEventListener('click', function() {
            closeModal();
            callback();
        });
    }

    /**
     * Show status toggle confirmation
     */
    function showStatusToggleConfirmation(itemId, action, itemType, callback) {
        const title = `Change ${capitalizeFirst(itemType)} Status`;
        const message = `<p class="text-gray-600">Are you sure you want to ${action} this ${itemType}? ${action === 'disable' ? 'Disabled products will not be visible to customers.' : ''}</p>`;

        const buttons = `
            <button id="modal-cancel" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </button>
            <button id="modal-confirm" class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors">
                ${capitalizeFirst(action)}
            </button>
        `;

        showModal(title, message, buttons);

        document.getElementById('modal-confirm').addEventListener('click', function() {
            closeModal();
            callback();
        });
    }

    /**
     * Show form submission confirmation
     */
    function showFormSubmissionConfirmation(message, title = 'Confirm Action', callback) {
        const modalTitle = title;
        const modalMessage = `<p class="text-gray-600">${message}</p>`;

        const buttons = `
            <button id="modal-cancel" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </button>
            <button id="modal-confirm" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                Confirm
            </button>
        `;

        showModal(modalTitle, modalMessage, buttons);

        document.getElementById('modal-confirm').addEventListener('click', function() {
            closeModal();
            callback();
        });
    }

    /**
     * Show export confirmation
     */
    function showExportConfirmation(url, exportType, callback) {
        const title = 'Export Data';
        const message = `<p class="text-gray-600">You are about to export ${exportType}. This file may contain sensitive customer information. Please ensure you handle this data securely.</p>`;

        const buttons = `
            <button id="modal-cancel" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </button>
            <button id="modal-confirm" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                Export
            </button>
        `;

        showModal(title, message, buttons);

        document.getElementById('modal-confirm').addEventListener('click', function() {
            closeModal();
            callback();
        });
    }

    /**
     * Show dangerous action confirmation
     */
    function showDangerousActionConfirmation(action, target, callback) {
        const title = `Dangerous Action: ${capitalizeFirst(action)} ${capitalizeFirst(target)}`;
        const message = `
            <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            This action cannot be undone!
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>You are about to ${action} the ${target}. This will permanently delete all data and cannot be recovered.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const buttons = `
            <button id="modal-cancel" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </button>
            <button id="modal-confirm" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                ${capitalizeFirst(action)} ${capitalizeFirst(target)}
            </button>
        `;

        showModal(title, message, buttons);

        document.getElementById('modal-confirm').addEventListener('click', function() {
            closeModal();
            callback();
        });
    }

    /**
     * Show single action confirmation
     */
    function showSingleActionConfirmation(action, itemType, callback) {
        const title = `${capitalizeFirst(action)} ${capitalizeFirst(itemType)}`;
        const message = `<p class="text-gray-600">Are you sure you want to ${action.toLowerCase()} this ${itemType}? This action cannot be undone.</p>`;

        let buttonColor = 'bg-red-600 hover:bg-red-700';
        if (action === 'publish') buttonColor = 'bg-green-600 hover:bg-green-700';
        if (action === 'disable') buttonColor = 'bg-yellow-600 hover:bg-yellow-700';

        const buttons = `
            <button id="modal-cancel" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </button>
            <button id="modal-confirm" class="px-4 py-2 ${buttonColor} text-white rounded-md hover:bg-opacity-90 transition-colors">
                ${capitalizeFirst(action)}
            </button>
        `;

        showModal(title, message, buttons);

        document.getElementById('modal-confirm').addEventListener('click', function() {
            closeModal();
            callback();
        });
    }

    /**
     * Show failure modal
     */
    function showFailureModal(title, message) {
        const modalMessage = `<p class="text-gray-600">${message}</p>`;
        const buttons = `
            <button id="modal-ok" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                OK
            </button>
        `;

        showModal(title, modalMessage, buttons);

        document.getElementById('modal-ok').addEventListener('click', function() {
            closeModal();
        });
    }

    // ========== UTILITY FUNCTIONS ==========

    /**
     * Show modal with content
     */
    function showModal(title, bodyContent, footerButtons) {
        const overlay = document.getElementById('admin-modal-overlay');
        const modal = document.getElementById('admin-modal');
        const titleEl = document.getElementById('admin-modal-title');
        const bodyEl = document.getElementById('admin-modal-body');
        const footerEl = document.getElementById('admin-modal-footer');

        titleEl.textContent = title;
        bodyEl.innerHTML = bodyContent;
        footerEl.innerHTML = footerButtons;

        overlay.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('scale-95', 'opacity-0');
            modal.classList.add('scale-100', 'opacity-100');
        }, 10);

        // Setup close handlers
        document.getElementById('admin-modal-close').addEventListener('click', closeModal);
        document.getElementById('modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeModal();
        });

        // Focus trap and ESC key
        modal.focus();
        document.addEventListener('keydown', handleKeyDown);
    }

    /**
     * Close modal
     */
    function closeModal() {
        const overlay = document.getElementById('admin-modal-overlay');
        const modal = document.getElementById('admin-modal');

        modal.classList.remove('scale-100', 'opacity-100');
        modal.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            overlay.classList.add('hidden');
        }, 300);

        document.removeEventListener('keydown', handleKeyDown);
    }

    /**
     * Handle keyboard events in modal
     */
    function handleKeyDown(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    }

    /**
     * Handle bulk status changes
     */
    function handleBulkStatusChange(status) {
        const selectedItems = document.querySelectorAll('.product-checkbox:checked');
        if (selectedItems.length === 0) {
            showFailureModal('No products selected', 'Please select products to update.');
            return;
        }

        const itemCount = selectedItems.length;
        const itemType = 'products';
        const action = status;
        const statusText = status === 'publish' ? 'Publish' : 'Move to Draft';

        showBulkActionConfirmation(
            status,
            itemCount,
            itemType,
            function() {
                // AJAX bulk status update
                const productIds = Array.from(selectedItems).map(cb => cb.value);

                const formData = new FormData();
                formData.append('action', 'bulk_update_status');
                formData.append('status', status);
                formData.append('product_ids', JSON.stringify(productIds));
                formData.append('csrf_token', window.csrfToken);

                fetch('ajax_bulk_update_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.showToast(`${statusText} ${itemCount} products successfully.`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        window.showToast(data.message || 'Failed to update products.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.showToast('An error occurred.', 'error');
                });
            }
        );
    }

    /**
     * Toggle item status via AJAX
     */
    function toggleStatusViaAjax(toggleElement, field) {
        const itemId = toggleElement.dataset.id;
        const currentStatus = toggleElement.classList.contains('bg-green-500') ? 1 : 0;
        const newStatus = currentStatus === 1 ? 0 : 1;

        const formData = new FormData();
        formData.append('action', 'toggle_' + field);
        formData.append('id', itemId);
        formData.append('csrf_token', window.csrfToken);

        fetch('ajax_toggle_' + field + '.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                if (newStatus === 1) {
                    toggleElement.classList.remove('bg-gray-300');
                    toggleElement.classList.add('bg-green-500');
                    toggleElement.querySelector('.translate-x-1')?.classList.add('translate-x-6');
                    toggleElement.querySelector('.translate-x-1')?.classList.remove('translate-x-1');
                } else {
                    toggleElement.classList.remove('bg-green-500');
                    toggleElement.classList.add('bg-gray-300');
                    toggleElement.querySelector('.translate-x-6')?.classList.add('translate-x-1');
                    toggleElement.querySelector('.translate-x-6')?.classList.remove('translate-x-6');
                }
                window.showToast(data.message || 'Status updated successfully.', 'success');
            } else {
                window.showToast(data.message || 'Failed to update status.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.showToast('An error occurred.', 'error');
        });
    }

    // ========== HELPER FUNCTIONS ==========

    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getActionTypeFromForm(form) {
        if (form.action.includes('delete')) return 'delete';
        if (form.action.includes('drop')) return 'drop';
        if (form.action.includes('reset')) return 'reset';
        if (form.action.includes('status')) return 'update';
        return 'submit';
    }

    function getItemTypeFromForm(form) {
        // Try to determine from form classes, action URL, or nearby elements
        const classes = Array.from(form.classList);
        const itemTypeClass = classes.find(cls => cls.includes('form-') || cls.includes('-form'));
        if (itemTypeClass) {
            return itemTypeClass.replace('form-', '').replace('-form', '').replace('-', ' ');
        }

        if (form.action.includes('user')) return 'user';
        if (form.action.includes('product')) return 'product';
        if (form.action.includes('category')) return 'category';
        if (form.action.includes('page')) return 'page';
        if (form.action.includes('message')) return 'message';

        return 'item';
    }

    function getFormItemCount(form) {
        // Check for hidden inputs with arrays (bulk operations)
        const hiddenArrays = form.querySelectorAll('input[type="hidden"][name$="[]"]');
        if (hiddenArrays.length > 0) {
            // Count unique values in arrays
            const ids = new Set();
            hiddenArrays.forEach(input => {
                if (input.value) ids.add(input.value);
            });
            return ids.size;
        }

        // Single item operations
        return 1;
    }

    // Make functions globally available for legacy compatibility
    window.closeDeleteModal = function() {
        closeModal();
    };

    window.showAdminToast = function(message, type = 'info') {
        if (window.showToast) {
            window.showToast(message, type);
        }
    };
});

/**
 * Enhanced Toast Notification System
 * Modern toast notifications with icons and animations
 */
window.showModernToast = function(message, type = 'info', duration = 3000) {
    const icons = {
        success: '<i class="fas fa-check-circle"></i>',
        error: '<i class="fas fa-times-circle"></i>',
        warning: '<i class="fas fa-exclamation-triangle"></i>',
        info: '<i class="fas fa-info-circle"></i>'
    };

    const toast = document.createElement('div');
    toast.className = `admin-toast ${type}`;
    toast.innerHTML = `
        <div style="font-size: 1.5rem;">${icons[type]}</div>
        <div style="flex: 1;">
            <div style="font-weight: 600; margin-bottom: 2px;">${type.charAt(0).toUpperCase() + type.slice(1)}</div>
            <div style="font-size: 0.9rem; opacity: 0.9;">${message}</div>
        </div>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer; opacity: 0.5; hover:opacity: 1;">
            <i class="fas fa-times"></i>
        </button>
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
};

/**
 * Loading Overlay
 * Show/hide loading spinner
 */
window.showLoading = function(message = 'Loading...') {
    const existing = document.getElementById('admin-loading-overlay');
    if (existing) return;

    const overlay = document.createElement('div');
    overlay.id = 'admin-loading-overlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div style="text-align: center;">
            <div class="loading-spinner"></div>
            <div style="color: white; margin-top: 20px; font-weight: 500;">${message}</div>
        </div>
    `;
    document.body.appendChild(overlay);
};

window.hideLoading = function() {
    const overlay = document.getElementById('admin-loading-overlay');
    if (overlay) overlay.remove();
};

/**
 * Confirmation Dialog Helper
 * Modern confirmation dialogs with Promise support
 */
window.showConfirm = function(message, title = 'Confirm Action', options = {}) {
    return new Promise((resolve) => {
        const {
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            type = 'info' // 'info', 'warning', 'danger'
        } = options;
        
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-[9999] overflow-y-auto';
        modal.innerHTML = `
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="this.closest('.fixed').remove(); window.confirmResolve(false);"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full ${type === 'danger' ? 'bg-red-100' : type === 'warning' ? 'bg-yellow-100' : 'bg-blue-100'} sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 ${type === 'danger' ? 'text-red-600' : type === 'warning' ? 'text-yellow-600' : 'text-blue-600'}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    ${type === 'danger' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />' : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'}
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">${title}</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">${message}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" class="confirm-btn w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 ${type === 'danger' ? 'bg-red-600 hover:bg-red-700' : type === 'warning' ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-blue-600 hover:bg-blue-700'} text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 ${type === 'danger' ? 'focus:ring-red-500' : type === 'warning' ? 'focus:ring-yellow-500' : 'focus:ring-blue-500'} sm:ml-3 sm:w-auto sm:text-sm">
                            ${confirmText}
                        </button>
                        <button type="button" class="cancel-btn mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            ${cancelText}
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Store resolve function globally for backdrop click
        window.confirmResolve = resolve;
        
        modal.querySelector('.confirm-btn').addEventListener('click', () => {
            modal.remove();
            resolve(true);
        });
        
        modal.querySelector('.cancel-btn').addEventListener('click', () => {
            modal.remove();
            resolve(false);
        });
        
        // ESC key to cancel
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                resolve(false);
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    });
};

// Legacy support
window.confirmAction = function(message, onConfirm, title = 'Confirm Action') {
    window.showConfirm(message, title).then(confirmed => {
        if (confirmed && typeof onConfirm === 'function') {
            onConfirm();
        }
    });
};

/**
 * Copy to Clipboard
 * Copy text with feedback
 */
window.copyToClipboard = function(text, successMessage = 'Copied to clipboard!') {
    navigator.clipboard.writeText(text).then(() => {
        if (window.showModernToast) {
            window.showModernToast(successMessage, 'success', 2000);
        } else {
            alert(successMessage);
        }
    }).catch(err => {
        console.error('Copy failed:', err);
        if (window.showModernToast) {
            window.showModernToast('Failed to copy', 'error');
        }
    });
};

/**
 * Table Row Actions
 * Quick actions for table rows
 */
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effect to table rows with data-href
    document.querySelectorAll('tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            if (!e.target.closest('button, a')) {
                window.location.href = this.dataset.href;
            }
        });
    });

    // Initialize tooltips
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.style.position = 'relative';
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'admin-tooltip';
            tooltip.textContent = this.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(0,0,0,0.9);
                color: white;
                padding: 6px 12px;
                border-radius: 6px;
                font-size: 12px;
                white-space: nowrap;
                margin-bottom: 8px;
                z-index: 10000;
                pointer-events: none;
            `;
            this.appendChild(tooltip);
        });
        element.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('.admin-tooltip');
            if (tooltip) tooltip.remove();
        });
    });
});

// Add slideOutRight animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);
