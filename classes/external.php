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
 * External API for the Multi-provider AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

/**
 * External API functions for Multi-provider AI Chat
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
            'api' => new external_value(PARAM_TEXT, 'API service to use (ollama, claude, openai, gemini)', VALUE_DEFAULT, '')
        ]);
    }

    /**
     * Get chat response from selected AI provider
     *
     * @param string $message User message
     * @param string $conversation Conversation history in JSON format
     * @param int $instanceid Block instance ID
     * @param int $contextid Context ID
     * @param string $sourceoftruth Source of truth
     * @param string $prompt System prompt
     * @param string $api API service to use (ollama, claude, openai, gemini)
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
        $openaikey = get_config('block_igis_ollama_claude', 'openaikey');
        $geminikey = get_config('block_igis_ollama_claude', 'geminikey');
        
        // If selected API is not available, try to fallback to an available one
        $apiAvailable = false;
        
        switch ($api) {
            case 'ollama':
                if (!empty($ollamaapiurl)) {
                    $apiAvailable = true;
                }
                break;
                
            case 'claude':
                if (!empty($claudeapikey)) {
                    $apiAvailable = true;
                }
                break;
                
            case 'openai':
                if (!empty($openaikey)) {
                    $apiAvailable = true;
                }
                break;
                
            case 'gemini':
                if (!empty($geminikey)) {
                    $apiAvailable = true;
                }
                break;
        }
        
        // If selected API is not available, find the first available one
        if (!$apiAvailable) {
            if (!empty($ollamaapiurl)) {
                $api = 'ollama';
            } else if (!empty($claudeapikey)) {
                $api = 'claude';
            } else if (!empty($openaikey)) {
                $api = 'openai';
            } else if (!empty($geminikey)) {
                $api = 'gemini';
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
        switch ($api) {
            case 'ollama':
                $aiResponse = self::get_ollama_response($message, $conversationHistory, $systemPrompt, $config);
                break;
                
            case 'claude':
                $aiResponse = self::get_claude_response($message, $conversationHistory, $systemPrompt, $config);
                break;
                
            case 'openai':
                $aiResponse = self::get_openai_response($message, $conversationHistory, $systemPrompt, $config);
                break;
                
            case 'gemini':
                $aiResponse = self::get_gemini_response($message, $conversationHistory, $systemPrompt, $config);
                break;
                
            default:
                throw new moodle_exception('Invalid API type');
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
            $log->api = $api;
            $log->timecreated = time();
            
            // Get model based on selected API
            switch ($api) {
                case 'ollama':
                    $log->model = !empty($config->ollamamodel) && get_config('block_igis_ollama_claude', 'instancesettings') ? 
                                  $config->ollamamodel : 
                                  get_config('block_igis_ollama_claude', 'ollamamodel');
                    break;
                
                case 'claude':
                    $log->model = !empty($config->claudemodel) && get_config('block_igis_ollama_claude', 'instancesettings') ? 
                                  $config->claudemodel : 
                                  get_config('block_igis_ollama_claude', 'claudemodel');
                    break;
                
                case 'openai':
                    $log->model = !empty($config->openaimodel) && get_config('block_igis_ollama_claude', 'instancesettings') ? 
                                  $config->openaimodel : 
                                  get_config('block_igis_ollama_claude', 'openaimodel');
                    break;
                
                case 'gemini':
                    $log->model = !empty($config->geminimodel) && get_config('block_igis_ollama_claude', 'instancesettings') ? 
                                  $config->geminimodel : 
                                  get_config('block_igis_ollama_claude', 'geminimodel');
                    break;
            }
            
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
        curl_close($ch);
        
        // Check for errors
        if ($httpCode != 200) {
            throw new moodle_exception('Failed to get response from Ollama API. HTTP code: ' . $httpCode);
        }
        
        // Decode the response
        $response = json_decode($result, true);
        if (!isset($response['message']['content'])) {
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
        
        // Make API request to Claude
        $ch = curl_init($apiurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $apikey,
            'anthropic-version: 2023-06-01'
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check for errors
        if ($httpCode != 200) {
            throw new moodle_exception('Failed to get response from Claude API. HTTP code: ' . $httpCode);
        }
        
        // Decode the response
        $response = json_decode($result, true);
        if (!isset($response['content'][0]['text'])) {
            throw new moodle_exception('Invalid response from Claude API');
        }
        
        return $response['content'][0]['text'];
    }
    
    /**
     * Get response from OpenAI API
     *
     * @param string $message User message
     * @param array $conversationHistory Conversation history
     * @param string $systemPrompt System prompt
     * @param object $config Block instance config
     * @return string AI response
     */
    private static function get_openai_response($message, $conversationHistory, $systemPrompt, $config) {
        // Get OpenAI API settings
        $apikey = get_config('block_igis_ollama_claude', 'openaikey');
        $model = get_config('block_igis_ollama_claude', 'openaimodel');
        $temperature = get_config('block_igis_ollama_claude', 'temperature');
        $max_tokens = get_config('block_igis_ollama_claude', 'max_tokens');
        
        // If instance level settings are allowed and set, use those instead
        if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config)) {
            // If custom model is set for this instance
            if (!empty($config->openaimodel)) {
                $model = $config->openaimodel;
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
            'max_tokens' => intval($max_tokens)
        ];
        
        // Make API request
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check for errors
        if ($httpCode != 200) {
            throw new moodle_exception('Failed to get response from OpenAI API. HTTP code: ' . $httpCode);
        }
        
        // Decode the response
        $response = json_decode($result, true);
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new moodle_exception('Invalid response from OpenAI API');
        }
        
        return $response['choices'][0]['message']['content'];
    }
    
    /**
     * Get response from Gemini API
     *
     * @param string $message User message
     * @param array $conversationHistory Conversation history
     * @param string $systemPrompt System prompt
     * @param object $config Block instance config
     * @return string AI response
     */
    private static function get_gemini_response($message, $conversationHistory, $systemPrompt, $config) {
        // Get Gemini API settings
        $apikey = get_config('block_igis_ollama_claude', 'geminikey');
        $model = get_config('block_igis_ollama_claude', 'geminimodel');
        $temperature = get_config('block_igis_ollama_claude', 'temperature');
        $max_tokens = get_config('block_igis_ollama_claude', 'max_tokens');
        
        // If instance level settings are allowed and set, use those instead
        if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config)) {
            // If custom model is set for this instance
            if (!empty($config->geminimodel)) {
                $model = $config->geminimodel;
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
        
        // Format conversations for Gemini API
        $contents = [];
        
        // Add system prompt as first user message if provided
        if (!empty($systemPrompt)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $systemPrompt]]
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => 'I understand and will follow these instructions.']]
            ];
        }
        
        // Add conversation history
        foreach ($conversationHistory as $exchange) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $exchange['message']]]
            ];
            
            if (isset($exchange['response'])) {
                $contents[] = [
                    'role' => 'model',
                    'parts' => [['text' => $exchange['response']]]
                ];
            }
        }
        
        // Add current message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];
        
        // Prepare API request data
        $data = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => floatval($temperature),
                'maxOutputTokens' => intval($max_tokens),
                'topP' => 0.95,
                'topK' => 40
            ]
        ];
        
        // Make API request
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apikey}";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check for errors
        if ($httpCode != 200) {
            throw new moodle_exception('Failed to get response from Gemini API. HTTP code: ' . $httpCode);
        }
        
        // Decode the response
        $response = json_decode($result, true);
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            throw new moodle_exception('Invalid response from Gemini API');
        }
        
        return $response['candidates'][0]['content']['parts'][0]['text'];
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