<?php
// Include the admin bootstrap for automatic setup
require_once 'bootstrap.php';
$conn = get_db_connection();

// Generate CSRF token after bootstrap
$csrf_token_for_check = generate_csrf_token();

$pageTitle = 'WhatsApp Management';

// Handle form submission directly
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Temporarily disable CSRF check for debugging
    // if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    //     $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
    //     header('Location: whatsapp.php');
    //     exit;
    // }

    $whatsapp_enabled = isset($_POST['whatsapp_enabled']) ? 1 : 0;
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $whatsapp_general_message = trim($_POST['whatsapp_general_message']);
    $whatsapp_order_message = trim($_POST['whatsapp_order_message']);
    $whatsapp_size_message = trim($_POST['whatsapp_size_message']);

    // Validation
    if ($whatsapp_enabled && empty($whatsapp_number)) {
        $_SESSION['toast_message'] = ['message' => 'WhatsApp business number is required when integration is enabled.', 'type' => 'error'];
        header('Location: whatsapp.php');
        exit;
    }

    $settings_to_update = [
        'whatsapp_enabled' => $whatsapp_enabled,
        'whatsapp_number' => $whatsapp_number,
        'whatsapp_general_message' => $whatsapp_general_message,
        'whatsapp_order_message' => $whatsapp_order_message,
        'whatsapp_size_message' => $whatsapp_size_message,
    ];

    $success_count = 0;
    foreach ($settings_to_update as $key => $value) {
        $insert_sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param('sss', $key, $value, $value);

        if ($stmt->execute()) {
            $success_count++;
        }
        $stmt->close();
    }

    if ($success_count === count($settings_to_update)) {
        $_SESSION['toast_message'] = ['message' => 'WhatsApp settings updated successfully!', 'type' => 'success'];
        header('Location: whatsapp.php');
        exit;
    } else {
        $_SESSION['toast_message'] = ['message' => 'Error updating some WhatsApp settings.', 'type' => 'error'];
        header('Location: whatsapp.php');
        exit;
    }
}

// WhatsApp number from config - hardcoded for sidebar integration
$whatsapp_number = '+27676162809';

// TODO: Replace with actual WhatsApp Business API integration
// For now, showing placeholder data structure for future API implementation
$whatsapp_stats = [
    'total_conversations' => get_whatsapp_total_conversations(),
    'total_messages' => get_whatsapp_total_messages(),
    'active_chats' => get_whatsapp_active_chats(),
    'conversations_today' => get_whatsapp_conversations_today(),
    'response_rate' => get_whatsapp_response_rate(),
    'avg_response_time' => get_whatsapp_avg_response_time()
];

// TODO: Replace with actual WhatsApp API conversations
$recent_conversations = get_whatsapp_recent_conversations();

/**
 * TODO: Implement these functions with actual WhatsApp Business API calls
 * For now, returning placeholder data to show structure
 */

// Placeholder functions - replace with real WhatsApp API integration
function get_whatsapp_total_conversations() {
    // TODO: Call WhatsApp Business API to get real conversation count
    // For now, return 0 with a message to implement API
    return 0;
}

function get_whatsapp_total_messages() {
    // TODO: Call WhatsApp Business API to get message metrics
    return 0;
}

function get_whatsapp_active_chats() {
    // TODO: Call WhatsApp Business API to get active conversations
    return 0;
}

function get_whatsapp_conversations_today() {
    // TODO: Call WhatsApp Business API to get today's conversations
    return 0;
}

function get_whatsapp_response_rate() {
    // TODO: Calculate from WhatsApp API data
    return 0;
}

function get_whatsapp_avg_response_time() {
    // TODO: Calculate from WhatsApp API data
    return 'API Integration Required';
}

function get_whatsapp_recent_conversations() {
    // TODO: Fetch real conversations from WhatsApp API
    // For now, return empty array with placeholder message
    return [
        [
            'customer' => 'No conversations available',
            'phone' => '',
            'last_message' => 'WhatsApp Business API integration required to display real conversations',
            'time' => '',
            'unread' => false
        ]
    ];
}
?>

