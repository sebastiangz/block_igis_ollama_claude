// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JavaScript for the Multi-provider AI Chat Block
 *
 * @module     block_igis_ollama_claude/chat
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/markdown'], 
function($, Ajax, Notification, Str, Markdown) {
    
    /**
     * Initialize the chat interface
     *
     * @param {number} instanceId Block instance ID
     * @param {string} uniqueId Unique ID for DOM elements
     * @param {number} contextId Context ID
     * @param {string} sourceOfTruth Source of truth
     * @param {string} customPrompt Custom prompt
     */
    const init = function(instanceId, uniqueId, contextId, sourceOfTruth, customPrompt) {
        console.log('Chat initialization started with instance ID:', instanceId);
        
        // DOM Elements
        const container = document.getElementById(`ollama-claude-chat-${uniqueId}`);
        const messagesContainer = document.getElementById(`ollama-claude-messages-${uniqueId}`);
        const inputField = document.getElementById(`ollama-claude-input-${uniqueId}`);
        const sendButton = document.getElementById(`ollama-claude-send-${uniqueId}`);
        const clearButton = document.getElementById(`ollama-claude-clear-${uniqueId}`);
        const apiSelector = document.getElementById(`ollama-claude-api-select-${uniqueId}`);
        const statusIndicator = document.getElementById(`ollama-claude-status-${uniqueId}`);
        
        // Helper variables
        const assistantName = document.getElementById(`ollama-claude-assistant-name-${uniqueId}`).value;
        const userName = document.getElementById(`ollama-claude-user-name-${uniqueId}`).value;
        const showLabels = document.getElementById(`ollama-claude-showlabels-${uniqueId}`).value === '1';
        const defaultApi = document.getElementById(`ollama-claude-defaultapi-${uniqueId}`).value;
        
        // Conversation history
        let conversation = [];
        // Request timeout reference
        let requestTimeout;
        // Animation frame for typing animation
        let typingAnimation;
        // Dots for typing animation
        let dots = 0;
        
        // Load conversation from localStorage if available
        const loadConversation = function() {
            const storedConversation = localStorage.getItem(`ollama-claude-conversation-${instanceId}`);
            if (storedConversation) {
                try {
                    conversation = JSON.parse(storedConversation);
                    // Display loaded conversation
                    conversation.forEach(entry => {
                        addMessageToUI(entry.message, 'user');
                        addMessageToUI(entry.response, 'assistant');
                    });
                    scrollToBottom();
                } catch (e) {
                    console.error('Failed to load conversation:', e);
                    conversation = [];
                }
            }
        };
        
        // Save conversation to localStorage
        const saveConversation = function() {
            localStorage.setItem(`ollama-claude-conversation-${instanceId}`, JSON.stringify(conversation));
        };
        
        // Clear conversation
        const clearConversation = function() {
            console.log('Clearing conversation');
            // Clear UI
            const welcomeMessage = messagesContainer.querySelector('.ollama-claude-welcome');
            messagesContainer.innerHTML = '';
            if (welcomeMessage) {
                messagesContainer.appendChild(welcomeMessage);
            } else {
                // Add welcome message if it doesn't exist
                const welcomeDiv = document.createElement('div');
                welcomeDiv.className = 'ollama-claude-welcome';
                const assistantMsg = document.createElement('div');
                assistantMsg.className = 'ollama-claude-message assistant';
                if (showLabels) {
                    const labelDiv = document.createElement('div');
                    labelDiv.className = 'ollama-claude-message-label';
                    labelDiv.textContent = assistantName;
                    assistantMsg.appendChild(labelDiv);
                }
                const contentDiv = document.createElement('div');
                contentDiv.className = 'ollama-claude-message-content';
                contentDiv.textContent = `Hola, soy ${assistantName}. ¿En qué puedo ayudarte hoy?`;
                assistantMsg.appendChild(contentDiv);
                welcomeDiv.appendChild(assistantMsg);
                messagesContainer.appendChild(welcomeDiv);
            }
            
            // Clear data
            conversation = [];
            saveConversation();
            
            // Call the web service to clear the conversation
            Ajax.call([{
                methodname: 'block_igis_ollama_claude_clear_conversation',
                args: {
                    instanceid: instanceId
                }
            }])[0].done(function() {
                // Optionally handle success
                updateStatus('ready');
            }).fail(function(error) {
                console.error('Failed to clear conversation:', error);
                updateStatus('error', 'No se pudo borrar la conversación');
            });
        };
        
        // Add message to UI
        const addMessageToUI = function(message, role, isError = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `ollama-claude-message ${role}${isError ? ' error' : ''}`;
            
            if (showLabels) {
                const labelDiv = document.createElement('div');
                labelDiv.className = 'ollama-claude-message-label';
                labelDiv.textContent = role === 'user' ? userName : assistantName;
                messageDiv.appendChild(labelDiv);
            }
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'ollama-claude-message-content';
            
            // Process markdown if it's an assistant message and not an error
            if (role === 'assistant' && !isError) {
                // Process markdown
                Markdown.render(message).then(function(html) {
                    contentDiv.innerHTML = html;
                    
                    // Set target="_blank" for all links
                    contentDiv.querySelectorAll('a').forEach(link => {
                        link.setAttribute('target', '_blank');
                        link.setAttribute('rel', 'noopener noreferrer');
                    });
                }).catch(function() {
                    // Fallback if markdown rendering fails
                    contentDiv.textContent = message;
                });
            } else {
                contentDiv.textContent = message;
            }
            
            messageDiv.appendChild(contentDiv);
            messagesContainer.appendChild(messageDiv);
            
            // Clear the floats
            const clearDiv = document.createElement('div');
            clearDiv.style.clear = 'both';
            messagesContainer.appendChild(clearDiv);
            
            scrollToBottom();
        };
        
        // Add typing indicator to UI
        const addTypingIndicator = function() {
            // Check if typing indicator already exists
            const existingIndicator = messagesContainer.querySelector('.ollama-claude-typing-indicator');
            if (existingIndicator) {
                return;
            }
            
            const typingDiv = document.createElement('div');
            typingDiv.className = 'ollama-claude-message assistant ollama-claude-typing-indicator';
            
            if (showLabels) {
                const labelDiv = document.createElement('div');
                labelDiv.className = 'ollama-claude-message-label';
                labelDiv.textContent = assistantName;
                typingDiv.appendChild(labelDiv);
            }
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'ollama-claude-message-content';
            contentDiv.innerHTML = '<span class="typing-dots">Pensando...</span>';
            
            typingDiv.appendChild(contentDiv);
            messagesContainer.appendChild(typingDiv);
            
            // Clear the floats
            const clearDiv = document.createElement('div');
            clearDiv.style.clear = 'both';
            messagesContainer.appendChild(clearDiv);
            
            scrollToBottom();
            
            // Start the typing animation
            startTypingAnimation();
        };
        
        // Remove typing indicator from UI
        const removeTypingIndicator = function() {
            const typingIndicator = messagesContainer.querySelector('.ollama-claude-typing-indicator');
            if (typingIndicator) {
                typingIndicator.remove();
                // Also remove the clear div after it
                const clearDiv = typingIndicator.nextElementSibling;
                if (clearDiv && clearDiv.style.clear === 'both') {
                    clearDiv.remove();
                }
            }
            
            // Stop the typing animation
            stopTypingAnimation();
        };
        
        // Start typing animation
        const startTypingAnimation = function() {
            const animateDots = function() {
                const typingDotsEl = messagesContainer.querySelector('.typing-dots');
                if (typingDotsEl) {
                    dots = (dots + 1) % 4;
                    let dotsText = 'Pensando';
                    for (let i = 0; i < dots; i++) {
                        dotsText += '.';
                    }
                    typingDotsEl.textContent = dotsText;
                }
                typingAnimation = requestAnimationFrame(animateDots);
            };
            
            typingAnimation = requestAnimationFrame(animateDots);
        };
        
        // Stop typing animation
        const stopTypingAnimation = function() {
            if (typingAnimation) {
                cancelAnimationFrame(typingAnimation);
                typingAnimation = null;
            }
        };
        
        // Scroll to bottom of messages container
        const scrollToBottom = function() {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        };
        
        // Update status indicator
        const updateStatus = function(status, message = '') {
            if (!statusIndicator) return;
            
            // Clear any existing timeout
            if (requestTimeout) {
                clearTimeout(requestTimeout);
                requestTimeout = null;
            }
            
            statusIndicator.className = 'ollama-claude-status';
            statusIndicator.classList.add(`status-${status}`);
            
            switch (status) {
                case 'ready':
                    statusIndicator.textContent = 'Listo';
                    statusIndicator.style.display = 'none';
                    break;
                case 'sending':
                    statusIndicator.textContent = 'Enviando mensaje...';
                    statusIndicator.style.display = 'block';
                    break;
                case 'receiving':
                    statusIndicator.textContent = 'Procesando respuesta...';
                    statusIndicator.style.display = 'block';
                    break;
                case 'error':
                    statusIndicator.textContent = message || 'Error en la solicitud';
                    statusIndicator.style.display = 'block';
                    // Auto-hide after 5 seconds
                    requestTimeout = setTimeout(function() {
                        statusIndicator.style.display = 'none';
                    }, 5000);
                    break;
                default:
                    statusIndicator.style.display = 'none';
            }
        };
        
        // Set loading state
        const setLoading = function(isLoading) {
            const normalState = sendButton.querySelector('.normal-state');
            const loadingState = sendButton.querySelector('.loading-state');
            
            if (isLoading) {
                if (normalState) normalState.classList.add('d-none');
                if (loadingState) loadingState.classList.remove('d-none');
                sendButton.disabled = true;
                inputField.disabled = true;
                if (apiSelector) {
                    apiSelector.disabled = true;
                }
                
                // Update status and add typing indicator
                updateStatus('sending');
                addTypingIndicator();
                
                // Set a timeout for long requests
                requestTimeout = setTimeout(function() {
                    updateStatus('receiving', 'La respuesta está tomando más tiempo de lo esperado...');
                }, 5000);
                
            } else {
                if (normalState) normalState.classList.remove('d-none');
                if (loadingState) loadingState.classList.add('d-none');
                sendButton.disabled = false;
                inputField.disabled = false;
                if (apiSelector) {
                    apiSelector.disabled = false;
                }
                
                // Update status and remove typing indicator
                updateStatus('ready');
                removeTypingIndicator();
                
                // Clear any timeout
                if (requestTimeout) {
                    clearTimeout(requestTimeout);
                    requestTimeout = null;
                }
                
                inputField.focus();
            }
        };
        
        // Get currently selected API
        const getSelectedApi = function() {
            if (apiSelector) {
                return apiSelector.value;
            }
            return defaultApi;
        };
        
        // Handle sending a message
        const sendMessage = function() {
            console.log('Sending message...');
            const message = inputField.value.trim();
            
            if (!message) {
                return;
            }
            
            // Add user message to UI
            addMessageToUI(message, 'user');
            
            // Clear input field
            inputField.value = '';
            
            // Set loading state
            setLoading(true);
            
            // Prepare the selected API
            const selectedApi = getSelectedApi();
            console.log('Selected API:', selectedApi);
            
            // Make API call
            Ajax.call([{
                methodname: 'block_igis_ollama_claude_get_chat_response',
                args: {
                    message: message,
                    conversation: JSON.stringify(conversation),
                    instanceid: instanceId,
                    contextid: contextId,
                    sourceoftruth: sourceOfTruth,
                    prompt: customPrompt,
                    api: selectedApi
                }
            }])[0].done(function(response) {
                console.log('Received response:', response);
                
                // Remove typing indicator before adding response
                removeTypingIndicator();
                
                // Add response to UI
                addMessageToUI(response.response, 'assistant');
                
                // Add to conversation history
                conversation.push({
                    message: message,
                    response: response.response
                });
                
                // Save conversation
                saveConversation();
                
                // Reset loading state
                setLoading(false);
            }).fail(function(error) {
                console.error('API call failed:', error);
                
                // Remove typing indicator
                removeTypingIndicator();
                
                // Handle error
                Str.get_string('erroroccurred', 'block_igis_ollama_claude')
                    .then(function(errorMsg) {
                        addMessageToUI(errorMsg, 'assistant', true);
                        updateStatus('error', 'Error al procesar la solicitud');
                        setLoading(false);
                    })
                    .catch(function() {
                        addMessageToUI('Ha ocurrido un error al procesar tu solicitud. Por favor, inténtalo de nuevo.', 'assistant', true);
                        updateStatus('error', 'Error al procesar la solicitud');
                        setLoading(false);
                    });
            });
        };
        
        // Event listeners
        if (sendButton) {
            sendButton.addEventListener('click', function(e) {
                e.preventDefault();
                sendMessage();
            });
            console.log('Send button event listener added');
        } else {
            console.error('Send button not found with ID:', `ollama-claude-send-${uniqueId}`);
        }
        
        if (inputField) {
            inputField.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            console.log('Input field event listener added');
        } else {
            console.error('Input field not found with ID:', `ollama-claude-input-${uniqueId}`);
        }
        
        if (clearButton) {
            clearButton.addEventListener('click', function(e) {
                e.preventDefault();
                clearConversation();
            });
            console.log('Clear button event listener added');
        } else {
            console.error('Clear button not found with ID:', `ollama-claude-clear-${uniqueId}`);
        }
        
        // API selector change event
        if (apiSelector) {
            apiSelector.addEventListener('change', function() {
                // Optionally show a message about changing API
                const selectedApi = apiSelector.value;
                let apiName = '';
                
                switch(selectedApi) {
                    case 'ollama':
                        apiName = 'Ollama (local)';
                        break;
                    case 'claude':
                        apiName = 'Claude (nube)';
                        break;
                    case 'openai':
                        apiName = 'OpenAI (nube)';
                        break;
                    case 'gemini':
                        apiName = 'Gemini (nube)';
                        break;
                    default:
                        apiName = selectedApi;
                }
                
                updateStatus('ready', `Cambiado a ${apiName}`);
            });
            console.log('API selector event listener added');
        }
        
        // Load existing conversation if available
        loadConversation();
        
        // Initialize status
        updateStatus('ready');
        
        // Focus input field
        if (inputField) {
            inputField.focus();
        }
        
        console.log('Chat initialization completed');
    };
    
    return {
        init: init
    };
});