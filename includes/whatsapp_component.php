<?php
/**
 * WhatsApp Integration Component
 * Adds a floating WhatsApp button with customizable messages
 */

// Prevent direct access
if (!defined('IN_MOSSE_LUXE')) {
    exit;
}

// Get WhatsApp settings
$whatsapp_enabled = get_setting('whatsapp_enabled', '1');
$whatsapp_number = get_setting('whatsapp_number', '+27676162809');
$whatsapp_general_message = get_setting('whatsapp_general_message', 'Hi! I\'d like to inquire about your products.');
$whatsapp_product_message = get_setting('whatsapp_product_message', 'Hi! I\'m interested in %PRODUCT_NAME%. Can you tell me more about it?');

// Only show if WhatsApp is enabled
if ($whatsapp_enabled === '1' && !empty($whatsapp_number)):
    // Clean phone number (remove spaces, + signs for URL)
    $clean_number = preg_replace('/[^\d]/', '', $whatsapp_number);
?>

<!-- WhatsApp Integration Styles -->
<style>
/* WhatsApp Premium Widget */
.whatsapp-premium-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 10000;
    font-family: 'Inter', sans-serif;
}

/* Enhanced Floating Button */
.whatsapp-float {
    position: relative;
    width: 65px;
    height: 65px;
    background: linear-gradient(135deg, #25D366 0%, #22C55E 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    font-size: 30px;
    box-shadow:
        0 8px 30px rgba(37, 211, 102, 0.4),
        0 0 0 1px rgba(255, 255, 255, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer;
    border: none;
    outline: none;
    animation: whatsapp-entrance 0.6s ease-out;
}

.whatsapp-float:hover {
    transform: scale(1.15) rotate(5deg);
    box-shadow:
        0 12px 40px rgba(37, 211, 102, 0.6),
        0 0 0 1px rgba(255, 255, 255, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
}

.whatsapp-float:active {
    transform: scale(1.05) rotate(2deg);
}

/* Pulse Animation */
@keyframes whatsapp-pulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 8px 30px rgba(37, 211, 102, 0.4);
    }
    50% {
        transform: scale(1.08);
        box-shadow: 0 12px 35px rgba(37, 211, 102, 0.7);
    }
}

@keyframes whatsapp-entrance {
    0% {
        opacity: 0;
        transform: scale(0.3) translateY(20px);
    }
    50% {
        opacity: 1;
        transform: scale(1.05) translateY(-5px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Premium Tooltip */
.whatsapp-tooltip {
    position: absolute;
    right: 80px;
    top: 50%;
    transform: translateY(-50%);
    background: linear-gradient(135deg, #1F2937 0%, #111827 100%);
    color: white;
    padding: 12px 18px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    pointer-events: none;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.whatsapp-tooltip::after {
    content: '';
    position: absolute;
    right: -6px;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid #1F2937;
    border-top: 6px solid transparent;
    border-bottom: 6px solid transparent;
}

.whatsapp-tooltip::before {
    content: '';
    position: absolute;
    right: -7px;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-left: 7px solid rgba(255, 255, 255, 0.1);
    border-top: 7px solid transparent;
    border-bottom: 7px solid transparent;
}

.whatsapp-float:hover .whatsapp-tooltip {
    opacity: 1;
    visibility: visible;
    right: 85px;
}

/* Premium Product WhatsApp Button */
.product-whatsapp-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #25D366 0%, #22C55E 100%);
    color: white;
    padding: 14px 24px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
    transform: translateY(0);
}

.product-whatsapp-btn:hover {
    background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 211, 102, 0.5);
    border-color: rgba(255, 255, 255, 0.4);
}

.product-whatsapp-btn:active {
    transform: translateY(0);
}

.product-whatsapp-btn i {
    font-size: 16px;
    transition: transform 0.3s ease;
}

.product-whatsapp-btn:hover i {
    transform: rotate(15deg);
}

/* Online Status Indicator */
.whatsapp-status {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 14px;
    height: 14px;
    background: #10B981;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 1px rgba(37, 211, 102, 0.3);
}

.whatsapp-status::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 4px;
    height: 4px;
    background: white;
    border-radius: 50%;
    animation: whatsapp-status-pulse 2s infinite;
}

@keyframes whatsapp-status-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Message Badge */
.whatsapp-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    animation: whatsapp-badge-bounce 0.6s ease-out;
}

@keyframes whatsapp-badge-bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

/* Premium Quick Actions Menu (Expandable) */
.whatsapp-quickmenu {
    position: absolute;
    bottom: 80px;
    right: 0;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.1);
    padding: 8px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px) scale(0.8);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: none;
}