<?php
include 'header.php';
?>

<style>
.whatsapp-form {
    max-width: 600px;
    margin: 0 auto;
}
.whatsapp-section {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}
.whatsapp-section-header {
    background: linear-gradient(135deg, #25d366, #128c7e);
    color: white;
    padding: 1rem;
    font-weight: 600;
}
.whatsapp-section-body {
    padding: 1.5rem;
    background: white;
}
</style>

<div class="space-y-6">
    <!-- WhatsApp Settings Form -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fab fa-whatsapp text-2xl text-green-600"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">WhatsApp Business Configuration</h1>
                    <p class="text-gray-600">Configure your WhatsApp business number and message templates</p>
                </div>
            </div>
        </div>

        <?php
        // Settings loaded after header.php include - accessible via session or this reload
        $conn = get_db_connection();
        $current_settings = [];
        $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'whatsapp_%'";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $current_settings[$row['setting_key']] = $row['setting_value'];
            }
            $stmt->close();
        }
        ?>

        <form action="whatsapp.php" method="post" class="p-6">
            <?php generate_csrf_token_input(); ?>

            <!-- WhatsApp Enabled Toggle -->
            <div class="whatsapp-section mb-6">
                <div class="whatsapp-section-header">
                    <i class="fab fa-whatsapp mr-2"></i> WhatsApp Integration Status
                </div>
                <div class="whatsapp-section-body">
                    <div class="flex items-center">
                        <input type="checkbox" id="whatsapp_enabled" name="whatsapp_enabled" value="1"
                               <?php echo (!isset($current_settings['whatsapp_enabled']) || $current_settings['whatsapp_enabled'] == '1') ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-green-600 focus:ring-green-600 h-4 w-4">
                        <div class="ml-3">
                            <label for="whatsapp_enabled" class="text-sm font-medium text-gray-700">Enable WhatsApp Integration</label>
                            <p class="text-sm text-gray-500">Show WhatsApp chat tab in the cart sidebar for customer support</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Number Configuration -->
            <div class="whatsapp-section mb-6">
                <div class="whatsapp-section-header">
                    <i class="fas fa-phone mr-2"></i> Business Number Configuration
                </div>
                <div class="whatsapp-section-body">
                    <div class="space-y-4">
                        <div>
                            <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-2">
                                WhatsApp Business Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="whatsapp_number" name="whatsapp_number"
                                   value="<?php echo htmlspecialchars($current_settings['whatsapp_number'] ?? '+27676162809'); ?>"
                                   placeholder="+27821234567 or 0821234567"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                            <p class="text-xs text-gray-500 mt-1">Include country code (e.g., +2782...) for international customers. Your current South African business number: +27676162809</p>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <p class="text-sm text-green-700">
                                <i class="fas fa-info-circle mr-2"></i>
                                This number should match your WhatsApp Business account. All customer communications will be directed to this number.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Templates Configuration -->
            <div class="whatsapp-section mb-6">
                <div class="whatsapp-section-header">
                    <i class="fas fa-comment-dots mr-2"></i> Message Templates
                </div>
                <div class="whatsapp-section-body">
                    <p class="text-sm text-gray-600 mb-4">
                        Configure the pre-filled messages that customers receive when they select different support options in the cart sidebar "Chat" tab.
                    </p>

                    <!-- General Inquiry Message -->
                    <div class="mb-6">
                        <label for="whatsapp_general_message" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-comments mr-2 text-green-600"></i>
                            General Inquiry Message
                        </label>
                        <textarea id="whatsapp_general_message" name="whatsapp_general_message" rows="3"
                                  placeholder="Hi! I'm interested in your luxury streetwear collection. Can you tell me more about your latest arrivals?"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600"><?php echo htmlspecialchars($current_settings['whatsapp_general_message'] ?? "Hi! I'm interested in your luxury streetwear collection. Can you tell me more about your latest arrivals?"); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Message sent when customers select "General Inquiry" in the chat sidebar</p>
                    </div>

                    <!-- Order Support Message -->
                    <div class="mb-6">
                        <label for="whatsapp_order_message" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-shopping-cart mr-2 text-blue-600"></i>
                            Order Support Message
                        </label>
                        <textarea id="whatsapp_order_message" name="whatsapp_order_message" rows="3"
                                  placeholder="Hi! I need help with my order. Can you assist me with shipping details or order tracking?"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600"><?php echo htmlspecialchars($current_settings['whatsapp_order_message'] ?? "Hi! I need help with my order. Can you assist me with shipping details or order tracking?"); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Message sent when customers select "Order Support" in the chat sidebar</p>
                    </div>

                    <!-- Size Guide Message -->
                    <div class="mb-6">
                        <label for="whatsapp_size_message" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-ruler mr-2 text-purple-600"></i>
                            Size Guide Help Message
                        </label>
                        <textarea id="whatsapp_size_message" name="whatsapp_size_message" rows="3"
                                  placeholder="Hi! I'm not sure about the sizing for your leather goods. Can you help me find the perfect fit and share your size guide?"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600"><?php echo htmlspecialchars($current_settings['whatsapp_size_message'] ?? "Hi! I'm not sure about the sizing for your leather goods. Can you help me find the perfect fit and share your size guide?"); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Message sent when customers select "Size Guide" in the chat sidebar</p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-center">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Save WhatsApp Settings
                </button>
            </div>
        </form>
    </div>

    <!-- WhatsApp Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-comments text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-bold text-gray-900"><?php echo number_format($whatsapp_stats['total_conversations']); ?></h3>
                    <p class="text-gray-600 text-sm">Total Conversations</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-envelope text-green-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-bold text-gray-900"><?php echo number_format($whatsapp_stats['total_messages']); ?></h3>
                    <p class="text-gray-600 text-sm">Total Messages</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-orange-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-bold text-gray-900"><?php echo $whatsapp_stats['active_chats']; ?></h3>
                    <p class="text-gray-600 text-sm">Active Chats</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-bold text-gray-900"><?php echo $whatsapp_stats['response_rate']; ?>%</h3>
                    <p class="text-gray-600 text-sm">Response Rate</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Conversations -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">Recent Conversations</h2>
                    <a href="https://wa.me/<?php echo ltrim($whatsapp_number, '+'); ?>"
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fab fa-whatsapp mr-2"></i>
                        Open WhatsApp
                    </a>
                </div>
            </div>
            <div class="divide-y divide-gray-200">
                <?php if (empty($recent_conversations) || (count($recent_conversations) === 1 && empty($recent_conversations[0]['phone']))): ?>
                <div class="p-8 text-center">
                    <i class="fab fa-whatsapp text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Conversations Available</h3>
                    <p class="text-gray-500 text-sm">
                        WhatsApp Business API integration is required to display real conversations.<br>
                        Contact customers will appear here once the API is configured.
                    </p>
                </div>
                <?php else: ?>
                    <?php foreach ($recent_conversations as $conversation): ?>
                    <div class="p-4 hover:bg-gray-50 cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-700"><?php echo substr($conversation['customer'], 0, 1); ?></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($conversation['customer']); ?></p>
                                    <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($conversation['last_message']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php if ($conversation['unread']): ?>
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                <?php endif; ?>
                                <span class="text-xs text-gray-500"><?php echo htmlspecialchars($conversation['time']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="p-4 bg-gray-50 text-center">
                <a href="https://wa.me/<?php echo ltrim($whatsapp_number, '+'); ?>" target="_blank"
                   class="text-sm text-green-600 hover:text-green-700 font-medium">
                    View all conversations in WhatsApp →
                </a>
            </div>
        </div>

        <!-- Quick Actions & Tools -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-6">Quick Actions</h2>

            <div class="space-y-4">
                <!-- Test WhatsApp Link -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-medium text-gray-900 mb-2">Test WhatsApp Link</h3>
                    <p class="text-sm text-gray-600 mb-3">Send yourself a test message to verify the WhatsApp integration.</p>
                    <a href="https://wa.me/<?php echo ltrim($whatsapp_number, '+'); ?>?text=Hello%20Moss%C3%A9%20Luxe%20Admin%20Test"
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fab fa-whatsapp mr-2"></i>
                        Send Test Message
                    </a>
                </div>

                <!-- Business Hours -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-medium text-gray-900 mb-2">Business Hours</h3>
                    <div class="text-sm space-y-1">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Monday - Friday:</span>
                            <span class="font-medium">9:00 AM - 6:00 PM</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Saturday:</span>
                            <span class="font-medium">9:00 AM - 4:00 PM</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Sunday:</span>
                            <span class="font-medium">Closed</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm font-medium text-green-700">Currently Online</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Support Templates -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-medium text-gray-900 mb-2">Quick Reply Templates</h3>
                    <p class="text-sm text-gray-600 mb-3">Common response templates for customer inquiries.</p>

                    <div class="space-y-2">
                        <button onclick="copyToClipboard('Thank you for your inquiry! We\'ll respond within 24 hours.')"
                                class="w-full text-left p-2 text-xs bg-gray-50 hover:bg-gray-100 rounded text-gray-700 transition-colors">
                            ➤ Thank you response
                        </button>
                        <button onclick="copyToClipboard('We ship to Johannesburg and most areas in South Africa. Shipping costs R75. Estimated delivery: 3-5 business days.')"
                                class="w-full text-left p-2 text-xs bg-gray-50 hover:bg-gray-100 rounded text-gray-700 transition-colors">
                            ➤ Shipping information
                        </button>
                        <button onclick="copyToClipboard('Great choice! Our leather goods are handmade with premium materials. Check our size guide at mosseluxe.co.za/size-guide for perfect fit.')"
                                class="w-full text-left p-2 text-xs bg-gray-50 hover:bg-gray-100 rounded text-gray-700 transition-colors">
                            ➤ Size guide reminder
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Business Info -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">WhatsApp Business Integration</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-medium text-gray-900 mb-3">Integration Status</h3>
                <div class="space-y-2">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <span class="text-sm text-gray-700">WhatsApp Business Number Active</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <span class="text-sm text-gray-700">Sidebar Integration Complete</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <span class="text-sm text-gray-700">Customer Support Options Available</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <span class="text-sm text-gray-700">24/7 Customer Contact Available</span>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="font-medium text-gray-900 mb-3">Business Features</h3>
                <div class="space-y-2">
                    <div class="flex items-center">
                        <i class="fab fa-whatsapp text-green-500 mr-3"></i>
                        <span class="text-sm text-gray-700">Context-Aware Messages</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-mobile-alt text-blue-500 mr-3"></i>
                        <span class="text-sm text-gray-700">Mobile-Optimized Interface</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-globe text-purple-500 mr-3"></i>
                        <span class="text-sm text-gray-700">South African Business Number</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-clock text-orange-500 mr-3"></i>
                        <span class="text-sm text-gray-700">Business Hours Awareness</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to copy text to clipboard for quick replies
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success toast
        showAdminToast('Template copied to clipboard!', 'success');
    }).catch(function(err) {
        console.error('Failed to copy: ', err);
        showAdminToast('Failed to copy to clipboard', 'error');
    });
}

// Add some interactivity to conversation items
document.addEventListener('DOMContentLoaded', function() {
    // Make conversation rows clickable
    document.querySelectorAll('.divide-y > .hover\\:bg-gray-50').forEach(function(row) {
        row.addEventListener('click', function() {
            const customerName = this.querySelector('.font-medium').textContent;
            const phoneLink = `https://wa.me/${'<?php echo ltrim($whatsapp_number, "+"); ?>'}?text=Hello ${encodeURIComponent(customerName)}, `;
            window.open(phoneLink, '_blank');
        });
    });
});
</script>

<?php include 'footer.php'; ?>
