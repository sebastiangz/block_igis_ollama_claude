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
 * @module     block_igis_ollama_claude/lib
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var questionString = 'Ask a question...';
var errorString = 'An error occurred! Please try again later.';

export const init = (data) => {
    const blockId = data.blockId;
    const uniqueId = data.uniqueId;
    const contextId = data.contextId;
    const sourceOfTruth = data.sourceOfTruth;
    const customPrompt = data.customPrompt;
    const defaultApi = data.defaultApi;
    
    console.log('Initializing Multi-provider AI Chat with block ID:', blockId);
    console.log('Using default API:', defaultApi);
    
    // Initialize local data storage if necessary
    let chatData = localStorage.getItem("igis_ollama_claude_data");
    if (chatData) {
        chatData = JSON.parse(chatData);
        if (!chatData[blockId]) {
            chatData[blockId] = {};
        }
    } else {
        chatData = {[blockId]: {}};
    }
    localStorage.setItem("igis_ollama_claude_data", JSON.stringify(chatData));

    // Prevent sidebar from closing when on-screen keyboard pops up (for mobile)
    window.addEventListener('resize', event => {
        event.stopImmediatePropagation();
    }, true);

    // Get DOM elements
    const inputField = document.querySelector(`.block_igis_ollama_claude[data-instance-id='${blockId}'] #igis_ollama_input`);
    const sendButton = document.querySelector(`.block_igis_ollama_claude[data-instance-id='${blockId}'] #go`);
    const refreshButton = document.querySelector(`.block_igis_ollama_claude[data-instance-id='${blockId}'] #refresh`);
    const apiSelector = document.querySelector(`.block_igis_ollama_claude[data-instance-id='${blockId}'] #api_selector`);
    
    // Handle key events for input field
    if (inputField) {
        inputField.addEventListener('keyup', e => {
            if (e.which === 13 && e.target.value !== "") {
                addToChatLog('user', e.target.value, blockId);
                createCompletion(e.target.value, blockId);
                e.target.value = '';
            }
        });
        console.log('Input field event listener added');
    } else {
        console.error('Input field not found');
    }
    
    // Handle send button click
    if (sendButton) {
        sendButton.addEventListener('click', e => {
            if (inputField && inputField.value !== "") {
                addToChatLog('user', inputField.value, blockId);
                createCompletion(inputField.value, blockId);
                inputField.value = '';
            }
        });
        console.log('Send button event listener added');
    } else {
        console.error('Send button not found');
    }
    
    // Handle refresh button click
    if (refreshButton) {
        refreshButton.addEventListener('click', e => {
            clearHistory(blockId);
        });
        console.log('Refresh button event listener added');
    } else {
        console.error('Refresh button not found');
    }
    
    // Handle API selector change
    if (apiSelector) {
        apiSelector.addEventListener('change', e => {
            console.log('API changed to:', apiSelector.value);
        });
        console.log('API selector event listener added');
    }
    
    // Load strings from language files
    require(['core/str'], function(str) {
        str.get_strings([
            {key: 'askaquestion', component: 'block_igis_ollama_claude'},
            {key: 'erroroccurred', component: 'block_igis_ollama_claude'}
        ]).then((results) => {
            questionString = results[0];
            errorString = results[1];
        });
    });
};

/**
 * Add a message to the chat UI
 * @param {string} type Which side of the UI the message should be on. Can be "user" or "assistant"
 * @param {string} message The text of the message to add
 * @param {int} blockId The ID of the block to manipulate
 */
const addToChatLog = (type, message, blockId) => {
    let messageContainer = document.querySelector(`.block_igis_ollama_claude[data-instance-id='${blockId}'] #igis_ollama_chat_log`);
    
    if (!messageContainer) {
        console.error('Message container not found');
        return;
    }
    
    const messageElem = document.createElement('div');
    messageElem.classList.add('igis_ollama_message');
    
    // Add appropriate class based on message type
    if (type === 'user') {
        messageElem.classList.add('user');
    } else if (type === 'assistant' || type === 'bot') {
        messageElem.classList.add('assistant');
    } else if (type === 'bot loading') {
        messageElem.classList.add('assistant');
        messageElem.classList.add('loading');
    } else if (type.includes('error')) {
        messageElem.classList.add('assistant');
        messageElem.classList.add('error');
    }

    const messageText = document.createElement('span');
    messageText.textContent = message;
    messageElem.appendChild(messageText);

    messageContainer.appendChild(messageElem);
    
    // Scroll to bottom
    messageContainer.scrollTop = messageContainer.scrollHeight;
    
    // If this is in a drawer or other scrollable container, also scroll that
    const parentScrollable = messageContainer.closest('.block_igis_ollama_claude > div');
    if (parentScrollable) {
        parentScrollable.scrollTop = parentScrollable.scrollHeight;
    }
};

/**
 * Clears the chat history from localStorage and UI
 */
