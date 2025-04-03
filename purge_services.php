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
 * Script to purge and reinstall web services
 *
 * @package    block_igis_ollama_claude
 * @copyright  2025 Sebastián González Zepeda <sgonzalez@infraestructuragis.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Only administrators can access this page
admin_externalpage_setup('blocksettingigis_ollama_claude');

// Check for confirmation
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$PAGE->set_title('Multi-provider AI Chat Block - Purgar servicios web');
$PAGE->set_heading('Purgar y reinstalar servicios web');

echo $OUTPUT->header();
echo $OUTPUT->heading('Purgar y reinstalar servicios web');

if (!$confirm) {
    // Show confirmation page
    echo '<div class="alert alert-warning">';
    echo '<p>Esta herramienta eliminará y volverá a registrar los servicios web del plugin Multi-provider AI Chat Block.</p>';
    echo '<p>Esto puede ayudar a solucionar problemas con las llamadas AJAX cuando no se están enviando correctamente los mensajes a las IAs.</p>';
    echo '<p>¿Estás seguro de que deseas continuar?</p>';
    echo '</div>';
    
    $continue_url = new moodle_url('/blocks/igis_ollama_claude/purge_services.php', ['confirm' => 1]);
    $cancel_url = new moodle_url('/admin/settings.php', ['section' => 'blocksettingigis_ollama_claude']);
    
    echo '<div class="mt-3">';
    echo '<a href="' . $continue_url->out() . '" class="btn btn-danger">Sí, purgar y reinstalar servicios</a> ';
    echo '<a href="' . $cancel_url->out() . '" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';
} else {
    // Perform the purge and reinstall
    echo '<div class="alert alert-info">Ejecutando purga y reinstalación de servicios web...</div>';
    
    // 1. Delete existing external functions
    $functions = [
        'block_igis_ollama_claude_get_chat_response',
        'block_igis_ollama_claude_clear_conversation'
    ];
    
    foreach ($functions as $function) {
        $DB->delete_records('external_functions', ['name' => $function]);
        echo "<div>Eliminada función: $function</div>";
    }
    
    // 2. Delete the service
    $service_shortname = 'igis_ollama_claude_service';
    $DB->delete_records('external_services', ['shortname' => $service_shortname]);
    echo "<div>Eliminado servicio: $service_shortname</div>";
    
    // 3. Purge all caches
    purge_all_caches();
    echo "<div>Purgadas todas las cachés</div>";
    
    // 4. Re-include the external.php file to register the functions again
    require_once($CFG->dirroot . '/blocks/igis_ollama_claude/classes/external.php');
    echo "<div>Recargado archivo external.php</div>";
    
    // 5. Re-include the services.php file to register the service again
    require_once($CFG->dirroot . '/blocks/igis_ollama_claude/db/services.php');
    echo "<div>Recargado archivo services.php</div>";
    
    // 6. Manually register the service
    $service = (object)[
        'name' => 'Multi-provider AI Chat Services',
        'shortname' => 'igis_ollama_claude_service',
        'enabled' => 1,
        'restrictedusers' => 0,
        'downloadfiles' => 0,
        'uploadfiles' => 0
    ];
    $service_id = $DB->insert_record('external_services', $service);
    echo "<div>Registrado servicio con ID: $service_id</div>";
    
    // 7. Manually register the functions
    foreach ($functions as $function) {
        $DB->execute(
            "INSERT INTO {external_services_functions} (externalserviceid, functionname) VALUES (?, ?)",
            [$service_id, $function]
        );
        echo "<div>Registrada función $function para el servicio</div>";
    }
    
    // 8. Update the plugin version in the DB to force a refresh
    $current_version = $DB->get_field('config_plugins', 'value', ['plugin' => 'block_igis_ollama_claude', 'name' => 'version']);
    if ($current_version) {
        $DB->set_field('config_plugins', 'value', $current_version + 1, ['plugin' => 'block_igis_ollama_claude', 'name' => 'version']);
        echo "<div>Actualizada versión del plugin</div>";
    }
    
    echo '<div class="alert alert-success mt-3">';
    echo 'Servicios web purgados y reinstalados correctamente.';
    echo '</div>';
    
    // 9. Provide links to next steps
    echo '<div class="mt-3">';
    echo '<p>Próximos pasos recomendados:</p>';
    echo '<ol>';
    echo '<li>Recarga esta página o vuelve a la configuración del plugin</li>';
    echo '<li>Abre la página de diagnóstico para verificar el registro de los servicios</li>';
    echo '<li>Prueba el envío de mensajes en el bloque de chat</li>';
    echo '</ol>';
    echo '</div>';
    
    $settings_url = new moodle_url('/admin/settings.php', ['section' => 'blocksettingigis_ollama_claude']);
    $diagnostics_url = new moodle_url('/blocks/igis_ollama_claude/diagnostics.php');
    
    echo '<div class="mt-3">';
    echo '<a href="' . $settings_url->out() . '" class="btn btn-primary">Volver a la configuración</a> ';
    echo '<a href="' . $diagnostics_url->out() . '" class="btn btn-info">Ver diagnósticos</a>';
    echo '</div>';
}

echo $OUTPUT->footer();