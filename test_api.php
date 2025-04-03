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
 * Test API page for Multi-provider AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/external.php');

// Only administrators can access this page
admin_externalpage_setup('blocksettingigis_ollama_claude');

$PAGE->set_title('Multi-provider AI Chat Block - Prueba de API');
$PAGE->set_heading('Prueba de API');

// Get parameters
$api = required_param('api', PARAM_ALPHA);
$message = required_param('message', PARAM_TEXT);

echo $OUTPUT->header();
echo $OUTPUT->heading('Prueba de envío de mensaje a la API: ' . $api);

// Validate API type
$valid_apis = ['ollama', 'claude', 'openai', 'gemini'];
if (!in_array($api, $valid_apis)) {
    echo $OUTPUT->notification('API no válida: ' . $api, 'error');
    echo $OUTPUT->footer();
    die();
}

// Check if the API is configured
$is_configured = false;
switch ($api) {
    case 'ollama':
        $is_configured = !empty(get_config('block_igis_ollama_claude', 'ollamaapiurl'));
        break;
    case 'claude':
        $is_configured = !empty(get_config('block_igis_ollama_claude', 'claudeapikey'));
        break;
    case 'openai':
        $is_configured = !empty(get_config('block_igis_ollama_claude', 'openaikey'));
        break;
    case 'gemini':
        $is_configured = !empty(get_config('block_igis_ollama_claude', 'geminikey'));
        break;
}

if (!$is_configured) {
    echo $OUTPUT->notification('La API ' . $api . ' no está configurada correctamente.', 'error');
    echo '<p><a href="' . new moodle_url('/blocks/igis_ollama_claude/diagnostics.php') . '" class="btn btn-primary">Volver a diagnósticos</a></p>';
    echo $OUTPUT->footer();
    die();
}

// Create a simple form to display the request info
echo '<div class="card mb-4">';
echo '<div class="card-header">Información de la solicitud</div>';
echo '<div class="card-body">';
echo '<p><strong>API:</strong> ' . $api . '</p>';
echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($message) . '</p>';
echo '</div>';
echo '</div>';

// Test API directly
$start_time = microtime(true);

try {
    // Create a context for the test
    $system_context = context_system::instance();
    
    // Set up configs
    $config = new stdClass();
    if ($api === 'ollama') {
        $config->ollamamodel = get_config('block_igis_ollama_claude', 'ollamamodel');
    } else if ($api === 'claude') {
        $config->claudemodel = get_config('block_igis_ollama_claude', 'claudemodel');
    } else if ($api === 'openai') {
        $config->openaimodel = get_config('block_igis_ollama_claude', 'openaimodel');
    } else if ($api === 'gemini') {
        $config->geminimodel = get_config('block_igis_ollama_claude', 'geminimodel');
    }
    
    // Get system prompt
    $systemprompt = get_config('block_igis_ollama_claude', 'completion_prompt');
    
    // Get the response from the selected API
    switch ($api) {
        case 'ollama':
            $response = block_igis_ollama_claude_external::get_ollama_response($message, [], $systemprompt, $config);
            break;
        case 'claude':
            $response = block_igis_ollama_claude_external::get_claude_response($message, [], $systemprompt, $config);
            break;
        case 'openai':
            $response = block_igis_ollama_claude_external::get_openai_response($message, [], $systemprompt, $config);
            break;
        case 'gemini':
            $response = block_igis_ollama_claude_external::get_gemini_response($message, [], $systemprompt, $config);
            break;
    }
    
    $end_time = microtime(true);
    $elapsed = round(($end_time - $start_time) * 1000);
    
    echo '<div class="card mb-4">';
    echo '<div class="card-header">Respuesta de la API (' . $elapsed . 'ms)</div>';
    echo '<div class="card-body">';
    echo '<div class="alert alert-success">';
    echo '<p><strong>Respuesta:</strong></p>';
    echo '<div style="white-space: pre-wrap;">' . htmlspecialchars($response) . '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
} catch (Exception $e) {
    $end_time = microtime(true);
    $elapsed = round(($end_time - $start_time) * 1000);
    
    echo '<div class="card mb-4">';
    echo '<div class="card-header">Error en la API (' . $elapsed . 'ms)</div>';
    echo '<div class="card-body">';
    echo '<div class="alert alert-danger">';
    echo '<p><strong>Error:</strong> ' . $e->getMessage() . '</p>';
    echo '<p><strong>Archivo:</strong> ' . $e->getFile() . ' (línea ' . $e->getLine() . ')</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Provide test for the web service too
echo '<h3>Prueba del servicio web</h3>';
echo '<p>También podemos probar utilizando el servicio web que usa el bloque:</p>';

// Generate a simple test form
echo '<div id="webservice-test" class="card mb-4">';
echo '<div class="card-body">';
echo '<button id="test-webservice" class="btn btn-info">Probar servicio web</button>';
echo '<div id="webservice-result" class="mt-3" style="display:none"></div>';
echo '</div>';
echo '</div>';

// Add JavaScript to test the webservice
$PAGE->requires->js_amd_inline("
require(['jquery', 'core/ajax'], function($, Ajax) {
    $('#test-webservice').on('click', function() {
        $(this).prop('disabled', true).text('Enviando solicitud...');
        $('#webservice-result').html('<div class=\"alert alert-info\">Enviando solicitud al servicio web...</div>').show();
        
        Ajax.call([{
            methodname: 'block_igis_ollama_claude_get_chat_response',
            args: {
                message: " . json_encode($message) . ",
                conversation: JSON.stringify([]),
                instanceid: 0,
                contextid: " . $system_context->id . ",
                sourceoftruth: '',
                prompt: '',
                api: " . json_encode($api) . "
            }
        }])[0].then(function(response) {
            $('#test-webservice').prop('disabled', false).text('Probar servicio web');
            $('#webservice-result').html('<div class=\"alert alert-success\"><p><strong>Respuesta del servicio web:</strong></p><div style=\"white-space: pre-wrap;\">' + 
                $('<div>').text(response.response).html() + '</div></div>');
        }).catch(function(error) {
            $('#test-webservice').prop('disabled', false).text('Probar servicio web');
            $('#webservice-result').html('<div class=\"alert alert-danger\"><p><strong>Error del servicio web:</strong> ' + 
                error.message + '</p></div>');
            console.error('Web service error:', error);
        });
    });
});
");

echo '<div class="mt-4">';
echo '<a href="' . new moodle_url('/blocks/igis_ollama_claude/diagnostics.php') . '" class="btn btn-primary">Volver a diagnósticos</a>';
echo '</div>';

echo $OUTPUT->footer();
