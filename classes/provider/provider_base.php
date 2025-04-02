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
 * Base provider class for AI services
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_igis_ollama_claude\provider;

defined('MOODLE_INTERNAL') || die;

/**
 * Base class for AI service providers
 */
abstract class provider_base {
    /** @var string The user message */
    protected $message;
    
    /** @var array The conversation history */
    protected $history;
    
    /** @var string The system prompt to guide the AI's behavior */
    protected $systemprompt;
    
    /** @var string The source of truth content */
    protected $sourceoftruth;
    
    /** @var string The model to use for this provider */
    protected $model;
    
    /** @var float Temperature setting (randomness) */
    protected $temperature;
    
    /** @var int Maximum number of tokens to generate */
    protected $max_tokens;
    
    /** @var string Assistant's name */
    protected $assistantname;
    
    /** @var string User's name */
    protected $username;
    
    /** @var string Thread ID for conversation history (if applicable) */
    protected $thread_id;

    /**
     * Constructor.
     *
     * @param string $message The user message
     * @param array $history The conversation history
     * @param array $settings Block settings
     * @param string $thread_id Thread ID for conversation persistence (optional)
     */
    public function __construct($message, $history, $settings, $thread_id = null) {
        $this->message = $message;
        $this->history = $history;
        $this->thread_id = $thread_id;
        
        // Set default values from global settings
        $this->systemprompt = get_config('block_igis_ollama_claude', 'completion_prompt');
        $this->assistantname = get_config('block_igis_ollama_claude', 'assistant_name');
        $this->username = get_config('block_igis_ollama_claude', 'user_name');
        $this->temperature = get_config('block_igis_ollama_claude', 'temperature');
        $this->max_tokens = get_config('block_igis_ollama_claude', 'max_tokens');
        
        // Override with block settings if available
        if (!empty($settings)) {
            foreach ($settings as $key => $value) {
                if (property_exists($this, $key) && !empty($value)) {
                    $this->$key = $value;
                }
            }
        }
        
        // Process source of truth if available
        if (!empty($settings['sourceoftruth'])) {
            $this->process_source_of_truth($settings['sourceoftruth']);
        }
    }
    
    /**
     * Process the source of truth by adding it to the system prompt
     *
     * @param string $sourceoftruth The source of truth content
     */
    protected function process_source_of_truth($sourceoftruth) {
        if (empty($sourceoftruth)) {
            return;
        }
        
        $this->sourceoftruth = $sourceoftruth;
        
        // Format based on provider type
        $providerType = $this->get_provider_type();
        
        switch ($providerType) {
            case 'ollama':
            case 'claude':
                // For Ollama and Claude, add to the beginning of the system prompt
                $preamble = "Below is a list of questions and their answers. This information should be used as a reference for any inquiries:\n\n";
                $this->systemprompt = $preamble . $sourceoftruth . "\n\n" . $this->systemprompt;
                break;
                
            case 'openai':
                // For OpenAI, use a different format that works better with their models
                $preamble = "REFERENCE INFORMATION:\n\n";
                $this->systemprompt = $preamble . $sourceoftruth . "\n\nPlease use the above reference information to answer questions accurately when it applies. " . $this->systemprompt;
                break;
                
            case 'gemini':
                // For Gemini, use a format adapted to its characteristics
                $preamble = "IMPORTANT FACTS:\n\n";
                $this->systemprompt = $preamble . $sourceoftruth . "\n\nUse these important facts to inform your responses when relevant. " . $this->systemprompt;
                break;
        }
        
        // Add a reinforcement instruction common to all
        $reinforcement = "\n\nWhen asked about topics covered in the reference information, always prioritize that information over your general knowledge. If information from the reference directly contradicts what you know, go with the reference information.";
        $this->systemprompt .= $reinforcement;
    }

