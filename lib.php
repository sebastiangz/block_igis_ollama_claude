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
 * Helper functions for the Multi-provider AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Log a message and response to the database
 *
 * @param string $message The user message
 * @param string $response The AI response
 * @param \context $context The context where the interaction occurred
 * @param string $api The API used
 * @param int $instanceid The block instance ID
 * @return bool Success status
 */
function block_igis_ollama_claude_log_message($message, $response, $context, $api = 'unknown', $instanceid = 0) {
    global $DB, $USER, $COURSE;
    
    if (!get_config('block_igis_ollama_claude', 'enablelogging')) {
        return false;
    }
    
    try {
        $log = new stdClass();
        $log->userid = $USER->id;
        $log->courseid = $COURSE->id;
        $log->contextid = $context->id;
        $log->instanceid = $instanceid;
        $log->message = $message;
        $log->response = $response;
        $log->api = $api;
        $log->model = '';
        
        // Get model based on API
        switch ($api) {
            case 'ollama':
                $log->model = get_config('block_igis_ollama_claude', 'ollamamodel');
                break;
            case 'claude':
                $log->model = get_config('block_igis_ollama_claude', 'claudemodel');
                break;
            case 'openai':
                $log->model = get_config('block_igis_ollama_claude', 'openaimodel');
                break;
            case 'gemini':
                $log->model = get_config('block_igis_ollama_claude', 'geminimodel');
                break;
        }
        
        $log->timecreated = time();
        
        $DB->insert_record('block_igis_ollama_claude_logs', $log);
        return true;
    } catch (Exception $e) {
        debugging('Error logging message: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Clean an array of parameters
 *
 * @param array $array The array to clean
 * @param string $type The type of cleaning to apply
 * @param bool $recursive Whether to clean recursively
 * @return array The cleaned array
 */
function block_igis_ollama_claude_clean_param_array($array, $type, $recursive = false) {
    if (!is_array($array)) {
        return [];
    }
    
    $result = [];
    foreach ($array as $key => $value) {
        if ($recursive && is_array($value)) {
            $result[$key] = block_igis_ollama_claude_clean_param_array($value, $type, true);
        } else {
            $result[$key] = clean_param($value, $type);
        }
    }
    
    return $result;
}

/**
 * Get available AI providers
 *
 * @return array Array of available providers
 */
function block_igis_ollama_claude_get_available_providers() {
    $providers = [];
    
    if (!empty(get_config('block_igis_ollama_claude', 'ollamaapiurl'))) {
        $providers['ollama'] = get_string('ollamaapi', 'block_igis_ollama_claude');
    }
    
    if (!empty(get_config('block_igis_ollama_claude', 'claudeapikey'))) {
        $providers['claude'] = get_string('claudeapi', 'block_igis_ollama_claude');
    }
    
    if (!empty(get_config('block_igis_ollama_claude', 'openaikey'))) {
        $providers['openai'] = get_string('openaiapi', 'block_igis_ollama_claude');
    }
    
    if (!empty(get_config('block_igis_ollama_claude', 'geminikey'))) {
        $providers['gemini'] = get_string('geminiapi', 'block_igis_ollama_claude');
    }
    
    return $providers;
}

/**
 * Get a provider-specific model list
 * 
 * @param string $provider The provider name
 * @return array List of models for the provider
 */
function block_igis_ollama_claude_get_provider_models($provider) {
    switch ($provider) {
        case 'ollama':
            // For Ollama, this would ideally be fetched from the API
            // But for simplicity, we'll just return some common models
            return [
                'claude' => 'Claude',
                'llama2' => 'Llama 2',
                'llama3' => 'Llama 3',
                'mistral' => 'Mistral',
                'gemma' => 'Gemma',
                'phi' => 'Phi',
                'deepseek-coder' => 'DeepSeek Coder',
                'openchat' => 'OpenChat',
                'wizardlm' => 'WizardLM',
                'orca-mini' => 'Orca Mini'
            ];
            
        case 'claude':
            return [
                'claude-3-opus-20240229' => 'Claude 3 Opus',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                'claude-3.5-sonnet-20240620' => 'Claude 3.5 Sonnet',
                'claude-3.7-sonnet-20250219' => 'Claude 3.7 Sonnet'
            ];
            
        case 'openai':
            return [
                'gpt-4o' => 'GPT-4o',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'gpt-4' => 'GPT-4',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo'
            ];
            
        case 'gemini':
            return [
                'gemini-pro' => 'Gemini Pro',
                'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash'
            ];
            
        default:
            return [];
    }
}

/**
 * Get logs for a specific user or context
 * 
 * @param int $userid User ID (0 for all users)
 * @param int $contextid Context ID (0 for all contexts)
 * @param int $limit Maximum number of logs to return
 * @param int $offset Offset to start from
 * @return array Array of log records
 */
function block_igis_ollama_claude_get_chat_logs($userid = 0, $contextid = 0, $limit = 100, $offset = 0) {
    global $DB;
    
    $params = [];
    $where = '';
    
    if ($userid > 0) {
        $where .= ' AND l.userid = :userid';
        $params['userid'] = $userid;
    }
    
    if ($contextid > 0) {
        $where .= ' AND l.contextid = :contextid';
        $params['contextid'] = $contextid;
    }
    
    $sql = "SELECT l.*, u.firstname, u.lastname, c.fullname as coursename
            FROM {block_igis_ollama_claude_logs} l
            JOIN {user} u ON u.id = l.userid
            JOIN {course} c ON c.id = l.courseid
            WHERE 1=1 $where
            ORDER BY l.timecreated DESC";
    
    return $DB->get_records_sql($sql, $params, $offset, $limit);
}

/**
 * Check if cache table exists and create it if not
 * 
 * @return bool Success status
 */
function block_igis_ollama_claude_check_cache_table() {
    global $DB, $CFG;
    
    // Check if table exists
    $dbman = $DB->get_manager();
    $table = new xmldb_table('block_igis_ollama_claude_cache');
    
    if (!$dbman->table_exists($table)) {
        // Include required files
        require_once($CFG->libdir . '/xmldb/xmldb_table.php');
        
        // Create cache table
        $table = new xmldb_table('block_igis_ollama_claude_cache');
        
        // Adding fields to cache table
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cache_key', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('response', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('model', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('time_created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        
        // Adding keys to cache table
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        
        // Adding indexes to cache table
        $table->add_index('cache_key_model', XMLDB_INDEX_UNIQUE, ['cache_key', 'model']);
        $table->add_index('time_created', XMLDB_INDEX_NOTUNIQUE, ['time_created']);
        
        // Create the cache table
        $dbman->create_table($table);
        
        // Enable caching
        if (!get_config('block_igis_ollama_claude', 'enable_cache')) {
            set_config('enable_cache', '1', 'block_igis_ollama_claude');
        }
        
        return true;
    }
    
    return false;
}

/**
 * Clean up old cache entries
 * 
 * @param int $maxAge Maximum age in seconds (default: 7 days)
 * @return int Number of removed entries
 */
function block_igis_ollama_claude_clean_cache($maxAge = 604800) {
    global $DB;
    
    $cutoff = time() - $maxAge;
    
    return $DB->delete_records_select(
        'block_igis_ollama_claude_cache',
        'time_created < :cutoff',
        ['cutoff' => $cutoff]
    );
}

/**
 * Set up JavaScript debugging tools
 */
function block_igis_ollama_claude_before_footer() {
    global $PAGE;
    
    if (debugging()) {
        // Add the debugging JavaScript module
        $PAGE->requires->js_call_amd('block_igis_ollama_claude/console', 'default');
        
        // Make it available in the global M object
        $js = "if (typeof M !== 'undefined') { 
            if (!M.block_igis_ollama_claude) M.block_igis_ollama_claude = {}; 
            require(['block_igis_ollama_claude/console'], function(console) { 
                M.block_igis_ollama_claude.debug = console; 
                console.log('Debugging tools loaded. Use M.block_igis_ollama_claude.debug in console'); 
            }); 
        }";
        
        $PAGE->requires->js_init_code($js);
    }
}