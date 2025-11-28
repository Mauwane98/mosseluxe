<?php if (!defined('SITE_URL')) return; ?>

<!-- Chat Widget -->
<div id="chat-widget" class="fixed bottom-6 right-6 z-[100]">
    <!-- Chat Button -->
    <button id="chat-toggle-btn" class="bg-green-600 hover:bg-green-700 text-white rounded-full w-16 h-16 flex items-center justify-center shadow-lg transition-all duration-300 hover:scale-110">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 2.98.97 4.29L2.5 21l6.71-1.47c1.31.61 2.75.97 4.29.97 5.52 0 10-4.48 10-10S17.52 2 12 2zM13 17h-2v-6h2v6zm0-8h-2V7h2v2z"/>
        </svg>
        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden" id="chat-unread-count">0</span>
    </button>

    <!-- Chat Panel -->
    <div id="chat-panel" class="absolute bottom-20 right-0 w-80 md:w-96 bg-white rounded-lg shadow-2xl hidden border border-gray-200 flex flex-col max-h-[500px] animate-in slide-in-from-bottom-2 duration-300">
        <!-- Chat Header -->
        <div class="bg-green-600 text-white p-4 rounded-t-lg flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 2.98.97 4.29L2.5 21l6.71-1.47c1.31.61 2.75.97 4.29.97 5.52 0 10-4.48 10-10S17.52 2 12 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-sm">Mossé Luxe Support</h3>
                    <p class="text-xs text-green-100">Typically replies instantly</p>
                </div>
            </div>
            <button id="chat-close-btn" class="text-white hover:text-green-200 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Messages Container -->
        <div id="chat-messages" class="flex-1 p-4 overflow-y-auto max-h-80 min-h-[200px] bg-gray-50 space-y-3">
            <!-- Welcome Message -->
            <div class="flex">
                <div class="bg-white rounded-lg p-3 shadow-sm max-w-[80%]">
                    <p class="text-sm">Hi! Welcome to Mossé Luxe! How can we help you today?</p>
                    <span class="text-xs text-gray-500 mt-1 block"><?php echo date('H:i'); ?></span>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div id="typing-indicator" class="hidden p-3">
            <div class="flex items-center gap-2">
                <div class="flex gap-1">
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
                <span class="text-xs text-gray-500">Support is typing...</span>
            </div>
        </div>

        <!-- Message Input -->
        <div class="p-4 border-t border-gray-200">
            <form id="chat-form" class="flex gap-2">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="text"
                       id="chat-message"
                       name="message"
                       placeholder="Type your message..."
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                       maxlength="500">
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors disabled:opacity-50"
                        id="chat-send-btn">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatToggleBtn = document.getElementById('chat-toggle-btn');
    const chatPanel = document.getElementById('chat-panel');
    const chatCloseBtn = document.getElementById('chat-close-btn');
    const chatForm = document.getElementById('chat-form');
    const chatMessageInput = document.getElementById('chat-message');
    const chatMessages = document.getElementById('chat-messages');

    let isChatOpen = false;

    // Toggle chat
    function toggleChat() {
        isChatOpen = !isChatOpen;
        if (isChatOpen) {
            chatPanel.classList.remove('hidden');
            chatMessageInput.focus();
        } else {
            chatPanel.classList.add('hidden');
        }
    }

    chatToggleBtn.addEventListener('click', toggleChat);
    chatCloseBtn.addEventListener('click', toggleChat);

    // Handle form submission
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = chatMessageInput.value.trim();
        if (message) {
            sendChatMessage(message);
            chatMessageInput.value = '';
        }
    });

    async function sendChatMessage(message) {
        // Add user message to chat
        addMessageToChat(message, 'user');

        // Show typing indicator
        const typingIndicator = document.getElementById('typing-indicator');
        typingIndicator.classList.remove('hidden');

        try {
            const response = await fetch('api/inquiry.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'chat',
                    message: message
                })
            });

            const data = await response.json();

            // Hide typing indicator
            typingIndicator.classList.add('hidden');

            if (data.success) {
                // Simulate reply after a short delay
                setTimeout(() => {
                    addMessageToChat("Thank you for your message! Our team will get back to you shortly.", 'support');
                }, 1000);
            } else {
                addMessageToChat("Sorry, we couldn't send your message. Please try again.", 'support');
            }
        } catch (error) {
            console.error('Chat error:', error);
            typingIndicator.classList.add('hidden');
            addMessageToChat("Network error. Please try again later.", 'support');
        }
    }

    function addMessageToChat(message, sender) {
        const messageDiv = document.createElement('div');
        const isUser = sender === 'user';

        messageDiv.className = `flex ${isUser ? 'justify-end' : 'justify-start'}`;
        messageDiv.innerHTML = `
            <div class="${isUser ? 'bg-green-600 text-white' : 'bg-white'} rounded-lg p-3 shadow-sm max-w-[80%]">
                <p class="text-sm">${message}</p>
                <span class="text-xs ${isUser ? 'text-green-100' : 'text-gray-500'} mt-1 block">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
            </div>
        `;

        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Close chat when clicking outside
    document.addEventListener('click', function(e) {
        if (isChatOpen && !chatPanel.contains(e.target) && !chatToggleBtn.contains(e.target)) {
            toggleChat();
        }
    });

    // Handle Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isChatOpen) {
            toggleChat();
        }
    });
});
</script>

<style>
#chat-panel {
    animation: chatSlideIn 0.3s ease-out;
}

@keyframes chatSlideIn {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

#chat-messages::-webkit-scrollbar {
    width: 4px;
}

#chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

#chat-messages::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

#chat-messages::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