const clearHistory = (blockId) => {
    console.log('Clearing chat history for block ID:', blockId);
    
    // Clear localStorage
    let chatData = localStorage.getItem("igis_ollama_claude_data");
    if (chatData) {
        chatData = JSON.parse(chatData);
        if (chatData[blockId]) {
            chatData[blockId] = {};
            localStorage.setItem("igis_ollama_claude_data", JSON.stringify(chatData));
        }
    }
    
    // Clear UI
    const messageContainer = document.querySelector(`.block_igis_ollama_claude[data-instance-id='${blockId}'] #igis_ollama_chat_log`);
    if (messageContainer) {
        messageContainer.innerHTML = "";
    } else {
        console.error('Message container not found for clearing');
    }
};

/**
 * Makes an API request to get a completion from the AI provider
 * @param {string} message The text to get a completion for
 * @param {int} blockId The ID of the block this message is being sent from
 */
const createCompletion = (message, blockId) => {
    console.log('Creating completion for message:', message);
    
    // Get currently selected API
    const apiSelector = document.querySelector(`.block_igis_ollama_claude[data-instance-id='${blockId}'] #api_selector`);
    const apiType = apiSelector ? apiSelector.value : defaultApi;
    
    // Get control elements
    const controlBar = document.querySelector(`.block_igis_ollama_claude[data-instance-id='${blockId}'] #control_bar`);
    const inputField = document.querySelector(`.block_igis_ollama_claude[data-instance-id='${blockId}'] #igis_ollama_input`);
    
    // Disable controls during request
    if (controlBar) controlBar.classList.add('disabled');
    if (inputField) {
        inputField.classList.remove('error');
        inputField.placeholder = questionString;
        inputField.blur();
    }
    
    // Show loading indicator
    addToChatLog('bot loading', '...', blockId);
    
    // Build transcript from existing messages
    const transcript = buildTranscript(blockId);
    
    // Make API request
    fetch(`${M.cfg.wwwroot}/blocks/igis_ollama_claude/api/completion.php`, {
        method: 'POST',
        body: JSON.stringify({
            message: message,
            history: transcript,
            blockId: blockId,
            api_type: apiType
        })
    })
    .then(response => {
        // Remove loading indicator
        let messageContainer = document.querySelector(`.block_igis_ollama_claude[data-instance-id='${blockId}'] #igis_ollama_chat_log`);
        if (messageContainer && messageContainer.lastElementChild) {
            messageContainer.removeChild(messageContainer.lastElementChild);
        }
        
        // Re-enable controls
        if (controlBar) controlBar.classList.remove('disabled');
        
        if (!response.ok) {
            throw new Error(response.statusText);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Received API response:', data);
        
        if (data.error) {
            addToChatLog('error', data.message || errorString, blockId);
        } else {
            addToChatLog('assistant', data.message, blockId);
            
            // Store in chat history
            let chatData = localStorage.getItem("igis_ollama_claude_data");
            if (chatData) {
                chatData = JSON.parse(chatData);
                if (!chatData[blockId].history) {
                    chatData[blockId].history = [];
                }
                
                chatData[blockId].history.push({
                    message: message,
                    response: data.message
                });
                
                localStorage.setItem("igis_ollama_claude_data", JSON.stringify(chatData));
            }
        }
        
        // Focus input field again
        if (inputField) inputField.focus();
    })
    .catch(error => {
        console.error('API call failed:', error);
        
        // Remove loading indicator if still present
        let messageContainer = document.querySelector(`.block_igis_ollama_claude[data-instance-id='${blockId}'] #igis_ollama_chat_log`);
        if (messageContainer) {
            const loadingMessage = messageContainer.querySelector('.loading');
            if (loadingMessage) {
                messageContainer.removeChild(loadingMessage);
            }
        }
        
        // Show error message
        addToChatLog('error', errorString, blockId);
        
        // Mark input field as error
        if (inputField) {
            inputField.classList.add('error');
            inputField.placeholder = errorString;
        }
        
        // Re-enable controls
        if (controlBar) controlBar.classList.remove('disabled');
    });
};

/**
 * Using the existing messages in the chat history, create an array that can be used for completion
 * @param {int} blockId The block from which to build the history
 * @return {Array} A transcript of the conversation up to this point
 */
const buildTranscript = (blockId) => {
    const messages = document.querySelectorAll(`.block_igis_ollama_claude[data-instance-id='${blockId}'] .igis_ollama_message`);
    
    // First try to get from localStorage
    let chatData = localStorage.getItem("igis_ollama_claude_data");
    if (chatData) {
        chatData = JSON.parse(chatData);
        if (chatData[blockId] && chatData[blockId].history) {
            return chatData[blockId].history;
        }
    }
    
    // Fall back to building from DOM
    let transcript = [];
    
    messages.forEach((message, index) => {
        // Skip the last message (which is being processed now)
        if (index === messages.length - 1 && message.classList.contains('loading')) {
            return;
        }
        
        const role = message.classList.contains('user') ? 'user' : 'assistant';
        const text = message.textContent.trim();
        
        transcript.push({
            role: role,
            message: text
        });
    });
    
    return transcript;
};