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
     * Get available API service
     * 
     * @param string $requestedApi The requested API
     * @param object $config Block config
     * @return string Available API
     */
    private static function get_available_api($requestedApi, $config) {
        $availableApis = [];
        
        // Check which APIs are available
        if (!empty(get_config('block_igis_ollama_claude', 'ollamaapiurl'))) {
            $availableApis[] = 'ollama';
        }
        if (!empty(get_config('block_igis_ollama_claude', 'claudeapikey'))) {
            $availableApis[] = 'claude';
        }
        if (!empty(get_config('block_igis_ollama_claude', 'openaikey'))) {
            $availableApis[] = 'openai';
        }
        if (!empty(get_config('block_igis_ollama_claude', 'geminikey'))) {
            $availableApis[] = 'gemini';
        }
        
        // If the requested API is available, use it
        if (in_array($requestedApi, $availableApis)) {
            return $requestedApi;
        }
        
        // If the requested API is not available, use the first available one
        if (!empty($availableApis)) {
            return $availableApis[0];
        }
        
        // If no APIs are available, throw an exception
        throw new \moodle_exception('no_api_available', 'block_igis_ollama_claude');
    }

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
            'api' => new external_value(PARAM_ALPHA, 'API service to use (ollama, claude, openai, gemini)', VALUE_DEFAULT, '')
        ]);
    }

    /**
     * Get chat response from AI provider
     *
     * @param string $message User message
     * @param string $conversation Conversation history in JSON format
     * @param int $instanceid Block instance ID
     * @param int $contextid Context ID
     * @param string $sourceoftruth Source of truth
     * @param string $prompt System prompt
     * @param string $api API service to use
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
        $context = \context::instance_by_id($contextid);
        self::validate_context($context);
        
        // Check if context is course context, and if not, get the course ID
        if ($context instanceof \context_course) {
            $courseid = $context->instanceid;
        } else {
            $courseid = $COURSE->id;
        }
        
        // Get block instance and config (for specific settings)
        $block = $DB->get_record('block_instances', ['id' => $instanceid], '*', MUST_EXIST);
        $configdata = $block->configdata;
        $config = !empty($configdata) ? unserialize(base64_decode($configdata)) : new \stdClass();
        
        // Determine which API to use if not specified
        if (empty($api)) {
            $api = get_config('block_igis_ollama_claude', 'defaultapi');
            
            // Check if block has specific API preference
            if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config->defaultapi)) {
                $api = $config->defaultapi;
            }
        }
        
        // Check if the selected API is available
        $api = self::get_available_api($api, $config);
        
        // Decode conversation history
        $conversationHistory = json_decode($conversation, true);
        if (!is_array($conversationHistory)) {
            $conversationHistory = [];
        }
        
        // Prepare block settings for provider
        $blockSettings = [];
        
        // Add source of truth
        if (!empty($sourceoftruth)) {
            $blockSettings['sourceoftruth'] = $sourceoftruth;
        }
        
        // Add system prompt
        if (!empty($prompt)) {
            $blockSettings['systemprompt'] = $prompt;
        } else {
            $blockSettings['systemprompt'] = get_config('block_igis_ollama_claude', 'completion_prompt');
        }
        
        // Add model and other settings from config if available
        if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config)) {
            // Add API-specific model settings
            if ($api === 'ollama' && !empty($config->ollamamodel)) {
                $blockSettings['ollamamodel'] = $config->ollamamodel;
            } else if ($api === 'claude' && !empty($config->claudemodel)) {
                $blockSettings['claudemodel'] = $config->claudemodel;
            } else if ($api === 'openai' && !empty($config->openaimodel)) {
                $blockSettings['openaimodel'] = $config->openaimodel;
            } else if ($api === 'gemini' && !empty($config->geminimodel)) {
                $blockSettings['geminimodel'] = $config->geminimodel;
            }
            
            // Add common settings
            if (!empty($config->temperature)) {
                $blockSettings['temperature'] = $config->temperature;
            }
            
            if (!empty($config->max_tokens)) {
                $blockSettings['max_tokens'] = $config->max_tokens;
            }
        }
        
        // Initialize the appropriate provider
        $providerClass = "\\block_igis_ollama_claude\\provider\\$api";
        if (!class_exists($providerClass)) {
            throw new \moodle_exception("Provider class not found: $providerClass");
        }
        
        $provider = new $providerClass($message, $conversationHistory, $blockSettings);
        $response = $provider->create_response($context);
        
        // Log the interaction if enabled
        if (get_config('block_igis_ollama_claude', 'enablelogging')) {
            $log = new \stdClass();
            $log->userid = $USER->id;
            $log->courseid = $courseid;
            $log->contextid = $contextid;
            $log->instanceid = $instanceid;
            $log->message = $message;
            $log->response = $response['message'];
            $log->sourceoftruth = $sourceoftruth;
            $log->prompt = $prompt;
            $log->api = $api;
            
            // Get model based on API type
            if ($api === 'ollama') {
                $log->model = get_config('block_igis_ollama_claude', 'ollamamodel');
                if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config->ollamamodel)) {
                    $log->model = $config->ollamamodel;
                }
            } else if ($api === 'claude') {
                $log->model = get_config('block_igis_ollama_claude', 'claudemodel');
                if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config->claudemodel)) {
                    $log->model = $config->claudemodel;
                }
            } else if ($api === 'openai') {
                $log->model = get_config('block_igis_ollama_claude', 'openaimodel');
                if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config->openaimodel)) {
                    $log->model = $config->openaimodel;
                }
            } else if ($api === 'gemini') {
                $log->model = get_config('block_igis_ollama_claude', 'geminimodel');
                if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config->geminimodel)) {
                    $log->model = $config->geminimodel;
                }
            }
            
            $log->timecreated = time();
            
            $DB->insert_record('block_igis_ollama_claude_logs', $log);
        }
        
        // Check if there's an error in the response
        if (isset($response['error']) && $response['error']) {
            // Return the error message but don't throw an exception
            // This allows the frontend to handle the error gracefully
            return [
                'error' => true,
                'message' => $response['message'],
                'code' => isset($response['code']) ? $response['code'] : 500
            ];
        }
        
        return [
            'response' => $response['message'],
            'metadata' => isset($response['metadata']) ? $response['metadata'] : null
        ];
    }

    /**
     * Returns description of get_chat_response returns
     *
     * @return external_single_structure
     */
    public static function get_chat_response_returns() {
        return new external_single_structure([
            'response' => new external_value(PARAM_RAW, 'AI response', VALUE_OPTIONAL),
            'error' => new external_value(PARAM_BOOL, 'Error flag', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
            'code' => new external_value(PARAM_INT, 'Error code', VALUE_OPTIONAL),
            'metadata' => new external_single_structure([
                'provider' => new external_value(PARAM_TEXT, 'Provider name', VALUE_OPTIONAL),
                'model' => new external_value(PARAM_TEXT, 'Model name', VALUE_OPTIONAL),
                'processing_time_ms' => new external_value(PARAM_INT, 'Processing time in milliseconds', VALUE_OPTIONAL),
                'tokens_used' => new external_value(PARAM_INT, 'Tokens used', VALUE_OPTIONAL)
            ], 'Response metadata', VALUE_OPTIONAL)
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