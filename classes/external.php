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
            'prompt' => new external_value(PARAM_RAW, 'System prompt', VALUE_DEFAULT, '')
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
     * @return array Response data
     */
    public static function get_chat_response($message, $conversation, $instanceid, $contextid, $sourceoftruth, $prompt) {
        global $DB, $USER, $COURSE;

        // Parameter validation
        $params = self::validate_parameters(self::get_chat_response_parameters(), [
            'message' => $message,
            'conversation' => $conversation,
            'instanceid' => $instanceid,
            'contextid' => $contextid,
            'sourceoftruth' => $sourceoftruth,
            'prompt' => $prompt
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
        
        // Get Ollama API URL and model from global settings or specific instance settings
        $apiurl = get_config('block_igis_ollama_claude', 'apiurl');
        $model = get_config('block_igis_ollama_claude', 'model');
        $temperature = get_config('block_igis_ollama_claude', 'temperature');
        $max_tokens = get_config('block_igis_ollama_claude', 'max_tokens');
        
        // If instance level settings are allowed and set, use those instead
        if (get_config('block_igis_ollama_claude', 'instancesettings') && !empty($config)) {
            // If custom model is set for this instance
            if (!empty($config->model)) {
                $model = $config->model;
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
        
        // Decode conversation history
        $conversationHistory = json_decode($conversation, true);
        if (!is_array($conversationHistory)) {
            $conversationHistory = [];
        }
        
        // Build conversation messages for API
        $messages = [];
        
        // Add system prompt if provided
        if (!empty($prompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $prompt
            ];
        }
        
        // Add source of truth if provided
        if (!empty($sourceoftruth)) {
            $sotMessage = "Below is a list of questions and their answers. This information should be used as a reference for any inquiries:\n\n" . $sourceoftruth;
            
            // Add SoT as a system message
            $messages[] = [
                'role' => 'system',
                'content' => $sotMessage
            ];
        }
        
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
        
        $aiResponse = $response['message']['content'];
        
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
            $log->model = $model;
            $log->timecreated = time();
            
            $DB->insert_record('block_igis_ollama_claude_logs', $log);
        }
        
        return [
            'response' => $aiResponse
        ];
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
