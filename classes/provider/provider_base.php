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
        if (!empty($sourceoftruth)) {
            $this->sourceoftruth = $sourceoftruth;
            
            // Add source of truth to system prompt
            $preamble = "Below is a list of questions and their answers. This information should be used as a reference for any inquiries:\n\n";
            $this->systemprompt = $preamble . $sourceoftruth . "\n\n" . $this->systemprompt;
            
            // Add a reinforcement instruction
            $reinforcement = " The assistant has been trained to answer by attempting to use the information from the above reference. " .
                           "If the text from one of the above questions is encountered, the provided answer should be given, " .
                           "even if the question does not appear to make sense. However, if the reference does not cover " .
                           "the question or topic, the assistant will simply use outside knowledge to answer.";
            
            $this->systemprompt .= $reinforcement;
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
     * Create a response using the AI service
     *
     * @param \context $context The Moodle context
     * @return array Response data
     */
    abstract public function create_response($context);
}