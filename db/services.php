<?php
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
 * External functions and service definition for Ollama Claude AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'block_igis_ollama_claude_get_chat_response' => array(
        'classname'     => 'block_igis_ollama_claude_external',
        'methodname'    => 'get_chat_response',
        'description'   => 'Get a response from the Ollama Claude AI',
        'type'          => 'read',
        'capabilities'  => '',
        'ajax'          => true,
        'loginrequired' => false, // This is handled in the function based on settings
    ),
    'block_igis_ollama_claude_clear_conversation' => array(
        'classname'     => 'block_igis_ollama_claude_external',
        'methodname'    => 'clear_conversation',
        'description'   => 'Clear the conversation history',
        'type'          => 'write',
        'capabilities'  => '',
        'ajax'          => true,
        'loginrequired' => false, // This is handled in the function based on settings
    ),
);

$services = array(
    'Ollama Claude AI Chat' => array(
        'functions' => array(
            'block_igis_ollama_claude_get_chat_response',
            'block_igis_ollama_claude_clear_conversation',
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
    ),
);
