// Accessibility Enhancements for Mossé Luxe

document.addEventListener('DOMContentLoaded', function() {
    initializeAccessibilityFeatures();
});

function initializeAccessibilityFeatures() {
    setupSkipLinks();
    setupTextSizeControls();
    setupHighContrastToggle();
    setupScreenReaderSupport();
    setupFormEnhancements();
    setupKeyboardNavigation();
}

// Skip Links for Screen Readers
function setupSkipLinks() {
    const skipLinks = document.querySelectorAll('.skip-link');
    skipLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const target = document.getElementById(this.getAttribute('href').substring(1));
            if (target) {
                target.focus();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

// Adjustable Text Size
function setupTextSizeControls() {
    // Store user's preferred text size in localStorage
    const textScale = localStorage.getItem('accessible-text-scale') || '100';
    document.documentElement.classList.add(`adj-text-scale-${textScale}`);

    // Text size controls (if added to page)
    const sizeControls = document.querySelectorAll('.text-size-control');
    sizeControls.forEach(control => {
        control.addEventListener('click', function() {
            const size = this.dataset.size;
            document.documentElement.className = document.documentElement.className.replace(/adj-text-scale-\d+/g, '');
            document.documentElement.classList.add(`adj-text-scale-${size}`);
            localStorage.setItem('accessible-text-scale', size);

            // Announce change to screen readers
            announceAccessibilityChange(`Text size changed to ${size}%`);
        });
    });
}

// High Contrast Mode Toggle
function setupHighContrastToggle() {
    const contrastPreference = localStorage.getItem('high-contrast') === 'true';
    if (contrastPreference) {
        document.documentElement.classList.add('force-high-contrast');
    }

    // Contrast toggle button (if added to page)
    const contrastToggle = document.getElementById('contrast-toggle');
    if (contrastToggle) {
        contrastToggle.addEventListener('click', function() {
            const isHighContrast = document.documentElement.classList.toggle('force-high-contrast');
            localStorage.setItem('high-contrast', isHighContrast);

            const status = isHighContrast ? 'High contrast mode enabled' : 'High contrast mode disabled';
            announceAccessibilityChange(status);
            this.setAttribute('aria-pressed', isHighContrast);
        });
    }
}

// Screen Reader Support
function setupScreenReaderSupport() {
    // Add aria-live region for dynamic content
    const liveRegion = document.createElement('div');
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    liveRegion.className = 'sr-only aria-live';
    liveRegion.id = 'aria-live-region';
    document.body.appendChild(liveRegion);

    // Add screen reader descriptions to icons
    const icons = document.querySelectorAll('svg:not([aria-hidden="true"])');
    icons.forEach(icon => {
        if (!icon.getAttribute('aria-label') && !icon.getAttribute('aria-labelledby')) {
            const context = icon.closest('a,button') || icon.parentElement;
            if (context) {
                const contextText = context.textContent.trim();
                if (contextText) {
                    icon.setAttribute('aria-label', contextText);
                }
            }
        }
    });
}

// Enhanced Form Accessibility
function setupFormEnhancements() {
    // Add error/success states with proper ARIA
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });

            input.addEventListener('input', function() {
                if (this.hasAttribute('aria-invalid')) {
                    validateField(this);
                }
            });
        });
    });
}

// Field validation with accessible feedback
function validateField(field) {
    const value = field.value.trim();
    const isRequired = field.hasAttribute('required');
    const type = field.type;

    let isValid = true;
    let message = '';

    // Basic validation
    if (isRequired && !value) {
        isValid = false;
        message = `${field.name || 'This field'} is required`;
    } else if (type === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        message = 'Please enter a valid email address';
    } else if (type === 'tel' && value && !isValidPhone(value)) {
        isValid = false;
        message = 'Please enter a valid phone number';
    }

    // Update field accessibility
    field.setAttribute('aria-invalid', !isValid);

    // Find or create error message element
    let errorElement = field.parentNode.querySelector('.field-error');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'field-error sr-only';
        errorElement.setAttribute('role', 'alert');
        field.parentNode.insertBefore(errorElement, field.nextSibling);
    }

    if (!isValid) {
        errorElement.textContent = message;
        errorElement.classList.remove('sr-only');
        field.classList.add('form-field-error');
        field.classList.remove('form-field-success');
    } else {
        errorElement.classList.add('sr-only');
        field.classList.remove('form-field-error');
        if (value) {
            field.classList.add('form-field-success');
        }
    }

    return isValid;
}

