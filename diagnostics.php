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
 * Diagnostics page for Multi-provider AI Chat Block
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Only administrators can access this page
admin_externalpage_setup('blocksettingigis_ollama_claude');

$PAGE->set_title('Multi-provider AI Chat Block - Diagnóstico');
$PAGE->set_heading('Diagnóstico de APIs');

echo $OUTPUT->header();
echo $OUTPUT->heading('Diagnóstico de configuración de APIs');

// Function to test API connectivity
function test_api_connection($api, $url, $headers = [], $timeout = 5) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    
    $status = ($result !== false && $errno == 0) ? 'success' : 'danger';
    $message = $error ? "Error: $error (código: $errno)" : "Código HTTP: $http_code";
    
    echo "<div class='alert alert-$status'>";
    echo "<strong>$api:</strong> " . ($status == 'success' ? 'Conectividad OK' : 'Error de conexión');
    echo "<br>URL: $url";
    echo "<br>Resultado: $message";
    echo "</div>";
    
    return $status == 'success';
}

// Check if required extensions are installed
echo "<h3>Verificación de requisitos PHP</h3>";
$extensions = ['curl', 'json', 'openssl'];
foreach ($extensions as $ext) {
    $installed = extension_loaded($ext);
    echo "<div class='alert alert-" . ($installed ? 'success' : 'danger') . "'>";
    echo "Extensión $ext: " . ($installed ? 'Instalada' : 'No instalada');
    echo "</div>";
}

// Check API configurations
echo "<h3>Configuración de APIs</h3>";

// Check Ollama API
$ollamaapiurl = get_config('block_igis_ollama_claude', 'ollamaapiurl');
if (!empty($ollamaapiurl)) {
    test_api_connection('Ollama API', $ollamaapiurl, ['Content-Type: application/json']);
} else {
    echo "<div class='alert alert-warning'><strong>Ollama API:</strong> URL no configurada</div>";
}

// Check Claude API
$claudeapikey = get_config('block_igis_ollama_claude', 'claudeapikey');
$claudeapiurl = get_config('block_igis_ollama_claude', 'claudeapiurl');
if (!empty($claudeapikey) && !empty($claudeapiurl)) {
    test_api_connection('Claude API', $claudeapiurl, [
        'Content-Type: application/json',
        'x-api-key: ' . substr($claudeapikey, 0, 3) . '...' . substr($claudeapikey, -3),
        'anthropic-version: 2023-06-01'
    ]);
} else {
    echo "<div class='alert alert-warning'><strong>Claude API:</strong> Clave API o URL no configurada</div>";
}

// Check OpenAI API
$openaikey = get_config('block_igis_ollama_claude', 'openaikey');
if (!empty($openaikey)) {
    test_api_connection('OpenAI API', 'https://api.openai.com/v1/models', [
        'Content-Type: application/json',
        'Authorization: Bearer ' . substr($openaikey, 0, 3) . '...' . substr($openaikey, -3)
    ]);
} else {
    echo "<div class='alert alert-warning'><strong>OpenAI API:</strong> Clave API no configurada</div>";
}

// Check Gemini API
$geminikey = get_config('block_igis_ollama_claude', 'geminikey');
if (!empty($geminikey)) {
    $geminiModel = get_config('block_igis_ollama_claude', 'geminimodel');
    $geminiModel = empty($geminiModel) ? 'gemini-1.5-pro' : $geminiModel;
    test_api_connection('Gemini API', "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}?key=" . substr($geminikey, 0, 3) . '...' . substr($geminikey, -3));
} else {
    echo "<div class='alert alert-warning'><strong>Gemini API:</strong> Clave API no configurada</div>";
}

// Check web service configuration
echo "<h3>Configuración de servicios web</h3>";

// Check if our external functions are registered
$externalfunctions = [
    'block_igis_ollama_claude_get_chat_response',
    'block_igis_ollama_claude_clear_conversation'
];

foreach ($externalfunctions as $function) {
    $exists = $DB->record_exists('external_functions', ['name' => $function]);
    echo "<div class='alert alert-" . ($exists ? 'success' : 'danger') . "'>";
    echo "Función $function: " . ($exists ? 'Registrada' : 'No registrada');
    echo "</div>";
    
    if (!$exists) {
        echo "<div class='alert alert-warning'>";
        echo "Es posible que necesite purgar las cachés de Moodle o reinstalar el plugin para resolver este problema.";
        echo "</div>";
    }
}

