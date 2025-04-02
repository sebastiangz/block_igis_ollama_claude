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
 * JavaScript for the Ollama Claude AI Chat Block
 *
 * @module     block_igis_ollama_claude/chat
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';
import * as Markdown from 'core/markdown';
import Log from 'core/log';

/**
 * Initialize the chat interface
 *
 * @param {number} instanceId Block instance ID
 * @param {string} uniqueId Unique ID for DOM elements
 * @param {number} contextId Context ID
 * @param {string} sourceOfTruth Source of truth
 * @param {string} customPrompt Custom prompt
 */
export const init = (instanceId, uniqueId, contextId, sourceOfTruth, customPrompt) => {
    // Make sure the DOM has loaded before initialization
    $(document).ready(function() {
        initChat(instanceId, uniqueId, contextId, sourceOfTruth, customPrompt);
    });
};

/**
 * Initialize the chat interface after DOM is ready
 *
 * @param {number} instanceId Block instance ID
 * @param {string} uniqueId Unique ID for DOM elements
 * @param {number} contextId Context ID
 * @param {string} sourceOfTruth Source of truth
 * @param {string} customPrompt Custom prompt
 */
const initChat = (instanceId, uniqueId, contextId, sourceOfTruth, customPrompt) => {
    try {
        console.log('Initializing chat with ID: ' + uniqueId);
        
        // DOM Elements
        const container = document.getElementById(`ollama-claude-chat-${uniqueId}`);
        if (!container) {
            console.error(`Container element not found: ollama-claude-chat-${uniqueId}`);
            return;
        }
        
        const messagesContainer = document.getElementById(`ollama-claude-messages-${uniqueId}`);
        const inputField = document.getElementById(`ollama-claude-input-${uniqueId}`);
        const sendButton = document.getElementById(`ollama-claude-send-${uniqueId}`);
        const clearButton = document.getElementById(`ollama-claude-clear-${uniqueId}`);
        const apiSelector = document.getElementById(`ollama-claude-api-select-${uniqueId}`);
        const statusIndicator = document.getElementById(`ollama-claude-status-${uniqueId}`);
        
        // Verify all required elements exist
        if (!messagesContainer || !inputField || !sendButton || !clearButton) {
            console.error('One or more required chat elements not found');
            return;
        }
        
        // Helper variables
        const assistantNameElement = document.getElementById(`ollama-claude-assistant-name-${uniqueId}`);
        const userNameElement = document.getElementById(`ollama-claude-user-name-${uniqueId}`);
        const showLabelsElement = document.getElementById(`ollama-claude-showlabels-${uniqueId}`);
        const defaultApiElement = document.getElementById(`ollama-claude-defaultapi-${uniqueId}`);
        
        if (!assistantNameElement || !userNameElement || !showLabelsElement) {
            console.error('One or more helper elements not found');
            return;
        }
        
        const assistantName = assistantNameElement.value;
        const userName = userNameElement.value;
        const showLabels = showLabelsElement.value === '1';
        const defaultApi = defaultApiElement ? defaultApiElement.value : 'ollama';
        
        // Conversation history
        let conversation = [];
        // Request timeout reference
        let requestTimeout;
        // Animation frame for typing animation
        let typingAnimation;
        // Dots for typing animation
        let dots = 0;
        
        // Load conversation from localStorage if available
        const loadConversation = () => {
            try {
                const storedConversation = localStorage.getItem(`ollama-claude-conversation-${instanceId}`);
                if (storedConversation) {
                    conversation = JSON.parse(storedConversation);
                    // Display loaded conversation
                    conversation.forEach(entry => {
                        addMessageToUI(entry.message, 'user');
                        addMessageToUI(entry.response, 'assistant');
                    });
                    scrollToBottom();
                }
            } catch (e) {
                console.error('Failed to load conversation:', e);
                conversation = [];
            }
        };
        
        // Save conversation to localStorage
        const saveConversation = () => {
            try {
                localStorage.setItem(`ollama-claude-conversation-${instanceId}`, JSON.stringify(conversation));
            } catch (e) {
                console.error('Failed to save conversation:', e);
            }
        };
        
        // Clear conversation
        const clearConversation = () => {
            // Clear UI
            const welcomeMessage = messagesContainer.querySelector('.ollama-claude-welcome');
            if (welcomeMessage) {
                messagesContainer.innerHTML = '';
                messagesContainer.appendChild(welcomeMessage);
            } else {
                messagesContainer.innerHTML = '';
            }
            
            // Clear data
            conversation = [];
            saveConversation();
            
            // Call the web service to clear the conversation
            try {
                Ajax.call([{
                    methodname: 'block_igis_ollama_claude_clear_conversation',
                    args: {
                        instanceid: instanceId
                    }
                }])[0].done(() => {
                    // Optionally handle success
                    updateStatus('ready');
                }).fail(error => {
                    console.error('Failed to clear conversation:', error);
                    updateStatus('error', 'No se pudo borrar la conversación');
                });
            } catch (error) {
                console.error('Error calling clear conversation service:', error);
            }
        };
        
        // Add message to UI
        const addMessageToUI = (message, role, isError = false) => {
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
                try {
                    Markdown.render(message).then((html) => {
                        contentDiv.innerHTML = html;
                        
                        // Set target="_blank" for all links
                        contentDiv.querySelectorAll('a').forEach(link => {
                            link.setAttribute('target', '_blank');
                            link.setAttribute('rel', 'noopener noreferrer');
                        });
                    }).catch((e) => {
                        // Fallback if markdown rendering fails
                        console.error('Markdown rendering failed:', e);
                        contentDiv.textContent = message;
                    });
                } catch (e) {
                    console.error('Error in markdown processing:', e);
                    contentDiv.textContent = message;
                }
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
        const addTypingIndicator = () => {
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
        const removeTypingIndicator = () => {
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
        const startTypingAnimation = () => {
            const animateDots = () => {
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
        const stopTypingAnimation = () => {
            if (typingAnimation) {
                cancelAnimationFrame(typingAnimation);
                typingAnimation = null;
            }
        };
        
        // Scroll to bottom of messages container
        const scrollToBottom = () => {
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        };
        
        // Update status indicator
        const updateStatus = (status, message = '') => {
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
                    requestTimeout = setTimeout(() => {
                        statusIndicator.style.display = 'none';
                    }, 5000);
                    break;
                default:
                    statusIndicator.style.display = 'none';
            }
        };
        
        // Set loading state
        const setLoading = (isLoading) => {
            const normalState = sendButton.querySelector('.normal-state');
            const loadingState = sendButton.querySelector('.loading-state');
            
            if (!normalState || !loadingState) {
                console.warn('Loading state elements not found');
                return;
            }
            
            if (isLoading) {
                normalState.classList.add('d-none');
                loadingState.classList.remove('d-none');
                sendButton.disabled = true;
                inputField.disabled = true;
                if (apiSelector) {
                    apiSelector.disabled = true;
                }
                
                // Update status and add typing indicator
                updateStatus('sending');
                addTypingIndicator();
                
                // Set a timeout for long requests
                requestTimeout = setTimeout(() => {
                    updateStatus('receiving', 'La respuesta está tomando más tiempo de lo esperado...');
                }, 5000);
                
            } else {
                normalState.classList.remove('d-none');
                loadingState.classList.add('d-none');
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
        const getSelectedApi = () => {
            if (apiSelector && apiSelector.value) {
                return apiSelector.value;
            }
            return defaultApi;
        };
        
        // Handle sending a message
        const sendMessage = () => {
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
            
            // Make API call
            try {
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
                }])[0].done(response => {
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
                }).fail(error => {
                    // Remove typing indicator
                    removeTypingIndicator();
                    
                    // Handle error
                    console.error('API call failed:', error);
                    getString('erroroccurred', 'block_igis_ollama_claude')
                        .then(errorMsg => {
                            addMessageToUI(errorMsg, 'assistant', true);
                            updateStatus('error', 'Error al procesar la solicitud');
                            setLoading(false);
                        })
                        .catch(e => {
                            console.error('Error getting string:', e);
                            addMessageToUI('Ha ocurrido un error al procesar tu solicitud. Por favor, inténtalo de nuevo.', 'assistant', true);
                            updateStatus('error', 'Error al procesar la solicitud');
                            setLoading(false);
                        });
                });
            } catch (error) {
                console.error('Error calling chat response service:', error);
                addMessageToUI('Ha ocurrido un error al procesar tu solicitud. Por favor, inténtalo de nuevo.', 'assistant', true);
                updateStatus('error', 'Error al llamar al servicio');
                setLoading(false);
            }
        };
        
        // Event listeners
        if (sendButton) {
            sendButton.addEventListener('click', sendMessage);
        }
        
        if (inputField) {
            inputField.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
        
        if (clearButton) {
            clearButton.addEventListener('click', clearConversation);
        }
        
        // API selector change event
        if (apiSelector) {
            apiSelector.addEventListener('change', () => {
                // Optionally show a message about changing API
                const selectedApi = apiSelector.value;
                const apiName = selectedApi === 'ollama' ? 'Ollama (local)' : 'Claude (nube)';
                updateStatus('ready', `Cambiado a ${apiName}`);
            });
        }
        
        // Load existing conversation if available
        loadConversation();
        
        // Initialize status
        updateStatus('ready');
        
        // Focus input field
        if (inputField) {
            inputField.focus();
        }
        
        console.log('Chat initialized successfully');
    } catch (error) {
        console.error('Error initializing chat:', error);
        Notification.exception(error);
    }
};