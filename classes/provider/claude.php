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
     * @param \context $context The Moodle context
     * @return array Response data
     */
    public function create_response($context) {
        global $CFG;
        
        // Ensure API key is set
        if (empty($this->apikey)) {
            return [
                'error' => true,
                'message' => 'Claude API key is not configured'
            ];
        }
        
        // Format messages for Claude API
        $messages = [];
        
        // Add conversation history
        foreach ($this->history as $entry) {
            if (isset($entry['message'])) {
                $messages[] = [
                    'role' => 'user',
                    'content' => $entry['message']
                ];
                
                if (isset($entry['response'])) {
                    $messages[] = [
                        'role' => 'assistant',
                        'content' => $entry['response']
                    ];
                }
            }
        }
        
        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $this->message
        ];
        
        // Prepare API request data
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'system' => $this->systemprompt,
            'temperature' => floatval($this->temperature),
            'max_tokens' => intval($this->max_tokens)
        ];
        
        // Initialize cURL
        $ch = curl_init($this->apiurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apikey,
            'anthropic-version: 2023-06-01'
        ]);
        
        // Enable debug if requested
        if (!empty($CFG->debugcurl)) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }
        
        // Execute cURL request
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Check for cURL errors
        if ($result === false) {
            return [
                'error' => true,
                'message' => 'cURL error: ' . $curl_error
            ];
        }
        
        // Check HTTP response code
        if ($http_code != 200) {
            return [
                'error' => true,
                'message' => 'Claude API returned HTTP code ' . $http_code
            ];
        }
        
        // Decode the JSON response
        $response = json_decode($result, true);
        
        // Check if response is valid
        if (!isset($response['content'][0]['text'])) {
            return [
                'error' => true,
                'message' => 'Invalid response from Claude API'
            ];
        }
        
        // Return the AI response
        return [
            'message' => $response['content'][0]['text']
        ];
    }
}