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
 * Console debugging utilities for Multi-provider AI Chat Block
 *
 * @module     block_igis_ollama_claude/console
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import $ from 'jquery';

/**
 * Console debugging object that exposes useful methods for developers
 * This can be accessed in the browser console with: 
 * M.block_igis_ollama_claude.debug
 */
export default {
    /**
     * Test a direct API call to the chat response method
     * 
     * @param {string} message - Test message to send
     * @param {string} api - API to use (ollama, claude, openai, gemini)
     * @param {number} instanceId - Block instance ID 
     */
    testApiCall: function(message, api, instanceId) {
        if (!message) {
            message = "Hola, ¿cómo estás?";
        }
        
        if (!api) {
            api = "ollama";
        }
        
        if (!instanceId) {
            // Try to find an instance ID from the page
            const instanceElement = document.querySelector('[id^="ollama-claude-instanceid-"]');
            if (instanceElement) {
                instanceId = instanceElement.value;
            } else {
                console.error('No instance ID provided and none found on page');
                return;
            }
        }
        
        // Find the context ID
        let contextId;
        const contextElement = document.querySelector('[id^="ollama-claude-contextid-"]');
        if (contextElement) {
            contextId = contextElement.value;
        } else {
            contextId = 1; // System context as fallback
        }
        
        console.log(`Testing API call with message: "${message}", api: "${api}", instanceId: ${instanceId}, contextId: ${contextId}`);
        
        // Make the API call
        Ajax.call([{
            methodname: 'block_igis_ollama_claude_get_chat_response',
            args: {
                message: message,
                conversation: JSON.stringify([]),
                instanceid: instanceId,
                contextid: contextId,
                sourceoftruth: '',
                prompt: '',
                api: api
            }
        }])[0].then(function(response) {
            console.log('API call successful!');
            console.log('Response:', response);
        }).catch(function(error) {
            console.error('API call failed:', error);
        });
    },
    
    /**
     * Get info about the chat instances on the current page
     */
    getInstanceInfo: function() {
        const instances = [];
        const containers = document.querySelectorAll('[id^="ollama-claude-chat-"]');
        
        containers.forEach(function(container) {
            const id = container.id;
            const uniqueId = id.replace('ollama-claude-chat-', '');
            
            const instanceIdElement = document.getElementById(`ollama-claude-instanceid-${uniqueId}`);
            const contextIdElement = document.getElementById(`ollama-claude-contextid-${uniqueId}`);
            const assistantNameElement = document.getElementById(`ollama-claude-assistant-name-${uniqueId}`);
            const userNameElement = document.getElementById(`ollama-claude-user-name-${uniqueId}`);
            const apiSelector = document.getElementById(`ollama-claude-api-select-${uniqueId}`);
            
            instances.push({
                uniqueId: uniqueId,
                instanceId: instanceIdElement ? instanceIdElement.value : 'unknown',
                contextId: contextIdElement ? contextIdElement.value : 'unknown',
                assistantName: assistantNameElement ? assistantNameElement.value : 'unknown',
                userName: userNameElement ? userNameElement.value : 'unknown',
                selectedApi: apiSelector ? apiSelector.value : 'unknown'
            });
        });
        
        console.table(instances);
        return instances;
    },
    
    /**
     * Check Moodle's web service registration for our methods
     */
    checkWebServices: function() {
        console.log('Checking web service registration...');
        console.log('If you see "methodinfo undefined", it might indicate a problem with service registration.');
        console.log('Try "Purge all caches" in the Site Administration.');
        
        try {
            const methodInfo1 = M.cfg.methodinfo.block_igis_ollama_claude_get_chat_response;
            console.log('block_igis_ollama_claude_get_chat_response:', methodInfo1 ? 'Registered' : 'Not registered');
        } catch (e) {
            console.error('block_igis_ollama_claude_get_chat_response: Not registered');
        }
        
        try {
            const methodInfo2 = M.cfg.methodinfo.block_igis_ollama_claude_clear_conversation;
            console.log('block_igis_ollama_claude_clear_conversation:', methodInfo2 ? 'Registered' : 'Not registered');
        } catch (e) {
            console.error('block_igis_ollama_claude_clear_conversation: Not registered');
        }
    },
    
    /**
     * Manually register a conversation in localStorage
     * 
     * @param {number} instanceId - Block instance ID
     */
    clearConversation: function(instanceId) {
        if (!instanceId) {
            // Try to find an instance ID from the page
            const instanceElement = document.querySelector('[id^="ollama-claude-instanceid-"]');
            if (instanceElement) {
                instanceId = instanceElement.value;
            } else {
                console.error('No instance ID provided and none found on page');
                return;
            }
        }
        
        localStorage.removeItem(`ollama-claude-conversation-${instanceId}`);
        console.log(`Conversation for instance ${instanceId} cleared from localStorage`);
    }
};