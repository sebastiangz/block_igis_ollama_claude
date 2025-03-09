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
    // DOM Elements
    const container = document.getElementById(`ollama-claude-chat-${uniqueId}`);
    const messagesContainer = document.getElementById(`ollama-claude-messages-${uniqueId}`);
    const inputField = document.getElementById(`ollama-claude-input-${uniqueId}`);
    const sendButton = document.getElementById(`ollama-claude-send-${uniqueId}`);
    const clearButton = document.getElementById(`ollama-claude-clear-${uniqueId}`);
    
    // Helper variables
    const assistantName = document.getElementById(`ollama-claude-assistant-name-${uniqueId}`).value;
    const userName = document.getElementById(`ollama-claude-user-name-${uniqueId}`).value;
    const showLabels = document.getElementById(`ollama-claude-showlabels-${uniqueId}`).value === '1';
    
    // Conversation history
    let conversation = [];
    
    // Load conversation from localStorage if available
    const loadConversation = () => {
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
    const saveConversation = () => {
        localStorage.setItem(`ollama-claude-conversation-${instanceId}`, JSON.stringify(conversation));
    };
    
    // Clear conversation
    const clearConversation = () => {
        // Clear UI
        const welcomeMessage = messagesContainer.querySelector('.ollama-claude-welcome');
        messagesContainer.innerHTML = '';
        messagesContainer.appendChild(welcomeMessage);
        
        // Clear data
        conversation = [];
        saveConversation();
        
        // Call the web service to clear the conversation
        Ajax.call([{
            methodname: 'block_igis_ollama_claude_clear_conversation',
            args: {
                instanceid: instanceId
            }
        }])[0].done(() => {
            // Optionally handle success
        }).fail(error => {
            console.error('Failed to clear conversation:', error);
        });
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
            Markdown.render(message).then((html) => {
                contentDiv.innerHTML = html;
                
                // Set target="_blank" for all links
                contentDiv.querySelectorAll('a').forEach(link => {
                    link.setAttribute('target', '_blank');
                    link.setAttribute('rel', 'noopener noreferrer');
                });
            }).catch(() => {
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
    
    // Scroll to bottom of messages container
    const scrollToBottom = () => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    };
    
    // Set loading state
    const setLoading = (isLoading) => {
        const normalState = sendButton.querySelector('.normal-state');
        const loadingState = sendButton.querySelector('.loading-state');
        
        if (isLoading) {
            normalState.classList.add('d-none');
            loadingState.classList.remove('d-none');
            sendButton.disabled = true;
            inputField.disabled = true;
        } else {
            normalState.classList.remove('d-none');
            loadingState.classList.add('d-none');
            sendButton.disabled = false;
            inputField.disabled = false;
            inputField.focus();
        }
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
        
        // Make API call
        Ajax.call([{
            methodname: 'block_igis_ollama_claude_get_chat_response',
            args: {
                message: message,
                conversation: JSON.stringify(conversation),
                instanceid: instanceId,
                contextid: contextId,
                sourceoftruth: sourceOfTruth,
                prompt: customPrompt
            }
        }])[0].done(response => {
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
            // Handle error
            getString('erroroccurred', 'block_igis_ollama_claude')
                .then(errorMsg => {
                    addMessageToUI(errorMsg, 'assistant', true);
                    console.error('API call failed:', error);
                    setLoading(false);
                })
                .catch(() => {
                    addMessageToUI('An error occurred while processing your request. Please try again.', 'assistant', true);
                    console.error('API call failed:', error);
                    setLoading(false);
                });
        });
    };
    
    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    
    inputField.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    clearButton.addEventListener('click', clearConversation);
    
    // Load existing conversation if available
    loadConversation();
    
    // Focus input field
    inputField.focus();
};
