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
 * Gemini provider for AI services
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_igis_ollama_claude\provider;

defined('MOODLE_INTERNAL') || die;

/**
 * Gemini provider class
 */
class gemini extends provider_base {
    /** @var string The Gemini API key */
    private $apikey;
    
    /**
     * Constructor.
     *
     * @param string $message The user message
     * @param array $history The conversation history
     * @param array $settings Block settings
     * @param string $thread_id Thread ID (not used for Gemini)
     */
    public function __construct($message, $history, $settings, $thread_id = null) {
        parent::__construct($message, $history, $settings, $thread_id);
        
        // Set Gemini-specific properties
        $this->apikey = get_config('block_igis_ollama_claude', 'geminikey');
        $this->model = get_config('block_igis_ollama_claude', 'geminimodel');
        
        // Override with block settings if available
        if (!empty($settings['geminimodel'])) {
            $this->model = $settings['geminimodel'];
        }
    }
    
    /**
     * Create a response using Gemini API
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
                'message' => 'Gemini API key is not configured'
            ];
        }
        
        // Format conversations for Gemini API
        $contents = [];
        
        // Add system prompt as first user message if provided
        if (!empty($this->systemprompt)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $this->systemprompt]]
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => 'I understand and will follow these instructions.']]
            ];
        }
        
        // Add conversation history
        foreach ($this->history as $entry) {
            if (isset($entry['message'])) {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => $entry['message']]]
                ];
                
                if (isset($entry['response'])) {
                    $contents[] = [
                        'role' => 'model',
                        'parts' => [['text' => $entry['response']]]
                    ];
                }
            }
        }
        
        // Add current message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $this->message]]
        ];
        
        // Prepare API request data
        $data = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => floatval($this->temperature),
                'maxOutputTokens' => intval($this->max_tokens),
                'topP' => 0.95,
                'topK' => 40
            ]
        ];
        
        // Initialize cURL
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apikey}";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
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
                'message' => 'Gemini API returned HTTP code ' . $http_code
            ];
        }
        
        // Decode the JSON response
        $response = json_decode($result, true);
        
        // Check if response is valid
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'error' => true,
                'message' => 'Invalid response from Gemini API'
            ];
        }
        
        // Return the AI response
        return [
        'message' => $responseText,
        'metadata' => [
            'provider' => 'claude', // o 'ollama', 'openai', 'gemini'
            'model' => $this->model,
            'tokens_used' => $tokensUsed, // si está disponible
            'processing_time' => $processingTime // si está disponible
        ]
    ];
    }
}