.whatsapp-float:hover .whatsapp-quickmenu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
    pointer-events: auto;
}

.whatsapp-quick-item {
    display: block;
    padding: 12px 16px;
    color: #374151;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.whatsapp-quick-item:hover {
    background: #F3F4F6;
    color: #25D366;
    transform: translateX(2px);
}

.whatsapp-quick-item i {
    font-size: 16px;
    width: 16px;
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .whatsapp-premium-widget {
        bottom: 15px;
        right: 15px;
    }

    .whatsapp-float {
        width: 58px;
        height: 58px;
        font-size: 26px;
    }

    .whatsapp-tooltip {
        display: none;
    }

    .whatsapp-quickmenu {
        bottom: 70px;
        right: -10px;
        min-width: 180px;
    }

    .product-whatsapp-btn {
        padding: 12px 20px;
        font-size: 13px;
    }
}

/* Success Animation */
@keyframes whatsapp-success {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.whatsapp-success {
    animation: whatsapp-success 0.6s ease;
}

/* Product page WhatsApp button */
.product-whatsapp-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #25D366;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.product-whatsapp-btn:hover {
    background: #20BA5A;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
}

.product-whatsapp-btn i {
    font-size: 18px;
}

/* Pulsing animation for attention */
@keyframes whatsapp-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.whatsapp-float.whatsapp-pulse {
    animation: whatsapp-pulse 2s infinite;
}
</style>

<!-- Premium WhatsApp Widget -->
<div class="whatsapp-premium-widget">
    <!-- Floating Button with Status -->
    <button type="button"
            class="whatsapp-float whatsapp-pulse"
            onclick="openWhatsAppGeneral()"
            title="Chat with Mossé Luxe on WhatsApp">
        <i class="fab fa-whatsapp"></i>
        <div class="whatsapp-status"></div>

        <!-- Quick Actions Menu (on hover) -->
        <div class="whatsapp-quickmenu">
            <a href="#" onclick="openWhatsAppGeneral(); return false;" class="whatsapp-quick-item">
                <i class="fas fa-comments"></i>
                General Inquiry
            </a>
            <a href="#" onclick="openWhatsAppOrder(); return false;" class="whatsapp-quick-item">
                <i class="fas fa-shopping-cart"></i>
                Order Support
            </a>
            <a href="#" onclick="openWhatsAppSize(); return false;" class="whatsapp-quick-item">
                <i class="fas fa-ruler"></i>
                Size Guide
            </a>
            <a href="tel:+27676162809" class="whatsapp-quick-item">
                <i class="fas fa-phone"></i>
                Call Support
            </a>
        </div>
    </button>

    <!-- Premium Tooltip -->
    <span class="whatsapp-tooltip">
        💬 Chat with Mossé Luxe
        <br><small style="color: #9CA3AF;">Usually replies instantly</small>
    </span>
</div>

