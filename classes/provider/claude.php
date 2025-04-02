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
 * Claude provider for AI services
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_igis_ollama_claude\provider;

defined('MOODLE_INTERNAL') || die;

/**
 * Claude provider class
 */
class claude extends provider_base {
    /** @var string The Claude API key */
    private $apikey;
    
    /** @var string The Claude API URL */
    private $apiurl;
    
    /**
     * Constructor.
     *
     * @param string $message The user message
     * @param array $history The conversation history
     * @param array $settings Block settings
     * @param string $thread_id Thread ID (not used for Claude)
     */
    public function __construct($message, $history, $settings, $thread_id = null) {
        parent::__construct($message, $history, $settings, $thread_id);
        
        // Set Claude-specific properties
        $this->apikey = get_config('block_igis_ollama_claude', 'claudeapikey');
        $this->apiurl = get_config('block_igis_ollama_claude', 'claudeapiurl');
        $this->model = get_config('block_igis_ollama_claude', 'claudemodel');
        
        // Override with block settings if available
        if (!empty($settings['claudemodel'])) {
            $this->model = $settings['claudemodel'];
        }
    }
    
    /**
     * Create a response using Claude API
     *
     *