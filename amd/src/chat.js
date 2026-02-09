define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    
    var ChatHandler = function() {
        this.isTyping = false;
        this.conversationHistory = [];
    };

    ChatHandler.prototype.init = function(contextid, blockid, config) {
        this.contextid = contextid;
        this.blockid = blockid;
        this.config = config;
        
        this.bindEvents();
        this.loadConversationHistory();
    };

    ChatHandler.prototype.bindEvents = function() {
        var self = this;
        
        // Enviar mensaje con Enter
        $('#chat-input-' + this.blockid).on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                self.sendMessage();
            }
        });

        // Enviar mensaje con botón
        $('#send-button-' + this.blockid).on('click', function() {
            self.sendMessage();
        });

        // Limpiar conversación
        $('#clear-button-' + this.blockid).on('click', function() {
            self.clearConversation();
        });

        // Auto-resize del textarea si se cambia el input por textarea
        $('#chat-input-' + this.blockid).on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    };

    ChatHandler.prototype.sendMessage = function() {
        var self = this;
        var input = $('#chat-input-' + this.blockid);
        var message = input.val().trim();

        if (!message || this.isTyping) {
            return;
        }

        // Agregar mensaje del usuario
        this.addMessage(message, 'user');
        
        // Limpiar input
        input.val('');
        input.css('height', 'auto');

        // Mostrar indicador de escritura
        this.showTypingIndicator();

        // Enviar mensaje al servidor
        var request = Ajax.call([{
            methodname: 'block_multi_ia_chat_send_message',
            args: {
                blockid: this.blockid,
                message: message
            }
        }]);

        request[0].done(function(response) {
            self.hideTypingIndicator();
            
            if (response.success) {
                self.addMessage(response.response, 'assistant');
                self.conversationHistory.push(
                    {role: 'user', content: message},
                    {role: 'assistant', content: response.response}
                );
            } else {
                self.addMessage('Error: ' + (response.error || 'Error desconocido'), 'error');
            }
        }).fail(function(error) {
            self.hideTypingIndicator();
            self.addMessage('Error de conexión. Inténtelo más tarde.', 'error');
            Notification.exception(error);
        });
    };

    ChatHandler.prototype.addMessage = function(content, type) {
        var messagesContainer = $('#messages-' + this.blockid);
        var messageClass = type === 'user' ? 'user-message' : 
                          type === 'error' ? 'error-message' : 'assistant-message';
        
        var messageHtml = '<div class="message ' + messageClass + '">' +
                         '<div class="message-content">' + this.escapeHtml(content) + '</div>' +
                         '<div class="message-time">' + this.getCurrentTime() + '</div>' +
                         '</div>';

        // Remover mensaje de bienvenida si existe
        messagesContainer.find('.welcome-message').remove();
        
        messagesContainer.append(messageHtml);
        this.scrollToBottom();
    };

    ChatHandler.prototype.showTypingIndicator = function() {
        this.isTyping = true;
        $('#typing-' + this.blockid).show();
        $('#send-button-' + this.blockid).prop('disabled', true);
        this.scrollToBottom();
    };

    ChatHandler.prototype.hideTypingIndicator = function() {
        this.isTyping = false;
        $('#typing-' + this.blockid).hide();
        $('#send-button-' + this.blockid).prop('disabled', false);
    };

    ChatHandler.prototype.scrollToBottom = function() {
        var container = $('#messages-' + this.blockid);
        container.scrollTop(container[0].scrollHeight);
    };

    ChatHandler.prototype.clearConversation = function() {
        var self = this;
        
        if (!confirm('¿Está seguro de que desea limpiar la conversación?')) {
            return;
        }

        var request = Ajax.call([{
            methodname: 'block_multi_ia_chat_clear_conversation',
            args: {
                blockid: this.blockid
            }
        }]);

        request[0].done(function(response) {
            if (response.success) {
                $('#messages-' + self.blockid).empty();
                self.conversationHistory = [];
                
                // Agregar mensaje de bienvenida
                $('#messages-' + self.blockid).html(
                    '<div class="welcome-message">' +
                    '<div class="message assistant-message">' +
                    '<div class="message-content">¡Hola! ¿En qué puedo ayudarte hoy?</div>' +
                    '</div>' +
                    '</div>'
                );
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    };

    ChatHandler.prototype.loadConversationHistory = function() {
        var self = this;
        
        if (!this.config.persist_conversations) {
            return;
        }

        var request = Ajax.call([{
            methodname: 'block_multi_ia_chat_get_conversation',
            args: {
                blockid: this.blockid
            }
        }]);

        request[0].done(function(response) {
            if (response.conversation && response.conversation.length > 0) {
                $('#messages-' + self.blockid).find('.welcome-message').remove();
                
                response.conversation.forEach(function(msg) {
                    var type = msg.role === 'user' ? 'user' : 'assistant';
                    self.addMessage(msg.content, type);
                });
                
                self.conversationHistory = response.conversation;
            }
        }).fail(function(error) {
            console.log('Error loading conversation history:', error);
        });
    };

    ChatHandler.prototype.escapeHtml = function(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    ChatHandler.prototype.getCurrentTime = function() {
        var now = new Date();
        return now.getHours().toString().padStart(2, '0') + ':' + 
               now.getMinutes().toString().padStart(2, '0');
    };

    return {
        init: function(contextid, blockid, config) {
            var chatHandler = new ChatHandler();
            chatHandler.init(contextid, blockid, config);
        }
    };
});