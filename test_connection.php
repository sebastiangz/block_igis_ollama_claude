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
 * Test connection with Ollama and Claude APIs
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Require admin login
admin_externalpage_setup('blocksettingigis_ollama_claude');

// Page setup
$title = get_string('testconnection', 'block_igis_ollama_claude');
$PAGE->set_url('/blocks/igis_ollama_claude/test_connection.php');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Get API settings
$ollamaapiurl = get_config('block_igis_ollama_claude', 'ollamaapiurl');
$ollamamodel = get_config('block_igis_ollama_claude', 'ollamamodel');
$claudeapikey = get_config('block_igis_ollama_claude', 'claudeapikey');
$claudeapiurl = get_config('block_igis_ollama_claude', 'claudeapiurl');
$claudemodel = get_config('block_igis_ollama_claude', 'claudemodel');

// Create a standard test message
$test_message = "Hello, this is a test message to verify the connection to the API. Please respond with a short confirmation.";

// Results storage
$ollama_result = null;
$claude_result = null;

// Test Ollama API if configured
if (!empty($ollamaapiurl) && !empty($ollamamodel)) {
    $ollama_result = test_ollama_api($ollamaapiurl, $ollamamodel, $test_message);
}

// Test Claude API if configured
if (!empty($claudeapikey) && !empty($claudeapiurl) && !empty($claudemodel)) {
    $claude_result = test_claude_api($claudeapiurl, $claudeapikey, $claudemodel, $test_message);
}

// Function to test Ollama API
function test_ollama_api($apiurl, $model, $message) {
    $result = new stdClass();
    $result->success = false;
    $result->message = '';
    $result->response = '';
    $result->error = '';
    $result->http_code = 0;
    
    // Build the messages array
    $messages = [
        [
            'role' => 'system',
            'content' => 'You are a helpful assistant that gives concise, accurate responses.'
        ],
        [
            'role' => 'user',
            'content' => $message
        ]
    ];
    
    // Prepare API request data
    $data = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 100,
        'stream' => false
    ];
    
    // Make API request with error handling
    try {
        $ch = curl_init($apiurl . '/api/chat');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 seconds timeout
        
        $response = curl_exec($ch);
        $result->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $result->error = curl_error($ch);
            $result->message = "Connection failed: " . $result->error;
        } else {
            $responseData = json_decode($response, true);
            
            if ($result->http_code == 200 && isset($responseData['message']['content'])) {
                $result->success = true;
                $result->response = $responseData['message']['content'];
                $result->message = "Connection successful";
            } else {
                $result->error = "API returned HTTP code {$result->http_code}";
                $result->message = "Invalid response format: " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : '');
            }
        }
        
        curl_close($ch);
    } catch (Exception $e) {
        $result->error = $e->getMessage();
        $result->message = "Exception occurred: " . $result->error;
    }
    
    return $result;
}

// Function to test Claude API
function test_claude_api($apiurl, $apikey, $model, $message) {
    $result = new stdClass();
    $result->success = false;
    $result->message = '';
    $result->response = '';
    $result->error = '';
    $result->http_code = 0;
    
    // Build the messages array
    $messages = [
        [
            'role' => 'user',
            'content' => $message
        ]
    ];
    
    // Prepare API request data
    $data = [
        'model' => $model,
        'messages' => $messages,
        'system' => 'You are a helpful assistant that gives concise, accurate responses.',
        'temperature' => 0.7,
        'max_tokens' => 100
    ];
    
    // Make API request with error handling
    try {
        $ch = curl_init($apiurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'anthropic-api-key: ' . $apikey,
            'anthropic-version: 2023-06-01',
            'x-api-key: ' . $apikey  // Keep old header for backward compatibility
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 15 seconds timeout
        
        $response = curl_exec($ch);
        $result->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $result->error = curl_error($ch);
            $result->message = "Connection failed: " . $result->error;
        } else {
            $responseData = json_decode($response, true);
            
            // Check multiple potential response formats
            if ($result->http_code == 200) {
                if (isset($responseData['content'][0]['text'])) {
                    $result->success = true;
                    $result->response = $responseData['content'][0]['text'];
                    $result->message = "Connection successful";
                } else if (isset($responseData['completion'])) {
                    $result->success = true;
                    $result->response = $responseData['completion'];
                    $result->message = "Connection successful (using legacy API format)";
                } else if (isset($responseData['message']['content'])) {
                    $result->success = true;
                    $result->response = $responseData['message']['content'];
                    $result->message = "Connection successful (using alternate API format)";
                } else {
                    $result->error = "Unexpected response structure";
                    $result->message = "API returned HTTP 200 but with unexpected format: " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : '');
                }
            } else {
                $result->error = "API returned HTTP code {$result->http_code}";
                $result->message = "Error response: " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : '');
            }
        }
        
        curl_close($ch);
    } catch (Exception $e) {
        $result->error = $e->getMessage();
        $result->message = "Exception occurred: " . $result->error;
    }
    
    return $result;
}

