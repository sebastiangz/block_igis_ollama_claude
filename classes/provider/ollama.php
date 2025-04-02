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
 * Ollama provider for AI services
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_igis_ollama_claude\provider;

defined('MOODLE_INTERNAL') || die;

/**
 * Ollama provider class
 */
class ollama extends provider_base {
    /** @var string The Ollama API URL */
    private $apiurl;
    
    /**
     * Constructor.
     *
     * @param string $message The user message
     * @param array $history The conversation history
     * @param array $settings Block settings
     * @param string $thread_id Thread ID (not used for Ollama)
     */
    public function __construct($message, $history, $settings, $thread_id = null) {
        parent::__construct($message, $history, $settings, $thread_id);
        
        // Set Ollama-specific properties
        $this->apiurl = get_config('block_igis_ollama_claude', 'ollamaapiurl');
        $this->model = get_config('block_igis_ollama_claude', 'ollamamodel');
        
        // Override with block settings if available
        if (!empty($settings['ollamamodel'])) {
            $this->model = $settings['ollamamodel'];
        }
    }
    
    /**
     * Create a response using Ollama API
     *
     * @param \context $context The Moodle context
     * @return array Response data
     */
    public function create_response($context) {
        global $CFG;
        
        // Start timing
        $startTime = microtime(true);
        
        // Ensure API URL is set
        if (empty($this->apiurl)) {
            return [
                'error' => true,
                'message' => 'Ollama API URL is not configured'
            ];
        }
        
        // Try to get from cache first
        $cachedResponse = $this->get_from_cache($this->message);
        if ($cachedResponse !== null) {
            return [
                'message' => $cachedResponse,
                'from_cache' => true,
                'provider' => 'ollama',
                'model' => $this->model
            ];
        }
        
        // Build the messages array
        $messages = [];
        
        // Add system message
        $messages[] = [
            'role' => 'system',
            'content' => $this->systemprompt
        ];
        
        // Add conversation history
        $history = $this->format_history();
        foreach ($history as $entry) {
            $messages[] = $entry;
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
            'temperature' => floatval($this->temperature),
            'max_tokens' => intval($this->max_tokens),
            'stream' => false
        ];
        
        try {
            // Make API request
            $ch = curl_init($this->apiurl . '/api/chat');
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
            
            // Set reasonable timeout
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // End timing
            $endTime = microtime(true);
            $processingTime = round(($endTime - $startTime) * 1000); // in milliseconds
            
            // Check for errors
            if ($result === false) {
                return [
                    'error' => true,
                    'message' => 'cURL error: ' . $curlError,
                    'code' => 500,
                    'provider' => 'ollama'
                ];
            }
            
            if ($httpCode != 200) {
                return [
                    'error' => true,
                    'message' => 'Ollama API returned HTTP code ' . $httpCode,
                    'code' => $httpCode,
                    'provider' => 'ollama'
                ];
            }
            
            // Decode the response
            $response = json_decode($result, true);
            if (!isset($response['message']['content'])) {
                return [
                    'error' => true,
                    'message' => 'Invalid response from Ollama API',
                    'provider' => 'ollama'
                ];
            }
            
            $responseText = $response['message']['content'];
            
            // Save to cache if enabled
            $this->save_to_cache($this->message, $responseText);
            
            // Return response with metadata
            return [
                'message' => $responseText,
                'metadata' => [
                    'provider' => 'ollama',
                    'model' => $this->model,
                    'processing_time_ms' => $processingTime,
                    'tokens_used' => isset($response['usage']['total_tokens']) ? $response['usage']['total_tokens'] : null
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => "Error inesperado: " . $e->getMessage(),
                'code' => 500,
                'provider' => 'ollama'
            ];
        }
    }
    
    /**
     * Extract error message from Ollama API response
     *
     * @param string $response_json JSON response from API
     * @return string Error message
     */
    protected function extract_error_message($response_json) {
        $response = json_decode($response_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Error de formato en la respuesta";
        }
        
        if (isset($response['error'])) {
            return $response['error'];
        }
        
        return "Error desconocido en la API Ollama";
    }