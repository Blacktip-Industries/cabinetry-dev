/**
 * Access Component - Chat JavaScript
 * Handles real-time chat functionality with AJAX polling
 */

(function() {
    'use strict';

    /**
     * Chat Polling Manager
     */
    class ChatPolling {
        constructor(options) {
            this.chatSessionId = options.chatSessionId;
            this.apiBase = options.apiBase || '/admin/components/access/api/chat';
            this.pollInterval = options.pollInterval || 3000;
            this.lastMessageId = options.lastMessageId || 0;
            this.onNewMessage = options.onNewMessage || null;
            this.isPolling = false;
            this.pollTimer = null;
        }

        start() {
            if (this.isPolling) return;
            this.isPolling = true;
            this.poll();
        }

        stop() {
            this.isPolling = false;
            if (this.pollTimer) {
                clearTimeout(this.pollTimer);
                this.pollTimer = null;
            }
        }

        poll() {
            if (!this.isPolling) return;

            fetch(`${this.apiBase}/poll.php?chat_session_id=${this.chatSessionId}&since_id=${this.lastMessageId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.messages && data.messages.length > 0) {
                        if (this.onNewMessage) {
                            data.messages.forEach(msg => {
                                this.onNewMessage(msg);
                                this.lastMessageId = Math.max(this.lastMessageId, msg.id);
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Chat polling error:', error);
                })
                .finally(() => {
                    if (this.isPolling) {
                        this.pollTimer = setTimeout(() => this.poll(), this.pollInterval);
                    }
                });
        }

        sendMessage(message) {
            return fetch(`${this.apiBase}/send.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    chat_session_id: this.chatSessionId,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.message_id) {
                    this.lastMessageId = Math.max(this.lastMessageId, data.message_id);
                    return data;
                } else {
                    throw new Error(data.error || 'Failed to send message');
                }
            });
        }
    }

    /**
     * Chat UI Manager
     */
    class ChatUI {
        constructor(containerId, options) {
            this.container = document.getElementById(containerId);
            if (!this.container) {
                console.error('Chat container not found:', containerId);
                return;
            }

            this.polling = new ChatPolling({
                chatSessionId: options.chatSessionId,
                apiBase: options.apiBase,
                pollInterval: options.pollInterval,
                lastMessageId: options.lastMessageId || 0,
                onNewMessage: (msg) => this.addMessage(msg)
            });

            this.setupForm();
            this.polling.start();
        }

        setupForm() {
            const form = this.container.querySelector('#chatForm');
            if (!form) return;

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const input = form.querySelector('textarea[name="message"]');
                const message = input.value.trim();
                
                if (!message) return;

                this.polling.sendMessage(message)
                    .then(() => {
                        input.value = '';
                        // Immediately poll for new messages
                        this.polling.poll();
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                    });
            });
        }

        addMessage(message) {
            const messagesContainer = this.container.querySelector('.chat-messages');
            if (!messagesContainer) return;

            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${message.sender_type === 'admin' ? 'message-admin' : 'message-user'}`;
            
            const senderName = (message.sender_first_name || '') + ' ' + (message.sender_last_name || '') || message.sender_email || 'Unknown';
            const messageTime = new Date(message.created_at).toLocaleString();
            
            messageDiv.innerHTML = `
                <div class="message-header">
                    <strong>${this.escapeHtml(senderName)}</strong>
                    <span class="message-time">${messageTime}</span>
                </div>
                <div class="message-content">${this.escapeHtml(message.message).replace(/\n/g, '<br>')}</div>
            `;

            messagesContainer.appendChild(messageDiv);
            this.scrollToBottom();
        }

        scrollToBottom() {
            const messagesContainer = this.container.querySelector('.chat-messages');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        destroy() {
            this.polling.stop();
        }
    }

    // Export to global scope
    window.AccessChat = {
        ChatPolling: ChatPolling,
        ChatUI: ChatUI
    };

})();