// Enhanced Keyboard Navigation
function setupKeyboardNavigation() {
    // Handle Tab navigation for modal/dialog focus traps
    document.addEventListener('keydown', function(e) {
        // ESC key handling for modals
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal[open], .drawer[open]');
            modals.forEach(modal => {
                const closeBtn = modal.querySelector('.modal-close, .drawer-close');
                if (closeBtn) {
                    closeBtn.click();
                }
            });
        }

        // Tab navigation within focus traps
        if (e.key === 'Tab') {
            const focusableElements = getFocusableElements();
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey) {
                // Backward tab
                if (document.activeElement === firstElement) {
                    lastElement.focus();
                    e.preventDefault();
                }
            } else {
                // Forward tab
                if (document.activeElement === lastElement) {
                    firstElement.focus();
                    e.preventDefault();
                }
            }
        }
    });
}

// Get focusable elements within current context
function getFocusableElements() {
    const focusableSelector = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
    return Array.from(document.querySelectorAll(focusableSelector)).filter(el => {
        return el.offsetParent !== null && !el.hasAttribute('disabled');
    });
}

// Announce changes to screen readers
function announceAccessibilityChange(message) {
    const liveRegion = document.getElementById('aria-live-region');
    if (liveRegion) {
        liveRegion.textContent = message;
        // Clear after announcement
        setTimeout(() => {
            liveRegion.textContent = '';
        }, 1000);
    }
}

// Utility functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    // Basic South African phone number validation
    const phoneRegex = /^(?:\+27|0)[6-8][0-9]{8}$/;
    const cleaned = phone.replace(/\s+/g, '').replace(/[()-]/g, '');
    return phoneRegex.test(cleaned);
}

// Address validation for South African addresses
function validateSouthAfricanAddress(addressData) {
    const errors = [];

    // Basic validation rules for South African addresses
    const postalCode = addressData.postal_code;
    if (postalCode && !/^[0-9]{4}$/.test(postalCode)) {
        errors.push('South African postal codes must be 4 digits');
    }

    // Province validation
    const validProvinces = [
        'Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal',
        'Limpopo', 'Mpumalanga', 'Northern Cape', 'North West', 'Western Cape'
    ];

    if (addressData.province && !validProvinces.includes(addressData.province)) {
        errors.push('Please select a valid South African province');
    }

    return errors;
}

// Initialize address validation on checkout page
function initializeAddressValidation() {
    const addressFields = document.querySelectorAll('[data-address-field]');
    const form = document.getElementById('checkout-form');

    if (!form || addressFields.length === 0) return;

    addressFields.forEach(field => {
        field.addEventListener('blur', function() {
            if (this.value.trim()) {
                validateAddressField(this);
            }
        });
    });
}

function validateAddressField(field) {
    const fieldType = field.dataset.addressField;
    let isValid = true;
    let message = '';

    switch (fieldType) {
        case 'postal_code':
            if (!/^[0-9]{4}$/.test(field.value.trim())) {
                isValid = false;
                message = 'South African postal codes must be exactly 4 digits';
            }
            break;
        case 'province':
            const validProvinces = [
                'Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal',
                'Limpopo', 'Mpumalanga', 'Northern Cape', 'North West', 'Western Cape'
            ];
            if (!validProvinces.includes(field.value)) {
                isValid = false;
                message = 'Please select a valid South African province';
            }
            break;
    }

    updateFieldValidity(field, isValid, message);
}

function updateFieldValidity(field, isValid, message) {
    field.setAttribute('aria-invalid', !isValid);

    let messageElement = field.parentNode.querySelector('.field-validation-message');
    if (!messageElement) {
        messageElement = document.createElement('div');
        messageElement.className = 'field-validation-message';
        field.parentNode.appendChild(messageElement);
    }

    if (!isValid) {
        messageElement.textContent = message;
        messageElement.className = 'field-validation-message error';
        field.classList.add('form-field-error');
    } else {
        messageElement.textContent = '';
        messageElement.className = 'field-validation-message';
        field.classList.remove('form-field-error');
        field.classList.add('form-field-success');
    }
}

// Auto-focus management for accessibility
function manageFocus(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.focus();
        // Scroll into view if needed
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Initialize address validation if on checkout page
if (document.getElementById('checkout-form')) {
    initializeAddressValidation();
}