// Check service registration
$servicename = 'igis_ollama_claude_service';
$serviceExists = $DB->record_exists('external_services', ['shortname' => $servicename]);
echo "<div class='alert alert-" . ($serviceExists ? 'success' : 'danger') . "'>";
echo "Servicio web '$servicename': " . ($serviceExists ? 'Registrado' : 'No registrado');
echo "</div>";

// Show configuration help
echo "<h3>Solución de problemas</h3>";
echo "<div class='card mb-3'>";
echo "<div class='card-body'>";
echo "<h5 class='card-title'>Errores comunes y soluciones</h5>";
echo "<ul>";
echo "<li><strong>No se pueden enviar mensajes:</strong> Compruebe la conectividad con el servicio API seleccionado y asegúrese de que las claves API son correctas.</li>";
echo "<li><strong>Ollama no responde:</strong> Verifique que el servidor Ollama esté ejecutándose en la URL configurada y que el modelo Claude esté instalado.</li>";
echo "<li><strong>Error en las respuestas de Claude/OpenAI/Gemini:</strong> Compruebe que la clave API es válida y tiene suficientes créditos disponibles.</li>";
echo "<li><strong>Funciones no registradas:</strong> Vaya a 'Administración del sitio' > 'Desarrollo' > 'Purgar todas las cachés' y recargue esta página.</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

// Add a section for testing a simple API call
echo "<h3>Probar envío de mensaje</h3>";
echo "<div class='card mb-3'>";
echo "<div class='card-body'>";
echo "<p>Utilice este formulario para probar el envío de un mensaje simple a la API seleccionada:</p>";

// Create a simple form to test the API
echo "<form method='post' action='" . $CFG->wwwroot . "/blocks/igis_ollama_claude/test_api.php' class='mb-3'>";
echo "<div class='form-group mb-2'>";
echo "<label for='test-api'>Seleccionar API:</label>";
echo "<select name='api' id='test-api' class='form-control'>";
if (!empty($ollamaapiurl)) {
    echo "<option value='ollama'>Ollama (local)</option>";
}
if (!empty($claudeapikey)) {
    echo "<option value='claude'>Claude (nube)</option>";
}
if (!empty($openaikey)) {
    echo "<option value='openai'>OpenAI (nube)</option>";
}
if (!empty($geminikey)) {
    echo "<option value='gemini'>Gemini (nube)</option>";
}
echo "</select>";
echo "</div>";
echo "<div class='form-group mb-2'>";
echo "<label for='test-message'>Mensaje de prueba:</label>";
echo "<input type='text' name='message' id='test-message' class='form-control' value='Hola, ¿cómo estás?' required>";
echo "</div>";
echo "<button type='submit' class='btn btn-primary'>Enviar mensaje de prueba</button>";
echo "</form>";

echo "<p class='text-muted'>Nota: Esta función envía un mensaje directo a la API seleccionada, sin pasar por la interfaz del bloque de chat.</p>";
echo "</div>";
echo "</div>";

// Verification of database tables
echo "<h3>Verificación de tablas de base de datos</h3>";
$tables = ['block_igis_ollama_claude_logs', 'block_igis_ollama_claude_cache'];
foreach ($tables as $table) {
    $tableExists = $DB->get_manager()->table_exists($table);
    echo "<div class='alert alert-" . ($tableExists ? 'success' : 'danger') . "'>";
    echo "Tabla $table: " . ($tableExists ? 'Existe' : 'No existe');
    echo "</div>";
}

// Server environment information
echo "<h3>Información del entorno del servidor</h3>";
echo "<div class='alert alert-info'>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Moodle Version:</strong> " . $CFG->release . "<br>";
echo "<strong>Plugin Version:</strong> " . get_config('block_igis_ollama_claude', 'version') . "<br>";
echo "<strong>Web Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "<strong>Operating System:</strong> " . PHP_OS . "<br>";
echo "</div>";

// Link to settings page
echo "<div class='mt-4'>";
echo "<a href='" . new moodle_url('/admin/settings.php', ['section' => 'blocksettingigis_ollama_claude']) . "' class='btn btn-primary mr-2'>Ir a la configuración del plugin</a>";
echo "<a href='" . new moodle_url('/blocks/igis_ollama_claude/purge_services.php') . "' class='btn btn-warning'>Purgar y reinstalar servicios web</a>";
echo "</div>";

echo $OUTPUT->footer();