// Output the page
echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('testconnection', 'block_igis_ollama_claude'));
echo html_writer::tag('p', get_string('testconnectioninfo', 'block_igis_ollama_claude'));

// Ollama API test results
echo html_writer::tag('h3', 'Ollama API');
if ($ollama_result) {
    $status_class = $ollama_result->success ? 'success' : 'danger';
    $status_text = $ollama_result->success ? 'Success' : 'Failed';
    
    echo html_writer::start_div('alert alert-' . $status_class);
    echo html_writer::tag('h4', 'Status: ' . $status_text);
    
    if ($ollama_result->success) {
        echo html_writer::tag('p', 'Connection to Ollama API was successful.');
        echo html_writer::tag('p', 'URL: ' . $ollamaapiurl);
        echo html_writer::tag('p', 'Model: ' . $ollamamodel);
        echo html_writer::start_div('card bg-light');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', 'Response:');
        echo html_writer::tag('p', htmlspecialchars($ollama_result->response));
        echo html_writer::end_div();

// Add page footer
echo $OUTPUT->footer();
        echo html_writer::end_div();
    } else {
        echo html_writer::tag('p', 'Connection to Ollama API failed.');
        echo html_writer::tag('p', 'URL: ' . $ollamaapiurl);
        echo html_writer::tag('p', 'Model: ' . $ollamamodel);
        echo html_writer::tag('p', 'Error: ' . $ollama_result->error);
        echo html_writer::tag('p', 'Message: ' . $ollama_result->message);
        echo html_writer::tag('p', 'HTTP Code: ' . $ollama_result->http_code);
    }
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('alert alert-warning');
    echo html_writer::tag('p', 'Ollama API is not configured or configuration is incomplete.');
    echo html_writer::end_div();
}

// Claude API test results
echo html_writer::tag('h3', 'Claude API');
if ($claude_result) {
    $status_class = $claude_result->success ? 'success' : 'danger';
    $status_text = $claude_result->success ? 'Success' : 'Failed';
    
    echo html_writer::start_div('alert alert-' . $status_class);
    echo html_writer::tag('h4', 'Status: ' . $status_text);
    
    if ($claude_result->success) {
        echo html_writer::tag('p', 'Connection to Claude API was successful.');
        echo html_writer::tag('p', 'URL: ' . $claudeapiurl);
        echo html_writer::tag('p', 'Model: ' . $claudemodel);
        echo html_writer::start_div('card bg-light');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', 'Response:');
        echo html_writer::tag('p', htmlspecialchars($claude_result->response));
        echo html_writer::end_div();
        echo html_writer::end_div();
    } else {
        echo html_writer::tag('p', 'Connection to Claude API failed.');
        echo html_writer::tag('p', 'URL: ' . $claudeapiurl);
        echo html_writer::tag('p', 'Model: ' . $claudemodel);
        echo html_writer::tag('p', 'Error: ' . $claude_result->error);
        echo html_writer::tag('p', 'Message: ' . $claude_result->message);
        echo html_writer::tag('p', 'HTTP Code: ' . $claude_result->http_code);
    }
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('alert alert-warning');
    echo html_writer::tag('p', 'Claude API is not configured or configuration is incomplete.');
    echo html_writer::end_div();
}

// Back to settings button
echo html_writer::start_div('mt-4');
$settings_url = new moodle_url('/admin/settings.php', array('section' => 'blocksettingigis_ollama_claude'));
echo html_writer::link(
    $settings_url,
    html_writer::tag('button', get_string('backtosettings', 'block_igis_ollama_claude'), array('class' => 'btn btn-primary')),
    array('title' => get_string('settings', 'block_igis_ollama_claude'))
);
echo html_writer::end_div();