    /**
     * Get the provider type from the class name
     *
     * @return string The provider type
     */
    protected function get_provider_type() {
        $className = get_class($this);
        if (strpos($className, '\\ollama') !== false) {
            return 'ollama';
        } else if (strpos($className, '\\claude') !== false) {
            return 'claude';
        } else if (strpos($className, '\\openai') !== false) {
            return 'openai';
        } else if (strpos($className, '\\gemini') !== false) {
            return 'gemini';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Format the conversation history for use in API calls
     *
     * @return array Formatted history
     */
    protected function format_history() {
        $formatted_history = [];
        
        foreach ($this->history as $entry) {
            if (isset($entry['message'])) {
                $formatted_history[] = [
                    'role' => 'user',
                    'content' => $entry['message']
                ];
                
                if (isset($entry['response'])) {
                    $formatted_history[] = [
                        'role' => 'assistant',
                        'content' => $entry['response']
                    ];
                }
            }
        }
        
        return $formatted_history;
    }

    /**
     * Limit conversation history to avoid exceeding token limits
     *
     * @param array $history The conversation history
     * @param int $maxMessages Maximum number of messages to keep
     * @return array Limited history
     */
    protected function limit_conversation_history($history, $maxMessages = 10) {
        // If history is shorter than the limit, return it as is
        if (count($history) <= $maxMessages) {
            return $history;
        }
        
        // Get the first messages to maintain initial context
        $firstMessages = array_slice($history, 0, 2);
        
        // Get the last messages to maintain recent context
        $lastMessages = array_slice($history, -($maxMessages - 2));
        
        // Combine and return
        return array_merge($firstMessages, $lastMessages);
    }

    /**
     * Estimate the number of tokens in a string
     *
     * @param string $text The text to estimate
     * @return int Estimated token count
     */
    protected function estimate_token_count($text) {
        // Basic estimation: approximately 4 characters per token for English
        // and 6 characters per token for Spanish/other languages
        
        // Detect if the text seems to be primarily in English
        $englishPattern = '/^[a-zA-Z0-9\s\.,;:!?\'"-]+$/';
        $isEnglish = preg_match($englishPattern, substr($text, 0, 500)) > 0;
        
        $charsPerToken = $isEnglish ? 4 : 6;
        return ceil(mb_strlen($text) / $charsPerToken);
    }

    /**
     * Check if the conversation history might exceed token limits
     *
     * @return bool True if history should be truncated
     */
    protected function should_truncate_history() {
        // Estimate the token size of the message and history
        $totalTokens = $this->estimate_token_count($this->message);
        foreach ($this->history as $entry) {
            if (isset($entry['message'])) {
                $totalTokens += $this->estimate_token_count($entry['message']);
            }
            if (isset($entry['response'])) {
                $totalTokens += $this->estimate_token_count($entry['response']);
            }
        }
        
        // Check against model limits
        $modelLimits = [
            'claude-3-opus-20240229' => 200000,
            'claude-3-sonnet-20240229' => 180000,
            'claude-3-haiku-20240307' => 150000,
            'gpt-4' => 8000,
            'gpt-3.5-turbo' => 4000,
            'gemini-pro' => 30000,
            'gemini-1.5-pro' => 100000,
            // Approximate values, adjust according to official documentation
        ];
        
        $modelLimit = isset($modelLimits[$this->model]) ? $modelLimits[$this->model] : 4000; // Default value
        
        // If the estimated total exceeds 80% of the limit, trim the history
        return ($totalTokens > $modelLimit * 0.8);
    }

    /**
     * Get cache key for a message
     *
     * @param string $message The message
     * @return string Cache key
     */
    protected function get_cache_key($message) {
        // Normalize the message (remove spaces, convert to lowercase)
        $normalized = strtolower(trim($message));
        // Create a cache key based on the message and model
        return md5($normalized . '_' . $this->model);
    }

    /**
     * Try to get a response from cache
     *
     * @param string $message The message
     * @return string|null Cached response or null
     */
    protected function get_from_cache($message) {
        global $CFG, $DB;
        
        // If caching is disabled, return null
        if (empty(get_config('block_igis_ollama_claude', 'enable_cache'))) {
            return null;
        }
        
        $cacheKey = $this->get_cache_key($message);
        
        // Search in the cache table
        $record = $DB->get_record('block_igis_ollama_claude_cache', [
            'cache_key' => $cacheKey,
            'model' => $this->model
        ]);
        
        // Check if the cache entry is valid (not expired)
        if ($record && time() - $record->time_created < 86400) { // 24 hours
            return $record->response;
        }
        
        return null;
    }

    /**
     * Save a response to cache
     *
     * @param string $message The message
     * @param string $response The response
     */
    protected function save_to_cache($message, $response) {
        global $DB;
        
        // If caching is disabled, do nothing
        if (empty(get_config('block_igis_ollama_claude', 'enable_cache'))) {
            return;
        }
        
        $cacheKey = $this->get_cache_key($message);
        
        // Check if an entry already exists for this key
        $existing = $DB->get_record('block_igis_ollama_claude_cache', [
            'cache_key' => $cacheKey,
            'model' => $this->model
        ]);
        
        if ($existing) {
            // Update existing entry
            $existing->response = $response;
            $existing->time_created = time();
            $DB->update_record('block_igis_ollama_claude_cache', $existing);
        } else {
            // Create new entry
            $record = new \stdClass();
            $record->cache_key = $cacheKey;
            $record->message = $message;
            $record->response = $response;
            $record->model = $this->model;
            $record->time_created = time();
            $DB->insert_record('block_igis_ollama_claude_cache', $record);
        }
    }

    /**
     * Extract error message from API response
     *
     * @param string $response_json JSON response from API
     * @return string Error message
     */
    protected function extract_error_message($response_json) {
        $response = json_decode($response_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Error de formato en la respuesta";
        }
        
        // Generic implementation to be overridden by each provider
        if (isset($response['error']['message'])) {
            return $response['error']['message'];
        }
        
        return "Error desconocido";
    }
    
    /**
     * Create a response using the AI service
     *
     * @param \context $context The Moodle context
     * @return array Response data
     */
    abstract public function create_response($context);
}