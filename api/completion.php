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
 * API endpoint for retrieving AI completions from different providers
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/blocks/igis_ollama_claude/lib.php');

global $DB, $PAGE, $USER;

// Log the request for debugging
error_log('Completion API request received: ' . json_encode($_SERVER['REQUEST_METHOD']));

// Check if user needs to be logged in
if (get_config('block_igis_ollama_claude', 'loggedinonly') !== "0") {
    require_login();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $CFG->wwwroot");
    die();
}

// Parse request body
$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    error_log('Invalid request body: ' . file_get_contents('php://input'));
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid request body']);
    die();
}

// Validate required parameters
$required_params = ['message', 'blockId'];
foreach ($required_params as $param) {
    if (!isset($body[$param])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => "Missing required parameter: $param"]);
        die();
    }
}

// Clean and extract parameters
$message = clean_param($body['message'], PARAM_RAW);
$history = isset($body['history']) ? $body['history'] : [];
$block_id = clean_param($body['blockId'], PARAM_INT);
$thread_id = isset($body['threadId']) ? clean_param($body['threadId'], PARAM_ALPHANUM) : null;
$api_type = isset($body['api_type']) ? clean_param($body['api_type'], PARAM_TEXT) : null;

// Log the cleaned parameters for debugging
error_log('Cleaned parameters: ' . json_encode([
    'message' => $message,
    'blockId' => $block_id,
    'api_type' => $api_type
]));

// Get block instance
$instance_record = $DB->get_record('block_instances', ['id' => $block_id], '*', MUST_EXIST);
if ($instance_record->blockname !== 'igis_ollama_claude') {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid block type']);
    die();
}

$instance = block_instance('igis_ollama_claude', $instance_record);
if (!$instance) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Block instance not found']);
    die();
}

// Get context
$context = context::instance_by_id($instance_record->parentcontextid);
$PAGE->set_context($context);

// Get course ID
$course_id = 0;
if ($context instanceof context_course) {
    $course_id = $context->instanceid;
} else if ($context instanceof context_module) {
    $course_id = $context->get_course_context()->instanceid;
} else {
    // Try to find a course context
    $contexts = $context->get_parent_contexts(false);
    foreach ($contexts as $ctx) {
        if ($ctx instanceof context_course) {
            $course_id = $ctx->instanceid;
            break;
        }
    }
}

// Extract block settings
$block_settings = [];
if (!empty($instance->config)) {
    foreach ($instance->config as $key => $value) {
        $block_settings[$key] = $value;
    }
}

// Determine which API to use
if (empty($api_type)) {
    // Use block-specific default API if set
    if (!empty($block_settings['defaultapi']) && get_config('block_igis_ollama_claude', 'instancesettings')) {
        $api_type = $block_settings['defaultapi'];
    } else {
        // Use global default API
        $api_type = get_config('block_igis_ollama_claude', 'defaultapi');
    }
}

// Ensure API is available
$available_apis = [];
if (!empty(get_config('block_igis_ollama_claude', 'ollamaapiurl'))) {
    $available_apis[] = 'ollama';
}
if (!empty(get_config('block_igis_ollama_claude', 'claudeapikey'))) {
    $available_apis[] = 'claude';
}
if (!empty(get_config('block_igis_ollama_claude', 'openaikey'))) {
    $available_apis[] = 'openai';
}
if (!empty(get_config('block_igis_ollama_claude', 'geminikey'))) {
    $available_apis[] = 'gemini';
}

// If selected API is not available, fallback to first available
if (!in_array($api_type, $available_apis)) {
    if (!empty($available_apis)) {
        $api_type = $available_apis[0];
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'No AI provider is configured']);
        die();
    }
}

// Add source of truth to settings
if (!empty(get_config('block_igis_ollama_claude', 'sourceoftruth'))) {
    $block_settings['sourceoftruth'] = get_config('block_igis_ollama_claude', 'sourceoftruth');
}
if (!empty($block_settings['sourceoftruth'])) {
    // Combine global and instance source of truth if both exist
    if (!empty(get_config('block_igis_ollama_claude', 'sourceoftruth'))) {
        $block_settings['sourceoftruth'] = get_config('block_igis_ollama_claude', 'sourceoftruth') . "\n\n" . $block_settings['sourceoftruth'];
    }
}

// Initialize the appropriate provider
try {
    $provider_class = "\\block_igis_ollama_claude\\provider\\$api_type";
    error_log("Initializing provider class: $provider_class");
    
    if (!class_exists($provider_class)) {
        throw new Exception("Provider class not found: $provider_class");
    }
    
    $provider = new $provider_class($message, $history, $block_settings, $thread_id);
    $response = $provider->create_response($context);
    
    // Log the interaction if enabled
    if (get_config('block_igis_ollama_claude', 'enablelogging')) {
        $log = new stdClass();
        $log->userid = $USER->id;
        $log->courseid = $course_id;
        $log->contextid = $context->id;
        $log->instanceid = $block_id;
        $log->message = $message;
        $log->response = isset($response['message']) ? $response['message'] : json_encode($response);
        $log->api = $api_type;
        $log->model = $api_type === 'ollama' ? get_config('block_igis_ollama_claude', 'ollamamodel') : 
                     ($api_type === 'claude' ? get_config('block_igis_ollama_claude', 'claudemodel') : 
                     ($api_type === 'openai' ? get_config('block_igis_ollama_claude', 'openaimodel') : 
                     get_config('block_igis_ollama_claude', 'geminimodel')));
        
        // Override with block settings if available
        if (get_config('block_igis_ollama_claude', 'instancesettings')) {
            if ($api_type === 'ollama' && !empty($block_settings['ollamamodel'])) {
                $log->model = $block_settings['ollamamodel'];
            } else if ($api_type === 'claude' && !empty($block_settings['claudemodel'])) {
                $log->model = $block_settings['claudemodel'];
            } else if ($api_type === 'openai' && !empty($block_settings['openaimodel'])) {
                $log->model = $block_settings['openaimodel'];
            } else if ($api_type === 'gemini' && !empty($block_settings['geminimodel'])) {
                $log->model = $block_settings['geminimodel'];
            }
        }
        
        $log->timecreated = time();
        
        // Insert log record
        $DB->insert_record('block_igis_ollama_claude_logs', $log);
    }
    
    // Return response
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Error in completion.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}