<!-- Premium WhatsApp Widget Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced WhatsApp Functions
    const whatsappNumber = '<?php echo htmlspecialchars($clean_number); ?>';

    window.openWhatsAppGeneral = function() {
        const message = '<?php echo str_replace("'", "\\'", $whatsapp_general_message); ?>';
        const whatsappUrl = 'https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(message);
        window.open(whatsappUrl, '_blank');
        trackWhatsAppClick('general_inquiry');
    };

    window.openWhatsAppProduct = function(productName) {
        const productMessage = '<?php echo str_replace("'", "\\'", $whatsapp_product_message); ?>';
        const finalMessage = productMessage.replace('%PRODUCT_NAME%', productName);
        const whatsappUrl = 'https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(finalMessage);
        window.open(whatsappUrl, '_blank');
        trackWhatsAppClick('product_inquiry');
    };

    window.openWhatsAppOrder = function() {
        const orderMessage = 'Hi! I need help with my order. Can you assist me?';
        const whatsappUrl = 'https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(orderMessage);
        window.open(whatsappUrl, '_blank');
        trackWhatsAppClick('order_support');
    };

    window.openWhatsAppSize = function() {
        const sizeMessage = 'Hi! Can you help me with sizing? I need advice on the right size.';
        const whatsappUrl = 'https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(sizeMessage);
        window.open(whatsappUrl, '_blank');
        trackWhatsAppClick('size_guide');
    };

    // Track WhatsApp interactions for analytics
    function trackWhatsAppClick(action) {
        if (typeof gtag !== 'undefined') {
            gtag('event', 'click', {
                event_category: 'whatsapp',
                event_label: action
            });
        }

        // Add success animation
        const btn = document.querySelector('.whatsapp-float');
        btn.classList.add('whatsapp-success');
        setTimeout(() => {
            btn.classList.remove('whatsapp-success');
        }, 600);
    }

    // Add pulsing effect on first visit
    const hasVisited = localStorage.getItem('whatsapp_visited');
    const whatsappBtn = document.querySelector('.whatsapp-float');

    if (!hasVisited) {
        // Add extra pulse animation for first-time visitors
        setTimeout(() => {
            whatsappBtn.style.animation = 'whatsapp-pulse 1s ease-in-out 3';
            showWelcomeBadge();
        }, 2000);
        localStorage.setItem('whatsapp_visited', 'true');
    } else {
        // Normal pulse for returning visitors
        setTimeout(() => {
            whatsappBtn.classList.add('whatsapp-pulse');
        }, 3000);
    }

    // Show welcome badge for first-time visitors
    function showWelcomeBadge() {
        const badge = document.createElement('div');
        badge.className = 'whatsapp-badge';
        badge.innerHTML = '1';
        badge.style.zIndex = '10001';

        whatsappBtn.appendChild(badge);

        // Remove badge after animation
        setTimeout(() => {
            badge.remove();
        }, 3000);
    }

    // Add entrance animation timing
    setTimeout(() => {
        whatsappBtn.style.opacity = '1';
    }, 100);

    // Track floating button clicks for analytics
    whatsappBtn.addEventListener('click', function() {
        trackWhatsAppClick('floating_button');
    });

    // Close menu on outside click (mobile)
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.whatsapp-premium-widget')) {
            // You can add logic here to close any open menus if needed
        }
    });
});
</script>

<?php endif; // End WhatsApp enabled check ?>

<?php
// Helper functions for WhatsApp integration

/**
 * Generate WhatsApp URL for product inquiries
 */
function generate_whatsapp_product_url($product_name) {
    $number = get_setting('whatsapp_number', '+27821234567');
    $clean_number = preg_replace('/[^\d]/', '', $number);
    $message_template = get_setting('whatsapp_product_message', 'Hi! I\'m interested in %PRODUCT_NAME%. Can you tell me more about it?');
    $message = str_replace('%PRODUCT_NAME%', $product_name, $message_template);

    return 'https://wa.me/' . $clean_number . '?text=' . urlencode($message);
}

/**
 * Generate WhatsApp URL for general inquiries
 */
function generate_whatsapp_general_url() {
    $number = get_setting('whatsapp_number', '+27821234567');
    $clean_number = preg_replace('/[^\d]/', '', $number);
    $message = get_setting('whatsapp_general_message', 'Hi! I\'d like to inquire about your products.');

    return 'https://wa.me/' . $clean_number . '?text=' . urlencode($message);
}

/**
 * Check if WhatsApp is enabled
 */
function is_whatsapp_enabled() {
    return get_setting('whatsapp_enabled', '0') === '1';
}
?>
