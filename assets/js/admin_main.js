document.addEventListener('DOMContentLoaded', function() {
    // Enhanced tooltip functionality
    const tooltips = document.querySelectorAll('.nav-tooltip');
    let tooltipElement = null;

    tooltips.forEach(el => {
        const tooltipText = el.getAttribute('data-tooltip');
        if (!tooltipText) return;

        el.addEventListener('mouseenter', (e) => {
            // Create tooltip element if it doesn't exist
            if (!tooltipElement) {
                tooltipElement = document.createElement('div');
                tooltipElement.className = 'fixed bg-black text-white text-xs rounded-md px-3 py-1.5 z-50 shadow-lg transition-opacity duration-200 opacity-0 pointer-events-none';
                document.body.appendChild(tooltipElement);
            }

            tooltipElement.textContent = tooltipText;

            // Position tooltip
            const rect = el.getBoundingClientRect();
            tooltipElement.style.left = `${rect.right + 10}px`;
            tooltipElement.style.top = `${rect.top + (rect.height / 2) - (tooltipElement.offsetHeight / 2)}px`;

            // Fade in
            setTimeout(() => {
                tooltipElement.style.opacity = '1';
            }, 50);
        });

        el.addEventListener('mouseleave', () => {
            if (tooltipElement) {
                tooltipElement.style.opacity = '0';
                // Remove after transition
                setTimeout(() => {
                    if (tooltipElement && tooltipElement.parentNode) {
                        tooltipElement.parentNode.removeChild(tooltipElement);
                        tooltipElement = null;
                    }
                }, 200);
            }
        });
    });

    // Dark mode toggle (keeping in admin_main.js but avoiding conflicts)
    const darkModeToggleBtn = document.getElementById('dark-mode-toggle');

    function applyTheme(theme) {
        const htmlElement = document.documentElement;
        if (theme === 'dark') {
            htmlElement.classList.add('dark');
            if (darkModeToggleBtn) {
                darkModeToggleBtn.querySelector('i').classList.remove('fa-moon');
                darkModeToggleBtn.querySelector('i').classList.add('fa-sun');
            }
        } else {
            htmlElement.classList.remove('dark');
            if (darkModeToggleBtn) {
                darkModeToggleBtn.querySelector('i').classList.remove('fa-sun');
                darkModeToggleBtn.querySelector('i').classList.add('fa-moon');
            }
        }
    }

    function toggleDarkMode() {
        const htmlElement = document.documentElement;
        if (htmlElement.classList.contains('dark')) {
            applyTheme('light');
            localStorage.setItem('theme', 'light');
        } else {
            applyTheme('dark');
            localStorage.setItem('theme', 'dark');
        }
    }

    // Load dark mode preference on initial load
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        applyTheme(savedTheme);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        // If no preference, check system preference
        applyTheme('dark');
    } else {
        applyTheme('light'); // Default to light
    }

    if (darkModeToggleBtn) {
        darkModeToggleBtn.addEventListener('click', toggleDarkMode);
    }

    // Product Management JavaScript (from admin/products.php)
    // Handle Status Toggle
    document.querySelectorAll('.status-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;
            const span = this.querySelector('span');

            fetch('ajax_toggle_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${window.csrfToken}&id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.new_status === 1) {
                        this.classList.remove('bg-gray-300');
                        this.classList.add('bg-green-500');
                        span.classList.remove('translate-x-1');
                        span.classList.add('translate-x-6');
                    } else {
                        this.classList.remove('bg-green-500');
                        this.classList.add('bg-gray-300');
                        span.classList.remove('translate-x-6');
                        span.classList.add('translate-x-1');
                    }
                    showAdminToast(data.message, 'success');
                } else {
                    showAdminToast(data.message || 'Failed to update status.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAdminToast('An error occurred while updating status.', 'error');
            });
        });
    });

    // Handle Featured Toggle
    document.querySelectorAll('.featured-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;

            fetch('ajax_toggle_featured.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${window.csrfToken}&id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.new_status === 1) {
                        this.classList.remove('text-gray-300');
                        this.classList.add('text-yellow-400');
                    } else {
                        this.classList.remove('text-yellow-400');
                        this.classList.add('text-gray-300');
                    }
                    showAdminToast(data.message, 'success');
                } else {
                    showAdminToast(data.message || 'Failed to update featured status.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAdminToast('An error occurred while updating featured status.', 'error');
            });
        });
    });

    // Bulk Actions Functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkActions = document.getElementById('bulkActions');

    // Handle select all checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionsVisibility();
        });
    }

    // Handle individual checkboxes
    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = checkedBoxes.length === productCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < productCheckboxes.length;
            }
            updateBulkActionsVisibility();
        });
    });

    function updateBulkActionsVisibility() {
        const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
        if (bulkActions) {
            if (checkedBoxes.length > 0) {
                bulkActions.classList.remove('hidden');
            } else {
                bulkActions.classList.add('hidden');
            }
        }
    }

    // Bulk Publish
    const bulkPublishBtn = document.getElementById('bulkPublish');
    if (bulkPublishBtn) {
        bulkPublishBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) return;

            window.showConfirm(
                `Are you sure you want to publish ${selectedIds.length} selected products?`,
                'Publish Products',
                { confirmText: 'Publish', cancelText: 'Cancel', type: 'info' }
            ).then(confirmed => {
                if (confirmed) bulkUpdateStatus(selectedIds, 1);
            });
        });
    }

    // Bulk Draft
    const bulkDraftBtn = document.getElementById('bulkDraft');
    if (bulkDraftBtn) {
        bulkDraftBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) return;

            window.showConfirm(
                `Are you sure you want to move ${selectedIds.length} selected products to draft?`,
                'Move to Draft',
                { confirmText: 'Move to Draft', cancelText: 'Cancel', type: 'warning' }
            ).then(confirmed => {
                if (confirmed) bulkUpdateStatus(selectedIds, 0);
            });
        });
    }

    // Bulk Delete
    const bulkDeleteBtn = document.getElementById('bulkDelete');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) return;

            window.showConfirm(
                `Are you sure you want to delete ${selectedIds.length} selected products? This action cannot be undone.`,
                'Delete Products',
                { confirmText: 'Delete', cancelText: 'Cancel', type: 'danger' }
            ).then(confirmed => {
                if (confirmed) bulkDeleteProducts(selectedIds);
            });
        });
    }

    function bulkUpdateStatus(productIds, status) {
        fetch('ajax_bulk_update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `csrf_token=${window.csrfToken}&ids=${productIds.join(',')}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAdminToast(`Successfully updated ${data.updated_count} products.`, 'success');
                location.reload();
            } else {
                showAdminToast(data.message || 'Failed to update products. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAdminToast('An error occurred. Please try again.', 'error');
        });
    }

    function bulkDeleteProducts(productIds) {
        fetch('ajax_bulk_delete_products.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${window.csrfToken}&ids=${productIds.join(',')}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAdminToast(`Successfully deleted ${data.deleted_count} products.`, 'success');
                location.reload();
            } else {
                showAdminToast(data.message || 'Failed to delete products. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAdminToast('An error occurred. Please try again.', 'error');
        });
    }

    // Global functions for modal (Categories and Users)
    window.confirmDelete = function(id, name, type) { // Added 'type' parameter
        const deleteMessage = document.getElementById('deleteMessage');
        const deleteIdInput = document.getElementById('deleteId'); // Generic ID input
        const deleteForm = document.getElementById('deleteForm');

        if (type === 'category') {
            deleteMessage.textContent = `Are you sure you want to delete the category "${name}"?`;
            deleteIdInput.name = 'category_id'; // Set name for category deletion
            deleteIdInput.value = id;
            deleteForm.action = 'categories.php'; // Set form action for category deletion
        } else if (type === 'user') {
            deleteMessage.textContent = `Are you sure you want to delete user "${name}"? This action cannot be undone.`;
            deleteIdInput.name = 'user_id'; // Set name for user deletion
            deleteIdInput.value = id;
            deleteForm.action = 'delete_user.php'; // Set form action for user deletion
        } else if (type === 'admin') {
            deleteMessage.textContent = `Are you sure you want to delete the admin "${name}"? This action cannot be undone.`;
            deleteIdInput.name = 'id';
            deleteIdInput.value = id;
            deleteForm.action = 'manage_admins.php';
        } else if (type === 'launching_soon') {
            deleteMessage.textContent = `Are you sure you want to delete the item "${name}"?`;
            deleteIdInput.name = 'item_id';
            deleteIdInput.value = id;
            deleteForm.action = 'launching_soon.php';
        } else if (type === 'hero_slide') {
            deleteMessage.textContent = `Are you sure you want to delete the slide "${name}"?`;
            deleteIdInput.name = 'id';
            deleteIdInput.value = id;
            deleteForm.action = 'delete_hero_slide.php';
        } else if (type === 'page') {
            deleteMessage.textContent = `Are you sure you want to delete the page "${name}"?`;
            deleteIdInput.name = 'id';
            deleteIdInput.value = id;
            deleteForm.action = 'delete_page.php';
        } else if (type === 'message') {
            deleteMessage.textContent = `Are you sure you want to delete the ${name}?`;
            deleteIdInput.name = 'id';
            deleteIdInput.value = id;
            deleteForm.action = 'delete_message.php';
        }
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    window.closeDeleteModal = function() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    }

    // Orders Management JavaScript (from admin/orders.php)
    // Bulk Actions Functionality for Orders
    const selectAllOrdersCheckbox = document.getElementById('selectAllOrders');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const bulkOrderActions = document.getElementById('bulkOrderActions');

    // Handle select all checkbox
    if (selectAllOrdersCheckbox) {
        selectAllOrdersCheckbox.addEventListener('change', function() {
            orderCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkOrderActionsVisibility();
        });
    }

    // Handle individual checkboxes
    orderCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
            if (selectAllOrdersCheckbox) {
                selectAllOrdersCheckbox.checked = checkedBoxes.length === orderCheckboxes.length;
                selectAllOrdersCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < orderCheckboxes.length;
            }
            updateBulkOrderActionsVisibility();
        });
    });

    function updateBulkOrderActionsVisibility() {
        const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
        if (bulkOrderActions) {
            if (checkedBoxes.length > 0) {
                bulkOrderActions.classList.remove('hidden');
            } else {
                bulkOrderActions.classList.add('hidden');
            }
        }
    }

    // Bulk Status Update
    const applyBulkStatusBtn = document.getElementById('applyBulkStatus');
    if (applyBulkStatusBtn) {
        applyBulkStatusBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
            const newStatus = document.getElementById('bulkStatusSelect').value;

            if (selectedIds.length === 0 || !newStatus) {
                showAdminToast('Please select orders and a status to apply.', 'error');
                return;
            }

            window.showConfirm(
                `Are you sure you want to change the status of ${selectedIds.length} selected orders to "${newStatus}"?`,
                'Update Order Status',
                { confirmText: 'Update Status', cancelText: 'Cancel', type: 'info' }
            ).then(confirmed => {
                if (confirmed) bulkUpdateOrderStatus(selectedIds, newStatus);
            });
        });
    }

    function bulkUpdateOrderStatus(orderIds, status) {
        fetch('ajax_update_order_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${window.csrfToken}&ids=${orderIds.join(',')}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAdminToast(`Successfully updated ${data.updated_count} orders.`, 'success');
                location.reload();
            } else {
                showAdminToast(data.message || 'Failed to update orders. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAdminToast('An error occurred. Please try again.', 'error');
        });
    }

    // Inline Status Update
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;

            fetch('ajax_update_order_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${window.csrfToken}&ids=${orderId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the select styling based on new status
                    const statusClasses = {
                        'Pending': 'bg-yellow-100 text-yellow-800',
                        'Processing': 'bg-blue-100 text-blue-800',
                        'Shipped': 'bg-indigo-100 text-indigo-800',
                        'Completed': 'bg-green-100 text-green-800',
                        'Cancelled': 'bg-red-100 text-red-800'
                    };

                    this.className = `status-select px-2 py-1 text-xs rounded-full border-0 text-white ${statusClasses[newStatus] || 'bg-gray-100 text-gray-800'}`;
                    showAdminToast('Order status updated successfully.', 'success');
                } else {
                    showAdminToast(data.message || 'Failed to update order status.', 'error');
                    location.reload(); // Reload to revert changes
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAdminToast('An error occurred. Please try again.', 'error');
                location.reload();
            });
        });
    });
});
