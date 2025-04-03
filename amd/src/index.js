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
 * AMD module entry point for the Multi-provider AI Chat Block
 *
 * @module     block_igis_ollama_claude/index
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import chat from './chat';
import $ from 'jquery';
import log from 'core/log';

/**
 * Initialize the chat module with diagnostic information
 */
const init = function() {
    try {
        // Log version and initialization
        log.debug('Multi-provider AI Chat Block initializing - v1.0.1');
        
        // Check if jQuery is working
        if (typeof $ === 'function') {
            log.debug('jQuery is loaded correctly');
        } else {
            log.error('jQuery is not loaded correctly');
        }
        
        // Check if we have access to the chat module
        if (typeof chat === 'object' && typeof chat.init === 'function') {
            log.debug('Chat module loaded correctly');
        } else {
            log.error('Chat module not loaded correctly');
        }
        
        // Find all chat containers on the page
        const chatContainers = document.querySelectorAll('[id^="ollama-claude-chat-"]');
        log.debug(`Found ${chatContainers.length} chat containers on the page`);
        
        // The actual initialization will be done by the chat.js init function
        // which is called from renderer.php
    } catch (error) {
        log.error('Error initializing Multi-provider AI Chat Block:', error);
    }
};

export default {
    init: init
};