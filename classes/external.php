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
 * External API for the Ollama Claude AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

/**
 * External API functions for Ollama Claude AI Chat
 */
class block_igis_ollama_claude_external extends external_api {

    /**
     * Returns description of get_chat_response parameters
     *
     * @return external_function_parameters
     */
    public static function get_chat_response_parameters() {
        return new external_function_parameters([
            'message' => new external_value(PARAM_RAW, 'User message'),
            'conversation' => new external_value(PARAM_RAW, 'Conversation history in JSON format', VALUE_DEFAULT, '[]'),
            'instanceid' => new external_value(PARAM_INT, 'Block instance ID'),
            'contextid' => new external_value(PARAM_INT, 'Context ID'),
            'sourceoftruth' => new external_value(PARAM_RAW, 'Source of truth', VALUE_DEFAULT, ''),
            'prompt' => new external_value(PARAM_RAW, 'System prompt', VALUE_DEFAULT, ''),
            'api' => new external_value(PARAM_ALPHA, 'API service to use (ollama or claude)', VALUE_DEFAULT, '')
        ]);
    }

    /**
     * Get chat response from Ollama Claude
     *
     * @param string $message User message
     * @param string $conversation Conversation history in JSON format
     * @param int $instanceid Block instance ID
     * @param int $contextid Context ID
     * @param string $sourceoftruth Source of truth
     * @param string $prompt System prompt
     * @param string $api API service to use (ollama or claude)
     * @return array Response data
     */
    public static function get_chat_response($message, $conversation, $instanceid, $contextid, $sourceoftruth, $prompt, $api) {
        global $DB, $USER, $COURSE;

        // Parameter validation
        $params = self::validate_parameters(self::get_chat_response_parameters(), [
            'message' => $message,
            'conversation' => $conversation,
            'instanceid' => $instanceid,
            'contextid' => $contextid,
            'sourceoftruth' => $sourceoftruth,
            'prompt' => $prompt,
            'api' => $api
        ]);
        
        // Get context
        $context = context::instance_by_id($contextid);
        self::validate_context($context);
        
        // Check if context is course context, and if not, get the course ID
        if ($context instanceof context_course) {
            $courseid = $context->instanceid;
        } else {
            $courseid = $COURSE->id;
        }
        
        // Get block instance and config (for specific settings)
        $block = $DB->get_record('block_instances', ['id' => $instanceid], '*', MUST_EXIST);
        $config = unserialize(base64_decode($block->configdata));
        
        // Determine which API to use if not specified
        if (empty($api)) {
            $api = get_config('block_igis_ollama_claude', 'defaultapi');
            
            // Check if block has specific API preference
            if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config) && !empty($config->defaultapi)) {
                $api = $config->defaultapi;
            }
        }
        
        // Check if the selected API is available
        $ollamaapiurl = get_config('block_igis_ollama_claude', 'ollamaapiurl');
        $claudeapikey = get_config('block_igis_ollama_claude', 'claudeapikey');
        
        if ($api === 'ollama' && empty($ollamaapiurl)) {
            if (!empty($claudeapikey)) {
                $api = 'claude'; // Fallback to Claude API
            } else {
                throw new moodle_exception('No API available');
            }
        } else if ($api === 'claude' && empty($claudeapikey)) {
            if (!empty($ollamaapiurl)) {
                $api = 'ollama'; // Fallback to Ollama API
            } else {
                throw new moodle_exception('No API available');
            }
        }
        
        // Decode conversation history
        $conversationHistory = json_decode($conversation, true);
        if (!is_array($conversationHistory)) {
            $conversationHistory = [];
        }
        
        // Build conversation messages for API
        $messages = [];
        
        // Add system prompt if provided
        if (!empty($prompt)) {
            $systemPrompt = $prompt;
        } else {
            $systemPrompt = get_config('block_igis_ollama_claude', 'completion_prompt');
        }
        
        // Add source of truth if provided
        if (!empty($sourceoftruth)) {
            $sotMessage = "Below is a list of questions and their answers. This information should be used as a reference for any inquiries:\n\n" . $sourceoftruth;
            
            // Add SoT to system prompt
            $systemPrompt = $sotMessage . "\n\n" . $systemPrompt;
        }
        
        // Route to appropriate API
        $aiResponse = '';
        if ($api === 'ollama') {
            $aiResponse = self::get_ollama_response($message, $conversationHistory, $systemPrompt, $config);
        } else {
            $aiResponse = self::get_claude_response($message, $conversationHistory, $systemPrompt, $config);
        }
        
        // Log the interaction if logging is enabled
        if (get_config('block_igis_ollama_claude', 'enablelogging')) {
            $log = new stdClass();
            $log->userid = $USER->id;
            $log->courseid = $courseid;
            $log->contextid = $contextid;
            $log->instanceid = $instanceid;
            $log->message = $message;
            $log->response = $aiResponse;
            $log->sourceoftruth = $sourceoftruth;
            $log->prompt = $prompt;
            $log->model = ($api === 'ollama') 
                ? get_config('block_igis_ollama_claude', 'ollamamodel') 
                : get_config('block_igis_ollama_claude', 'claudemodel');
                
            // Check for instance-specific model settings
            if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config)) {
                if ($api === 'ollama' && !empty($config->ollamamodel)) {
                    $log->model = $config->ollamamodel;
                } else if ($api === 'claude' && !empty($config->claudemodel)) {
                    $log->model = $config->claudemodel;
                }
            }
            
            $log->api = $api;
            $log->timecreated = time();
            
            $DB->insert_record('block_igis_ollama_claude_logs', $log);
        }
        
        return [
            'response' => $aiResponse
        ];
    }
    
    /**
     * Get response from Ollama API
     *
     * @param string $message User message
     * @param array $conversationHistory Conversation history
     * @param string $systemPrompt System prompt
     * @param object $config Block instance config
     * @return string AI response
     */
    private static function get_ollama_response($message, $conversationHistory, $systemPrompt, $config) {
        // Get Ollama API settings
        $apiurl = get_config('block_igis_ollama_claude', 'ollamaapiurl');
        $model = get_config('block_igis_ollama_claude', 'ollamamodel');
        $temperature = get_config('block_igis_ollama_claude', 'temperature');
        $max_tokens = get_config('block_igis_ollama_claude', 'max_tokens');
        
        // If instance level settings are allowed and set, use those instead
        if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config)) {
            // If custom model is set for this instance
            if (!empty($config->ollamamodel)) {
                $model = $config->ollamamodel;
            }
            
            // If custom temperature is set for this instance
            if (!empty($config->temperature)) {
                $temperature = $config->temperature;
            }
            
            // If custom max_tokens is set for this instance
            if (!empty($config->max_tokens)) {
                $max_tokens = $config->max_tokens;
            }
        }
        
        // Build the messages array
        $messages = [];
        
        // Add system message
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt
        ];
        
        // Add conversation history
        foreach ($conversationHistory as $exchange) {
            $messages[] = [
                'role' => 'user',
                'content' => $exchange['message']
            ];
            
            if (isset($exchange['response'])) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $exchange['response']
                ];
            }
        }
        
        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        // Prepare API request data
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => floatval($temperature),
            'max_tokens' => intval($max_tokens),
            'stream' => false
        ];
        
        // Make API request
        $ch = curl_init($apiurl . '/api/chat');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Log errors if the request fails
        if ($result === false) {
            $error = curl_error($ch);
            error_log('Ollama API error: ' . $error);
            curl_close($ch);
            throw new moodle_exception('Failed to connect to Ollama API: ' . $error);
        }
        
        curl_close($ch);
        
        // Check for errors
        if ($httpCode != 200) {
            error_log('Ollama API error: HTTP code ' . $httpCode . ', Response: ' . $result);
            throw new moodle_exception('Failed to get response from Ollama API. HTTP code: ' . $httpCode);
        }
        
        // Decode the response
        $response = json_decode($result, true);
        if (!isset($response['message']['content'])) {
            error_log('Invalid Ollama API response: ' . $result);
            throw new moodle_exception('Invalid response from Ollama API');
        }
        
        return $response['message']['content'];
    }
    
    /**
     * Get response from Claude API
     *
     * @param string $message User message
     * @param array $conversationHistory Conversation history
     * @param string $systemPrompt System prompt
     * @param object $config Block instance config
     * @return string AI response
     */
    private static function get_claude_response($message, $conversationHistory, $systemPrompt, $config) {
        // Get Claude API settings
        $apikey = get_config('block_igis_ollama_claude', 'claudeapikey');
        $apiurl = get_config('block_igis_ollama_claude', 'claudeapiurl');
        $model = get_config('block_igis_ollama_claude', 'claudemodel');
        $temperature = get_config('block_igis_ollama_claude', 'temperature');
        $max_tokens = get_config('block_igis_ollama_claude', 'max_tokens');
        
        // If instance level settings are allowed and set, use those instead
        if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config)) {
            // If custom model is set for this instance
            if (!empty($config->claudemodel)) {
                $model = $config->claudemodel;
            }
            
            // If custom temperature is set for this instance
            if (!empty($config->temperature)) {
                $temperature = $config->temperature;
            }
            
            // If custom max_tokens is set for this instance
            if (!empty($config->max_tokens)) {
                $max_tokens = $config->max_tokens;
            }
        }
        
        // Build the messages array for Claude API
        $messages = [];
        
        // Add conversation history
        foreach ($conversationHistory as $exchange) {
            $messages[] = [
                'role' => 'user',
                'content' => $exchange['message']
            ];
            
            if (isset($exchange['response'])) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $exchange['response']
                ];
            }
        }
        
        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        // Prepare API request data for Claude
        $data = [
            'model' => $model,
            'messages' => $messages,
            'system' => $systemPrompt,
            'temperature' => floatval($temperature),
            'max_tokens' => intval($max_tokens)
        ];
        
        // Make API request to Claude with updated headers
        $ch = curl_init($apiurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'anthropic-api-key: ' . $apikey,  // Updated from x-api-key to anthropic-api-key
            'anthropic-version: 2023-06-01',
            'x-api-key: ' . $apikey  // Keep old header for backward compatibility
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Log errors if the request fails
        if ($result === false) {
            $error = curl_error($ch);
            error_log('Claude API error: ' . $error);
            curl_close($ch);
            throw new moodle_exception('Failed to connect to Claude API: ' . $error);
        }
        
        // Log the response for debugging
        error_log('Claude API HTTP code: ' . $httpCode);
        error_log('Claude API response: ' . substr($result, 0, 500) . (strlen($result) > 500 ? '...' : ''));
        
        curl_close($ch);
        
        // Check for errors
        if ($httpCode != 200) {
            error_log('Claude API error: HTTP code ' . $httpCode . ', Response: ' . $result);
            throw new moodle_exception('Failed to get response from Claude API. HTTP code: ' . $httpCode);
        }
        
        // Decode the response
        $response = json_decode($result, true);
        
        // Handle different response formats for Claude API (check both newer and older formats)
        if (isset($response['content'][0]['text'])) {
            return $response['content'][0]['text'];
        } else if (isset($response['completion'])) {
            return $response['completion'];
        } else if (isset($response['message']['content'])) {
            return $response['message']['content'];
        } else {
            error_log('Invalid Claude API response format: ' . $result);
            throw new moodle_exception('Invalid response format from Claude API');
        }
    }

    /**
     * Returns description of get_chat_response returns
     *
     * @return external_single_structure
     */
    public static function get_chat_response_returns() {
        return new external_single_structure([
            'response' => new external_value(PARAM_RAW, 'AI response')
        ]);
    }

    /**
     * Returns description of clear_conversation parameters
     *
     * @return external_function_parameters
     */
    public static function clear_conversation_parameters() {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'Block instance ID')
        ]);
    }

    /**
     * Clear the conversation history
     *
     * @param int $instanceid Block instance ID
     * @return array Status
     */
    public static function clear_conversation($instanceid) {
        global $USER;

        // Parameter validation
        $params = self::validate_parameters(self::clear_conversation_parameters(), [
            'instanceid' => $instanceid
        ]);
        
        // Simply return success since the conversation is stored client-side
        return [
            'status' => true,
            'message' => 'Conversation cleared successfully'
        ];
    }

    /**
     * Returns description of clear_conversation returns
     *
     * @return external_single_structure
     */
    public static function clear_conversation_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Operation success status'),
            'message' => new external_value(PARAM_TEXT, 'Status message')
        ]);
    }
}